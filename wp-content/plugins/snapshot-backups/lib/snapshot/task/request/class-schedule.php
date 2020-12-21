<?php // phpcs:ignore
/**
 * Setting, updating, deleting etc. of backup schedules between plugin and service.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Request;

use WPMUDEV\Snapshot4;
use WPMUDEV\Snapshot4\Task;

/**
 * Schedule requesting class
 */
class Schedule extends Task {

	const OPTION_BACKUP_SCHEDULE    = 'wp_snapshot_backup_schedule';
	const ERR_STRING_REQUEST_PARAMS = 'Request for a backup schedule was not successful';

	/**
	 * Required request parameters, with their sanitization method
	 *
	 * @var array
	 */
	protected $required_params = array(
		'frequency' => 'sanitize_text_field',
		'status'    => 'sanitize_text_field',
		'time'      => self::class . '::validate_time',
		'files'     => 'sanitize_text_field',
		'tables'    => 'sanitize_text_field',
	);

	/**
	 * Checks time format (H:i)
	 *
	 * @param string $time Time string.
	 *
	 * @return string|false
	 */
	public static function validate_time( $time ) {
		return preg_match( '/^(([01]\d)|(2[0-3])):[0-5]\d$/', $time ) ? $time : false;
	}

	/**
	 * Places the request calls to the service for processing the backup schedule.
	 *
	 * @param array $args Arguments coming from the ajax call.
	 */
	public function apply( $args = array() ) {
		$request_model  = $args['request_model'];
		$schedule_model = $args['schedule_model'];
		$action         = $args['action'];

		$stored_schedule = get_site_option( self::OPTION_BACKUP_SCHEDULE );
		if ( 'delete' === $action && ( ( isset( $stored_schedule['bu_status'] ) && 'inactive' === $stored_schedule['bu_status'] ) || ! $stored_schedule ) ) {
			return;
		}

		if ( 'create' === $action ) {
			// We must ensure there's not another schedule remotely, before creating one.

			// if site id *** has no schedules.
			$request_model->set( 'ok_codes', array( 404 ) );

			$first_response = $request_model->schedule_request( 'get_status_all', $schedule_model );

			if ( $request_model->add_errors( $this ) ) {
				return false;
			}

			$first_response_data = json_decode( wp_remote_retrieve_body( $first_response ), true );
			if ( is_array( $first_response_data ) && isset( $first_response_data[0]['created_at'] ) ) {
				// So, a remote schedule exists, lets first try to match the local and remote schedules, by deleting both.
				Snapshot4\Main::handle_schedules();

				// OK, now retry.
				$first_response = $request_model->schedule_request( 'get_status_all', $schedule_model );

				if ( $request_model->add_errors( $this ) ) {
					return false;
				}

				$second_response_data = json_decode( wp_remote_retrieve_body( $first_response ), true );
				if ( is_array( $second_response_data ) && isset( $second_response_data[0]['created_at'] ) ) {
					$this->add_error(
						'snapshot_schedule_duplication_attempt',
						__( 'Snapshot tried to create a schedule while one already exists API-side.', 'snapshot' )
					);
					return false;
				}
			}
		}

		$request_model->set( 'ok_codes', array() );

		$response = $request_model->schedule_request( $action, $schedule_model );
		if ( $request_model->add_errors( $this ) ) {
			return false;
		}

		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		$schedule_model->set_data( $response_data );

		switch ( strtolower( $action ) ) {
			case 'create':
			case 'get_status':
				update_site_option( self::OPTION_BACKUP_SCHEDULE, $schedule_model->get_data() );
				break;
			case 'update':
			case 'delete':
				// The "service" does not return schedule_id, read it from DB.
				$active_schedule = get_site_option( self::OPTION_BACKUP_SCHEDULE );
				update_site_option(
					self::OPTION_BACKUP_SCHEDULE,
					array_merge(
						$schedule_model->get_data(),
						array(
							'schedule_id' => $active_schedule['schedule_id'],
						)
					)
				);
				break;
		}

		return $response_data;
	}
}