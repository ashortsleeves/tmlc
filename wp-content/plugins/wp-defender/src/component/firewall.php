<?php

namespace WP_Defender\Component;

use WP_Defender\Component;

class Firewall extends Component {
	/**
	 * Queue hooks when this class init
	 */
	public function add_hooks() {
		add_filter( 'defender_ip_lockout_assets', array( &$this, 'output_scripts_data' ) );
		//cron for cleanup
		$next_cleanup = wp_next_scheduled( 'clean_up_old_log' );
		if ( false === $next_cleanup || $next_cleanup > strtotime( '+90 minutes' ) ) {
			wp_clear_scheduled_hook( 'clean_up_old_log' );
			wp_schedule_event( time(), 'hourly', 'clean_up_old_log' );
		}

		add_action( 'clean_up_old_log', array( &$this, 'clean_up_old_log' ) );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function output_scripts_data( $data ) {
		$model            = new \WP_Defender\Model\Setting\Firewall();
		$data['settings'] = array(
			'storage_days' => isset( $model->storage_days ) ? $model->storage_days : 30,
			'class'        => \WP_Defender\Model\Setting\Firewall::class,
		);

		return $data;
	}

	/**
	 * Cron for delete old log
	 */
	public function clean_up_old_log() {
		$settings = new \WP_Defender\Model\Setting\Firewall();
		/**
		 * Filter Count days for IP logs to be saved to DB
		 *
		 * @since 2.3
		 *
		 * @param string
		 */
		$storage_days = apply_filters( 'ip_lockout_logs_store_backward', $settings->storage_days );

		if ( ! is_numeric( $storage_days ) ) {
			return;
		}
		$time_string = '-' . $storage_days . ' days';
		$timestamp   = $this->local_to_utc( $time_string );
		 \WP_Defender\Model\Lockout_Log::remove_logs( $timestamp );
	}
}