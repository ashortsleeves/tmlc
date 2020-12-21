<?php

namespace WP_Defender\Component\Security_Tweaks;

use WP_Error;
use SplFileObject;
use Calotes\Base\Component;

/**
 * Class Hide_Error
 * @package WP_Defender\Component\Security_Tweaks
 */
class Hide_Error extends Component {
	public $slug = 'hide-error';
	public $what_to_change = '';

	/**
	 * @return bool
	 */
	public function check() {
		$data = $this->what_to_change();

		return $data['resolved'];
	}

	/**
	 * Here is the code for processing, if the return is true, we add it to resolve list, WP_Error if any error
	 *
	 * @return bool|\WP_Error
	 */
	public function process() {
		$data                 = $this->what_to_change();
		$this->what_to_change = $data['required_change'];

		if ( 'wp_debug' === $this->what_to_change ) {
			return $this->disable_debug();
		}

		if ( 'wp_debug_display' === $this->what_to_change ) {
			return $this->disable_debug_display();
		}

		return false;
	}

	/**
	 * This is for un-do stuff that has be done in @process
	 *
	 * @return bool|\WP_Error
	 */
	public function revert() {
		$data                 = $this->what_to_change();
		$this->what_to_change = $data['required_change'];

		if ( 'wp_debug' === $this->what_to_change ) {
			return $this->enable_debug();
		}

		if ( 'wp_debug_display' === $this->what_to_change ) {
			return $this->enable_debug_display();
		}

		return false;
	}

	/**
	 * Shield up
	 *
	 * @return bool
	 */
	public function shield_up() {
		return true;
	}

	/**
	 * Get whether to change WP_DEBUG or WP_DEBUG_DISPLAY constant
	 *
	 * @return array
	 */
	private function what_to_change() {
		$debug          = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$debug_log      = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG;
		$debug_display  = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY;
		$what_to_change = '';
		$resolved       = false;

		if ( $debug && $debug_log ) {
			$what_to_change = 'wp_debug_display';
		}

		if ( ! $debug || ( $debug && $debug_display && ! $debug_log ) ) {
			$what_to_change = 'wp_debug';
		}

		if ( ! $debug || ! $debug_display ) {
			$resolved = true;
		}

		return [
			'resolved'        => $resolved,
			'required_change' => $what_to_change,
		];
	}

	/**
	 * Enable debugging
	 *
	 * @return bool
	 */
	private function enable_debug() {
		return $this->set_debug_data( 'wp_debug', true );
	}

	/**
	 * Disable debugging
	 *
	 * @return bool
	 */
	private function disable_debug() {
		return $this->set_debug_data( 'wp_debug', false );
	}

	/**
	 * Enable debug display
	 *
	 * @return bool
	 */
	private function enable_debug_display() {
		return $this->set_debug_data( 'wp_debug_display', true );
	}

	/**
	 * Disable debug display
	 *
	 * @return bool
	 */
	private function disable_debug_display() {
		return $this->set_debug_data( 'wp_debug_display', false );
	}

	/**
	 * Set debug data in wp-congig.php
	 *
	 * @param string $debug_type
	 * @param bool $value
	 *
	 * @return bool|WP_Error
	 */
	private function set_debug_data( $debug_type, $value ) {
		$obj_file = $this->file();
		if ( false === $obj_file ) {
			return new WP_Error(
				'defender_file_not_writable',
				__( 'The file wp-config.php is not writable', 'wpdef' )
			);
		}

		$value             = $value ? 'true' : 'false';
		$pattern           = $this->get_pattern( $debug_type );
		$debug_type        = strtoupper( $debug_type );
		$hook_line_pattern = $this->get_hook_line_pattern();
		$debug_line        = "define( '{$debug_type}', {$value} ); // Added by Defender";
		$lines             = [];
		$line_found        = false;
		$hook_line_no      = null;

		foreach ( $obj_file as $line ) {
			if ( ! $line_found && preg_match( $pattern, $line ) ) {
				// If this is revert request and the changes is not made by us throw error
				if ( 'true' === $value && ! preg_match( "/^define\(\s*['|\"]{$debug_type}['|\"],(.*)\);\s*\/\/\s*Added\s*by\s*Defender.?.*/i", $line ) ) {
					return new WP_Error(
						'defender_line_not_found',
						__( 'Sorry, we only support vanilla setup.', 'wpdef' )
					);
				}

				$lines[]    = $debug_line;
				$line_found = true;
				continue;
			}

			// If there is no match, keep reference of `hook_line_no` so that we can insert data there as needed.
			if ( ! $line_found && preg_match( $hook_line_pattern, $line ) ) {
				$hook_line_no               = $obj_file->key();
				$lines[ $hook_line_no + 1 ] = trim( $line );
				continue;
			}

			$lines[] = trim( $line );
		}

		// There is no match, so set WP_DEBUG data just before the hook line ei: `$table_prefix`.
		if ( ! $line_found && ! is_null( $hook_line_no ) ) {
			$line_found             = true;
			$lines[ $hook_line_no ] = $debug_line;
			ksort( $lines );
		}

		return $line_found
			? $this->write( $lines )
			: new WP_Error(
				'defender_line_not_found',
				__( 'Sorry, we only support vanilla setup.', 'wpdef' ),
				404
			);
	}

	/**
	 * Get WP_DEBUG or WP_DEBUG_DISPLAY pattern
	 *
	 * @param string type
	 *
	 * @return string
	 */
	private function get_pattern( $type ) {
		return 'wp_debug' === $type ? $this->get_wp_debug_pattern() : $this->get_wp_debug_display_pattern();
	}

	/**
	 * Get pattern for WP_DEBUG
	 *
	 * @return string
	 */
	private function get_wp_debug_pattern() {
		return "/^define\(\s*['|\"]WP_DEBUG['|\"],(.*)\)/";
	}

	/**
	 * Get pattern for WP_DEBUG_DISPLAY
	 *
	 * @return string
	 */
	private function get_wp_debug_display_pattern() {
		return "/^define\(\s*['|\"]WP_DEBUG_DISPLAY['|\"], (.*)\)/";
	}

	/**
	 * Get hook line pattern
	 *
	 * @return string
	 */
	private function get_hook_line_pattern() {
		global $wpdb;

		return '/^\$table_prefix\s*=\s*[\'|\"]' . $wpdb->prefix . '[\'|\"]/';
	}

	/**
	 * Get file instance
	 *
	 * @return false|SplFileObject
	 */
	private function file() {
		static $file = false;

		if ( ! $file ) {
			try {
				$file = new SplFileObject( defender_wp_config_path(), 'r+' );
			} catch ( Exception $e ) {
				return false;
			}
		}

		return $file;
	}

	/**
	 * Write to the file
	 *
	 * @param array $lines
	 *
	 * @return bool
	 */
	private function write( $lines ) {
		$file = $this->file();
		$file->flock( LOCK_EX );
		$file->fseek( 0 );

		$bytes = $file->fwrite( implode( "\n", $lines ) );

		if ( $bytes ) {
			$file->ftruncate( $file->ftell() );
		}

		$file->flock( LOCK_UN );

		return (bool) $bytes;
	}


	/**
	 * Return a summary data of this tweak
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'slug'             => $this->slug,
			'title'            => __( 'Hide error reporting', 'wpdef' ),
			'errorReason'      => __( 'Error debugging is currently allowed.', 'wpdef' ),
			'successReason'    => __( 'You\'ve disabled all error reporting, Houston will never report a problem.', 'wpdef' ),
			'misc'             => [],
			'bulk_description' => __( 'Error debugging feature is useful for active development, but on live sites provides hackers yet another way to find loopholes in your site\'s security. We will disable error reporting for you.', 'wpdef' ),
			'bulk_title'       => __( 'Error Reporting', 'wpdef' )
		];
	}
}