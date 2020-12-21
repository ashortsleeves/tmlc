<?php // phpcs:ignore
/**
 * Snapshot helpers: settings helper class
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Helper;

use WPMUDEV\Snapshot4\Model\Env;

/**
 * Settings helper class
 */
class Settings {

	/**
	 * Returns true if user has selected to keep their settings upon uninstall.
	 *
	 * @return bool
	 */
	public static function get_remove_settings() {
		return boolval( get_site_option( 'snapshot_remove_on_uninstall' ) );
	}

	/**
	 * Returns true if the "Welcome modal" was viewed
	 *
	 * @return bool
	 */
	public static function get_started_seen() {
		return boolval( get_site_option( 'snapshot_started_seen' ) );
	}

	/**
	 * Mark "Welcome modal" as viewed
	 *
	 * @param bool $value Set True to mark as viewed.
	 */
	public static function set_started_seen( $value ) {
		update_site_option( 'snapshot_started_seen', intval( boolval( $value ) ) );
	}

	/**
	 * Returns true if the "Welcome modal" was viewed (even in a previous install)
	 *
	 * @return bool
	 */
	public static function get_started_seen_persistent() {
		return boolval( get_site_option( 'snapshot_started_seen_persistent' ) );
	}

	/**
	 * Mark "Welcome modal" as viewed (even for later installs)
	 *
	 * @param bool $value Set True to mark as viewed.
	 */
	public static function set_started_seen_persistent( $value ) {
		update_site_option( 'snapshot_started_seen_persistent', intval( boolval( $value ) ) );
	}

	/**
	 * Returns true if filelist log must be more detailed.
	 *
	 * @return bool
	 */
	public static function get_filelist_log_verbose() {
		return boolval( defined( 'SNAPSHOT4_FILELIST_LOG_VERBOSE' ) && SNAPSHOT4_FILELIST_LOG_VERBOSE );
	}

	/**
	 * Returns true if zipstream log must be more detailed.
	 *
	 * @return bool
	 */
	public static function get_zipstream_log_verbose() {
		return boolval( defined( 'SNAPSHOT4_FILE_ZIPSTREAM_LOG_VERBOSE' ) && SNAPSHOT4_FILE_ZIPSTREAM_LOG_VERBOSE );
	}

	/**
	 * Returns API URL of the Service. By default it's a "prod" environment URL.
	 *
	 * @return string
	 */
	public static function get_service_api_url() {
		$url = 'https://bbna4i2zbe.execute-api.us-east-1.amazonaws.com/prod/';

		if ( defined( 'SNAPSHOT4_SERVICE_API_URL' ) && is_string( SNAPSHOT4_SERVICE_API_URL ) ) {
			$url = SNAPSHOT4_SERVICE_API_URL;
		}

		return $url;
	}

	/**
	 * Returns email settings.
	 *
	 * @return array
	 */
	public static function get_email_settings() {
		$default = array(
			'on_fail_send'       => false,
			'on_fail_recipients' => array(),
		);

		$email_settings = get_site_option( 'snapshot_email_settings', $default );

		if ( empty( $email_settings['on_fail_recipients'] ) ) {
			$email_settings['on_fail_recipients'] = array(
				array(
					'name'  => wp_get_current_user()->display_name,
					'email' => get_site_option( 'admin_email' ),
				),
			);
		}

		$on_fail_recipients_count = count( $email_settings['on_fail_recipients'] );

		$result['notice_type'] = $email_settings['on_fail_send'] && $on_fail_recipients_count > 0 ? 'success' : null;
		if ( 'success' !== $result['notice_type'] ) {
			$result['notice_text'] = __( 'Failed backup email notification is currently disabled.', 'snapshot' );
		} elseif ( 1 === $on_fail_recipients_count ) {
			$result['notice_text'] = __( 'Failed backup email notification is enabled for 1 recipient.', 'snapshot' );
		} else {
			/* translators: %d - Number of email recipients */
			$result['notice_text'] = sprintf( __( 'Failed backup email notification is enabled for %d recipients.', 'snapshot' ), $on_fail_recipients_count );
		}

		$result['email_settings'] = $email_settings;

		return $result;
	}

	/**
	 * Update email settings.
	 *
	 * @param array $fields Update this params, e.g. on_fail_send, on_fail_recipients.
	 */
	public static function update_email_settings( array $fields ) {
		$settings       = self::get_email_settings();
		$email_settings = $settings['email_settings'];
		foreach ( $fields as $field => $value ) {
			$email_settings[ $field ] = $value;
		}
		if ( isset( $email_settings['on_fail_recipients'] ) && ! count( $email_settings['on_fail_recipients'] ) ) {
			$email_settings['on_fail_send'] = false;
		}
		update_site_option( 'snapshot_email_settings', $email_settings );
	}

	/**
	 * Returns x.y.z plugin's version
	 *
	 * @return string
	 */
	public static function get_plugin_patch_version() {
		return explode( '-', SNAPSHOT_BACKUPS_VERSION )[0];
	}

	/**
	 * Returns true if the "What's new" modal was viewed
	 *
	 * @return bool
	 */
	public static function get_whats_new_seen() {
		$seen_version = get_site_option( 'snapshot_whats_new_seen' );
		if ( ! $seen_version ) {
			return false;
		}
		return version_compare( self::get_plugin_patch_version(), $seen_version ) <= 0;
	}

	/**
	 * Mark "What's new" modal as viewed
	 *
	 * @param string $value Set seen version, null = current version.
	 */
	public static function set_whats_new_seen( $value = null ) {
		if ( is_null( $value ) ) {
			$value = self::get_plugin_patch_version();
		}
		update_site_option( 'snapshot_whats_new_seen', $value );
	}

	/**
	 * Allows the user to delete a backup for the current session
	 *
	 * @param bool $value true - allow, false - deny.
	 */
	public static function allow_delete_backup( $value = true ) {
		$token = sha1( wp_get_session_token() );
		set_transient( "snapshot_allow_delete_backup_$token", intval( boolval( $value ) ), 86400 );
	}

	/**
	 * Returns true if the user in the current session is allowed to delete a backup
	 *
	 * @return bool
	 */
	public static function can_delete_backup() {
		if ( Env::is_phpunit_test() ) {
			// Skip checking during unit test.
			return true;
		}

		$extra_step = get_transient( 'snapshot_extra_security_step' );
		if ( false !== $extra_step && 0 === intval( $extra_step ) ) {
			return true;
		}

		$token = sha1( wp_get_session_token() );
		$value = get_transient( "snapshot_allow_delete_backup_$token" );
		return boolval( $value );
	}

}