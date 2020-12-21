<?php // phpcs:ignore
/**
 * Snapshot models: Download model
 *
 * Holds information for downloading the exported backup.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Model;

use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Controller\Ajax\Restore;
use WPMUDEV\Snapshot4\Helper\Lock;

/**
 * Export email model class
 */
class Download extends Model {

	/**
	 * Download chunk
	 *
	 * @var int
	 */
	private $chunk = 0;

	/**
	 * Reading step
	 *
	 * @var int
	 */
	private $step = 0;

	/**
	 * The basepath where the file will be downloaded at.
	 *
	 * @var string
	 */
	private $local_base = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->chunk      = 50 * 1024 * 1024;
		$this->step       = 5 * 1024 * 1024;
		$this->local_base = Lock::get_lock_dir();
	}

	/**
	 * Returns string to be used when an export has failed to be downloaded on restore.
	 *
	 * @return string
	 */
	public function get_download_error_string() {
		return esc_html__( 'the exported backup was being downloaded', 'snapshot' );
	}

	/**
	 * Downloads from S3 url, using chunks.
	 *
	 * @param string $download_link Download link.
	 */
	public function download_backup_chunk( $download_link ) {
		$pointer = intval( get_site_option( Restore::SNAPSHOT_DOWNLOAD_BACKUP_PROGRESS, '0' ) );
		$start   = $pointer;

		$local_dirpath = path_join( $this->local_base, $this->get( 'backup_id' ) );

		if ( ! file_exists( $local_dirpath ) ) {
			mkdir( $local_dirpath, 0755 );
		}
		$local_filepath = path_join( $local_dirpath, $this->get( 'backup_id' ) . '.zip' );

		$download_done = false;

		// phpcs:ignore
		$localfile_handle = fopen( $local_filepath, 'ab' );

		if ( false === $localfile_handle ) {
			$this->errors[] = array(
				'failed_fopen',
				/* translators: %s - Local File */
				sprintf( __( 'We couldn\'t download the remote backup zip in %s to restore.', 'snapshot' ), $local_filepath ),
			);
			return;
		}

		$last_pass = false;
		while ( $pointer < $start + $this->chunk ) {
			$step_end = $pointer + $this->step;

			$headers = array(
				'Range' => 'bytes=' . $pointer . '-' . $step_end,
			);
			$args    = array(
				'timeout'   => 60,
				'sslverify' => false,
				'headers'   => $headers,
			);

			$response = wp_remote_get( $download_link, $args );

			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code < 200 || $response_code >= 300 ) {
				$this->errors[] = array(
					'failed_download_link',
					__( 'We couldn\'t download the remote backup zip from the given download link.', 'snapshot' ),
				);
				return;
			}

			$contents = wp_remote_retrieve_body( $response );

			if ( strlen( $contents ) < $this->step ) {
				$last_pass = true;
			}

			// phpcs:ignore
			fwrite( $localfile_handle, $contents );

			if ( $last_pass ) {
				$this->set( 'download_completed', true );

				$download_done = true;

				delete_site_option( Restore::SNAPSHOT_DOWNLOAD_BACKUP_PROGRESS );

				$lock_content = array(
					'stage' => 'files',
				);
				Lock::write( $lock_content, $this->get( 'backup_id' ) );
				break;
			}

			$pointer += ( $this->step + 1 );
		}

		// phpcs:ignore
		fclose( $localfile_handle );

		if ( ! $download_done ) {
			update_site_option( Restore::SNAPSHOT_DOWNLOAD_BACKUP_PROGRESS, $pointer );
		}

		return true;

	}
}