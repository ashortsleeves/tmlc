<?php // phpcs:ignore
/**
 * API helper class
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Helper;

/**
 * Helper class
 */
class Api {

	/**
	 * Gets the API key using Dashboard's method.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		global $wpmudev_un;

		if ( ! is_object( $wpmudev_un ) && class_exists( 'WPMUDEV_Dashboard' ) && method_exists( 'WPMUDEV_Dashboard', 'instance' ) ) {
			$wpmudev_un = \WPMUDEV_Dashboard::instance();
		}

		if ( is_object( $wpmudev_un ) && method_exists( $wpmudev_un, 'get_apikey' ) ) {
			$api_key = $wpmudev_un->get_apikey();
		} elseif ( class_exists( 'WPMUDEV_Dashboard' ) && is_object( \WPMUDEV_Dashboard::$api ) && method_exists( \WPMUDEV_Dashboard::$api, 'get_key' ) ) {
			$api_key = \WPMUDEV_Dashboard::$api->get_key();
		} else {
			$api_key = '';
		}

		return $api_key;
	}

	/**
	 * Gets site's id.
	 *
	 * @return int
	 */
	public static function get_site_id() {
		$site_id = get_site_option( 'wpmudev_site_id' );

		if ( empty( $site_id ) ) {
			global $wpmudev_un;

			if ( ! is_object( $wpmudev_un ) && class_exists( 'WPMUDEV_Dashboard' ) && method_exists( 'WPMUDEV_Dashboard', 'instance' ) ) {
				$wpmudev_un = \WPMUDEV_Dashboard::instance();
			}

			if ( is_object( $wpmudev_un ) && method_exists( $wpmudev_un, 'get_site_id' ) ) {
				$site_id = $wpmudev_un->get_site_id();
			} elseif ( class_exists( 'WPMUDEV_Dashboard' ) && is_object( \WPMUDEV_Dashboard::$api ) && method_exists( \WPMUDEV_Dashboard::$api, 'get_site_id' ) ) {
				$site_id = \WPMUDEV_Dashboard::$api->get_site_id();
			} else {
				$site_id = '';
			}
		}

		if ( empty( $site_id ) ) {
			Log::error( __( 'The site doesn\'t seem to have an ID assigned. Try login into the WPMU DEV Dashboard again.', 'snapshot' ) );
		}

		return apply_filters( 'wp_snapshot_site_id', $site_id );
	}

	/**
	 * Returns WPMUDEV Dashboard API object
	 *
	 * @return \WPMUDEV_Dashboard_Api|null
	 */
	public static function get_dashboard_api() {
		if ( class_exists( 'WPMUDEV_Dashboard' ) && ! empty( \WPMUDEV_Dashboard::$api ) ) {
			return \WPMUDEV_Dashboard::$api;
		}
		return null;
	}

	/**
	 * Returns user profile
	 *
	 * @return array|null
	 */
	public static function get_dashboard_profile() {
		$api = self::get_dashboard_api();
		if ( $api && is_callable( array( $api, 'get_profile' ) ) ) {
			$result = $api->get_profile();
			return isset( $result['profile'] ) ? $result['profile'] : null;
		}
		return null;
	}

	/**
	 * Returns user profile username (email)
	 *
	 * @return string
	 */
	public static function get_dashboard_profile_username() {
		$profile = self::get_dashboard_profile();
		return isset( $profile['user_name'] ) ? $profile['user_name'] : '';
	}

	/**
	 * Verify WPMU DEV password
	 *
	 * @param string $password User's password.
	 * @return boolean
	 */
	public static function verify_password( $password ) {
		$username = self::get_dashboard_profile_username();
		if ( ! $username ) {
			return false;
		}

		$api = self::get_dashboard_api();
		if ( ! $api ) {
			return false;
		}

		$data = array(
			'username'     => $username,
			'password'     => $password,
			'redirect_url' => network_admin_url(),
		);

		$response = $api->call( 'authenticate', $data, 'POST', array( 'redirection' => 0 ) );
		$location = wp_remote_retrieve_header( $response, 'Location' );
		$params   = array();
		parse_str( wp_parse_url( $location, PHP_URL_QUERY ), $params );
		if ( ! empty( $params['set_apikey'] ) ) {
			return true;
		}

		return false;
	}
}