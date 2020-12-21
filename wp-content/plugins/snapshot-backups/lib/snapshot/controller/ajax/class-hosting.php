<?php // phpcs:ignore
/**
 * Snapshot controllers: Hosting backups AJAX controller class
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Controller\Ajax;

use WPMUDEV\Snapshot4\Controller;
use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Helper;
use WPMUDEV\Snapshot4\Model;

/**
 * Hosting backups AJAX controller class
 */
class Hosting extends Controller\Ajax {

	/**
	 * Boots the controller and sets up event listeners.
	 */
	public function boot() {
		if ( ! is_admin() ) {
			return false;
		}

		add_action( 'wp_ajax_snapshot-list_hosting_backups', array( $this, 'json_list_hosting_backups' ) );
		add_action( 'wp_ajax_snapshot-download_hosting_backup', array( $this, 'json_download_hosting_backup' ) );
	}

	/**
	 * Handles requesting the hosting API for actions about backup listing.
	 */
	public function json_list_hosting_backups() {
		$this->do_request_sanity_check( 'snapshot_list_hosting_backups', self::TYPE_POST );

		$task    = new Task\Hosting\Listing();
		$backups = $task->apply();

		if ( ! is_array( $backups ) ) {
			wp_send_json_error();
		}

		$template = new Helper\Template();
		foreach ( $backups as $key => $item ) {
			$item['icon']              = $item['is_automate'] ? 'automate' : 'storage-server-data';
			$item['icon_tooltip_text'] = $item['is_automate']
				? __( 'WPMU DEV Automate backup', 'snapshot' )
				: __( 'WPMU DEV Hosting backup', 'snapshot' );

			$item['destination_icon']  = 'wpmudev-logo';
			$item['destination_title'] = $item['is_automate']
				? __( 'WPMU DEV (Automate)', 'snapshot' )
				: __( 'WPMU DEV (Hosting)', 'snapshot' );

			$site_id                  = Helper\Api::get_site_id();
			$hub_hosting_backups_link = sprintf( 'https://premium.wpmudev.org/hub2/site/%s/backups', rawurlencode( $site_id ) );
			$item['manage_link']      = $hub_hosting_backups_link;

			ob_start();
			$template->render( 'pages/hosting_backups/row', $item );
			$backups[ $key ]['html_row'] = ob_get_clean();
		}

		$last_backup_ts = null;
		if ( isset( $backups[0] ) ) {
			$last_backup_ts = $backups[0]['created_at'];
		}

		$next_backup_ts  = Model\Schedule::get_next_backup_timestamp( 'daily', '02:00' );
		$backup_schedule = __( 'Nightly @', 'snapshot' ) . ' ' . Helper\Datetime::format( $next_backup_ts, Helper\Datetime::get_time_format() );
		/* translators: %s - Backup schedule */
		$backup_schedule_tooltip = sprintf( __( 'The hosting backups are running %s. You can\'t update the schedule of hosting backups.', 'snapshot' ), $backup_schedule );

		wp_send_json_success(
			array(
				'backups'                 => $backups,
				'last_backup_time'        => Helper\Datetime::format( $last_backup_ts ),
				'next_backup_time'        => Helper\Datetime::format( $next_backup_ts ),
				'backup_schedule'         => $backup_schedule,
				'backup_schedule_tooltip' => $backup_schedule_tooltip,
			)
		);
	}

	/**
	 * Handles requesting the hosting API for backup downloading.
	 */
	public function json_download_hosting_backup() {
		$this->do_request_sanity_check( 'snapshot_download_hosting_backup', self::TYPE_POST );

		$data              = array();
		$data['backup_id'] = isset( $_POST['backup_id'] ) ? $_POST['backup_id'] : null; // phpcs:ignore

		$task = new Task\Hosting\Export();

		$validated_data = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_data ) ) {
			wp_send_json_error( $validated_data );
		}

		$args['backup_id'] = $validated_data['backup_id'];

		$result = $task->apply( $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'error' => $result ) );
		}

		wp_send_json_success(
			array(
				'api_response' => $result,
				/* translators: %s - Website hostname */
				'notice_html'  => sprintf( __( 'We are preparing your backup export for <strong>%s</strong>. You will recieve an email with the backup file to download.', 'snapshot' ), esc_html( wp_parse_url( get_site_url(), PHP_URL_HOST ) ) ),
			)
		);
	}
}