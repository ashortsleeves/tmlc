<?php // phpcs:ignore
/**
Plugin Name: Snapshot Pro
Plugin URI: https://wpmudev.com/project/snapshot/
Description: Make and schedule incremental backups of your WordPress websites and store them on secure cloud storage. Snapshot Backups are logged and can be restored with a click or manually with the included installer. Snapshot gives you simple, faster, managed backups that take up less space.
Version: 4.3.5
Network: true
Text Domain: snapshot
Author: WPMU DEV
Author URI: http://wpmudev.com
WDP ID: 3760011

@package snapshot
 */

/*
Copyright 2007-2020 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 – GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

use WPMUDEV\Snapshot4\Controller\Activate;
use WPMUDEV\Snapshot4\Main;

define( 'SNAPSHOT_BACKUPS_VERSION', '4.3.5' );
define( 'SNAPSHOT_PLUGIN_FILE', __FILE__ );

if ( ! defined( 'SNAPSHOT4_BACKUP_TIMEOUT' ) ) {
	define( 'SNAPSHOT4_BACKUP_TIMEOUT', 30 * 60 );
}

if ( ! defined( 'SNAPSHOT_IS_TEST_ENV' ) ) {
	define( 'SNAPSHOT_IS_TEST_ENV', false );
}

require_once dirname( __FILE__ ) . '/lib/functions.php';
require_once dirname( __FILE__ ) . '/lib/loader.php';

register_activation_hook(
	__FILE__,
	array( Activate::class, 'on_activate' )
);

Main::get()->boot();