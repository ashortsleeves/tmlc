<?php // phpcs:ignore
/**
 * Plugin main class and entry point.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4;

use WPMUDEV\Snapshot4\Helper\Fs;
use WPMUDEV\Snapshot4\Helper\Log;
use WPMUDEV\Snapshot4\Helper\Singleton;
use WPMUDEV\Snapshot4\Controller;

/**
 * Main class
 */
class Main extends Singleton {

	const SNAPSHOT4_V3_ADMIN_NOTICE_DISMISSED = 'snapshot4_v3_admin_notice_dismissed';
	const SNAPSHOT_V3_PLUGIN_FILE             = 'snapshot/snapshot.php';

	/**
	 * Boots the controller and sets up event listeners.
	 */
	public function boot() {
		$controllers = array(
			Controller\Activate::class,
			Controller\Admin::class,
			Controller\Ajax::class,
			Controller\Hub::class,
			Controller\Ajax\Schedule::class,
			Controller\Ajax\Listing::class,
			Controller\Ajax\Export::class,
			Controller\Ajax\Backup::class,
			Controller\Ajax\Hosting::class,
			Controller\Ajax\Restore::class,
			Controller\Ajax\Destination::class,
			Controller\Ajax\Destination\S3::class,
			Controller\Ajax\Destination\Googledrive::class,
			Controller\Service\Hub::class,
			Controller\Service\Backup::class,
			Controller\Service\Backup\Fetching::class,
			Controller\Service\Backup\Zipstreaming::class,
			Controller\Service\Export\Email::class,
		);
		foreach ( $controllers as $cname ) {
			if ( class_exists( $cname ) ) {
				$controller = call_user_func( array( $cname, 'get' ) );
				$controller->boot();
			}
		}

		if ( 'install_wpmudev_dash' === filter_input( INPUT_GET, 'plugin', FILTER_SANITIZE_URL ) && 'install-plugin' === filter_input( INPUT_GET, 'action', FILTER_SANITIZE_URL ) ) {
			require_once __DIR__ . '/../wpmudev/dash/wpmudev-dash-notification.php';
		}

		if ( ! wp_next_scheduled( 'snapshot4_handle_schedules' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'snapshot4_handle_schedules' );
		}

		add_action( 'snapshot4_handle_schedules', array( $this, 'handle_schedules' ) );

		// Add a cron to create an empty index.php file inside the log folder, for security purposes.
		if ( ! wp_next_scheduled( 'snapshot4_add_empty_index' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'snapshot4_add_empty_index' );
		}

		add_action( 'snapshot4_add_empty_index', array( $this, 'add_empty_index' ) );

		add_action( 'snapshot4_retry_region_migration', array( $this, 'retry_region_migration' ) );

		// Allow WP Heartbeat API on WP Engine for Snapshot pages.
		add_filter( 'wpe_heartbeat_allowed_pages', array( $this, 'wpe_allow_heartbeat' ) );

		// Add prompt to uninstall v3.
		add_action( is_multisite() ? 'network_admin_notices' : 'admin_notices', array( $this, 'snapshot_admin_notices_v3_prompt' ) );

		if ( is_main_site() ) {
			$migration_attempt = get_site_option( 'snapshot4_region_migration_attempt', false );

			if ( empty( $migration_attempt ) ) {
				// Add nonces that will be used for AJAX calls throughout the WP admin.
				add_action( 'admin_head', array( $this, 'add_global_snapshot_nonces' ) );

				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_snapshot_scripts' ) );
			}
		}
	}

	/**
	 * Handles local and remote schedules to ensure they're always in agreement.
	 */
	public static function handle_schedules() {
		$stored_schedule = get_site_option( 'wp_snapshot_backup_schedule' );

		if ( isset( $stored_schedule['schedule_id'] ) ) {
			// We have a schedule in the local db, lets make the service-side schedule like the local one.
			// @TODO: Refactor this *please*.
			$request_data['status']             = isset( $stored_schedule['bu_status'] ) ? $stored_schedule['bu_status'] : null;
			$request_data['schedule_id']        = isset( $stored_schedule['schedule_id'] ) ? $stored_schedule['schedule_id'] : null;
			$request_data['site_id']            = isset( $stored_schedule['site_id'] ) ? $stored_schedule['site_id'] : null;
			$request_data['frequency']          = isset( $stored_schedule['bu_frequency'] ) ? $stored_schedule['bu_frequency'] : null;
			$request_data['files']              = isset( $stored_schedule['bu_files'] ) ? $stored_schedule['bu_files'] : null;
			$request_data['tables']             = isset( $stored_schedule['bu_tables'] ) ? $stored_schedule['bu_tables'] : null;
			$request_data['time']               = isset( $stored_schedule['bu_time'] ) ? $stored_schedule['bu_time'] : null;
			$request_data['frequency_weekday']  = isset( $stored_schedule['bu_frequency_weekday'] ) ? $stored_schedule['bu_frequency_weekday'] : null;
			$request_data['frequency_monthday'] = isset( $stored_schedule['bu_frequency_monthday'] ) ? $stored_schedule['bu_frequency_monthday'] : null;

			$schedule_model = new Model\Schedule( $request_data );
			$request_model  = new Model\Request\Schedule();

			$reset_schedule_args                   = array();
			$reset_schedule_args['request_model']  = $request_model;
			$reset_schedule_args['schedule_model'] = $schedule_model;
			$reset_schedule_args['action']         = 'update';

			$task = new Task\Request\Schedule();

			$task->apply( $reset_schedule_args );
		} else {
			// We have no schedules in the local db, lets ensure we dont have schedules system-side either.

			$request_model = new Model\Request\Schedule();
			$schedule      = new Model\Schedule( array() );
			$response      = $request_model->schedule_request( 'get_status_all', $schedule );
			$response_code = wp_remote_retrieve_response_code( $response );

			if ( 200 === $response_code ) {
				$all_schedules = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( is_array( $all_schedules ) && isset( $all_schedules[0]['schedule_id'] ) ) {
					// We have confirmed we have a schedule system-side, lets take its id and delete it please.

					$request_data = array(
						'schedule_id' => $all_schedules[0]['schedule_id'],
					);

					// But first, lets completely delete the local entry, to ensure no funny business.
					delete_site_option( 'wp_snapshot_backup_schedule' );

					// Ok, lets delete the remote schedule too now.
					$schedule_model = new Model\Schedule( $request_data );
					$request_model  = new Model\Request\Schedule();

					$reset_schedule_args                   = array();
					$reset_schedule_args['request_model']  = $request_model;
					$reset_schedule_args['schedule_model'] = $schedule_model;
					$reset_schedule_args['action']         = 'hard_delete';

					$task = new Task\Request\Schedule();

					$task->apply( $reset_schedule_args );
				}
			}
		}
	}

	/**
	 * Creates empty index file in logs folder.
	 */
	public static function add_empty_index() {
		Log::check_dir( true );
	}

	/**
	 * Handles local and remote schedules to ensure they're always in agreement.
	 */
	public static function retry_region_migration() {
		$local_region = get_site_option( 'snapshot_backup_region', false );

		if ( empty( $local_region ) ) {
			// We're good, no local region here.
			return;
		}

		// Store the region system-side.
		$data           = array();
		$data['action'] = 'get';

		$get_task         = new Task\Request\Region();
		$validated_params = $get_task->validate_request_data( $data );

		$args                  = $validated_params;
		$args['request_model'] = new Model\Request\Region();

		$region = $get_task->apply( $args );

		if ( $get_task->has_errors() ) {
			// The request failed, lets repeat in an hour.
			wp_schedule_single_event( time() + 3600, 'snapshot4_retry_region_migration' );
			return;
		}

		if ( ! empty( $region ) ) {
			// Region is set system-side, no need to update it.
			return;
		}

		$data['action'] = 'set';

		$set_task         = new Task\Request\Region();
		$validated_params = $set_task->validate_request_data( $data );

		$args                  = $validated_params;
		$args['request_model'] = new Model\Request\Region();
		$args['region']        = strtoupper( $local_region );

		$result = $set_task->apply( $args );

		if ( $set_task->has_errors() ) {
			foreach ( $set_task->get_errors() as $error ) {
				Log::error( $error->get_error_message() );
			}

			wp_schedule_single_event( time() + 3600, 'snapshot4_retry_region_migration' );

			return;
		}

		$regions = array(
			'US',
			'EU',
		);

		if ( ! in_array( $result, $regions, true ) ) {
			Log::error( __( 'The backup region had a different value than expected and it wasn\'t stored properly system-side.', 'snapshot' ) );
			wp_schedule_single_event( time() + 3600, 'snapshot4_retry_region_migration' );
			return;
		}

		// Let's delete the local db entry of region.
		delete_site_option( 'snapshot_backup_region' );

		Log::info( __( 'The backup region has been set system-side.', 'snapshot' ) );
	}

	/**
	 * Allow WP Heartbeat API on WP Engine for Snapshot pages.
	 *
	 * @param array $heartbeat_allowed_pages Pages allowed for Heartbeat.
	 * @return array
	 */
	public function wpe_allow_heartbeat( $heartbeat_allowed_pages ) {
		if ( 'snapshot-backups' === filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING ) ) {
			array_push( $heartbeat_allowed_pages, 'admin.php' );
		}
		return array_unique( $heartbeat_allowed_pages );
	}

	/**
	 * Show WP notice to uninstall v3.
	 */
	public function snapshot_admin_notices_v3_prompt() {
		if ( ! current_user_can( is_multisite() ? 'manage_network_options' : 'manage_options' ) ) {
			return;
		}

		// Don't show the admin notice on Snapshot pages.
		if ( 'snapshot' === get_current_screen()->parent_base || 'snapshot_pro_dashboard' === get_current_screen()->parent_base ) {
			return;
		}

		// Don't show the admin notice if dismissed.
		if ( get_site_option( self::SNAPSHOT4_V3_ADMIN_NOTICE_DISMISSED ) ) {
			return;
		}

		// Don't show the admin notice if Snapshot v3 isn't active.
		if ( ! is_plugin_active( self::SNAPSHOT_V3_PLUGIN_FILE ) ) {
			return;
		}
		$out    = new Helper\Template();
		$assets = new Helper\Assets();

		// Check if there are local (v3) snapshots around.
		$v3_local    = false;
		$v3_settings = get_option( 'wpmudev_snapshot' );

		if ( isset( $v3_settings['items'] ) && is_array( $v3_settings['items'] ) && ! empty( $v3_settings['items'] ) ) {
			$v3_local = true;
		}

		$out->render(
			'common/v4-admin-prompt',
			array(
				'assets'   => $assets,
				'v3_local' => $v3_local,
			)
		);

		wp_enqueue_script(
			'snapshot-uninstall-prompt',
			$assets->get_asset( 'js/snapshot-global.js' ),
			array( 'jquery-ui-dialog' ),
			SNAPSHOT_BACKUPS_VERSION,
			true
		);
		wp_enqueue_style(
			'snapshot-uninstall-prompt',
			$assets->get_asset( 'css/snapshot-global.css' ),
			array( 'wp-jquery-ui-dialog' ),
			SNAPSHOT_BACKUPS_VERSION
		);

		wp_localize_script( 'snapshot-uninstall-prompt', 'SnapshotAjaxGlobal', array( 'snapshot_ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	/**
	 * Add nonces that will be used for AJAX calls throughout the WP admin.
	 */
	public function enqueue_snapshot_scripts() {
		$assets = new Helper\Assets();

		wp_enqueue_script(
			'snapshot-migration',
			$assets->get_asset( 'js/snapshot-migrate.js' ),
			array( 'jquery' ),
			SNAPSHOT_BACKUPS_VERSION,
			true
		);

		wp_localize_script( 'snapshot-migration', 'SnapshotAjaxMigration', array( 'snapshot_migrationajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	/**
	 * Add nonces that will be used for AJAX calls throughout the WP admin.
	 */
	public function add_global_snapshot_nonces() {
		wp_nonce_field( 'snapshot_migrate_region', '_wpnonce-snapshot_migrate_region' );
	}

}