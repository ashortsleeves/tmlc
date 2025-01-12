<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use Calotes\Helper\Array_Cache;
use Calotes\Helper\HTTP;
use WP_Defender\Controller2;

class Main_Setting extends Controller2 {
	public $slug = 'wdf-setting';

	/**
	 * Use for cache
	 * @var \WP_Defender\Model\Setting\Main_Setting
	 */
	public $model;

	/**
	 * @var \WP_Defender\Component\Backup_Settings
	 */
	protected $service;

	public function __construct() {
		$this->register_page(
			esc_html__( 'Settings', 'wpdef' ),
			$this->slug,
			array(
				&$this,
				'main_view',
			),
			$this->parent_slug
		);

		//internal cache
		$this->model   = new \WP_Defender\Model\Setting\Main_Setting();
		$this->service = wd_di()->get( \WP_Defender\Component\Backup_Settings::class );
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
		$this->register_routes();
	}

	/**
	 * Safe way to get cached model
	 * @return \WP_Defender\Model\Setting\Main_Setting
	 */
	private function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return wd_di()->get( \WP_Defender\Model\Setting\Main_Setting::class );
	}

	/**
	 *
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script( 'def-settings', 'settings', $this->data_frontend() );
		wp_enqueue_script( 'def-settings' );
		$this->enqueue_main_assets();
	}

	/**
	 * Render the root element for frontend
	 */
	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 * Store settings into db
	 *
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$model = $this->get_model();
		$data  = $request->get_data();

		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();

			return new Response(
				true,
				array(
					'message' => __( 'Your settings have been updated.', 'wpdef' ),
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => $model->get_formatted_errors(),
			)
		);
	}


	/**
	 * Reset settings
	 * @defender_route
	 */
	public function reset_settings( Request $request ) {
		wd_di()->get( \WP_Defender\Controller\Advanced_Tools::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Audit_Logging::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Dashboard::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Security_Tweaks::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Scan::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Firewall::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Firewall_Logs::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Login_Lockout::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Nf_Lockout::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Mask_Login::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Notification::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Tutorial::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Two_Factor::class )->remove_settings();
		wd_di()->get( \WP_Defender\Controller\Blocklist_Monitor::class )->remove_settings();
		$this->remove_settings();

		//Todo: submit stats to dev
		return new Response(
			true,
			array(
				'message'  => __( 'Your settings have been reset.', 'wpdef' ),
				'redirect' => network_admin_url( 'admin.php?page=wp-defender' ),
				'interval' => 1,
			)
		);
	}

	function remove_settings() {
		wd_di()->get( \WP_Defender\Model\Setting\Main_Setting::class )->delete();
	}

	function remove_data() {
		// TODO: Implement remove_data() method.
	}

	function data_frontend() {
		$model = $this->get_model();

		$this->service->maybe_create_default_config();
		$configs = $this->service->get_configs();

		foreach ( $configs as &$config ) {
			//unset the data as we dont need it
			unset( $config['configs'] );
		}

		$link = ( new \WP_Defender\Behavior\WPMUDEV() )->is_member()
			? 'https://premium.wpmudev.org/translate/projects/wpdef/'
			: 'https://translate.wordpress.org/projects/wp-plugins/defender-security/';

		return array_merge(
			array(
				'general'       => array(
					'translate'        => $model->translate,
					'usage_tracking'   => $model->usage_tracking,
					'translation_link' => $link,
				),
				'data_settings' => array(
					'uninstall_settings' => $model->uninstall_settings,
					'uninstall_data'     => $model->uninstall_data,
				),
				'accessibility' => array(
					'high_contrast_mode' => $model->high_contrast_mode,
				),
				'misc'          => array(
					'setting_url' => network_admin_url( is_multisite() ? 'settings.php' : 'options-general.php' ),
				),
				'configs'       => $configs,
			),
			$this->dump_routes_and_nonces()
		);
	}

	function to_array() {
		// TODO: Implement to_array() method.
	}

	public function import_data( $data ) {
		$model = $this->get_model();

		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
		}
	}

	private function validate_importer( $importer ) {
		if ( $this->service->verify_config_data( $importer ) ){
			//validate content
			//this is the current data, we use this for verify the schema
			$sample = $this->service->gather_data();
			foreach ( $importer['configs'] as $slug => $module ) {
				//this is not in the sample, file is invalid
				if ( ! isset( $sample[ $slug ] ) ) {
					return false;
				}

				$keys        = array_keys( $sample[ $slug ] );
				$import_keys = array_keys( $module );
				$diff        = array_diff( $import_keys, $keys );
				if ( count( $diff ) ) {
					return false;
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Import config
	 *
	 * @defender_route
	 */
	public function import_config( Request $request ) {
		$data = $request->get_data();

		$file     = $_FILES['file'];
		$tmp      = $file['tmp_name'];
		$content  = file_get_contents( $tmp );
		$importer = json_decode( $content, true );
		if ( ! is_array( $importer ) ) {
			return new Response(
				false,
				array(
					'message' => __( 'The file is corrupted.', 'wpdef' ),
				)
			);
		}

		//If it's old config structure then we upgrade configs to new format
		if ( ! empty( $importer['configs'] ) && ! $this->service->check_for_new_structure( $importer['configs'] ) ) {
			$adapter             = wd_di()->get( \WP_Defender\Component\Config\Config_Adapter::class );
			$importer['configs'] = $adapter->upgrade( $importer['configs'] );
		}

		if ( ! $this->validate_importer( $importer ) ) {
			return new Response(
				false,
				array(
					'message' => __( 'An error occurred while importing the file. Please check your file or upload another file.', 'wpdef' ),
				)
			);
		}

		//sanitize the files a bit
		$name    = strip_tags( $importer['name'] );
		$configs = array(
			'name'     => $name,
			'immortal' => false,
		);

		$configs['configs']     = $importer['configs'];
		$configs['description'] = isset( $importer['description'] ) && ! empty( $importer['description'] )
			? sanitize_textarea_field( $importer['description'] )
			: '';
		$configs['strings']     = $this->service->import_module_strings( $importer );
		$key                    = 'wp_defender_config_import_' . time();
		update_site_option( $key, $configs );
		$this->service->index_key( $key );

		return new Response(
			true,
			array(
				'message' => sprintf(
					/* translators: %s: Config name. */
					__(
						'<strong>%s</strong> config has been uploaded successfully – you can now apply it to this site.',
						'wpdef'
					),
					$name
				),
				'configs' => $this->service->get_configs(),
			)
		);
	}

	/**
	 * Create config
	 *
	 * @defender_route
	 */
	public function new_config( Request $request ) {
		$data = $request->get_data();
		$name = trim( $data['name'] );
		if ( empty( $name ) ) {
			return new Response(
				false,
				array(
					'message' => __( 'Invalid config name', 'wpdef' ),
				)
			);
		}
		$name     = strip_tags( $name );
		$desc     = isset( $data['desc'] ) && ! empty( $data['desc'] ) ? wp_kses_post( $data['desc'] ) : '';
		$key      = 'wp_defender_config_' . time();
		$settings = $this->service->parse_data_for_import();
		$data     = array_merge(
			array(
				'name'        => $name,
				'immortal'    => false,
				'description' => $desc,
			),
			$settings
		);
		unset( $data['labels'] );
		if ( update_site_option( $key, $data ) ) {
			$this->service->index_key( $key );

			return new Response(
				true,
				array(
					'message' => sprintf(
						/* translators: %s: Config name. */
						__( '<strong>%s</strong> config saved successfully.', 'wpdef' ),
						$name
					),
					'configs' => $this->service->get_configs(),
				)
			);
		} else {
			return new Response(
				false,
				array(
					'message' => __( 'An error occurred while saving your config. Please try it again.', 'wpdef' ),
				)
			);
		}
	}

	/**
	 * Download config
	 *
	 * @defender_route
	 */
	public function download_config() {
		$key = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : false;
		if ( empty( $key ) ) {
			return new Response(
				false,
				array(
					'message' => __( 'Invalid config', 'wpdef' ),
				)
			);
		}

		$config = get_site_option( $key );
		if ( false === $config ) {
			return new Response(
				false,
				array(
					'message' => __( 'Invalid config', 'wpdef' ),
				)
			);
		}
		$sample = $this->service->gather_data();
		foreach ( $sample as $slug => $data ) {
			foreach ( $data as $key => $val ) {
				if ( ! isset( $config['configs'][ $slug ][ $key ] ) ) {
					$config['configs'][ $slug ][ $key ] = null;
				}
			}
		}
		$json     = json_encode( $config );
		$filename = 'wp-defender-config-' . sanitize_file_name( $config['name'] ) . '.json';
		header( 'Content-disposition: attachment; filename=' . $filename );
		header( 'Content-type: application/json' );
		echo $json;
		exit;
	}

	/**
	 * Apply config
	 *
	 * @defender_route
	 */
	public function apply_config( Request $request ) {
		$data = $request->get_data();
		$key  = trim( $data['key'] );
		if ( empty( $key ) ) {
			return new Response(
				false,
				array(
					'message' => __( 'Invalid config', 'wpdef' ),
				)
			);
		}

		$config = get_site_option( $key );
		if ( false === $config ) {
			return new Response(
				false,
				array(
					'message' => __( 'Invalid config', 'wpdef' ),
				)
			);
		}

		$this->service->make_config_active( $key );
		//Return error message or bool value for auth action
		$restore_result = $this->service->restore_data( $config['configs'] );
		if ( is_string( $restore_result ) ) {
			return new Response( false, $restore_result );
		}

		$message = sprintf(
			/* translators: %s: Config name. */
			__(
				'<strong>%s</strong> config has been applied successfully.',
				'wpdef'
			),
			$config['name']
		);
		$return = array();
		if ( $restore_result ) {
			$login_url           = wp_login_url();
			$settings_mask_login = new \WP_Defender\Model\Setting\Mask_Login();
			if ( $settings_mask_login->is_active() ) {
				$login_url = $settings_mask_login->get_new_login_url();
			}
			$message .= '<br/>' . sprintf(
				/* translators: %s: Login link. */
				__(
					'Because of some security recommendations get applied, You will now need to <a href="%s"><strong>re-login</strong></a>.<br/>This will auto reload now',
					'wpdef'
				),
				$login_url
			);
			$return['reload'] = 3;
			$redirect         = urlencode( network_admin_url( 'admin.php?page=wdf-setting&view=configs' ) );
			if ( isset( $data['screen'] ) && 'dashboard' === $data['screen'] ) {
				$redirect = urlencode( network_admin_url( 'admin.php?page=wp-defender' ) );
			}
			$return['redirect'] = add_query_arg(
				'redirect_to',
				$redirect,
				$login_url
			);
			$return['interval'] = 2;
		}

		$return['message'] = $message;
		$return['configs'] = $this->service->get_configs();

		return new Response( true, $return );
	}

	/**
	 * Update config
	 *
	 * @defender_route
	 */
	public function update_config( Request $request ) {
		$data        = $request->get_data();
		$key         = trim( $data['key'] );
		$name        = trim( $data['name'] );
		$description = trim( $data['description'] );
		if ( empty( $name ) || empty( $key ) ) {
			return new Response(
				false,
				array(
					'message' => __( 'Invalid config', 'wpdef' ),
				)
			);
		}

		$config = get_site_option( $key );
		if ( false === $config ) {
			return new Response(
				false,
				array(
					'message' => __( 'Invalid config', 'wpdef' ),
				)
			);
		}

		$old_config            = $config;
		$config['name']        = sanitize_text_field( $name );
		$config['description'] = sanitize_textarea_field( $description );

		// Check data has been changed or not.
		if (
			$old_config['name'] === $config['name'] &&
			$old_config['description'] === $config['description']
		) {
			// Data is not changed, so not need to run update query.
			$option_updated = true;
		} else {
			$option_updated = update_site_option( $key, $config );
		}

		if ( $option_updated ) {
			return new Response(
				true,
				array(
					'message' => sprintf(
						/* translators: %s: Config name. */
						__( '<strong>%s</strong> config saved successfully.', 'wpdef' ),
						$name
					),
					'configs' => $this->service->get_configs(),
				)
			);
		} else {
			return new Response(
				false,
				array(
					'message' => __( 'An error occurred while saving your config. Please try it again.', 'wpdef' ),
				)
			);
		}
	}

	/**
	 * Delete config
	 *
	 * @defender_route
	 */
	public function delete_config( Request $request ) {
		$data = $request->get_data();
		$key  = trim( $data['key'] );
		if ( empty( $key ) ) {
			return new Response(
				false,
				array(
					'message' => __( 'Invalid config', 'wpdef' ),
				)
			);
		}
		if ( 0 === strpos( $key, 'wp_defender_config' ) ) {
			delete_site_option( $key );

			return new Response(
				true,
				array(
					'message' => __( 'Config removed successfully.', 'wpdef' ),
					'configs' => $this->service->get_configs(),
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => __( 'Invalid config', 'wpdef' ),
			)
		);
	}

	/**
	 * @return array
	 */
	public function export_strings() {
		return array();
	}
}