<?php // phpcs:ignore
/**
 * Fetching a backup zipstream of requested tables from the plugin to the service.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Backup\Zipstream;

use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Helper\Db;
use WPMUDEV\Snapshot4\Helper\Settings;

/**
 * Backup zipstream task class
 */
class Tables extends Task\Backup\Zipstream {

	const ERR_CLEAN_TEMP_FILE       = 'snapshot_is_done_tables';
	const ERR_STRING_REQUEST_PARAMS = 'Request for table zipstream was not successful';

	/**
	 * Required request parameters, with their sanitization method
	 *
	 * @var array
	 */
	protected $required_params = array(
		'db_chunk'     => 'intval',
		'table'        => self::class . '::validate_table',
		'starting_row' => 'intval',
	);

	/**
	 * All db tables.
	 *
	 * @var array
	 */
	private static $all_tables = array();

	/**
	 * Checks table param against the actual db tables.
	 *
	 * @param array $table table to be exported.
	 *
	 * @return array|false
	 */
	public static function validate_table( $table ) {
		if ( empty( self::$all_tables ) ) {
			self::$all_tables = Db::get_all_database_tables();
		}

		if ( ! in_array( $table, array_column( self::$all_tables, 'name' ), true ) ) {
			// We can't go through with the db iteration.
			return false;
		}

		return $table;
	}

	/**
	 * Runs over the requested tables and builds a zipstream out of them.
	 *
	 * @param array $args Info about the current table requesting, like the rows we're gonna split the export into , what we're continuing to export from, etc.
	 */
	public function apply( $args = array() ) {
		require dirname( SNAPSHOT_PLUGIN_FILE ) . '/vendor/autoload.php';

		$model = $args['model'];

		// Clear the temporary sql file from previous exports.
		if ( ! $model->clear_temp_sql_file() ) {
			/* translators: %s - Temp sql file name */
			$this->add_error( self::ERR_CLEAN_TEMP_FILE, sprintf( __( 'Snapshot couldn\'t clear out the temporary sql file before exporting the db: %s', 'snapshot' ), $model->get_temp_sql_filename() ) );
			return false;
		}

		// Enable output of HTTP headers.
		$zipstream_options = new \ZipStream\Option\Archive();
		$zipstream_options->setSendHttpHeaders( true );
		if ( Settings::get_zipstream_flush_buffer() ) {
			$zipstream_options->setFlushOutput( true );
		}

		// Actually zipstream the rows of the requested table.
		$this->zipstream_table( $model, $zipstream_options );
	}

	/**
	 * Builds a zipstream out of exporting the requested table from the row we left off to the row the chunk arg is allowing us to.
	 *
	 * @param object $model   Model\Backup\Zipstream\Tables instance.
	 * @param object $options \ZipStream\Option\Archive instance.
	 */
	public function zipstream_table( $model, $options ) {
		global $wpdb;

		// Create a new zipstream object.
		$zip   = new \ZipStream\ZipStream( $model->name_zipstream(), $options );
		$table = $model->get( 'requested_table' );

		$table_rows = $wpdb->get_row( esc_sql( "SELECT count(*) as total_rows FROM `{$table}`;" ) ); // db call ok; no-cache ok.
		// @TODO: Improve by calculating the above in the beginning of the backup and make it persistent.

		// Export the table.
		$table_starting_row = $model->get( 'starting_row' );
		$result             = $model->backup_table( $table, $model->get( 'db_chunk' ), $table_rows->total_rows, $table_starting_row );

		$model->set( 'done', $result['done'] );
		$model->set( 'current_row', $result['current_row'] );

		// And add it to the zipstream.
		$zip->addFileFromPath( $table . '.sql', $model->get_temp_sql_filename() );

		// But also include the manifest file that contains the info on where we left off.
		$manifest_contents['table']       = $table;
		$manifest_contents['done']        = $result['done'];
		$manifest_contents['current_row'] = $result['current_row'];
		$manifest_contents['db_prefix']   = $wpdb->base_prefix;

		$zip->addFile( 'manifest.txt', wp_json_encode( $manifest_contents, JSON_FORCE_OBJECT ) );

		// finish the zip stream.
		$zip->finish();
	}
}