<?php // phpcs:ignore
/**
 * Various functions
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Controller;

/**
 * Checks if a particular error code is represented in errors
 *
 * @param string $errstr Error code fragment.
 * @param array  $errors Errors array, or error object instance.
 *
 * @return bool
 */
function snapshot_has_error( $errstr, $errors ) {
	if ( ! is_array( $errors ) ) {
		$errors = array( $errors );
	}

	$delimiter = Model::SCOPE_DELIMITER;

	foreach ( $errors as $error ) {
		if ( ! is_wp_error( $error ) ) {
			continue;
		}
		$code = $error->get_error_code();

		$pos = stripos( $code, "{$errstr}{$delimiter}" );
		if ( 0 === $pos ) {
			// Position zero - we matched error up to delimiter.
			return true;
		}

		$string = "{$delimiter}{$errstr}";
		$pos    = stripos( $code, $string );
		if ( false !== $pos && strlen( $code ) === $pos + strlen( $string ) ) {
			// We found the substring, and it matches from delimiter to EOS.
			return true;
		}
	}

	return false;
}

/**
 * Checks if the user can perform snapshot actions
 *
 * @param int $user_id Optional user ID - defaults to current user.
 *
 * @return bool
 */
function snapshot_user_can_snapshot( $user_id = false ) {
	if ( ! empty( $user_id ) ) {
		// Implement for non-current user.
		return false;
	}
	return current_user_can(
		Controller\Admin::get()->get_capability()
	);
}

/**
 * Returns HTTP request body
 *
 * @param bool $decode_json Decode request body as JSON.
 * @param bool $return_array When true, returned objects will be converted into associative arrays.
 *
 * @return string|array|object|null
 */
function get_request_body( $decode_json = false, $return_array = false ) {
	$result = file_get_contents( 'php://input' );
	$result = apply_filters( 'snapshot_get_request_body', $result );
	if ( $decode_json ) {
		$result = json_decode( $result, $return_array );
	}

	return $result;
}

if ( ! function_exists( 'wp_timezone_string' ) ) {
	/**
	 * Retrieves the timezone from site settings as a string.
	 *
	 * @return string PHP timezone string or a ±HH:MM offset.
	 */
	function wp_timezone_string() {
		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			return $timezone_string;
		}

		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

		return $tz_offset;
	}
}

if ( ! function_exists( 'wp_timezone' ) ) {
	/**
	 * * Retrieves the timezone from site settings as a `DateTimeZone` object.
	 *
	 * @return \DateTimeZone
	 */
	function wp_timezone() {
		return new \DateTimeZone( wp_timezone_string() );
	}
}