<?php // phpcs:ignore
/**
 * Update backup progress task in the backups page.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Backup;

use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Controller;

/**
 * Update backup progress task class.
 */
class Progress extends Task {

	/**
	 * Takes the info about the running backup from the db and displays the appropriate row.
	 *
	 * @param array $args Arguments coming from the ajax call.
	 */
	public function apply( $args = array() ) {
		$model          = $args['model'];
		$backup_running = $model->get( 'backup_running' );

		$backup_running_info = $model->get_running_backup_info( $backup_running );

		if ( $model->add_errors( $this ) ) {
			delete_site_option( Controller\Ajax\Backup::SNAPSHOT_RUNNING_BACKUP );
			delete_site_option( Controller\Ajax\Backup::SNAPSHOT_RUNNING_BACKUP_STATUS );

			return false;
		}

		return $backup_running_info;

	}
}