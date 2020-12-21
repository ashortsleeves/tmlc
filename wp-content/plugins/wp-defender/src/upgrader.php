<?php

namespace WP_Defender;

class Upgrader {

	/**
	 * Migrate old security headers from security tweaks. Trigger it once time
	 */
	public function migrate_security_headers() {
		$model   = new \WP_Defender\Model\Setting\Security_Headers();
		$new_key = $model->table;
		$option  = get_site_option( $new_key );

		if ( empty( $option ) ) {
			//Part of Security tweaks data
			$old_key      = 'wd_hardener_settings';
			$old_settings = get_site_option( $old_key );
			if ( ! is_array( $old_settings ) ) {
				$old_settings = json_decode( $old_settings, true );
				if ( is_array( $old_settings ) && isset( $old_settings['data'] ) && ! empty( $old_settings['data'] ) ) {
					//Exists 'X-Frame-Options'
					if ( isset( $old_settings['data']['sh_xframe'] ) && ! empty( $old_settings['data']['sh_xframe'] ) ) {
						$header_data = $old_settings['data']['sh_xframe'];

						$mode = ( isset( $header_data['mode'] ) && ! empty( $header_data['mode'] ) )
							? strtolower( $header_data['mode'] )
							: false;
						if ( 'allow-from' === $mode ) {
							$model->sh_xframe_mode = 'allow-from';
							if ( isset( $header_data['values'] ) && ! empty( $header_data['values'] ) ) {
								$urls                  = explode( ' ', $header_data['values'] );
								$model->sh_xframe_urls = implode( PHP_EOL, $urls );
							}
						} elseif ( in_array( $mode, array( 'sameorigin', 'deny' ), true ) ) {
							$model->sh_xframe_mode = $mode;
						}
						$model->sh_xframe = true;
					}

					//Exists 'X-XSS-Protection'
					if ( isset( $old_settings['data']['sh_xss_protection'] ) && ! empty( $old_settings['data']['sh_xss_protection'] ) ) {
						$header_data = $old_settings['data']['sh_xss_protection'];

						if ( isset( $header_data['mode'] )
							&& ! empty( $header_data['mode'] )
							&& in_array( $header_data['mode'], array( 'sanitize', 'block' ), true )
						) {
							$model->sh_xss_protection_mode = $header_data['mode'];
							$model->sh_xss_protection      = true;
						}
					}

					//Exists 'X-Content-Type-Options'
					if ( isset( $old_settings['data']['sh_content_type_options'] ) && ! empty( $old_settings['data']['sh_content_type_options'] ) ) {
						$header_data = $old_settings['data']['sh_content_type_options'];

						if ( isset( $header_data['mode'] ) && ! empty( $header_data['mode'] ) ) {
							$model->sh_content_type_options_mode = $header_data['mode'];
							$model->sh_content_type_options      = true;
						}
					}

					//Exists 'Strict Transport'
					if ( isset( $old_settings['data']['sh_strict_transport'] ) && ! empty( $old_settings['data']['sh_strict_transport'] ) ) {
						$header_data = $old_settings['data']['sh_strict_transport'];

						if ( isset( $header_data['hsts_preload'] ) && ! empty( $header_data['hsts_preload'] ) ) {
							$model->hsts_preload = (int) $header_data['hsts_preload'];
						}
						if ( isset( $header_data['include_subdomain'] ) && ! empty( $header_data['include_subdomain'] ) ) {
							$model->include_subdomain = in_array(
								$header_data['include_subdomain'],
								array( 'true', '1', 1 ),
								true
							) ? 1 : 0;
						}
						if ( isset( $header_data['hsts_cache_duration'] ) && ! empty( $header_data['hsts_cache_duration'] ) ) {
							$model->hsts_cache_duration = $header_data['hsts_cache_duration'];
						}
						$model->sh_strict_transport = true;
					}

					//Exists 'Referrer Policy'
					if ( isset( $old_settings['data']['sh_referrer_policy'] ) && ! empty( $old_settings['data']['sh_referrer_policy'] ) ) {
						$header_data = $old_settings['data']['sh_referrer_policy'];

						if ( isset( $header_data['mode'] ) && ! empty( $header_data['mode'] ) ) {
							$model->sh_referrer_policy_mode = $header_data['mode'];
							$model->sh_referrer_policy      = true;
						}
					}

					//Exists 'Feature-Policy'
					if ( isset( $old_settings['data']['sh_feature_policy'] ) && ! empty( $old_settings['data']['sh_feature_policy'] ) ) {
						$header_data = $old_settings['data']['sh_feature_policy'];

						if ( isset( $header_data['mode'] ) && ! empty( $header_data['mode'] ) ) {
							$mode                          = strtolower( $header_data['mode'] );
							$model->sh_feature_policy_mode = $mode;
							if ( 'origins' === $mode && isset( $header_data['values'] ) && ! empty( $header_data['values'] ) ) {
								//The values differ from the values of the 'X-Frame-Options' key, because they may be array.
								if ( is_array( $header_data['values'] ) ) {
									$model->sh_feature_policy_urls = implode( PHP_EOL, $header_data['values'] );
									//otherwise
								} elseif ( is_string( $header_data['values'] ) ) {
									$urls                          = explode( ' ', $header_data['values'] );
									$model->sh_feature_policy_urls = implode( PHP_EOL, $urls );
								}
							}
							$model->sh_feature_policy = true;
						}
					}
					//Save
					$model->save();
				}
			}
		}
	}

	/**
	 *
	 * If user upgrade from an older version to latest version
	 *
	 * @param $current_version
	 */
	public function maybe_show_new_features( $current_version ) {
		if ( false === $current_version ) {
			//do nothing
			return;
		}

		if ( version_compare( $current_version, DEFENDER_DB_VERSION, '<' ) ) {
			update_site_option( 'wd_show_new_feature', true );
		}
	}

	/**
	 *
	 * Migrate configs for latest versions.
	 * @since 2.4
	 *
	 * @param $current_version
	 */
	public function migrate_configs( $current_version ) {
		if (
			version_compare( $current_version, '2.2', '>=' )
			&& version_compare( $current_version, '2.4', '<' )
		) {
			$config_component = wd_di()->get( \WP_Defender\Component\Backup_Settings::class );
			$prev_data        = $config_component->backup_data();
			if ( empty( $prev_data ) ) {
				return;
			}
			$adapter       = wd_di()->get( \WP_Defender\Component\Config\Config_Adapter::class );
			$migrated_data = $adapter->upgrade( $prev_data );
			$config_component->restore_data( $migrated_data, true );
			// Hide Onboard page
			update_site_option( 'wp_defender_shown_activator', true );

			$configs = $config_component->get_configs();
			if ( ! empty( $configs ) ) {
				foreach ( $configs as $k => $config ) {
					if (
						$config_component->verify_config_data( $config )
						&& ! $config_component->check_for_new_structure( $config['configs'] )
					) {
						$new_data            = $config;
						$new_data['configs'] = $adapter->upgrade( $config['configs'] );

						/**
						 * Import config 'strings' and the active tag if a config has it.
						 */
						if ( isset( $config['is_active'] ) ) {
							$new_data['is_active'] = $config['is_active'];
						}
						$new_data['strings'] = $config_component->import_module_strings( $new_data );
						//Update config data
						update_site_option( $k, $new_data );
						continue;
					}
				}
			}
		}
		// For older versions we do not use old models, e.g. for version < 2.2. So the default values will be used.
	}

	/**
	 * Run an upgrade/installation.
	 */
	public function run() {
		$db_version = get_site_option( 'wd_db_version' );
		if ( empty( $db_version ) ) {

			return update_site_option( 'wd_db_version', DEFENDER_DB_VERSION );
		}
		if ( DEFENDER_DB_VERSION === $db_version ) {
			return;
		}
		$this->maybe_show_new_features( $db_version );
		$this->migrate_configs( $db_version );
		if ( version_compare( $db_version, '2.2.9', '<' ) ) {
			$this->migrate_security_headers();
		}
		update_site_option( 'wd_db_version', DEFENDER_DB_VERSION );
	}
}