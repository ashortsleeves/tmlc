<?php // phpcs:ignore
/**
 * Populate welcome modal with appropriate copies.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Task\Check\Hub;
use WPMUDEV\Snapshot4\Helper\Settings;

$button_class = 'sui-button-blue';
$this->render(
	'modals/edit-schedule',
	array(
		'modal_title'   => __( 'Backup schedule', 'snapshot' ),
		'message'       => __( 'Edit your backup schedule to run automatically. We highly recommend a weekly or daily frequency depending on how active your website is.', 'snapshot' ),
		'button'        => __( 'Save schedule', 'snapshot' ),
		'button_saving' => __( 'Saving', 'snapshot' ),
		'status'        => 'active',
		'files'         => 'all',
		'tables'        => 'all',
	)
);

if ( snapshot_has_error( Hub::ERR_DASH_PRESENT, $errors ) ) {
	$this->render(
		'modals/welcome-wpmu-dashboard',
		array(
			'modal_title'        => __( 'Install WPMU DEV Dashboard', 'snapshot' ),
			'message'            => __( 'Whoops, looks like you don\'t have the WPMU DEV Dashboard plugin installed and activated. This plugin is the API connection between WPMU DEV and your site, so if you want to use WPMU DEV to store your backups simply download and install it.', 'snapshot' ),
			'button'             => __( 'Install plugin', 'snapshot' ),
			'button_loading'     => __( 'Installing plugin', 'snapshot' ),
			'button_class'       => $button_class,
			'active_first_slide' => true,
			'installed'          => false,
		)
	);
} elseif ( snapshot_has_error( Hub::ERR_DASH_ACTIVE, $errors ) ) {
	$this->render(
		'modals/welcome-wpmu-dashboard',
		array(
			'modal_title'        => __( 'Activate WPMU DEV Dashboard', 'snapshot' ),
			/* translators: %s - Admin name */
			'message'            => sprintf( __( '%s, welcome to the hottest backup plugin for WordPress. It looks like you haven\'t activated the WPMU DEV Dashboard plugin. The plugin is the API connection between WPMU DEV and your site, so if you want to use WPMU DEV to store your backups simply activate the plugin.', 'snapshot' ), wp_get_current_user()->display_name ),
			'button'             => __( 'Activate plugin', 'snapshot' ),
			'button_loading'     => __( 'Activating plugin', 'snapshot' ),
			'button_class'       => $button_class,
			'active_first_slide' => true,
			'installed'          => true,
		)
	);
} elseif ( snapshot_has_error( Hub::ERR_DASH_APIKEY, $errors ) ) {
	$this->render(
		'modals/welcome-wpmu-dashboard',
		array(
			'active_first_slide' => false,
			'button_class'       => $button_class,
			'installed'          => true,
			'button_loading'     => __( 'Logging in', 'snapshot' ),
		)
	);
}

if ( true === $welcome_modal ) {
	$this->render(
		'modals/welcome',
		array(
			'modal_title'  => $welcome_modal_alt ? __( 'Welcome back', 'snapshot' ) : __( 'Welcome to Snapshot Pro', 'snapshot' ),
			/* translators: %s - Admin name */
			'message'      => $welcome_modal_alt ? sprintf( __( '%s, welcome back to the hottest backup plugin for WordPress. We have saved all your settings and old backups. You\'ll be able to restore the backups anytime and use already configured settings.', 'snapshot' ), wp_get_current_user()->display_name ) : sprintf( __( '%s, welcome to the hottest backup plugin for WordPress. Snapshot Pro is successfully connected with the WPMU DEV Dashboard plugin and you\'re ready to create your first backup.', 'snapshot' ), wp_get_current_user()->display_name ),
			'message2'     => __( 'Please choose backup region to continue.', 'snapshot' ),
			'button'       => $welcome_modal_alt ? __( 'Okay, thanks!', 'snapshot' ) : __( 'Get started', 'snapshot' ),
			'button_class' => $button_class,

			'status'       => 'active',
			'files'        => 'all',
			'tables'       => 'all',
		)
	);
}

if ( ! Settings::get_whats_new_seen() ) {
	$this->render( 'modals/whats-new' );
}