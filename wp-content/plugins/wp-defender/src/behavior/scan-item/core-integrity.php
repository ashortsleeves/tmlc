<?php

namespace WP_Defender\Behavior\Scan_Item;

use Calotes\Base\File;
use Calotes\Component\Behavior;
use WP_Defender\Component\Error_Code;
use WP_Defender\Model\Scan;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Traits\Formats;
use WP_Defender\Traits\IO;

class Core_Integrity extends Behavior {
	use Formats, IO;

	/**
	 * Return general data so we can output on frontend
	 * @return array
	 */
	public function to_array() {
		$data            = $this->owner->raw_data;
		$file            = $data['file'];
		$file_created_at = @filemtime( $file );
		if ( $file_created_at ) {
			$file_created_at = $this->format_date_time( $file_created_at );
		} else {
			$file_created_at = 'n/a';
		}
		$file_size = @filesize( $file );
		if ( ! $file_size ) {
			$file_size = 'n/a';
		} else {
			$file_size = $this->format_bytes_into_readable( $file_size );
		}

		return [
			'id'         => $this->owner->id,
			'type'       => Scan_Item::TYPE_INTEGRITY,
			'file_name'  => pathinfo( $file, PATHINFO_BASENAME ),
			'full_path'  => $file,
			'date_added' => $file_created_at,
			'size'       => $file_size,
			'scenario'   => $data['type'],
			'short_desc' => $this->get_short_description(),
		];
	}

	/**
	 * We will get the origin code by looking into svn repo
	 *
	 * @return false|string|\WP_Error
	 */
	private function get_origin_code() {
		global $wp_version;
		$data            = $this->owner->raw_data;
		$file            = $data['file'];
		$relative_path   = str_replace( ABSPATH, '', $file );
		$source_file_url = "http://core.svn.wordpress.org/tags/$wp_version/" . $relative_path;
		$ds              = DIRECTORY_SEPARATOR;
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin' . $ds . 'includes' . $ds . 'file.php';
		}
		$tmp = download_url( $source_file_url );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}
		$content = file_get_contents( $tmp );
		@unlink( $tmp );

		return $content;
	}

	/**
	 * Restore the file with it's origin content
	 * @return array
	 */
	public function resolve() {
		$data = $this->owner->raw_data;
		if ( 'modified' !== $data['type'] ) {
			//should not be here if it doesnt modified case
			return;
		}

		$origin = $this->get_origin_code();
		if ( is_wp_error( $origin ) || false === $origin ) {
			return;
		}

		//now it time
		$path = $data['file'];

		$ret = file_put_contents( $path, $origin );
		if ( $ret ) {
			$scan = Scan::get_last();
			$scan->remove_issue( $this->owner->id );

			return [
				'message' => __( 'This item has been resolved.', 'wpdef' )
			];
		}
	}

	/**
	 * @return array
	 */
	public function ignore() {
		$scan = Scan::get_last();
		$scan->ignore_issue( $this->owner->id );

		return [
			'message' => __( 'The suspicious file has been successfully ignored.', 'wpdef' )
		];
	}

	/**
	 * @return array
	 */
	public function unignore() {
		$scan = Scan::get_last();
		$scan->unignore_issue( $this->owner->id );

		return [
			'message' => __( 'The suspicious file has been successfully restored.', 'wpdef' )
		];
	}

	/**
	 * Delete the file, or whole folder
	 */
	public function delete() {
		$data = $this->owner->raw_data;
		$scan = Scan::get_last();
		if ( 'unversion' === $data['type'] && unlink( $data['file'] ) ) {
			$scan->remove_issue( $this->owner->id );

			return [
				'message' => __( 'This item has been permanently removed', 'wpdef' )
			];
		} elseif ( 'dir' === $data['type'] && $this->delete_dir( $data['file'] ) ) {
			$scan->remove_issue( $this->owner->id );

			return [
				'message' => __( 'This item has been permanently removed', 'wpdef' )
			];
		}

		return new \WP_Error( Error_Code::NOT_WRITEABLE, __( 'Defender doesn\'t have enough permission to remove this file', 'wpdef' ) );
	}

	/**
	 *  Return the source code depend the type of the issue
	 *  If it is unversion, return full source
	 *  If it is dir, we return a list of files
	 *  If it is modified, we will return the current code & origin
	 *
	 * @return array
	 */
	public function pull_src() {
		$data = $this->owner->raw_data;
		if ( ! file_exists( $data['file'] ) && ! is_dir( $data['file'] ) ) {
			return [
				'code'   => '',
				'origin' => ''
			];
		}
		switch ( $data['type'] ) {
			case 'unversion':
				return [
					'code' => file_get_contents( $data['file'] )
				];
			case 'dir':
				$dir_tree = new File( $data['file'], true, true, [], [], false );

				return [
					'code' => implode( PHP_EOL, $dir_tree->get_dir_tree() )
				];
			case 'modified':
				return [
					'code'   => file_get_contents( $data['file'] ),
					'origin' => $this->get_origin_code()
				];
		}
	}

	/**
	 * @return string
	 */
	private function get_short_description() {
		$data = $this->owner->raw_data;
		if ( 'unversion' === $data['type'] ) {
			return esc_html__( 'Unknown file in WordPress core', 'wpdef' );
		} elseif ( 'dir' === $data['type'] ) {
			return esc_html__( 'This directory does not belong to WordPress core', 'wpdef' );
		} elseif ( 'modified' === $data['type'] ) {
			return esc_html__( 'This WordPress core file appears modified', 'wpdef' );
		}
	}
}