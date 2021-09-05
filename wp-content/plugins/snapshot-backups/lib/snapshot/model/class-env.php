<?php // phpcs:ignore
/**
 * Snapshot models: environment recognition model
 *
 * @package snanpshot
 */

namespace WPMUDEV\Snapshot4\Model;

/**
 * Environment model
 */
class Env {

	/**
	 * Whether we're running as part of a test suite
	 *
	 * @return bool
	 */
	public static function is_phpunit_test() {
		return ( defined( 'SNAPSHOT_IS_TEST_ENV' ) && SNAPSHOT_IS_TEST_ENV ) &&
			defined( 'SNAPSHOT_TESTS_DATA_DIR' ) &&
			class_exists( 'WP_UnitTestCase' ) &&
			function_exists( '_manually_load_plugin' );
	}

	/**
	 * Checks whether we're on WP Engine
	 *
	 * @return bool
	 */
	public static function is_wp_engine() {
		return defined( 'WPE_APIKEY' );
	}

	/**
	 * Whether we're in an environment that requires auth pings
	 *
	 * This generally means WP Engine.
	 *
	 * @return bool
	 */
	public static function is_auth_requiring_env() {

		/**
		 * Decide whether we're in an auth-requiring environment.
		 *
		 * Used in building ping request arguments to establish runner
		 * execution context.
		 *
		 * @param bool $is_auth Check result this far.
		 *
		 * @return bool
		 */
		return (bool) apply_filters(
			'snapshot_is_auth_requiring_env',
			self::is_wp_engine()
		);
	}

	/**
	 * Checks whether we're on WPMU DEV Hosting
	 *
	 * @return bool
	 */
	public static function is_wpmu_hosting() {
		return isset( $_SERVER['WPMUDEV_HOSTED'] ) && ! empty( $_SERVER['WPMUDEV_HOSTED'] );
	}

	/**
	 * Checks whether we're on WPMU DEV Hosting staging
	 *
	 * @return bool
	 */
	public static function is_wpmu_staging() {
		if ( ! self::is_wpmu_hosting() ) {
			return false;
		}

		return isset( $_SERVER['WPMUDEV_HOSTING_ENV'] ) &&
			'staging' === $_SERVER['WPMUDEV_HOSTING_ENV'];
	}

	/**
	 * Returns WPMUDEV API key
	 *
	 * @return string|bool
	 */
	public static function get_wpmu_api_key() {
		$api_key = defined( 'WPMUDEV_APIKEY' ) && WPMUDEV_APIKEY
			? WPMUDEV_APIKEY
			: get_site_option( 'wpmudev_apikey', false );
		return $api_key;
	}

	/**
	 * Returns WPMUDEV hosting site id
	 *
	 * @return string|null
	 */
	public static function get_wpmu_hosting_site_id() {
		$hosting_site_id = defined( 'WPMUDEV_HOSTING_SITE_ID' ) && WPMUDEV_HOSTING_SITE_ID
			? WPMUDEV_HOSTING_SITE_ID
			: null;
		return $hosting_site_id;
	}

	/**
	 * Returns WPMUDEV API Server URL
	 *
	 * @return string
	 */
	public static function get_wpmu_api_server_url() {
		$api_server_url = defined( 'WPMUDEV_CUSTOM_API_SERVER' ) && WPMUDEV_CUSTOM_API_SERVER
			? trailingslashit( WPMUDEV_CUSTOM_API_SERVER )
			: 'https://wpmudev.com/';
		return $api_server_url;
	}
}