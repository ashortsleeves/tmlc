<?php // phpcs:ignore
/**
 * Snapshot controllers: Export backup service actions
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Controller\Service\Export;

use WPMUDEV\Snapshot4\Controller;
use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Helper\Log;

/**
 * Export backup service actions handling controller class
 */
class Email extends Controller\Service {

	/**
	 * Gets the list of known service actions
	 *
	 * @return array Known actions
	 */
	public function get_known_actions() {
		$known = array(
			self::EXPORT_BACKUP_EMAIL,
			self::EXPORT_LOGGING,
		);
		return $known;
	}

	/**
	 * Retrieves the export link from the service and sends an email including that link.
	 *
	 * @param object $params Parameters of the current request.
	 * @param string $action Current action.
	 * @param object $request Current request.
	 */
	public function json_export_backup_email( $params, $action, $request = false ) {
		Log::info( __( 'The API has completed a backup export.', 'snapshot' ) );

		$task = new Task\Export\Email();

		$data = (array) $params;

		$validated_params = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_params ) ) {
			return $this->send_response_error( $validated_params, $request );
		}

		$model  = new Model\Export\Email();
		$export = array();

		$export['id']            = $validated_params['snapshot_id'];
		$export['export_link']   = $validated_params['export_link'];
		$export['email_account'] = $validated_params['email_account'];
		$export['display_name']  = $validated_params['display_name'];

		$model->set( 'export', $export );
		$args          = array();
		$args['model'] = $model;

		$result = $task->apply( $args );

		if ( is_wp_error( $result ) ) {
			return $this->send_response_error( $result, $request );
		}

		Log::info( __( 'An email with the export link has been sent.', 'snapshot' ) );

		return $this->send_response_success( true, $request );
	}

	/**
	 * Receives the failure log in case the export failed.
	 *
	 * @param object $params Parameters of the current request.
	 * @param string $action Current action.
	 * @param object $request Current request.
	 */
	public function json_export_logging( $params, $action, $request = false ) {
		$data = (array) $params;

		$backup_id    = isset( $data['snapshot_id'] ) ? sanitize_key( $data['snapshot_id'] ) : null;
		$failure_code = isset( $data['export_status'] ) ? sanitize_text_field( $data['export_status'] ) : null;

		/* translators: %s - export failure code */
		Log::error( sprintf( __( 'The backup export has failed: %s.', 'snapshot' ), $failure_code ), array(), $backup_id );

		return $this->send_response_success( true, $request );
	}
}