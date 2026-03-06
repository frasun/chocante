<?php
/**
 * Chocante cache crawler commands.
 *
 * ## COMMANDS
 *
 *   wp crawler build    Build queue from sitemaps in config
 *   wp crawler run      Process the crawler queue
 *   wp crawler flush    Clear the queue
 *
 * ## CONSTANTS (wp-config.php)
 *
 *   CHOCANTE_CRAWLER_CONFIG    Absolute path to config.php
 *   CHOCANTE_CRAWLER_LOG       Set true to always save logs to disk
 *
 * ## LITESPEED CONSTANTS (wp-config.php)
 *
 *   LITESPEED_CRAWLER_THREADS    Max concurrent requests (default: 3)
 *   LITESPEED_CRAWLER_LOAD_LIMIT Server load ceiling (default: 10)
 *   LITESPEED_CRAWLER_TIMEOUT    Per-request timeout in seconds (default: 30)
 *   LITESPEED_CRAWLER_USLEEP     Delay between batches in microseconds (default: 500000)
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

class Chocante_Crawler_CLI {

	/**
	 * Build queue from sitemaps defined in config.
	 *
	 * @when after_wp_load
	 */
	public function build( $args, $assoc ) {
		$engine = new Chocante_Crawler_Engine();
		$engine->build();
	}

	/**
	 * Process the crawler queue.
	 *
	 * @when after_wp_load
	 */
	public function run( $args, $assoc ) {
		if ( ! Chocante_Crawler_Queue::acquire_lock() ) {
			WP_CLI::warning( 'Another crawler instance is running, exiting.' );
			return;
		}

		try {
			$engine = new Chocante_Crawler_Engine();

			if ( isset( $assoc['single'] ) ) {
				$engine->enqueue_urls( array( $assoc['single'] ) );
			} elseif ( isset( $assoc['sitemap'] ) ) {
				$engine->enqueue_sitemap( $assoc['sitemap'] );
			}

			$flag = Chocante_Crawler_Engine::CRAWLER_DIR . '/rebuild.flag';
			if ( file_exists( $flag ) ) {
				unlink( $flag );
				$engine->build();
			}

			if ( ! apply_filters( 'litespeed_conf', 'crawler' ) ) {
				WP_CLI::warning( 'Crawler is disabled, queue built but skipping run.' );
				return;
			}

			$engine->run();
		} finally {
			Chocante_Crawler_Queue::release_lock();
		}
	}

	/**
	 * Clear the queue and any pending flags.
	 *
	 * @when after_wp_load
	 */
	public function flush( $args, $assoc ) {
		$engine = new Chocante_Crawler_Engine();
		$engine->flush();
	}
	/**
	 * Show current queue contents.
	 *
	 * @when after_wp_load
	 */
	public function queue( $args, $assoc ) {
		$path = Chocante_Crawler_Engine::queue_path();
		if ( ! file_exists( $path ) ) {
			WP_CLI::log( 'Queue is empty' );
			return;
		}
		$queue = json_decode( file_get_contents( $path ), true ) ?: array();
		if ( empty( $queue ) ) {
			WP_CLI::log( 'Queue is empty' );
			return;
		}
		WP_CLI::log( count( $queue ) . ' entries in queue' );
		WP_CLI::log( '' );
		foreach ( $queue as $i => $e ) {
			$ck = ! empty( $e['cookies'] )
				? implode( ' ', array_map( fn( $k, $v ) => "$k=$v", array_keys( $e['cookies'] ), $e['cookies'] ) )
				: '(no cookies)';
			WP_CLI::log( sprintf( '[%d] %s | %s | ua=%s', $i + 1, $e['url'], $ck, $e['ua'] ) );
		}
	}

	/**
	 * Show crawler status: running PID, queue size.
	 *
	 * @when after_wp_load
	 */
	public function status( $args, $assoc ) {
		$lock = Chocante_Crawler_Engine::CRAWLER_DIR . '/crawler.lock';
		if ( file_exists( $lock ) ) {
			$pid     = (int) file_get_contents( $lock );
			$running = $pid && function_exists( 'posix_kill' ) && posix_kill( $pid, 0 );
			WP_CLI::log( $running ? "Running (PID $pid)" : "Lock exists but process $pid is dead (stale lock)" );
		} else {
			WP_CLI::log( 'Not running' );
		}

		$queue = Chocante_Crawler_Engine::queue_path();
		$count = file_exists( $queue ) ? count( json_decode( file_get_contents( $queue ), true ) ?: array() ) : 0;
		WP_CLI::log( "Queue: $count entries" );
	}

	/**
	 * Kill running crawler, remove lock, flush queue.
	 *
	 * @when after_wp_load
	 */
	public function kill( $args, $assoc ) {
		$lock = Chocante_Crawler_Engine::CRAWLER_DIR . '/crawler.lock';
		if ( file_exists( $lock ) ) {
			$pid = (int) file_get_contents( $lock );
			if ( $pid && function_exists( 'posix_kill' ) ) {
				posix_kill( $pid, SIGTERM );
				WP_CLI::log( "Sent SIGTERM to PID $pid" );
			}
			unlink( $lock );
		}

		( new Chocante_Crawler_Engine() )->flush();
		WP_CLI::success( 'Crawler killed and queue flushed.' );
	}
}

$instance = new Chocante_Crawler_CLI();
WP_CLI::add_command( 'crawler', $instance );
WP_CLI::add_command( 'crawler build', array( $instance, 'build' ) );
WP_CLI::add_command( 'crawler run', array( $instance, 'run' ) );
WP_CLI::add_command( 'crawler flush', array( $instance, 'flush' ) );
WP_CLI::add_command( 'crawler queue', array( $instance, 'queue' ) );
WP_CLI::add_command( 'crawler status', array( $instance, 'status' ) );
WP_CLI::add_command( 'crawler kill', array( $instance, 'kill' ) );
