<?php // phpcs:ignore
/**
 * Snapshot controllers: Restore AJAX controller class
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Controller\Ajax;

use WPMUDEV\Snapshot4\Controller;
use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Helper\Lock;
use WPMUDEV\Snapshot4\Helper\Log;
use WPMUDEV\Snapshot4\Helper\Zip;

/**
 * Rrestore AJAX controller class
 */
class Restore extends Controller\Ajax {

	const SNAPSHOT_DOWNLOAD_BACKUP_PROGRESS = 'snapshot_download_backup_progress';

	/**
	 * Boots the controller and sets up event listeners.
	 */
	public function boot() {
		if ( ! is_admin() ) {
			return false;
		}

		add_action( 'wp_ajax_snapshot-process_restore', array( $this, 'json_process_restore' ) );
		add_action( 'wp_ajax_snapshot-cancel_restore', array( $this, 'json_cancel_restore' ) );
		add_action( 'wp_ajax_nopriv_snapshot-check_logged_in', array( $this, 'json_check_logged_in' ) );
		add_action( 'wp_ajax_snapshot-check_logged_in', array( $this, 'json_check_logged_in' ) );
	}

	/**
	 * Responsible to identify whether we got an error during restore because we got logged out or not.
	 */
	public function json_check_logged_in() {
		wp_send_json_success(
			array(
				'logged_in' => is_user_logged_in() ? 'yes' : 'no',
			)
		);

	}

	/**
	 * Cancels the running restore.
	 */
	public function json_cancel_restore() {
		$this->do_request_sanity_check( 'snapshot_cancel_backup_restore', self::TYPE_POST );

		$data              = array();
		$data['backup_id'] = isset( $_POST['backup_id'] ) ? $_POST['backup_id'] : null; // phpcs:ignore

		$task = new Task\Restore();

		$validated_data = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_data ) ) {
			wp_send_json_error( $validated_data );
		}

		Model\Restore::clean_residuals();

		wp_send_json_success();
	}

	/**
	 * Responsible for calling the appropriate restore action, depending on what stage we're on, according to the locks in snapshot folder.
	 */
	public function json_process_restore() {
		$this->do_request_sanity_check( 'snapshot_trigger_backup_restore', self::TYPE_POST );

		$data = array();

		$data['backup_id'] = isset( $_POST['data']['backup_id'] ) ? $_POST['data']['backup_id'] : null; // phpcs:ignore
		$data['export_id'] = isset( $_POST['data']['export_id'] ) ? $_POST['data']['export_id'] : null; // phpcs:ignore
		$data['initial'] = isset( $_POST['data']['initial'] ) ? boolval( $_POST['data']['initial'] ) : null; // phpcs:ignore
		$data['download_link'] = isset( $_POST['data']['download_link'] ) ? $_POST['data']['download_link'] : null; // phpcs:ignore
		$expand                = isset( $_POST['expand'] ) ? $_POST['expand'] : null;  // phpcs:ignore

		$task = new Task\Restore();

		$validated_data = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_data ) ) {
			wp_send_json_error( $validated_data );
		}

		if ( $data['initial'] ) {
			// This is a brand new restore, we have to clear ot any residuals from older restores.
			Model\Restore::clean_residuals();

			Log::info( __( 'Restore has been initiated', 'snapshot' ), array(), $validated_data['backup_id'] );
		}

		// Now, we are going to identify the stage we're at, by reading the lock file.
		$lock = Lock::read( $validated_data['backup_id'] );

		if ( ! isset( $lock['stage'] ) ) {
			$this->trigger_export( $validated_data['backup_id'] );
		}

		$result = array();
		switch ( $lock['stage'] ) {
			case 'export':
			case 'exporting':
				$result = $this->get_export_status( $data['export_id'], $validated_data['backup_id'] );
				break;
			case 'download':
				$result = $this->download_backup( $data['download_link'], $validated_data['backup_id'] );
				break;
			case 'files':
				$result = $this->restore_files( $validated_data['backup_id'] );
				break;
			case 'tables':
				$result = $this->restore_tables( $validated_data['backup_id'] );
				break;
			case 'finalize':
				$result = $this->finalize_restore( $validated_data['backup_id'] );
				break;
			default:
				break;
		}

		if ( 'log' === $expand ) {
			$log_offset    = isset( $_POST['log_offset'] ) ? intval( $_POST['log_offset'] ) : 0;  // phpcs:ignore
			$log           = Log::parse_log_file( $validated_data['backup_id'], $log_offset );	// phpcs:ignore
			$result['log'] = $log;
		}

		wp_send_json_success( $result );
	}

	/**
	 * Trigger a backup restore by requesting a backup export.
	 *
	 * @param string $backup_id Backup id.
	 */
	public function trigger_export( $backup_id ) {
		$data['backup_id'] = $backup_id;

		$task  = new Task\Request\Export();
		$model = new Model\Request\Export();

		$validated_data = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_data ) ) {
			wp_send_json_error(
				array(
					'failed_stage' => $model->get_trigger_error_string(),
					'error'        => $validated_data,
				)
			);
		}

		$args                  = $validated_data;
		$args['request_model'] = $model;
		$args['send_email']    = false;
		$result                = $task->apply( $args );

		if ( $task->has_errors() ) {
			Log::error( __( 'A backup export couldn\'t be triggered.', 'snapshot' ), array(), $backup_id );

			wp_send_json_error(
				array(
					'failed_stage' => $model->get_trigger_error_string(),
				)
			);
		}

		// We just started exporting, please update the lock file.
		$lock_content = array(
			'stage' => 'export',
		);
		Lock::write( $lock_content, $backup_id );

		Log::info( __( 'A backup export has been triggered.', 'snapshot' ), array(), $backup_id );

		wp_send_json_success(
			array(
				'task'         => 'export',
				'api_response' => $result,
			)
		);

	}

	/**
	 * Get the status of an export.
	 *
	 * @param string $export_id Export id.
	 * @param string $backup_id Backup id.
	 */
	public function get_export_status( $export_id, $backup_id ) {
		// We now quering for export status, please update the lock file.
		$lock_content = array(
			'stage' => 'exporting',
		);
		Lock::write( $lock_content, $backup_id );

		Log::info( __( 'Checking backup export status.', 'snapshot' ), array(), $backup_id );

		$data['export_id'] = $export_id;

		$task  = new Task\Request\Export\Status();
		$model = new Model\Request\Export\Status();

		$validated_data = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_data ) ) {
			wp_send_json_error(
				array(
					'failed_stage' => $model->get_status_error_string(),
					'error'        => $validated_data,
				)
			);
		}

		$args                  = $validated_data;
		$args['request_model'] = $model;
		$result                = $task->apply( $args );

		if ( $task->has_errors() ) {
			Log::error( __( 'The backup export has failed.', 'snapshot' ), array(), $backup_id );

			wp_send_json_error(
				array(
					'failed_stage' => $model->get_status_error_string(),
				)
			);
		}

		if ( isset( $result['export_status'] ) && 'export_completed' === $result['export_status'] ) {
			// We're done exporting, please update the lock file.
			$lock_content = array(
				'stage' => 'download',
			);
			Lock::write( $lock_content, $backup_id );

			Log::info( __( 'The backup export has been completed.', 'snapshot' ), array(), $backup_id );
		}

		return array(
			'task'         => 'exporting',
			'api_response' => $result,
		);
	}

	/**
	 * Downloads a backup from a S3 link.
	 *
	 * @param string $download_link Download link.
	 * @param string $backup_id Backup id.
	 */
	public function download_backup( $download_link, $backup_id ) {
		$data['download_link'] = $download_link;
		$data['backup_id']     = $backup_id;

		$task  = new Task\Download();
		$model = new Model\Download();

		$validated_data = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_data ) ) {
			wp_send_json_error(
				array(
					'failed_stage' => $model->get_download_error_string(),
					'error'        => $validated_data,
				)
			);
		}

		$args          = $validated_data;
		$args['model'] = $model;

		$task->apply( $args );

		if ( $task->has_errors() ) {
			$errors = $task->get_errors();
			foreach ( $errors as $error ) {
				Log::error( $error->get_error_message(), array(), $backup_id );
			}
			Log::error( __( 'We couldn\'t download the exported backup.', 'snapshot' ), array(), $backup_id );

			wp_send_json_error(
				array(
					'failed_stage' => $model->get_download_error_string(),
				)
			);
		}

		Log::info( __( 'Downloading the exported backup.', 'snapshot' ), array(), $backup_id );

		if ( true === $model->get( 'download_completed' ) ) {
			Log::info( __( 'The exported backup has been downloaded.', 'snapshot' ), array(), $backup_id );
		}

		return array(
			'task' => 'download',
			'done' => $model->get( 'download_completed' ),
		);
	}

	/**
	 * Deals with restoring the files from the exported backup.
	 *
	 * @param string $backup_id Backup id.
	 */
	public function restore_files( $backup_id ) {
		$data['backup_id'] = $backup_id;

		$task  = new Task\Restore\Files();
		$model = new Model\Restore\Files( $data['backup_id'] );

		$model->set( 'skipped_files', array() );

		$validated_data = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_data ) ) {
			wp_send_json_error(
				array(
					'failed_stage' => $model->get_files_error_string(),
					'error'        => $validated_data,
				)
			);
		}

		Log::info( __( 'File restoration is under way.', 'snapshot' ), array(), $backup_id );

		$args          = $validated_data;
		$args['model'] = $model;

		$task->apply( $args );

		if ( $task->has_errors() ) {
			$errors          = $task->get_errors();
			$warning_counter = 0;
			foreach ( $errors as $error ) {
				// Lets see if the error was something that we can recover from (eg. unable to overwrite an unwrittable file).
				if ( false !== strpos( $error->get_error_code(), 'failed_file_move' ) ) {
					// We can recover from that.
					Log::warning( $error->get_error_message(), array(), $backup_id );
					$warning_counter++;

					continue;
				}
				Log::error( $error->get_error_message(), array(), $backup_id );
			}

			if ( count( $errors ) !== $warning_counter ) {
				// This means that aside from recoverable warnings, we faced irrecoverable ones too, lets fail the restore.
				Log::error( __( 'File restoration has failed.', 'snapshot' ), array(), $backup_id );

				wp_send_json_error(
					array(
						'failed_stage' => $model->get_files_error_string(),
					)
				);
			}
		}

		if ( true === $model->get( 'is_done' ) ) {
			// We are done restoring files, please update the lock file.
			$lock_content = array(
				'stage' => 'tables',
			);
			Lock::write( $lock_content, $backup_id );

			Log::info( __( 'File restoration has been completed.', 'snapshot' ), array(), $backup_id );
		}

		return array(
			'task'          => 'files',
			'done'          => $model->get( 'is_done' ),
			'skipped_files' => $model->get( 'skipped_files', array() ),
		);
	}

	/**
	 * Deals with restoring the tables from the exported backup.
	 *
	 * @param string $backup_id Backup id.
	 */
	public function restore_tables( $backup_id ) {
		$data['backup_id'] = $backup_id;

		$task  = new Task\Restore\Tables();
		$model = new Model\Restore\Tables( $data['backup_id'] );

		$model->set( 'skipped_tables', array() );

		$validated_data = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_data ) ) {
			wp_send_json_error(
				array(
					'failed_stage' => $model->get_tables_error_string(),
					'error'        => $validated_data,
				)
			);
		}

		Log::info( __( 'DB restoration is under way.', 'snapshot' ), array(), $backup_id );

		$args          = $validated_data;
		$args['model'] = $model;

		$task->apply( $args );

		if ( $task->has_errors() ) {
			$errors = $task->get_errors();
			foreach ( $errors as $error ) {
				Log::error( $error->get_error_message(), array(), $backup_id );
			}
			Log::error( __( 'DB restoration has failed.', 'snapshot' ), array(), $backup_id );

			wp_send_json_error(
				array(
					'failed_stage' => $model->get_tables_error_string(),
				)
			);
		}

		if ( true === $model->get( 'is_done' ) ) {
			// We are done restoring files, please update the lock file.
			$lock_content = array(
				'stage' => 'finalize',
			);
			Lock::write( $lock_content, $backup_id );

			$task->cleanup();

			Log::info( __( 'DB restoration has been completed.', 'snapshot' ), array(), $backup_id );
		}

		return array(
			'task'           => 'tables',
			'done'           => $model->get( 'is_done' ),
			'skipped_tables' => $model->get( 'skipped_tables', array() ),
		);
	}

	/**
	 * Cleans up residuals, etc.
	 *
	 * @param string $backup_id Backup id.
	 */
	public function finalize_restore( $backup_id ) {
		Model\Restore::clean_residuals();

		Log::info( __( 'Restore has been completed successfully.', 'snapshot' ), array(), $backup_id );

		return array(
			'task' => 'finalize',
			'done' => true,
			'home' => get_home_url(),
		);
	}
}