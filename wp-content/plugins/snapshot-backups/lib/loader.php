<?php // phpcs:ignore
/**
 * Snapshot autoloader
 *
 * @package snapshot
 */

/**
 * Class name to file mapping procedure
 *
 * @param string $class Class name.
 */
function snapshot_resolve_class( $class ) {
	$matches = array();
	if ( ! preg_match( '/^WPMUDEV\\\\Snapshot4\\\\(.+)$/', $class, $matches ) ) {
		return false;
	}

	$class = $matches[1];

	$raw  = explode( '\\', strtolower( $class ) );
	$file = 'class-' . array_pop( $raw ) . '.php';
	$path = dirname( __FILE__ ) . '/snapshot/' . join( DIRECTORY_SEPARATOR, $raw ) . "/{$file}";
	if ( is_readable( $path ) ) {
		require_once $path;
	}
}
spl_autoload_register( 'snapshot_resolve_class' );