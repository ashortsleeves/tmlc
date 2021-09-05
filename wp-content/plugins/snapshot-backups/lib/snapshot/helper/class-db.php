<?php // phpcs:ignore
/**
 * Database helper class
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Helper;

/**
 * Helper class
 */
class Db {

	/**
	 * Fetches all database tables.
	 *
	 * @uses $wpdb global
	 *
	 * @return array
	 */
	public static function get_all_database_tables() {
		global $wpdb;

		$db_name = self::get_db_name();
		if ( empty( $db_name ) && defined( 'DB_NAME' ) ) {
			$db_name = DB_NAME;
		}
		if ( empty( $db_name ) ) {
			return array();
		}

		$all_tables = $wpdb->get_results( $wpdb->prepare( 'SELECT table_name as table_name, table_type as table_type FROM information_schema.tables WHERE table_schema = %s ORDER BY table_type = "VIEW"', $db_name ) ); // db call ok; no-cache ok.
		if ( empty( $all_tables ) ) {
			return array();
		}

		array_walk(
			$all_tables,
			function ( &$row ) {
				$row = array(
					'name'    => $row->table_name,
					'is_view' => 'VIEW' === $row->table_type,
				);
			}
		);

		return $all_tables;
	}

	/**
	 * Get the database name for multisites included.
	 *
	 * @return string
	 */
	public static function get_db_name() {

		global $wpdb;

		$db_class = get_class( $wpdb );

		if ( 'm_wpdb' === $db_class ) {

			$test_sql   = 'SELECT ID FROM ' . $wpdb->prefix . 'posts LIMIT 1';
			$query_data = $wpdb->analyze_query( $test_sql );
			if ( isset( $query_data['dataset'] ) ) {

				global $db_servers;
				if ( isset( $db_servers[ $query_data['dataset'] ][0]['name'] ) ) {
					return $db_servers[ $query_data['dataset'] ][0]['name'];
				}
			}
		} else {
			return DB_NAME;
		}
	}

	/**
	 * Better addslashes for SQL queries.
	 * Taken from phpMyAdmin.
	 *
	 * @param string $a_string The string to be addslashed.
	 * @param bool   $is_like If it's like arg.
	 *
	 * @return string
	 */
	public static function sql_addslashes( $a_string = '', $is_like = false ) {
		if ( $is_like ) {
			$a_string = str_replace( '\\', '\\\\\\\\', $a_string );
		} else {
			$a_string = str_replace( '\\', '\\\\', $a_string );
		}

		return str_replace( '\'', '\\\'', $a_string );
	}

	/**
	 * Add backquotes to tables and db-names in
	 * SQL queries. Taken from phpMyAdmin.
	 *
	 * @param string $a_name The string to be backquoted.
	 *
	 * @return string
	 */
	public static function backquote( $a_name ) {
		if ( ! empty( $a_name ) && '*' !== $a_name ) {
			return '`' . $a_name . '`';
		} else {
			return $a_name;
		}
	}
}