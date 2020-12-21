<?php

namespace WP_Defender\Component\Audit;

use Calotes\Base\Component;
use WP_Defender\Component\Error_Code;
use WP_Defender\Traits\Formats;

/**
 * Class Audit_Api
 * @package WP_Defender\Component\Audit
 * @deprecated
 */
class Audit_Api extends Component {
	use Formats;

	const ACTION_ADDED = 'added',
		ACTION_UPDATED = 'updated',
		ACTION_DELETED = 'deleted',
		ACTION_TRASHED = 'trashed',
		ACTION_RESTORED = 'restored';

	public static $end_point = 'audit.wpmudev.org';

	public static function get_event_type() {
		return \Calotes\Helper\Array_Cache::get( 'event_types' );
	}

	/**
	 * @param $slug
	 *
	 * @return mixed
	 */
	public static function get_action_text( $slug ) {
		$dic = \Calotes\Helper\Array_Cache::get( 'dictionary', array() );

		return isset( $dic[ $slug ] ) ? $dic[ $slug ] : $slug;
	}

	/**
	 * We get all the hooks from internal component and add it to wp hook system on wp_load time
	 */
	public static function setup_events() {
		//we only queue for
		if ( defined( 'DOING_CRON' ) && constant( 'DOING_CRON' ) == true ) {
			//this is cron, we only queue the core audit to catch auto update
			$events_class = array(
				//Todo: new Core_Audit()
			);
		} else {
			$events_class = array(
				new Comment_Audit(),
				//Todo: new Core_Audit(),
				//Todo: new Media_Audit(),
				//Todo: new Options_Audit(),
				//Todo: new Post_Audit(),
				//Todo: new Users_Audit()
			);
		}

		//we will build up the dictionary here
		$dictionary  = self::dictionary();
		$event_types = array();

		foreach ( $events_class as $class ) {
			$hooks      = $class->get_hooks();
			$dictionary = array_merge( $class->dictionary(), $dictionary );
			foreach ( $hooks as $key => $hook ) {
				$func = function () use ( $key, $hook, $class ) {
					//this is argurements of the hook
					$args = func_get_args();
					//this is hook data, defined in each events class
					$class->build_log_data( $key, $args, $hook );
				};
				add_action( $key, $func, 11, count( $hook['args'] ) );
				$event_types[] = $hook['event_type'];
			}
		}

		\Calotes\Helper\Array_Cache::set( 'event_types', array_unique( $event_types ) );
		\Calotes\Helper\Array_Cache::set( 'dictionary', $dictionary );
	}

	/**
	 * @param array $filter
	 * @param string $order_by
	 * @param string $order
	 * @param bool $nopaging
	 *
	 * @return array|mixed|object|\WP_Error
	 * @throws \Exception
	 */
	public static function pull_logs( $filter = array(), $order_by = 'timestamp', $order = 'desc', $nopaging = false ) {
		$data = $filter;
		$data['site_url'] = network_site_url();
		$data['order_by'] = $order_by;
		$data['order']    = $order;
		$data['nopaging'] = $nopaging;
		$data['timezone'] = get_option( 'gmt_offset' );
		$component = new \WP_Defender\Component();
		//if timezone is 9.5 mean we will convert it manually to UTC before submit
		if ( '9.5' === $data['timezone'] ) {
			$formats = \WP_Defender\Traits\Formats();
			$dateFrom          = $formats->format_date_time( $component->local_to_utc( $data['date_from'] ), false );
			$dateTo            = $formats->format_date_time( $component->local_to_utc( $data['date_to'] ), false );
			$data['date_from'] = $dateFrom;
			$data['date_to']   = $dateTo;
			$data['timezone']  = '0';
		}
		$wpmu_dev = new WPMUDEV();
		$response = $component->dev_call( 'https://' . self::$end_point . '/logs', $data, array(
			'method'  => 'GET',
			'timeout' => 20,
			'headers' => array(
				'apikey' => $wpmu_dev->get_apikey()
			)
		), true );

		//todo need to remove in some next versions
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$body    = wp_remote_retrieve_body( $response );
			$results = json_decode( $body, true );
			if ( isset( $results['message'] ) ) {
				return new \WP_Error( Error_Code::API_ERROR, $results['message'] );
			}

			return $results;
		}

		return new \WP_Error( Error_Code::API_ERROR, sprintf( __( "Whoops, Defender had trouble loading up your event log. You can try a <a href='%s'class=''>​quick refresh</a>​ of this page or check back again later.", 'wpdef' ),
			network_admin_url( 'admin.php?page=wdf-logging' ) ) );
	}

	/**
	 * Queue event data prepare for submitting
	 *
	 * @param $data
	 */
	public static function queue_events_data( $data ) {
		$events   = \Calotes\Helper\Array_Cache::get( 'events_queue', array() );
		$events[] = $data;
		\Calotes\Helper\Array_Cache::set( 'events_queue', $events );
	}
}