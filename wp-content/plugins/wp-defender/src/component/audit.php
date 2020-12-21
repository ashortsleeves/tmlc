<?php

namespace WP_Defender\Component;

use Calotes\Helper\Array_Cache;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component;
use WP_Defender\Model\Audit_Log;
use WP_Defender\Traits\IO;

class Audit extends Component {
	use IO;

	const CACHE_LAST_CHECKPOINT = 'wd_audit_fetch_checkpoint';

	/**
	 *
	 * All the logs should be fetched through this function, it will automate query the API and fetch local log if the date range not exists
	 *
	 * @param $date_from
	 * @param $date_to
	 * @param array $events
	 * @param string $user_id
	 * @param string $ip
	 * @param int $paged
	 * return Audit_Log[]
	 */
	public function fetch( $date_from, $date_to, $events = [], $user_id = '', $ip = '', $paged = 1 ) {
		$internal = Audit_Log::query( $date_from, $date_to, $events, $user_id, $ip, $paged );
		$this->log( sprintf( 'Found %s from local', count( $internal ) ), 'audit' );
		$checkpoint = get_site_option( self::CACHE_LAST_CHECKPOINT );
		if ( false === $checkpoint ) {
			//this case where user install the plugin, have some localdata but never reach to logs page
			//then check point will be today
			$checkpoint = time();
		}
		$checkpoint = intval( $checkpoint );
		$date_from  = intval( $date_from );
		if ( count( $internal ) === 0 && $checkpoint > $date_from ) {
			//have to fetch from API
			$this->log( 'fetch from cloud', 'audit' );
			$cloud = $this->query_from_api( $date_from, $date_to );
			$this->log( var_export( $cloud, true ), 'audit' );
			if ( is_wp_error( $cloud ) ) {
				$this->log( sprintf( 'Fetch error %s', $cloud->get_error_message() ), 'audit' );

				return $cloud;
			}
			if ( count( $cloud ) ) {
				//no data from cloud too
				Audit_Log::mass_insert( $cloud );
				//because this is roughly fetch, so we have to filter out again using the local data
				$internal = Audit_Log::query( $date_from, $date_to, $events, $user_id, $ip, $paged );
			}
			//cache the last time fetch, this will be useful in case of mixed data
			update_site_option( self::CACHE_LAST_CHECKPOINT, $date_from );
		} else {
			//this case we have the data, however, maybe it can be out of the cached range, so we have to check
			//note that, the out of range only happen with date_from, as the local always have newest data
			if ( $checkpoint > $date_from ) {
				//we have some data out of range, fetch and cache
				//fetch the data from
				$this->log( sprintf( 'checkpoint %s - date from %s', date( 'Y-m-d H:i:s', $checkpoint ),
					date( 'Y-m-d H:i:s', $date_from ) ), 'audit' );
				$cloud = $this->query_from_api( $date_from, $checkpoint );
				if ( is_wp_error( $cloud ) ) {
					$this->log( sprintf( 'Fetch error %s', $cloud->get_error_message() ), 'audit' );

					return $cloud;
				}
				if ( is_array( $cloud ) ) {
					//silence the error here, as we actually have data
					Audit_Log::mass_insert( $cloud );
					$internal = Audit_Log::query( $date_from, $date_to, $events, $user_id, $ip, $paged );
					//cache the last time fetch, this will be useful in case of mixed data
					update_site_option( self::CACHE_LAST_CHECKPOINT, $date_from );
				}
			}
		}

		return $internal;
	}

	/**
	 * @param $date_from
	 * @param $date_to
	 *
	 * @return \WP_Error
	 * @throws \Exception
	 */
	public function query_from_api( $date_from, $date_to ) {
		$date_format = 'Y-m-d H:i:s';
		$date_to     = date( $date_format, $date_to );
		$date_from   = date( $date_format, $date_from );
		$args        = [
			'site_url'  => network_site_url(),
			'order_by'  => 'timestamp',
			'order'     => 'desc',
			'nopaging'  => true,
			'timezone'  => get_option( 'gmt_offset' ),
			'date_from' => $date_from,
			'date_to'   => $date_to,
		];

		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$data = $this->make_wpmu_request( WPMUDEV::API_AUDIT, $args, [
			'method' => 'GET'
		] );

		if ( is_wp_error( $data ) ) {
			$this->log( sprintf( 'Fetch error %s', $data->get_error_message() ), 'audit' );

			return $data;
		}

		if ( $data['status'] !== 'success' ) {
			return new \WP_Error( Error_Code::API_ERROR, __( 'Something wrong happen, please try again!', 'wpdef' ) );
		}

		return $data['data'];
	}

	public function flush() {
		$logs = Audit_Log::get_logs_need_flush();
		//build the data
		$data = [];
		foreach ( $logs as $log ) {
			$item = $log->export();
			unset( $item['synced'] );
			unset( $item['safe'] );
			unset( $item['id'] );
			$item['msg'] = addslashes( $item['msg'] );
			$data[]      = $item;
		}

		if ( count( $data ) ) {
			$ret = $this->curl_to_api( $data );
			if ( ! is_wp_error( $ret ) ) {
				foreach ( $logs as $log ) {
					$log->synced = 1;
					$log->save();
				}
			}
		}
	}

	/**
	 * @param $data
	 */
	public function curl_to_api( $data ) {
		$this->log( 'use curl' );
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$ret = $this->make_wpmu_request( WPMUDEV::API_AUDIT_ADD, $data, [
			'method'  => 'POST',
			'timeout' => 3,
			'headers' => array(
				'apikey' => $this->get_apikey()
			)
		] );

		return $ret;
	}

	/**
	 * @param $data
	 *
	 * @return bool
	 */
	public function socket_to_api( $data ) {
		$sockets = Array_Cache::get( 'sockets', 'audit', [] );
		//we will need to wait a little bit
		if ( count( $sockets ) == 0 ) {
			//fall back
			return false;
		}
		$this->log( sprintf( 'Flush %s to cloud', count( $data ) ), 'audit' );
		$start_time = microtime( true );
		$sks        = $sockets;
		$r          = null;
		$e          = null;
		if ( ( $socket_ready = @stream_select( $r, $sks, $e, 1 ) ) === false ) {
			//this case error happen

			return false;
		}

		$fp = array_shift( $sockets );

		$uri  = '/logs/add_multiple';
		$vars = http_build_query( $data );
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		fwrite( $fp, "POST " . $uri . "  HTTP/1.1\r\n" );
		fwrite( $fp, "Host: " . $this->strip_protocol( $this->get_endpoint() ) . "\r\n" );
		fwrite( $fp, "Content-Type: application/x-www-form-urlencoded\r\n" );
		fwrite( $fp, "Content-Length: " . strlen( $vars ) . "\r\n" );
		fwrite( $fp, "apikey:" . $this->get_apikey() . "\r\n" );
		fwrite( $fp, "Connection: close\r\n" );
		fwrite( $fp, "\r\n" );
		fwrite( $fp, $vars );
		socket_set_timeout( $fp, 5 );
		$res = '';
		while ( ! feof( $fp ) ) {
			$res .= fgets( $fp, 1024 );

			$end_time = microtime( true );
			if ( $end_time - $start_time > 3 ) {
				fclose( $fp );
				break;
			}
		}


		return true;
	}

	/**
	 * Open an socket to API for faster transmit
	 */
	public function open_socket() {
		$sockets  = Array_Cache::get( 'sockets', 'audit', [] );
		$endpoint = $this->strip_protocol( $this->get_endpoint() );
		if ( empty( $sockets ) ) {
			$fp = @stream_socket_client( 'ssl://' . $endpoint . ':443', $errno, $errstr,
				5 );
			if ( is_resource( $fp ) ) {
				Array_Cache::set( 'sockets', [ $fp ], 'audit' );
			}
		}
	}

	/**
	 * @param $url
	 *
	 * @return string
	 */
	private function strip_protocol( $url ) {
		$parts = parse_url( $url );

		$host = $parts['host'] . ( isset( $parts['path'] ) ? $parts['path'] : null );
		$host = rtrim( $host, '/' );

		return $host;
	}

	private function get_endpoint() {
		$base = defined( 'WPMUDEV_CUSTOM_AUDIT_SERVER' ) ? constant( 'WPMUDEV_CUSTOM_AUDIT_SERVER' ) : 'https://audit.wpmudev.org/';

		return $base;
	}

	/**
	 * Queue all the events listeners, so we can listen and buil log base on
	 * user behaviors
	 */
	public function enqueue_event_listener() {
		if ( defined( 'DOING_CRON' ) && constant( 'DOING_CRON' ) == true ) {
			//this is cron, we only queue the core audit to catch auto update
			$events_class = array(//Todo: new Core_Audit()
			);
		} else {
			$events_class = [
				new Component\Audit\Comment_Audit(),
				new Component\Audit\Core_Audit(),
				new Component\Audit\Media_Audit(),
				new Component\Audit\Post_Audit(),
				new Component\Audit\Users_Audit(),
				new Component\Audit\Options_Audit()
			];
		}

		foreach ( $events_class as $class ) {
			$hooks = $class->get_hooks();
			foreach ( $hooks as $key => $hook ) {
				$func = function () use ( $key, $hook, $class ) {
					//this is argurements of the hook
					$args = func_get_args();
					//this is hook data, defined in each events class
					$class->build_log_data( $key, $args, $hook );
				};
				add_action( $key, $func, 11, count( $hook['args'] ) );
			}
		}
	}

	/**
	 * @throws \ReflectionException
	 */
	public function log_audit_events() {
		$events = Array_Cache::get( 'logs', 'audit', [] );

		if ( ! count( $events ) || ! class_exists( \WP_Defender\Model\Audit_Log::class ) ) {
			return;
		}
		$model = new \WP_Defender\Model\Audit_Log();
		foreach ( $events as $event ) {
			$model->import( $event );
			$model->synced = 0;
			$model->save();
		}
	}
}