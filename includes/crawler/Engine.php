<?php

defined( 'ABSPATH' ) || exit;

class Chocante_Crawler_Engine {

	const VERSION     = '1.0.0';
	const CRAWLER_DIR = WP_CONTENT_DIR . '/uploads/crawler';

	const DEFAULT_THREADS    = 3;
	const DEFAULT_TIMEOUT    = 30;
	const DEFAULT_USLEEP     = 500000;
	const DEFAULT_UA         = 'lscache_walker';
	const DEFAULT_LOAD_LIMIT = 10;

	// LITESPEED_CRAWLER_DURATION intentionally unused —
	// LSC uses it to stop before PHP max_execution_time in web context.
	// We run via WP cron triggered by system cron (CLI context), no time limit applies.

	private $cfg;
	private $threads;
	private $cur_threads = -1;
	private $thread_time = 0;
	private $load_limit;
	private $timeout;
	private $usleep;
	private $drop_domain;
	private $local_port;
	private $ignore_noncacheable;
	private $log_to_disk;
	private $is_cli;
	private $start_time;

	public function __construct() {
		$this->is_cli              = defined( 'WP_CLI' ) && WP_CLI;
		$this->log_to_disk         = defined( 'CHOCANTE_CRAWLER_LOG' ) && CHOCANTE_CRAWLER_LOG;
		$this->threads             = defined( 'LITESPEED_CRAWLER_THREADS' ) ? (int) LITESPEED_CRAWLER_THREADS : self::DEFAULT_THREADS;
		$this->load_limit          = defined( 'LITESPEED_CRAWLER_LOAD_LIMIT' ) ? (float) LITESPEED_CRAWLER_LOAD_LIMIT : (float) self::DEFAULT_LOAD_LIMIT;
		$this->timeout             = defined( 'LITESPEED_CRAWLER_TIMEOUT' ) ? (int) LITESPEED_CRAWLER_TIMEOUT : self::DEFAULT_TIMEOUT;
		$this->usleep              = defined( 'LITESPEED_CRAWLER_USLEEP' ) ? (int) LITESPEED_CRAWLER_USLEEP : self::DEFAULT_USLEEP;
		$this->drop_domain         = defined( 'LITESPEED_CRAWLER_DROP_DOMAIN' ) ? (bool) LITESPEED_CRAWLER_DROP_DOMAIN : false;
		$this->local_port          = defined( 'LITESPEED_CRAWLER_LOCAL_PORT' ) ? (int) LITESPEED_CRAWLER_LOCAL_PORT : null;
		$this->ignore_noncacheable = defined( 'LITESPEED_CRAWLER_IGNORE_NONCACHEABLE' ) ? (bool) LITESPEED_CRAWLER_IGNORE_NONCACHEABLE : false;

		$this->_ensure_dirs();
	}

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	public function build() {
		$this->cfg = $this->_load_config();
		$urls      = $this->_fetch_all_urls();

		if ( empty( $urls ) ) {
			$this->out( 'red', 'No URLs found in sitemaps' );
			return false;
		}

		$this->out( 'cyan', 'URLs fetched: ' . count( $urls ) );

		$queue = array();
		foreach ( $urls as $url ) {
			$rule = $this->_match_rule( $url );
			foreach ( $this->_expand_rule( $url, $rule ) as $entry ) {
				$queue[] = $entry;
			}
		}

		$this->_write_queue( $queue );
		$this->out( 'green', 'Queue built: ' . count( $queue ) . ' entries' );
		return true;
	}

	public function enqueue_urls( $urls ) {
		$this->cfg = $this->_load_config();
		$entries   = array();
		foreach ( $urls as $url ) {
			$rule    = $this->_match_rule( $url );
			$entries = array_merge( $entries, $this->_expand_rule( $url, $rule ) );
		}
		if ( empty( $entries ) ) {
			$this->out( 'yellow', 'No entries to enqueue' );
			return;
		}
		$this->_append_to_queue( $entries );
		$this->out( 'green', 'Enqueued ' . count( $entries ) . ' entries' );
	}

	public function enqueue_sitemap( $sitemap_url ) {
		$this->cfg = $this->_load_config();
		$timeout   = defined( 'LITESPEED_CRAWLER_MAP_TIMEOUT' ) ? (int) LITESPEED_CRAWLER_MAP_TIMEOUT : 30;
		$this->out( 'cyan', "Fetching sitemap: $sitemap_url" );
		$urls = $this->_parse_sitemap( $sitemap_url, $timeout );
		if ( empty( $urls ) ) {
			$this->out( 'red', 'No URLs found in sitemap' );
			return;
		}
		$entries = array();
		foreach ( $urls as $url ) {
			$rule    = $this->_match_rule( $url );
			$entries = array_merge( $entries, $this->_expand_rule( $url, $rule ) );
		}
		$this->_append_to_queue( $entries );
		$this->out( 'green', 'Enqueued ' . count( $entries ) . ' entries' );
	}

	public function run() {
		$this->cfg = $this->_load_config();

		// Check rebuild flag
		$flag = self::CRAWLER_DIR . '/rebuild.flag';
		if ( file_exists( $flag ) ) {
			unlink( $flag );
			$this->out( 'yellow', 'Rebuild flag detected, rebuilding queue...' );
			$this->build();
		}

		$queue = $this->_read_queue();

		if ( empty( $queue ) ) {
			$this->out( 'yellow', 'Queue is empty, nothing to do.' );
			return;
		}

		$this->out(
			'cyan',
			sprintf(
				'Crawler v%s | threads=%d (max) load_limit=%.1f timeout=%d usleep=%d',
				self::VERSION,
				$this->threads,
				$this->load_limit,
				$this->timeout,
				$this->usleep
			)
		);
		$this->out( 'cyan', count( $queue ) . ' entries in queue' );
		$this->out( 'cyan', 'Log: ' . $this->_log_path() );

		$this->start_time = time();
		$this->_run_queue( $queue );
	}

	public function flush() {
		$this->_write_queue( array() );
		$flag = self::CRAWLER_DIR . '/rebuild.flag';
		if ( file_exists( $flag ) ) {
			unlink( $flag );
		}
		$this->out( 'green', 'Queue flushed' );
	}

	// -------------------------------------------------------------------------
	// Config
	// -------------------------------------------------------------------------

	private function _load_config() {
		$path = defined( 'CHOCANTE_CRAWLER_CONFIG' ) ? CHOCANTE_CRAWLER_CONFIG : __DIR__ . '/config.php';
		if ( ! file_exists( $path ) ) {
			$this->out( 'red', "Config not found: $path" );
			return array();
		}
		$cfg = require $path;

		return $cfg;
	}

	// -------------------------------------------------------------------------
	// Sitemap Fetching
	// -------------------------------------------------------------------------

	private function _fetch_all_urls() {
		$urls    = array();
		$timeout = defined( 'LITESPEED_CRAWLER_MAP_TIMEOUT' ) ? (int) LITESPEED_CRAWLER_MAP_TIMEOUT : 30;

		if ( ! empty( $this->cfg['sitemaps'] ) ) {
			foreach ( $this->cfg['sitemaps'] as $sitemap_url ) {
				$this->out( 'cyan', "Fetching sitemap: $sitemap_url" );
				$urls = array_merge( $urls, $this->_parse_sitemap( $sitemap_url, $timeout ) );
			}
		}

		if ( ! empty( $this->cfg['urls'] ) ) {
			$extra = is_callable( $this->cfg['urls'] )
			? ( $this->cfg['urls'] )()
			: (array) $this->cfg['urls'];
			$urls  = array_merge( $urls, $extra );
		}

		return array_unique( $urls );
	}

	private function _parse_sitemap( $url, $timeout ) {
		$xml = $this->_fetch_xml( $url, $timeout );
		if ( ! $xml ) {
			$this->out( 'red', "Failed to fetch: $url" );
			return array();
		}

		$xml->registerXPathNamespace( 'sm', 'http://www.sitemaps.org/schemas/sitemap/0.9' );

		$sub_sitemaps = $xml->xpath( '//sm:sitemap/sm:loc' );
		if ( $sub_sitemaps ) {
			$urls = array();
			foreach ( $sub_sitemaps as $loc ) {
				$sub_url = trim( (string) $loc );
				$this->out( 'cyan', "  → sub-sitemap: $sub_url" );
				$urls = array_merge( $urls, $this->_parse_sitemap( $sub_url, $timeout ) );
			}
			return $urls;
		}

		$locs = $xml->xpath( '//sm:url/sm:loc' );
		if ( ! $locs ) {
			return array();
		}
		return array_map( fn( $l ) => trim( (string) $l ), $locs );
	}

	private function _fetch_xml( $url, $timeout ) {
		$ch = curl_init();
		curl_setopt_array(
			$ch,
			array(
				CURLOPT_URL            => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => 0,
				CURLOPT_TIMEOUT        => $timeout,
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_USERAGENT      => 'Mozilla/5.0',
			)
		);
		$body = curl_exec( $ch );
		$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( $code !== 200 || ! $body ) {
			return null;
		}
		return simplexml_load_string( $body ) ?: null;
	}

	// -------------------------------------------------------------------------
	// Rule Matching & Expansion
	// -------------------------------------------------------------------------

	public function match_rule( $url ) {
		return $this->_match_rule( $url );
	}

	public function expand_rule( $url, $rule ) {
		return $this->_expand_rule( $url, $rule );
	}

	private function _match_rule( $url ) {
		if ( empty( $this->cfg['rules'] ) ) {
			return null;
		}
		foreach ( $this->cfg['rules'] as $rule ) {
			$m   = $rule['match'];
			$hit = $m === ''
				? true
				: ( $m[0] === '/' && substr( $m, -1 ) === '/'
					? (bool) preg_match( $m, $url )
					: strpos( $url, $m ) !== false );
			if ( $hit ) {
				return $rule;
			}
		}
		return null;
	}

	private function _expand_rule( $url, $rule ) {
		if ( ! $rule ) {
			return array(
				array(
					'url'     => $url,
					'cookies' => array(),
					'ua'      => self::DEFAULT_UA,
				),
			);
		}
		$ua      = isset( $rule['ua'] ) && $rule['ua'] !== null ? $rule['ua'] : self::DEFAULT_UA;
		$cookies = $rule['cookies'] ?? array();
		if ( empty( $cookies ) ) {
			return array(
				array(
					'url'     => $url,
					'cookies' => array(),
					'ua'      => $ua,
				),
			);
		}

		$entries = array();
		foreach ( $this->_cross_product( $cookies ) as $combo ) {
			$entries[] = array(
				'url'     => $url,
				'cookies' => $combo,
				'ua'      => $ua,
			);
		}

		if ( ! empty( $rule['combos'] ) ) {
			foreach ( $rule['combos'] as $combo ) {
				$entries[] = array(
					'url'     => $url,
					'cookies' => $combo,
					'ua'      => $ua,
				);
			}
		}

		return $entries;
	}

	public static function cross_product( $cookies, $keys = null, $i = 0 ) {
		if ( $keys === null ) {
			$keys = array_keys( $cookies );
		}
		if ( $i >= count( $keys ) ) {
			return array( array() );
		}
		$key     = $keys[ $i ];
		$values  = $cookies[ $key ];
		$results = array();
		foreach ( $values as $val ) {
			foreach ( self::cross_product( $cookies, $keys, $i + 1 ) as $rest ) {
				$results[] = ( $val === null || $val === '_null' ) ? $rest : array_merge( array( $key => $val ), $rest );
			}
		}
		return $results;
	}

	private function _cross_product( $cookies, $keys = null, $i = 0 ) {
		return self::cross_product( $cookies, $keys, $i );
	}

	// -------------------------------------------------------------------------
	// Load Monitor
	// -------------------------------------------------------------------------

	private function _adjust_threads() {
		if ( ! function_exists( 'sys_getloadavg' ) ) {
			$this->cur_threads = $this->threads;
			return;
		}

		$load  = (float) sys_getloadavg()[0];
		$limit = $this->load_limit;
		$max   = $this->threads;

		if ( $load >= $limit ) {
			$current = 1;
			usleep( $this->usleep * 3 );
		} elseif ( $load >= $limit * 0.75 ) {
			$ratio   = ( $limit - $load ) / ( $limit * 0.25 );
			$current = max( 1, (int) round( $max * $ratio ) );
		} else {
			$current = $max;
		}

		if ( $current !== $this->cur_threads ) {
			$this->out( 'white', "Load {$load} / limit {$limit} → threads adjusted to {$current}" );
		}

		$this->cur_threads = $current;
		$this->thread_time = time();
	}

	// -------------------------------------------------------------------------
	// Curl Engine
	// -------------------------------------------------------------------------

	private function _run_queue( $queue ) {
		$stats = array(
			'hit'     => 0,
			'miss'    => 0,
			'nocache' => 0,
			'fail'    => 0,
			'unknown' => 0,
		);
		$total = count( $queue );
		$done  = 0;

		$this->_adjust_threads();
		if ( $this->cur_threads === 0 ) {
			$this->out( 'red', 'Server load too high to start, aborting.' );
			return;
		}

		$i = 0;
		while ( $i < count( $queue ) ) {
			if ( ( time() - $this->thread_time ) > 60 ) {
				$this->_adjust_threads();
				if ( $this->cur_threads === 0 ) {
					$this->out( 'red', 'Server load critical, pausing 30s...' );
					sleep( 30 );
					$this->_adjust_threads();
				}
			}

			$chunk   = array_slice( $queue, $i, $this->cur_threads, true );
			$results = $this->_multi_curl( $chunk );

			// Remove processed entries from disk queue
			$processed = array_map( fn( $k ) => $queue[ $k ]['url'] . serialize( $queue[ $k ]['cookies'] ), array_keys( $results ) );
			$remaining = array_filter( $this->_read_queue(), fn( $e ) => ! in_array( $e['url'] . serialize( $e['cookies'] ), $processed ) );
			$this->_write_queue( array_values( $remaining ) );

			foreach ( $results as $key => $result ) {
				++$done;
				$entry            = $queue[ $key ];
				$status           = $result['status'];
				$stats[ $status ] = ( $stats[ $status ] ?? 0 ) + 1;

				$label = "[$done/$total] {$entry['url']}";
				$ck    = $this->_cookie_str( $entry['cookies'] );
				if ( $ck ) {
					$label .= " | $ck";
				}

				switch ( $status ) {
					case 'hit':
						$this->out( 'lime', "$label → hit" );
						break;
					case 'miss':
						$this->out( 'strawberry', "$label → miss" );
						break;
					case 'nocache':
						$this->out( 'grey', "$label → no-cache" );
						break;
					case 'fail':
						$this->out( 'red', "$label → fail {$result['detail']}" );
						break;
					default:
						$this->out( 'darkblue', "$label → HTTP {$result['detail']} {$entry['ua']}" );
				}
			}

			$i += count( $chunk );
			usleep( $this->usleep );
		}

		$elapsed = time() - $this->start_time;
		$this->out( 'cyan', '' );
		$this->out( 'cyan', '==================== Summary ====================' );
		$this->out( 'lime', '  hit     : ' . $stats['hit'] );
		$this->out( 'strawberry', '  miss    : ' . $stats['miss'] );
		$this->out( 'grey', '  no-cache: ' . $stats['nocache'] );
		$this->out( 'darkblue', '  unknown : ' . $stats['unknown'] );
		$this->out( 'red', '  failed  : ' . $stats['fail'] );
		$this->out( 'cyan', "  total   : $total in {$elapsed}s" );
		$this->out( 'cyan', '=================================================' );
	}

	private function _multi_curl( $entries ) {
		$mh    = curl_multi_init();
		$curls = array();

		foreach ( $entries as $key => $entry ) {
			$url = $this->drop_domain ? $this->_localize_url( $entry['url'] ) : $entry['url'];
			$ch  = curl_init();
			curl_setopt_array(
				$ch,
				array(
					CURLOPT_URL            => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_HEADER         => true,
					CURLOPT_NOBODY         => false,
					CURLOPT_FOLLOWLOCATION => false,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_SSL_VERIFYHOST => 0,
					CURLOPT_USERAGENT      => $entry['ua'],
					CURLOPT_ENCODING       => 'gzip',
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
					CURLOPT_HTTPHEADER     => array( 'Cache-Control: max-age=0' ),
					CURLOPT_COOKIE         => $this->_cookie_str( $entry['cookies'] ),
					CURLOPT_TIMEOUT        => $this->timeout,
					CURLOPT_CONNECTTIMEOUT => 10,
				)
			);

			if ( $this->local_port ) {
				$parsed    = parse_url( $url );
				$server_ip = defined( 'LITESPEED_CRAWLER_SERVER_IP' ) ? LITESPEED_CRAWLER_SERVER_IP : '127.0.0.1';
				$resolved  = $parsed['host'] . ':' . $this->local_port . ':' . $server_ip;
				curl_setopt( $ch, CURLOPT_RESOLVE, array( $resolved ) );
				curl_setopt( $ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
			}

			$curls[ $key ] = $ch;
			curl_multi_add_handle( $mh, $ch );
		}

		do {
			$status = curl_multi_exec( $mh, $active );
			if ( $active ) {
				curl_multi_select( $mh );
			}
		} while ( $active && CURLM_OK === $status );

		$results = array();
		foreach ( $curls as $key => $ch ) {
			$header_size     = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
			$content         = curl_multi_getcontent( $ch );
			$http_code       = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			$curl_error      = curl_error( $ch );
			$header          = substr( $content, 0, $header_size );
			$results[ $key ] = $curl_error
				? array(
					'status' => 'fail',
					'detail' => $curl_error,
				)
				: $this->_parse_status( $header, $http_code );
			curl_multi_remove_handle( $mh, $ch );
			curl_close( $ch );
		}

		curl_multi_close( $mh );
		return $results;
	}

	private function _parse_status( $header, $code ) {
		if ( in_array( $code, array( 400, 401, 403, 404, 500, 502 ) ) ) {
			return array(
				'status' => 'fail',
				'detail' => "HTTP $code",
			);
		}
		if ( ! $this->ignore_noncacheable && stripos( $header, 'x-litespeed-cache-control: no-cache' ) !== false ) {
			return array(
				'status' => 'nocache',
				'detail' => 'no-cache',
			);
		}
		foreach ( array( 'x-litespeed-cache', 'x-qc-cache', 'x-lsadc-cache' ) as $h ) {
			if ( stripos( $header, $h ) !== false ) {
				if ( stripos( $header, $h . ': miss' ) !== false ) {
					return array(
						'status' => 'miss',
						'detail' => 'miss',
					);
				}
				if ( stripos( $header, $h . ': hit' ) !== false ) {
					return array(
						'status' => 'hit',
						'detail' => 'hit',
					);
				}
				return array(
					'status' => 'miss',
					'detail' => 'miss',
				);
			}
		}
		return array(
			'status' => 'unknown',
			'detail' => $code,
		);
	}

	// -------------------------------------------------------------------------
	// Queue
	// -------------------------------------------------------------------------

	public function _read_queue() {
		$path = $this->_queue_path();
		if ( ! file_exists( $path ) ) {
			return array();
		}
		return json_decode( file_get_contents( $path ), true ) ?: array();
	}

	public function _write_queue( $queue ) {
		$fp = fopen( $this->_queue_path(), 'c' );
		if ( flock( $fp, LOCK_EX ) ) {
			ftruncate( $fp, 0 );
			fwrite( $fp, json_encode( array_values( $queue ), JSON_PRETTY_PRINT ) );
			flock( $fp, LOCK_UN );
		}
		fclose( $fp );
	}

	private function _append_to_queue( $entries ) {
		$path = $this->_queue_path();
		$fp   = fopen( $path, 'c+' );
		if ( ! flock( $fp, LOCK_EX ) ) {
			fclose( $fp );
			return; }

		$existing = array();
		if ( filesize( $path ) > 0 ) {
			$existing = json_decode( stream_get_contents( $fp, -1, 0 ), true ) ?: array();
		}

		$index = array();
		foreach ( $existing as $e ) {
			$index[ $e['url'] . serialize( $e['cookies'] ) ] = true;
		}

		foreach ( $entries as $entry ) {
			$key = $entry['url'] . serialize( $entry['cookies'] );
			if ( ! isset( $index[ $key ] ) ) {
				$existing[]    = $entry;
				$index[ $key ] = true;
			}
		}

		ftruncate( $fp, 0 );
		rewind( $fp );
		fwrite( $fp, json_encode( $existing, JSON_PRETTY_PRINT ) );
		flock( $fp, LOCK_UN );
		fclose( $fp );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function _cookie_str( $cookies ) {
		if ( empty( $cookies ) ) {
			return '';
		}
		return implode( '; ', array_map( fn( $k, $v ) => "$k=$v", array_keys( $cookies ), $cookies ) );
	}

	private function _localize_url( $url ) {
		$parsed = parse_url( $url );
		$port   = $this->local_port ?? 443;
		$scheme = $port === 80 ? 'http' : 'https';
		return $scheme . '://' . $parsed['host']
			. ( $parsed['path'] ?? '/' )
			. ( isset( $parsed['query'] ) ? '?' . $parsed['query'] : '' );
	}

	public static function queue_path() {
		return self::CRAWLER_DIR . '/queue.json'; }

	private function _queue_path() {
		return self::queue_path(); }
	private function _log_path() {
		return self::CRAWLER_DIR . '/logs/crawler-' . date( 'Y-m-d' ) . '.log'; }

	private function _ensure_dirs() {
		foreach ( array( '', '/logs' ) as $sub ) {
			$dir = self::CRAWLER_DIR . $sub;
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
		}
	}

	public function out( $color, $msg ) {
		$colors = array(
			'lime'       => "\033[38;5;150m",
			'strawberry' => "\033[38;5;203m",
			'grey'       => "\033[38;5;249m",
			'yellow'     => "\033[38;5;173m",
			'red'        => "\033[38;5;167m",
			'green'      => "\033[38;5;107m",
			'cyan'       => "\033[38;5;73m",
			'white'      => "\033[1;37m",
			'darkblue'   => "\033[38;5;75m",
		);

		$line = ( $colors[ $color ] ?? '' ) . $msg . "\033[0m";

		if ( $this->is_cli ) {
			WP_CLI::log( $line );
		} else {
			error_log( 'Chocante Crawler: ' . $msg );
		}

		if ( $this->log_to_disk ) {
			file_put_contents( $this->_log_path(), date( 'Y-m-d H:i:s' ) . ' ' . $msg . "\n", FILE_APPEND );
		}
	}
}
