<?php // phpcs:ignore
/**
 * Export backup task.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Request;

use WPMUDEV\Snapshot4\Task;

/**
 * Export backup task class.
 */
class Export extends Task {
	const ERR_SERVICE_UNREACHABLE = 'snapshot_export_backup_service_unreachable';

	/**
	 * Required request parameters, with their sanitization method
	 *
	 * @var array
	 */
	protected $required_params = array(
		'backup_id' => 'sanitize_key',
	);

	/**
	 * Export backup.
	 *
	 * @param array $args Arguments coming from the ajax call.
	 */
	public function apply( $args = array() ) {
		$request_model = $args['request_model'];
		$send_email    = ( isset( $args['send_email'] ) && false === $args['send_email'] ) ? false : true;

		$response = $request_model->export_backup( $args['backup_id'], $send_email );
		if ( $request_model->add_errors( $this ) ) {
			return false;
		}

		$result = json_decode( wp_remote_retrieve_body( $response ), true );

		return $result;
	}
}