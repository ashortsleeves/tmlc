<?php // phpcs:ignore
/**
 * Snapshot controllers: Schedule endpoints for Hub
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Controller;

use WPMUDEV\Snapshot4\Controller;
use WPMUDEV\Snapshot4\Main;
use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Helper;
use WPMUDEV\Snapshot4\Helper\Log;
use WPMUDEV\Snapshot4\Helper\Settings;

/**
 * Schedule endpoints for Hub handling controller class
 */
class Hub extends Controller\Service {

	/**
	 * Gets the list of known service actions
	 *
	 * @return array Known actions
	 */
	public function get_known_actions() {
		$known = array(
			self::HUB_END_START_BACKUP,
			self::HUB_END_DELETE_BACKUPS,
			self::HUB_END_DELETE_CACHE,
			self::HUB_END_DELETE_SETTINGS_CACHE,
		);
		return $known;
	}

	/**
	 * Triggers a new manual backup.
	 *
	 * @param object $params Parameters of the current request.
	 * @param string $action Current action.
	 * @param object $request Current request.
	 */
	public function json_hub_end_start_backup( $params, $action, $request = false ) {
		Log::info( __( 'The Hub requested to trigger a new backup.', 'snapshot' ) );
		$data = (array) $params;

		$task = new Task\Request\Manual();

		$validated_params = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_params ) ) {
			return $this->send_response_error( $validated_params, $request );
		}

		$model = new Model\Request\Manual();

		$args          = $validated_params;
		$args['model'] = $model;
		$result        = $task->apply( $args );

		if ( $task->has_errors() ) {
			$errors = array();
			foreach ( $task->get_errors() as $error ) {
				$errors[] = $error;
				Log::error( $error->get_error_message() );
				return $this->send_response_error( $error, $request );
			}
		}

		$response = (object) array(
			'backup_running' => $result,
		);

		Log::info( __( 'Communication with the service API, in order to create manual backup, was successful.', 'snapshot' ) );

		return $this->send_response_success( $response, $request );
	}

	/**
	 * Deletes all backups for the site.
	 *
	 * @param object $params Parameters of the current request.
	 * @param string $action Current action.
	 * @param object $request Current request.
	 */
	public function json_hub_end_delete_backups( $params, $action, $request = false ) {
		$task = new Task\Request\Delete();

		$args                  = array();
		$args['request_model'] = new Model\Request\Delete();
		$task->apply( $args );

		if ( $task->has_errors() ) {
			foreach ( $task->get_errors() as $error ) {
				return $this->send_response_error( $error, $request );
			}
		}

		$response = (object) array(
			'backups_deleted' => true,
		);

		return $this->send_response_success( $response, $request );
	}

	/**
	 * Deletes transient of the backup listing.
	 *
	 * @param object $params Parameters of the current request.
	 * @param string $action Current action.
	 * @param object $request Current request.
	 */
	public function json_hub_end_delete_cache( $params, $action, $request = false ) {
		delete_transient( 'snapshot_listed_backups' );
		delete_transient( 'snapshot_current_stats' );

		$response = (object) array(
			'cache_deleted' => true,
		);

		return $this->send_response_success( $response, $request );
	}

	/**
	 * Deletes transient of the "extra security step" option.
	 *
	 * @param object $params Parameters of the current request.
	 * @param string $action Current action.
	 * @param object $request Current request.
	 */
	public function json_hub_end_delete_settings_cache( $params, $action, $request = false ) {
		delete_transient( 'snapshot_extra_security_step' );

		$response = (object) array(
			'cache_deleted' => true,
		);

		return $this->send_response_success( $response, $request );
	}
}