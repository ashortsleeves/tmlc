<?php // phpcs:ignore
/**
 * Filesystem helper class
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Helper;

/**
 * Helper class
 */
class Fs {

	/**
	 * Resolves relative template path to an actual absolute path
	 *
	 * @return string
	 */
	public static function get_root_path() {
		$home_path = get_home_path();

		// Flywheel fix.
		if ( defined( 'FLYWHEEL_CONFIG_DIR' ) ) {
			$home_path = dirname( WP_CONTENT_DIR );
		}

		return trailingslashit( wp_normalize_path( apply_filters( 'wp_snapshot_home_path', $home_path ) ) );
	}

	/**
	 * Creates an empty index file for security purposes
	 *
	 * @return string
	 */
	public static function add_index_file( $index_pathname ) {
		if ( ! file_exists( $index_pathname ) ) {
			$file = fopen( $index_pathname, 'w' );
			if ( false === $file ) {
				return;
			}
			fwrite( $file, "<?php\n// Silence is golden." );
			fclose( $file );
		}
	}
}