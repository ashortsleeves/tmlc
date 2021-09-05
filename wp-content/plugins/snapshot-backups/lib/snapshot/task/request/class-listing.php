<?php // phpcs:ignore
/**
 * Listing of scheduled backups.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Request;

use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Controller;

/**
 * Listing backups requesting class
 */
class Listing extends Task {

	/**
	 * Required request parameters, with their sanitization method
	 *
	 * @var array
	 */
	protected $required_params = array(
		'schedule_id' => null,
	);

	/**
	 * Places the request calls to the service for processing the listed backups.
	 *
	 * @param array $args Arguments coming from the ajax call.
	 */
	public function apply( $args = array() ) {
		$request_model = $args['request_model'];

		// Lets assume we have no failed backups in the list.
		$request_model->set( 'failed_backups', 0 );

		if ( ! empty( $args['force_refresh'] ) ) {
			delete_transient( 'snapshot_listed_backups' );
			delete_transient( 'snapshot_current_stats' );
			$backups = false;
		} else {
			$backups = get_transient( 'snapshot_listed_backups' );
		}
		if ( false === $backups ) {
			$response = $request_model->list_backups();
			if ( $request_model->add_errors( $this ) ) {
				return false;
			}

			$backups = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( isset( $backups['message'] ) ) {
				$backups = array();
			}

			set_transient( 'snapshot_listed_backups', $backups, 60 * 60 );
		}

		array_walk( $backups, 'self::add_backup_type' );

		// Return only backup ids (for log list).
		if ( ! empty( $args['return_ids'] ) ) {
			$result = array();
			if ( is_array( $backups ) ) {
				foreach ( $backups as $backup ) {
					$result[] = array(
						'backup_id'      => $backup['snapshot_id'],
						'created_at'     => strtotime( $backup['created_at'] ),
						'type'           => $backup['type'],
						'tpd_exp_status' => isset( $backup['tpd_exp_status'] ) ? $backup['tpd_exp_status'] : array(),
					);
				}
			}
			usort(
				$result,
				function ( $item1, $item2 ) {
					return $item2['created_at'] - $item1['created_at'];
				}
			);
			return $result;
		}

		$backups_info = array();

		if ( is_array( $backups ) ) {
			$backups = $request_model->sort_backups( $backups );

			// Build the backup row for displaying in the backup list.
			foreach ( $backups as $backup ) {
				// Display only the completed backups, since we're going to display any currently running ones with Task\Backup\Progress.
				if ( isset( $backup['snapshot_status'] ) && 'snapshot_completed' !== $backup['snapshot_status'] && 0 !== strpos( $backup['snapshot_status'], 'snapshot_failed_' ) ) {
					// Save its status for later use in Task\Backup\Progress.
					update_site_option( Controller\Ajax\Backup::SNAPSHOT_RUNNING_BACKUP_STATUS, $backup['snapshot_status'] );
					continue;
				}

				$backups_info[] = $request_model->get_backup_info( $backup );
			}
		}

		return $backups_info;
	}

	/**
	 * Adds "type" field.
	 *
	 * @param array $backup Backup info.
	 */
	public static function add_backup_type( &$backup ) {
		if ( ! is_array( $backup ) ) {
			return;
		}

		$type = 'scheduled';
		if ( isset( $backup['bu_frequency'] ) ) {
			if ( 'manual' === $backup['bu_frequency'] ) {
				$type = 'manual';
			}
		}
		if ( isset( $backup['is_automate'] ) && $backup['is_automate'] ) {
			$type = 'automate';
		}

		$backup['type'] = $type;
	}
}