<?php // phpcs:ignore
/**
 * Dblist exchange between plugin and service
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Backup;

use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Helper\Db;

/**
 * Dblist exchange task class
 */
class Dblist extends Task {

	const ERROR_EMPTY_DB      = 'snapshot_empty_db';
	const WARNING_EMPTY_TABLE = 'snapshot_empty_table';

	const ERR_STRING_REQUEST_PARAMS = 'Request for DB list was not successful';

	/**
	 * Required request parameters, with their sanitization method
	 *
	 * @var array
	 */
	protected $required_params = array(
		'ex_rt'       => 'intval',
		'tables_left' => self::class . '::validate_tables',
	);

	/**
	 * All db tables.
	 *
	 * @var array
	 */
	private static $all_tables = array();

	/**
	 * Checks tables param against the actual db tables.
	 *
	 * @param array $tables tables left to be iterated.
	 *
	 * @return array|false
	 */
	public static function validate_tables( $tables ) {
		if ( empty( self::$all_tables ) ) {
			self::$all_tables = Db::get_all_database_tables();
		}

		foreach ( $tables as $table ) {
			if ( ! in_array( $table, self::$all_tables, true ) ) {
				// We can't go through with the db iteration.
				return false;
			}
		}

		return $tables;
	}

	/**
	 * Runs over the site's db tables and returns all info to the controller.
	 *
	 * @param array $args Info about what time the file iteration started and its timelimit.
	 */
	public function apply( $args = array() ) {
		$model = $args['model'];

		$this->get_tables( $model );

		if ( empty( $model->get( 'tables_left' ) ) ) {
			// So we are done. Say so.
			$model->set( 'is_done', true );
		}
	}

	/**
	 * Runs over the site's files and returns all info to the controller.
	 *
	 * @param object $model Model\Backup\Dblist instance.
	 */
	public function get_tables( $model ) {

		$tables = $model->get( 'tables_left' );
		if ( empty( $tables ) ) {
			$tables = empty( self::$all_tables ) ? Db::get_all_database_tables() : self::$all_tables;
			$tables = apply_filters( 'snapshot_tables_for_backup', $tables );
		}

		if ( empty( $tables ) ) {
			// Something went wrong with retrieving db tables. - Lets show an ERROR in the log and return error in service.
			$this->add_error(
				self::ERROR_EMPTY_DB,
				__( 'Empty db - Snapshot faced an issue when trying to get the db\'s tables.', 'snapshot' )
			);
			return false;
		}

		while ( ! empty( $tables ) ) {
			$item  = array();
			$table = array_pop( $tables );

			$item['name']     = $table;
			$item['checksum'] = $this->get_table_checksum( $table );
			if ( null === $item['checksum'] ) {
				// Something went wrong with getting the table's checksum. - Lets show an ERROR in the log.
				$this->add_error(
					self::WARNING_EMPTY_TABLE,
					/* translators: %s - table name */
					sprintf( __( 'Unreachable table %s: Snapshot faced an issue when trying to get the table\'s checksum.', 'snapshot' ), $table )
				);
				return false;
			}

			$item['size'] = $this->get_table_size( $table );

			$model->add( 'tables', $item );
			$model->set( 'tables_left', $tables );

			// If we have exceed the imposed time limit, lets pause the iteration here.
			if ( $model->has_exceeded_timelimit() ) {
				break;
			}
		}
		$model->set( 'db_name', Db::get_db_name() );
	}

	/**
	 * Calculates the checksum of the table.
	 *
	 * @param string $table Table to calculate its checksum.
	 *
	 * @return string $results['Checksum'] Checksum of table.
	 */
	public function get_table_checksum( $table ) {
		global $wpdb;

		$results = $wpdb->get_row( esc_sql( "CHECKSUM TABLE `{$table}`" ), ARRAY_A ); // db call ok; no-cache ok.

		return apply_filters( 'wp_snapshot_table_checksum', $results['Checksum'], $table );
	}

	/**
	 * Calculates the size of the table.
	 *
	 * @param string $table Table to calculate its checksum.
	 *
	 * @return string $results['Checksum'] Checksum of table.
	 */
	public function get_table_size( $table ) {
		global $wpdb;

		$db_name = Db::get_db_name();

		$table_size = $wpdb->get_var( $wpdb->prepare( 'SELECT ( DATA_LENGTH + INDEX_LENGTH ) FROM information_schema.tables WHERE table_schema = %s AND table_name LIKE %s', $db_name, $table ) ); // db call ok; no-cache ok.

		return $table_size;
	}
}