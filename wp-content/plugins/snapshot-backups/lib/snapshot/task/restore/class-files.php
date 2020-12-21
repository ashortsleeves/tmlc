<?php // phpcs:ignore
/**
 * Restore files task.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Restore;

use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Model\Restore;
use WPMUDEV\Snapshot4\Model\Env;
use WPMUDEV\Snapshot4\Helper\Fs;

/**
 * Restore files task class
 */
class Files extends Task {

	/**
	 * Required request parameters, with their sanitization method
	 *
	 * @var array
	 */
	protected $required_params = array(
		'backup_id' => null, // backup_id has already been sanitised in json_process_restore().
	);

	/**
	 * Restores files.
	 *
	 * @param array $args Restore arguments, like backup_id and rootpath.
	 */
	public function apply( $args = array() ) {
		$exported_root = Restore::get_intermediate_destination( $args['backup_id'] );
		$model         = $args['model'];
		$source        = $model->get_root();
		$destination   = Fs::get_root_path();

		if ( ! file_exists( $exported_root ) ) {
			$model->extract_backup( $exported_root );

			if ( $model->add_errors( $this ) ) {
				return;
			}

			return;
		}

		$file_items = $model->get_files();
		if ( ! is_array( $file_items ) ) {
			$file_items = array();
		}

		// Store where we left off, for the next file iteration.
		update_site_option( $model::KEY_PATHS, $model->get( 'paths_left' ) );

		$skip_wp_config = Env::is_wpmu_staging();

		foreach ( $file_items as $item ) {
			$filepath = preg_replace( '/^' . preg_quote( $source, '/' ) . '/i', '', $item );
			if ( $skip_wp_config && '/wp-config.php' === $filepath ) {
				$model->add( 'skipped_files', $filepath );
				continue;
			}
			$path     = trim( wp_normalize_path( dirname( $filepath ) ), '/' );
			$fullpath = trailingslashit( wp_normalize_path( "{$destination}{$path}" ) );

			if ( ! is_dir( $fullpath ) ) {
				wp_mkdir_p( $fullpath );
			}

			$dest_file = $fullpath . basename( $item );
			if ( ! rename( $item, $dest_file ) ) {
				$error_code = 'failed_file_move';
				/* translators: %1s - temp file path, %2s - restored file path */
				$error_message = sprintf( __( 'Couldn\'t move the temp %1$1s file to its restored path: %2$2s.', 'snapshot' ), $item, $dest_file );
				$this->add_error( $error_code, $error_message );

				$model->add( 'skipped_files', $dest_file );
			}
		}
	}
}