<?php // phpcs:ignore
/**
 * Snapshot requesting model abstraction class.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Helper\Zip;

use WPMUDEV\Snapshot4\Helper\Lock;
use Pclzip;

/**
 * Pclzip helper class
 */
class Pcl extends Abstraction {

	/**
	 * Initializes.
	 */
	public function initialize() {
		if ( ! defined( 'PCLZIP_TEMPORARY_DIR' ) ) {
			define( 'PCLZIP_TEMPORARY_DIR', Lock::get_lock_dir() );
		}
		if ( ! class_exists( 'PclZip' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
		}
		$this->_zip = new PclZip( $this->_path );
	}

	/**
	 * Check for zip file
	 *
	 * @param string $path Path to check.
	 *
	 * @return bool
	 */
	public function has( $path ) {
		$path = $this->_to_root_relative( $path );
		if ( empty( $path ) ) {
			return false;
		}

		$contents = $this->_zip->listContent();
		if ( empty( $contents ) ) {
			return false;
		}

		foreach ( $contents as $entry ) {
			if ( empty( $entry['filename'] ) ) {
				continue;
			}
			if ( $path === $entry['filename'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extracts from zip file
	 *
	 * @param string $destination Path to extract.
	 *
	 * @return bool
	 */
	public function extract( $destination ) {
		if ( empty( $destination ) ) {
			return false;
		}

		$destination = wp_normalize_path( $destination );
		if ( empty( $destination ) || ! file_exists( $destination ) ) {
			return false;
		}

		$zip_contents = $this->_zip->listContent();
		if ( empty( $zip_contents ) ) {
			return false;
		}

		$extract_files = $this->_zip->extract( PCLZIP_OPT_PATH, $destination );

		return ! empty( $extract_files );
	}

	/**
	 * Extracts specific files from zip file
	 *
	 * @param string $destination Path to extract.
	 * @param array  $files Files to extract.
	 *
	 * @return bool
	 */
	public function extract_specific( $destination, $files ) {
		if ( empty( $destination ) ) {
			return false;
		}

		if ( empty( $files ) ) {
			return false;
		}
		if ( ! is_array( $files ) ) {
			return false;
		}

		$destination = wp_normalize_path( $destination );
		if ( empty( $destination ) || ! file_exists( $destination ) ) {
			return false;
		}

		$zip_contents = $this->_zip->listContent();
		if ( empty( $zip_contents ) ) {
			return false;
		}

		$extract_files = $this->_zip->extract( PCLZIP_OPT_PATH, $destination, PCLZIP_OPT_BY_NAME, $files );

		return ! empty( $extract_files );
	}
}