<?php // phpcs:ignore
/**
 * Snapshot helpers: datetime helper class
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Helper;

/**
 * Datetime helper class
 */
class Datetime {

	/**
	 * Returns datetime format
	 *
	 * @return string
	 */
	public static function get_format() {
		$format  = self::get_date_format();
		$format .= _x( ' @ ', 'date time sep', 'snapshot' );
		$format .= self::get_time_format();
		return $format;
	}

	/**
	 * Returns date format
	 *
	 * @return string
	 */
	public static function get_date_format() {
		return get_option( 'date_format' );
	}

	/**
	 * Returns time format
	 *
	 * @return string
	 */
	public static function get_time_format() {
		return get_option( 'time_format' );
	}

	/**
	 * Returns array with hour list 0...23
	 *
	 * @return array
	 */
	public static function get_hour_list() {
		$dt = new \DateTime();
		$dt->setTimezone( new \DateTimeZone( 'UTC' ) );
		$dt->setTimestamp( 0 );

		$result      = array();
		$time_format = self::get_time_format();
		foreach ( range( 0, 23 ) as $hour ) {
			$dt->setTime( $hour, 0, 0 );

			$key   = $dt->format( 'H:i' );
			$value = $dt->format( $time_format );

			$result[ $key ] = $value;
		}

		return $result;
	}

	/**
	 * Returns user's timezone
	 *
	 * @return \DateTimeZone
	 */
	public static function get_timezone() {
		return wp_timezone();
	}

	/**
	 * Format a time/date
	 *
	 * @param integer            $timestamp Timestamp.
	 * @param string|null        $format    Date/time format.
	 * @param \DateTimeZone|null $timezone  Timezone.
	 * @return string
	 */
	public static function format( $timestamp, $format = null, $timezone = null ) {
		if ( is_null( $format ) ) {
			$format = self::get_format();
		}
		if ( is_null( $timezone ) ) {
			$timezone = self::get_timezone();
		}
		$dt = new \DateTime();
		$dt->setTimestamp( $timestamp );
		$dt->setTimezone( $timezone );
		return $dt->format( $format );
	}

}