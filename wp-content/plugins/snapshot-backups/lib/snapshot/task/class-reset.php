<?php // phpcs:ignore
/**
 * Reset settings task.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task;

use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Helper\Log;

/**
 * Reset settings task class
 */
class Reset extends Task {

	/**
	 * Does the initial actions needed to trigger a restore.
	 *
	 * @param array $args Restore arguments, like backup_id and rootpath.
	 */
	public function apply( $args = array() ) {
		$request_data = get_site_option( 'wp_snapshot_backup_schedule' );

		// First, lets see if we can reset the schedule, by making it inactive.
		if ( false !== $request_data ) {
			$schedule_model = new Model\Schedule( $request_data );
			$request_model  = new Model\Request\Schedule();

			$reset_schedule_args                   = array();
			$reset_schedule_args['request_model']  = $request_model;
			$reset_schedule_args['schedule_model'] = $schedule_model;
			$reset_schedule_args['action']         = 'delete';

			$task = new Task\Request\Schedule();

			$task->apply( $reset_schedule_args );
			if ( $task->has_errors() ) {
				foreach ( $task->get_errors() as $error ) {
					Log::error( $error->get_error_message() );
				}

				wp_send_json_error();
			}
		}

		delete_site_option( 'snapshot_global_exclusions' );
		delete_site_option( 'snapshot_remove_on_uninstall' );
		delete_site_option( 'snapshot_email_settings' );
	}
}