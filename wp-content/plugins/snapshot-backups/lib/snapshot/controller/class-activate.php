<?php // phpcs:ignore
/**
 * Snapshot controllers: activation setup controller
 *
 * Handles plugin activation.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Controller;

use WPMUDEV\Snapshot4\Controller;
use WPMUDEV\Snapshot4\Helper\Log;
use WPMUDEV\Snapshot4\Helper\Fs;

/**
 * Setup activation class
 */
class Activate extends Controller {

	/**
	 * Satisfy the interface
	 */
	public function boot() {
	}

	/**
	 * Runs on plugin activation.
	 */
	public static function on_activate() {
		// Create the Snapshot folder where logs are gonna reside.
		Log::check_dir( true );

		// Ensure no schedule stored at first install.
		if ( empty( get_site_option( 'snapshot_v4_installed' ) ) || empty( get_site_option( 'snapshot_v4_cleaned_up' ) ) ) {
			delete_site_option( 'wp_snapshot_backup_schedule' );

			add_site_option( 'snapshot_v4_installed', true );
			add_site_option( 'snapshot_v4_cleaned_up', true );
		}
	}
}