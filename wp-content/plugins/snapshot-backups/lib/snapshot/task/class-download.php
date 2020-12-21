<?php // phpcs:ignore
/**
 * Download from S3 task.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task;

use WPMUDEV\Snapshot4\Task;

/**
 * Download task class
 */
class Download extends Task {

	/**
	 * Required request parameters, with their sanitization method
	 *
	 * @var array
	 */
	protected $required_params = array(
		'backup_id'     => 'sanitize_key',
		'download_link' => self::class . '::validate_url',
	);

	/**
	 * Validates download links.
	 *
	 * @param string $download_link Download link for exported backup.
	 *
	 * @return string
	 */
	public static function validate_url( $download_link ) {
		return wp_strip_all_tags(
			stripslashes(
				filter_var( $download_link, FILTER_VALIDATE_URL )
			)
		);

	}

	/**
	 * Does the initial actions needed to trigger a restore.
	 *
	 * @param array $args Restore arguments, like backup_id and rootpath.
	 */
	public function apply( $args = array() ) {
		$download_link = $args['download_link'];
		$backup_id     = $args['backup_id'];
		$model         = $args['model'];

		$model->set( 'backup_id', $backup_id );
		$model->set( 'download_completed', false );

		$result = $model->download_backup_chunk( $download_link );

		if ( $model->add_errors( $this ) ) {
			return;
		}
	}
}