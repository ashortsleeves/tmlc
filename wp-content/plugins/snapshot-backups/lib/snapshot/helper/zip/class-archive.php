<?php // phpcs:ignore
/**
 * Snapshot requesting model abstraction class.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Helper\Zip;

/**
 * Archive helper class
 */
class Archive extends Abstraction {

	/**
	 * Initializes.
	 */
	public function initialize() {
		$this->_zip = new \ZipArchive();
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

		$handle = $this->_zip->open( $this->_path );
		if ( ! $handle ) {
			return false;
		}

		$status = $this->_zip->locateName( $path );
		$this->_zip->close();

		return false === $status ? false : true;
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

		$handle = $this->_zip->open( $this->_path );
		if ( ! $handle ) {
			return false;
		}

		$status = $this->_zip->extractTo( $destination );

		$this->_zip->close();

		return $status;
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

		$handle = $this->_zip->open( $this->_path );
		if ( ! $handle ) {
			return false;
		}

		$status = $this->_zip->extractTo( $destination, $files );
		$this->_zip->close();

		return $status;
	}
}