<?php
//simple functions that can access globally
use Calotes\Helper\Array_Cache;

/**
 * @param $path
 *
 * @return string
 */
function defender_asset_url( $path ) {
	$base_url = plugin_dir_url( dirname( __FILE__ ) );

	return untrailingslashit( $base_url ) . $path;
}

/**
 * @param $path
 *
 * @return string
 */
function defender_path( $path ) {
	$base_path = plugin_dir_path( dirname( __FILE__ ) );

	return $base_path . $path;
}

/**
 * Sanitize submitted data
 *
 * @param  array  $data
 *
 * @return array
 */
function defender_sanitize_data( $data ) {
	foreach ( $data as $key => &$value ) {
		if ( is_array( $value ) ) {
			$value = defender_sanitize_data( $value );
		} else {
			$value = sanitize_textarea_field( $value );
		}
	}

	return $data;
}

/**
 * Retrieve wp-config.php file path
 *
 * @return string
 */
function defender_wp_config_path() {
	if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
		return ABSPATH . 'wp-config.php';
	}

	if ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
		return dirname( ABSPATH ) . '/wp-config.php';
	}

	if ( defined( 'WD_TEST' ) && constant( 'WD_TEST' ) == true ) {
		return '/tmp/wordpress-tests-lib/wp-tests-config.php';
	}
}

/**
 * Check whether we're on windows platform or not
 *
 * @return bool
 */
function defender_is_windows() {
	return '\\' === DIRECTORY_SEPARATOR;
}

/**
 * @return \DI\Container
 */
function wd_di() {
	global $wp_defender_di;

	return $wp_defender_di;
}

/**
 * @return \WP_Defender\Central
 */
function wd_central() {
	global $wp_defender_central;

	return $wp_defender_central;
}

/**
 * Delete every data & settings
 */
function defender_nuke() {
	Array_Cache::get( 'advanced_tools' )->remove_data();
	Array_Cache::get( 'audit' )->remove_data();
	Array_Cache::get( 'dashboard' )->remove_data();
	Array_Cache::get( 'security_tweaks' )->remove_data();
	Array_Cache::get( 'scan' )->remove_data();
	Array_Cache::get( 'ip_lockout' )->remove_data();
	Array_Cache::get( 'two_fa' )->remove_data();
	Array_Cache::get( 'advanced_tools' )->remove_data();
	Array_Cache::get( 'notification' )->remove_data();
	Array_Cache::get( 'tutorial' )->remove_data();
	Array_Cache::get( 'blocklist_monitor' )->remove_data();

	Array_Cache::get( 'advanced_tools' )->remove_settings();
	Array_Cache::get( 'audit' )->remove_settings();
	Array_Cache::get( 'dashboard' )->remove_settings();
	Array_Cache::get( 'security_tweaks' )->remove_settings();
	Array_Cache::get( 'scan' )->remove_settings();
	Array_Cache::get( 'ip_lockout' )->remove_settings();
	Array_Cache::get( 'two_fa' )->remove_settings();
	Array_Cache::get( 'advanced_tools' )->remove_settings();
	Array_Cache::get( 'notification' )->remove_settings();
	Array_Cache::get( 'tutorial' )->remove_settings();
	Array_Cache::get( 'blocklist_monitor' )->remove_settings();

	delete_site_option( 'wp_defender' );
	delete_option( 'wp_defender' );
	delete_option( 'wd_db_version' );
	delete_site_option( 'wd_db_version' );
	delete_site_option( 'wp_defender_shown_activator' );
}

/**
 * Get backward compatibility
 *
 * @return array
 */
function defender_backward_compatibility() {
	$wpmu_dev         = new \WP_Defender\Behavior\WPMUDEV();
	$two_fa_settings  = new \WP_Defender\Model\Setting\Two_Fa();
	$list             = wd_di()->get( \WP_Defender\Controller\Two_Factor::class )->dump_routes_and_nonces();		 		 	     									
	$lost_url         = add_query_arg(
		array(
			'action'     => 'wp_defender/v1/hub/',
			'_def_nonce' => $list['nonces']['send_backup_code'],
			'route'      => $list['routes']['send_backup_code'],
		),
		admin_url( 'admin-ajax.php' )
	);

	return array(
		'is_free'          => ! $wpmu_dev->is_pro(),
		'plugin_url'       => defender_asset_url( '' ),
		'two_fa_settings'  => $two_fa_settings,
		'two_fa_component' => \WP_Defender\Component\Two_Fa::class,
		'lost_url'         => $lost_url
	);
}

/**
 * Polyfill functions for supporting WordPress 5.3
 *
 * @since 2.4.2
 */
if ( ! function_exists( 'wp_timezone_string' ) ) {
	/**
	 * Retrieves the timezone from site settings as a string.
	 *
	 * Uses the `timezone_string` option to get a proper timezone if available,
	 * otherwise falls back to an offset.
	 *
	 * @since 5.3.0
	 *
	 * @return string PHP timezone string or a ±HH:MM offset.
	 */
	function wp_timezone_string() {
		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			return $timezone_string;
		}

		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

		return $tz_offset;
	}
}

if ( ! function_exists( 'wp_timezone' ) ) {
	/**
	 * Retrieves the timezone from site settings as a `DateTimeZone` object.
	 *
	 * Timezone can be based on a PHP timezone string or a ±HH:MM offset.
	 *
	 * @since 5.3.0
	 *
	 * @return DateTimeZone Timezone object.
	 */
	function wp_timezone() {
		return new DateTimeZone( wp_timezone_string() );
	}
}

if ( ! function_exists( 'defender_wp_check_php_version' ) ) {
	/**
	 * Polyfill for function of WP core wp_check_php_version().
	 *
	 * @since WP 5.1.0
	 * @since WP 5.1.1 Added the {@see 'wp_is_php_version_acceptable'} filter.
	 *
	 * @return array|false Array of PHP version data. False on failure.
	 */
	function defender_wp_check_php_version() {
		$version = phpversion();
		$key     = md5( $version );

		$response = get_site_transient( 'php_check_' . $key );
		if ( false === $response ) {
			$url = 'http://api.wordpress.org/core/serve-happy/1.0/';
			if ( wp_http_supports( array( 'ssl' ) ) ) {
				$url = set_url_scheme( $url, 'https' );
			}

			$url = add_query_arg( 'php_version', $version, $url );

			$response = wp_remote_get( $url );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}

			/**
			 * Response should be an array with:
			 *  'recommended_version' - string - The PHP version recommended by WordPress.
			 *  'is_supported' - boolean - Whether the PHP version is actively supported.
			 *  'is_secure' - boolean - Whether the PHP version receives security updates.
			 *  'is_acceptable' - boolean - Whether the PHP version is still acceptable for WordPress.
			 */
			$response = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $response ) ) {
				return false;
			}

			set_site_transient( 'php_check_' . $key, $response, WEEK_IN_SECONDS );
		}

		if ( isset( $response['is_acceptable'] ) && $response['is_acceptable'] ) {
			/**
			 * Filters whether the active PHP version is considered acceptable by WordPress.
			 *
			 * Returning false will trigger a PHP version warning to show up in the admin dashboard to administrators.
			 *
			 * This filter is only run if the wordpress.org Serve Happy API considers the PHP version acceptable, ensuring
			 * that this filter can only make this check stricter, but not loosen it.
			 *
			 * @since 5.1.1
			 *
			 * @param bool   $is_acceptable Whether the PHP version is considered acceptable. Default true.
			 * @param string $version       PHP version checked.
			 */
			$response['is_acceptable'] = (bool) apply_filters( 'wp_is_php_version_acceptable', true, $version );
		}

		return $response;
	}
}