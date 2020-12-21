<?php // phpcs:ignore
/**
 * Snapshot models: Export backup requests model
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Model\Request;

use WPMUDEV\Snapshot4\Model;

/**
 * Export backup requests model class
 */
class Export extends Model\Request {
	const DEFAULT_ERROR = 'snapshot_export_backup_service_unreachable';

	/**
	 * Export backup request endpoint
	 *
	 * @var string
	 */
	protected $endpoint = 'exports';

	/**
	 * Returns action string for logger
	 *
	 * @return string
	 */
	protected function get_action_string() {
		return __( 'export backup', 'snapshot' );
	}

	/**
	 * Returns string to be used on errors during restore.
	 *
	 * @return string
	 */
	public function get_trigger_error_string() {
		return esc_html__( 'requesting for a backup export', 'snapshot' );
	}

	/**
	 * Start backup export
	 *
	 * @param string $backup_id Backup id.
	 * @param bool   $send_email Whether to send email (for downloads) or not (for restores).
	 *
	 * @return array|mixed|object
	 */
	public function export_backup( $backup_id, $send_email ) {
		$method = 'post';
		$path   = $this->get_api_url();

		$data = array(
			'snapshot_id' => $backup_id,
			'send_email'  => $send_email,
		);

		$response = $this->request( $path, $data, $method );

		return $response;
	}
}