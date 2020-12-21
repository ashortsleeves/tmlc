<?php

namespace WP_Defender\Controller;

/**
 * Since advanced tools will have many sub modules, this just using for render
 *
 * Class Advanced_Tools
 * @package WP_Defender\Controller
 */
class Advanced_Tools extends \WP_Defender\Controller2 {
	public $slug = 'wdf-advanced-tools';

	/**
	 * Advanced_Tools constructor.
	 */
	public function __construct() {
		$this->register_page( esc_html__( 'Advanced Tools', 'wpdef' ), $this->slug, [
			&$this,
			'main_view'
		], $this->parent_slug );
		add_action( 'defender_enqueue_assets', [ &$this, 'enqueue_assets' ] );
	}

	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}

		$data = [];
		wp_enqueue_script( 'clipboard' );
		$data = apply_filters( 'wp_defender_advanced_tools_data', $data );
		wp_localize_script( 'def-advancedtools', 'advanced_tools', $data );
		wp_enqueue_script( 'def-advancedtools' );
		$this->enqueue_main_assets();
	}

	/**
	 * Render the root element for frontend
	 */
	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 *
	 */
	public function remove_settings() {
		( new \WP_Defender\Model\Setting\Mask_Login() )->delete();
		( new \WP_Defender\Model\Setting\Security_Headers() )->delete();
	}

	//we dont have any data on this module
	public function remove_data() {
		return;
	}

	public function data_frontend() {
		return [
			'mask_login'       => wd_di()->get( Mask_Login::class )->data_frontend(),
			'security_headers' => wd_di()->get( Security_Headers::class )->data_frontend()
		];
	}

	public function to_array() {
		// TODO: Implement to_array() method.
	}

	public function import_data( $data ) {
		// TODO: Implement import_data() method.
	}

	/**
	 * @return array
	 */
	public function export_strings() {
		return [];
	}
}