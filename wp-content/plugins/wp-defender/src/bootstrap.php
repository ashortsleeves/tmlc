<?php

namespace WP_Defender;

use Calotes\DB\Mapper;
use Calotes\Helper\Array_Cache;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\Cli;
use WP_Defender\Controller\Advanced_Tools;
use WP_Defender\Controller\Dashboard;
use WP_Defender\Controller\Audit_Logging;
use WP_Defender\Controller\Firewall;
use WP_Defender\Controller\HUB;
use WP_Defender\Controller\Mask_Login;
use WP_Defender\Controller\Notification;
use WP_Defender\Controller\Onboard;
use WP_Defender\Controller\Scan;
use WP_Defender\Controller\Security_Headers;
use WP_Defender\Controller\Security_Tweaks;
use WP_Defender\Controller\Two_Factor;
use WP_Defender\Controller\Main_Setting;
use WP_Defender\Controller\WAF;
use WP_Defender\Controller\Tutorial;
use WP_Defender\Controller\Blocklist_Monitor;

/**
 * Class Bootstrap
 * @package WP_Defender
 */
class Bootstrap {
	/**
	 * Activation
	 */
	public function activation_hook() {
		$this->maybe_create_defender_lockout_table();
		$this->maybe_create_defender_lockout_log_table();
		$this->maybe_create_defender_scan_table();
		$this->maybe_create_defender_scan_item_table();
		$this->maybe_create_audit_log_table();
		$this->maybe_create_email_logs_table();
	}

	/**
	 * Deactivation
	 */
	public function deactivation_hook() {
		wp_clear_scheduled_hook( 'clean_up_old_log' );
		wp_clear_scheduled_hook( 'audit_sync_events' );
		wp_clear_scheduled_hook( 'wdf_maybe_send_report' );
	}

	protected function maybe_create_email_logs_table() {
		global $wpdb;
		$table_name      = $wpdb->base_prefix . 'defender_email_log';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `timestamp` int NOT NULL,
 `source` varchar(255) NOT NULL,
 `to` varchar (255) NOT NULL,
 PRIMARY KEY (`id`)
) $charset_collate";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Though our data mainly store on API side, we will need a table for caching
	 */
	protected function maybe_create_audit_log_table() {
		global $wpdb;
		$table_name      = $wpdb->base_prefix . 'defender_audit_log';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `timestamp` int NOT NULL,
 `event_type` varchar(255) NOT NULL,
 `action_type` varchar (255) NOT NULL,
 `site_url` varchar (255) NOT NULL,
 `user_id` int NOT NULL,
 `context` varchar (255) NOT NULL,
 `ip` varchar (255) NOT NULL,
 `msg` varchar (255) NOT NULL,
 `blog_id` int NOT NULL,
 `synced` int NOT NULL,
 `ttl` int NOT NULL,
 PRIMARY KEY (`id`)
) $charset_collate";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	protected function maybe_create_defender_scan_item_table() {
		global $wpdb;
		$table_name      = $wpdb->base_prefix . 'defender_scan_item';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `parent_id` int NOT NULL,
 `type` varchar(255) NOT NULL,
 `status` varchar (255) NOT NULL,
 `raw_data` text NOT NULL,
 PRIMARY KEY (`id`)
) $charset_collate";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	protected function maybe_create_defender_scan_table() {
		global $wpdb;
		$table_name      = $wpdb->base_prefix . 'defender_scan';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `percent` float NOT NULL,
 `total_tasks` tinyint(4) NOT NULL,
 `task_checkpoint` varchar(255) NOT NULL,
 `status` varchar(255) NOT NULL,
 `date_start` datetime NOT NULL,
 `date_end` datetime NOT NULL,
 `is_automation` Bool NOT NULL,
 PRIMARY KEY (`id`)
) $charset_collate";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	protected function maybe_create_defender_lockout_log_table() {
		global $wpdb;
		$table_name      = $wpdb->base_prefix . 'defender_lockout_log';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `log` text,
  `ip` varchar(255) DEFAULT NULL,
  `date` int(11) DEFAULT NULL,
  `type` varchar(16) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `blog_id` int(11) DEFAULT NULL,
  `tried` VARCHAR (255),
  PRIMARY KEY (`id`)
) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Create the table _defender_lockout for Ip Lockout module
	 */
	protected function maybe_create_defender_lockout_table() {
		global $wpdb;
		$table_name      = $wpdb->base_prefix . 'defender_lockout';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) DEFAULT NULL,
  `status` varchar(16) DEFAULT NULL,
  `lockout_message` text,
  `release_time` int(11) DEFAULT NULL,
  `lock_time` int(11) DEFAULT NULL,
  `lock_time_404` int(11) DEFAULT NULL,
  `attempt` int(11) DEFAULT NULL,
  `attempt_404` int(11) DEFAULT NULL,
  `meta` text,
  PRIMARY KEY (`id`)
) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Load all modules
	 */
	public function init_modules() {
		//Init main ORM
		Array_Cache::set( 'orm', new Mapper() );
		/**
		 * if this is a fresh install and there were no requests from the Hub before,
		 * then we should display the onboarding first
		 */
		$hub_class = wd_di()->get( HUB::class );
		$hub_class->set_onboarding_status( $this->maybe_show_onboarding() );
		$hub_class->listen_to_requests();
		if ( $hub_class->get_onboarding_status() && 'cli' !== php_sapi_name() ) {
			//if in cli we should init this normally
			Array_Cache::set( 'onboard', wd_di()->get( Onboard::class ) );
		} else {
			//Initialize the main controllers of every modules
			wd_di()->get( Dashboard::class );
		}
		wd_di()->get( Security_Tweaks::class );
		wd_di()->get( Scan::class );
		wd_di()->get( Audit_Logging::class );
		wd_di()->get( Firewall::class );
		wd_di()->get( WAF::class );
		wd_di()->get( Two_Factor::class );
		wd_di()->get( Advanced_Tools::class );
		wd_di()->get( Mask_Login::class );
		wd_di()->get( Security_Headers::class );
		wd_di()->get( Notification::class );
		wd_di()->get( Main_Setting::class );
		wd_di()->get( Tutorial::class );
		wd_di()->get( Blocklist_Monitor::class );
		$this->init_wpmudev_dashnotice();
	}

	/**
	 * @return bool
	 */
	private function maybe_show_onboarding() {
		//first we need to check if the site is newly create
		global $wpdb;
		$option = 'wp_defender_shown_activator';
		if ( ! is_multisite() ) {
			$res = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = '$option'" );
		} else {
			$network_id = get_current_network_id();
			$res = $wpdb->get_var( "SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = '$option' AND site_id = $network_id" );
		}
		//Get '1' for direct SQL request if Onboarding was already
		if ( empty( $res ) ) {
			return true;
		}

		return false;
	}

	public function init_wpmudev_dashnotice() {
		global $wpmudev_notices;
		$wpmudev_notices[] = array(
			'id'      => 1081723,
			'name'    => 'Defender',
			'screens' => array(
				'toplevel_page_wp-defender',
				'toplevel_page_wp-defender-network',
				'defender_page_wdf-settings',
				'defender_page_wdf-settings-network',
				'defender_page_wdf-logging',
				'defender_page_wdf-logging-network',
				'defender_page_wdf-hardener',
				'defender_page_wdf-hardener-network',
				'defender_page_wdf-scan',
				'defender_page_wdf-scan-network',
				'defender_page_wdf-ip-lockout',
				'defender_page_wdf-ip-lockout-network',
				'defender_page_wdf-waf',
				'defender_page_wdf-waf-network',
				'defender_page_wdf-2fa',
				'defender_page_wdf-2fa-network',
				'defender_page_wdf-advanced-tools',
				'defender_page_wdf-advanced-tools-network',
				'defender_page_wdf-notification',
				'defender_page_wdf-notification-network',
				'defender_page_wdf-tutorial',
				'defender_page_wdf-tutorial-network',
			)
		);
		/** @noinspection PhpIncludeInspection */
		include_once( defender_path( 'extra/dash-notice/wpmudev-dash-notification.php' ) );
	}

	public function init_cli_command() {
		\WP_CLI::add_command( 'defender', Cli::class );
	}

	public function add_sui_to_body( $classes ) {
		$pages = array(
			'wp-defender',
			'wdf-hardener',
			'wdf-scan',
			'wdf-logging',
			'wdf-ip-lockout',
			'wdf-waf',
			'wdf-2fa',
			'wdf-advanced-tools',
			'wdf-notification',
			'wdf-setting',
			'wdf-tutorial',
		);
		$page  = isset( $_GET['page'] ) ? $_GET['page'] : null;
		if ( ! in_array( $page, $pages, true ) ) {
			return $classes;
		}
		$classes .= sprintf( ' sui-%s ', DEFENDER_SUI );

		return $classes;
	}

	/**
	 * Register all core assets
	 */
	public function register_assets() {
		$base_url = plugin_dir_url( dirname( __FILE__ ) );
		wp_enqueue_style( 'defender-menu', $base_url . 'assets/css/defender-icon.css' );

		$css_files = array(
			'defender' => $base_url . 'assets/css/styles.css',
		);

		foreach ( $css_files as $slug => $file ) {
			wp_register_style( $slug, $file, array(), DEFENDER_VERSION );
		}
		$is_min       = defined( 'SCRIPT_DEBUG' ) && constant( 'SCRIPT_DEBUG' ) === true ? '' : '.min';
		$dependencies = [
			'def-vue',
			'defender',
			'wp-i18n'
		];
		$js_files     = array(
			'wpmudev-sui'        => [
				$base_url . 'assets/js/shared-ui.js',
			],
			'defender'           => [
				$base_url . 'assets/js/scripts.js',
			],
			'def-vue'            => [
				$base_url . 'assets/js/vendor/vue.runtime' . $is_min . '.js'
			],
			'def-dashboard'      => [
				$base_url . 'assets/app/dashboard.js',
				$dependencies,
			],
			'def-securitytweaks' => [
				$base_url . 'assets/app/security-tweak.js',
				$dependencies,
			],
			'def-scan'           => [
				$base_url . 'assets/app/scan.js',
				$dependencies,
			],
			'def-audit'          => [
				$base_url . 'assets/app/audit.js',
				$dependencies,
			],
			'def-iplockout'      => [
				$base_url . 'assets/app/ip-lockout.js',
				$dependencies,
			],
			'def-advancedtools'  => [
				$base_url . 'assets/app/advanced-tools.js',
				$dependencies,
			],
			'def-settings'       => [
				$base_url . 'assets/app/settings.js',
				$dependencies,
			],
			'def-2fa'            => [
				$base_url . 'assets/app/two-fa.js',
				$dependencies
			],
			'def-notification'   => [
				$base_url . 'assets/app/notification.js',
				$dependencies
			],
			'def-waf'            => [
				$base_url . 'assets/app/waf.js',
				$dependencies
			],
			'def-onboard'        => [
				$base_url . 'assets/app/onboard.js',
				$dependencies
			],
			'def-tutorial'       => [
				$base_url . 'assets/app/tutorial.js',
				$dependencies
			]
		);

		global $wp_version;
		$is_older_5_2 = false;
		if ( version_compare( $wp_version, '5.2', '<' ) ) {
			// Check clipboard.js is registered or not.
			// Remove it when the minimum WP version will be increased.
			if ( ! wp_script_is( 'clipboard', 'registered' ) ) {
				$js_files['clipboard'] = [
					$base_url . 'assets/js/clipboard.min.js',
				];
			}
			$js_files['wpmudev-sui'] = [
				$base_url . 'assets/js/shared-ui.js',
				[ 'clipboard' ],
			];
			$js_files['defender'] = [
				$base_url . 'assets/js/scripts.js',
				[ 'wpmudev-sui' ],
			];
			$is_older_5_2 = true;
		}

		foreach ( $js_files as $slug => $file ) {
			if ( isset( $file[1] ) ) {
				wp_register_script( $slug, $file[0], $file[1], DEFENDER_VERSION, true );
			} else {
				wp_register_script( $slug, $file[0], array( 'jquery' ), DEFENDER_VERSION, true );
			}
		}
		$wpmu_dev = new WPMUDEV();
		wp_localize_script( 'def-vue', 'defender', [
			'whitelabel'    => $wpmu_dev->white_label_status(),
			'misc'          => [
				'high_contrast' => $wpmu_dev->maybe_high_contrast(),
			],
			'site_url'      => network_site_url(),
			'admin_url'     => network_admin_url(),
			'defender_url'  => $base_url,
			'is_free'       => $wpmu_dev->is_pro() ? 0 : 1,
			'is_membership' => true,
			'is_whitelabel' => $wpmu_dev->is_whitelabel_enabled() ? 'enabled' : 'disabled',
			'is_older_5_2'  => $is_older_5_2,
		] );
		do_action( 'defender_enqueue_assets' );
	}

	/**
	 * Check to exist table
	 *
	 * @param string $table_name
	 *
	 * @return bool
	 */
	private function table_exists( $table_name ) {
		global $wpdb;

		return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}

	/**
	 * Check and create tables if its aren't existed
	 */
	public function check_if_table_exists() {
		$db_version = get_site_option( 'wd_db_version' );
		if ( isset( $db_version ) && version_compare( $db_version, '2.4', '>=') ) {

			return;
		}
		global $wpdb;

		if ( ! $this->table_exists( $wpdb->base_prefix . 'defender_lockout' ) ) {
			$this->maybe_create_defender_lockout_table();
		}
		if ( ! $this->table_exists( $wpdb->base_prefix . 'defender_lockout_log' ) ) {
			$this->maybe_create_defender_lockout_log_table();
		}
		if ( ! $this->table_exists( $wpdb->base_prefix . 'defender_scan' ) ) {
			$this->maybe_create_defender_scan_table();
		}
		if ( ! $this->table_exists( $wpdb->base_prefix . 'defender_scan_item' ) ) {
			$this->maybe_create_defender_scan_item_table();
		}
		if ( ! $this->table_exists( $wpdb->base_prefix . 'defender_audit_log' ) ) {
			$this->maybe_create_audit_log_table();
		}
		if ( ! $this->table_exists( $wpdb->base_prefix . 'defender_email_log' ) ) {
			$this->maybe_create_email_logs_table();
		}
	}
}