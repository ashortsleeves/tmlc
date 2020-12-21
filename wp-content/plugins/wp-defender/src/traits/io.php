<?php

namespace WP_Defender\Traits;

trait IO {
	/**
	 * A simple function to create & return the folder that we can use to write tmp files
	 *
	 * @return string
	 */
	protected function get_tmp_path() {
		$upload_dir = wp_upload_dir()['basedir'];
		$tmp_dir    = $upload_dir . DIRECTORY_SEPARATOR . 'wp-defender';
		if ( ! is_dir( $tmp_dir ) ) {
			wp_mkdir_p( $tmp_dir );
		}

		if ( ! is_file( $tmp_dir . DIRECTORY_SEPARATOR . 'index.php' ) ) {
			file_put_contents( $tmp_dir . DIRECTORY_SEPARATOR . 'index.php', '' );
		}

		return $tmp_dir;
	}

	/**
	 * @param $category
	 *
	 * @return string
	 */
	public function get_log_path( $category = '' ) {
		$file = empty( $category ) ? 'defender.log' : $category;

		return $this->get_tmp_path() . DIRECTORY_SEPARATOR . $file;
	}

	/**
	 * Create a lock, this will be use in scanning
	 *
	 * @return string
	 */
	protected function get_lock_path() {
		return $this->get_tmp_path() . DIRECTORY_SEPARATOR . 'scan.lock';
	}

	/**
	 * Delete a folder with every content inside
	 *
	 * @param $dir
	 */
	public function delete_dir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$it    = new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS );
		$files = new \RecursiveIteratorIterator( $it,
			\RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $files as $file ) {
			if ( $file->isDir() ) {
				$ret = rmdir( $file->getPathname() );
			} else {
				$ret = unlink( $file->getPathname() );
			}
			if ( false === $ret ) {
				return false;
			}
		}
		rmdir( $dir );

		return true;
	}
}