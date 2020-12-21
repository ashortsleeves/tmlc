<?php

namespace WP_Defender\Component\Security_Tweaks;

use Calotes\Base\Component;

/**
 * Class Disable_File_Editor
 * @package WP_Defender\Component\Security_Tweaks
 */
class Disable_File_Editor extends Component {
	public $slug = 'disable-file-editor';

	/**
	 * @return bool
	 */
	public function check() {
		if ( defined( 'DISALLOW_FILE_EDIT' ) && constant( 'DISALLOW_FILE_EDIT' ) === true ) {
			return true;
		}

		return false;
	}

	/**
	 * Here is the code for processing, if the return is true, we add it to resolve list, WP_Error if any error
	 *
	 * @return bool|\WP_Error
	 */
	public function process() {
		return true;
	}

	/**
	 * This is for un-do stuff that has be done in @process
	 *
	 * @return bool|\WP_Error
	 */
	public function revert() {
		return true;
	}

	/**
	 * Define the DISALLOW_FILE_EDIT constant so we can hide the editor page
	 */
	public function shield_up() {
		if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			define( 'DISALLOW_FILE_EDIT', true );
		}
	}

	/**
	 * Return a summary data of this tweak
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'slug'             => $this->slug,
			'title'            => __( 'Disable the file editor', 'wpdef' ),
			'errorReason'      => __( 'The file editor is currently enabled.', 'wpdef' ),
			'successReason'    => __( 'You\'ve disabled the file editor, winning.', 'wpdef' ),
			'misc'             => [],
			'bulk_description' => __( 'The file editor is currently active, this means anyone with access to your login information can further edit your plugin and theme files and inject malicious code. We will disable file editor for you.', 'wpdef' ),
			'bulk_title'       => __( 'File Editor', 'wpdef' )
		];
	}
}