<?php
/**
 * Plugin Name: Defender Pro
 * Plugin URI:  https://premium.wpmudev.org/project/wp-defender/
 * Version:     2.4.5
 * Description: Get regular security scans, vulnerability reports, safety recommendations and customized hardening for your site in just a few clicks. Defender is the analyst and enforcer who never sleeps.
 * Author:      WPMU DEV
 * Author URI:  https://premium.wpmudev.org/
 * WDP ID:      1081723
 * License:     GNU General Public License (Version 2 - GPLv2)
 * Text Domain: wpdef
 * Network:     true
 */

define( 'DEFENDER_VERSION', '2.4.5' );
define( 'DEFENDER_DB_VERSION', '2.4.5' );
define( 'DEFENDER_SUI', '2-9-6' );
define( 'DEFENDER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/functions.php';
//create container
$builder = new \DI\ContainerBuilder();
global $wp_defender_di;
$wp_defender_di = $builder->build();
global $wp_defender_central;
$wp_defender_central = new \WP_Defender\Central();
do_action( 'wp_defender' );
//include routes
require_once __DIR__ . '/src/bootstrap.php';
$bootstrap = new \WP_Defender\Bootstrap();
$bootstrap->check_if_table_exists();
//init
add_action( 'init', [ $bootstrap, 'init_modules' ], 8 );
//register routes
add_action( 'init', function () {
	require_once __DIR__ . '/src/routes.php';
}, 9 );

if ( class_exists( 'WP_ClI' ) ) {
	$bootstrap->init_cli_command();
}
//include admin class
require_once __DIR__ . '/src/class-admin.php';
add_action( 'admin_init', [ ( new \WP_Defender\Admin() ), 'init' ] );
add_action( 'init', [ ( new \WP_Defender\Upgrader() ), 'run' ] );
add_action( 'admin_enqueue_scripts', [ $bootstrap, 'register_assets' ] );
add_filter( 'admin_body_class', [ $bootstrap, 'add_sui_to_body' ], 99 );

register_deactivation_hook( __FILE__, [ $bootstrap, 'deactivation_hook' ] );
register_activation_hook( __FILE__, [ $bootstrap, 'activation_hook' ] );