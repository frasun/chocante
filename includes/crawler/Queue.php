<?php

defined( 'ABSPATH' ) || exit;

class Chocante_Crawler_Queue {

	const CRAWLER_DIR = WP_CONTENT_DIR . '/uploads/crawler';

	private static function _load_config_safe() {
		$path = defined( 'CHOCANTE_CRAWLER_CONFIG' ) ? CHOCANTE_CRAWLER_CONFIG : __DIR__ . '/config.php';
		if ( ! file_exists( $path ) ) {
			return null;
		}
		$cfg = ( static function ( $p ) {
			return require $p;
		} )( $path );
		return ! empty( $cfg['rules'] ) ? $cfg : null;
	}

	/**
	 * Enqueue URLs for crawling via action hook.
	 *
	 * Rebuild from global sitemap: do_action( 'chocante_crawler_enqueue' );
	 * Pass sitemap url: do_action( 'chocante_crawler_enqueue', [ 'sitemap' => 'https://...' ] );
	 * Pass urls: do_action( 'chocante_crawler_enqueue', [ ''urls' => [ 'https://...' ] ] );
	 *
	 * @param array $data Options.
	 */
	public static function enqueue( $data = array() ) {
		if ( empty( $data ) ) {
			file_put_contents( self::CRAWLER_DIR . '/rebuild.flag', time() );
			return;
		}

		$cfg = self::_load_config_safe();
		if ( ! $cfg ) {
			return;
		}

		$urls = array();
		if ( ! empty( $data['sitemap'] ) ) {
			$urls = self::_fetch_sitemap_urls( $data['sitemap'] );
		} elseif ( ! empty( $data['urls'] ) ) {
			$urls = (array) $data['urls'];
		}

		if ( empty( $urls ) ) {
			return;
		}

		$entries = array();
		foreach ( $urls as $url ) {
			if ( isset( $data['cookies'] ) ) {
				foreach ( Chocante_Crawler_Engine::cross_product( $data['cookies'] ) as $combo ) {
					$entries[] = array(
						'url'     => $url,
						'cookies' => $combo,
						'ua'      => $data['ua'] ?? Chocante_Crawler_Engine::DEFAULT_UA,
					);
				}
			} else {
				$rule    = self::_match_rule( $url, $cfg );
				$entries = array_merge( $entries, self::_expand_rule( $url, $rule ) );
			}
		}

		if ( ! empty( $entries ) ) {
			self::_append_to_queue( $entries );
		}
	}

	/**
	 * Called by litespeed_task_crawler WP cron event.
	 */
	public static function cron_run() {
		if ( ! self::acquire_lock() ) {
			error_log( 'Chocante Crawler: skipping cron run, another instance is running.' );
			return;
		}

		try {
			$engine = new Chocante_Crawler_Engine();

			$flag = Chocante_Crawler_Engine::CRAWLER_DIR . '/rebuild.flag';
			if ( file_exists( $flag ) ) {
				unlink( $flag );
				$engine->build();
			}

			if ( ! apply_filters( 'litespeed_conf', 'crawler' ) ) {
				error_log( 'Chocante Crawler: disabled, queue built but skipping run.' );
				return;
			}

			$engine->run();
		} finally {
			self::release_lock();
		}
	}

	// -------------------------------------------------------------------------
	// Lock
	// -------------------------------------------------------------------------

	public static function acquire_lock() {
		$lock = self::_lock_path();

		if ( file_exists( $lock ) ) {
			$pid = (int) file_get_contents( $lock );
			if ( $pid && function_exists( 'posix_kill' ) && posix_kill( $pid, 0 ) ) {
				return false; // still running
			}
			unlink( $lock ); // stale lock
		}

		file_put_contents( $lock, getmypid() );
		return true;
	}

	public static function release_lock() {
		$lock = self::_lock_path();
		if ( file_exists( $lock ) ) {
			unlink( $lock );
		}
	}

	private static function _lock_path() {
		return self::CRAWLER_DIR . '/crawler.lock';
	}

	private static function _fetch_sitemap_urls( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 30,
				'user-agent' => 'Mozilla/5.0',
			)
		);
		$body     = wp_remote_retrieve_body( $response );
		if ( ! $body ) {
			return array();
		}
		$xml = simplexml_load_string( $body );
		if ( ! $xml ) {
			return array();
		}
		$xml->registerXPathNamespace( 'sm', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
		$locs = $xml->xpath( '//sm:url/sm:loc' );
		if ( ! $locs ) {
			return array();
		}
		return array_map( fn( $l ) => trim( (string) $l ), $locs );
	}

	private static function _match_rule( $url, $cfg ) {
		if ( empty( $cfg['rules'] ) ) {
			return null;
		}
		foreach ( $cfg['rules'] as $rule ) {
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

	private static function _expand_rule( $url, $rule ) {
		$ua      = isset( $rule['ua'] ) && $rule['ua'] !== null ? $rule['ua'] : Chocante_Crawler_Engine::DEFAULT_UA;
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
		foreach ( Chocante_Crawler_Engine::cross_product( $cookies ) as $combo ) {
			$entries[] = array(
				'url'     => $url,
				'cookies' => $combo,
				'ua'      => $ua,
			);
		}
		return $entries;
	}

	private static function _append_to_queue( $entries ) {
		$path = Chocante_Crawler_Engine::queue_path();
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
}
