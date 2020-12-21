<?php // phpcs:ignore
/**
 * Snapshot controllers: Schedule endpoints for Hub
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Controller;

use WPMUDEV\Snapshot4\Controller;
use WPMUDEV\Snapshot4\Main;
use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Helper;
use WPMUDEV\Snapshot4\Helper\Log;
use WPMUDEV\Snapshot4\Helper\Settings;

/**
 * Schedule endpoints for Hub handling controller class
 */
class Hub extends Controller\Service {

	/**
	 * Gets the list of known service actions
	 *
	 * @return array Known actions
	 */
	public function get_known_actions() {
		$known = array(
			self::HUB_END_GET_SCHEDULE,
			self::HUB_END_SET_SCHEDULE,
			self::HUB_END_DEL_SCHEDULE,
			self::HUB_END_START_BACKUP,
			self::HUB_END_DELETE_BACKUPS,
			self::HUB_END_DELETE_CACHE,
			self::HUB_END_DELETE_SETTINGS_CACHE,
		);
		return $known;
	}

	/**
	 * Gets schedule details from local DB.
	 *
	 * @param object $params Parameters of the current request.
	 * @param string $action Current action.
	 * @param object $request Current request.
	 */
	public function json_hub_end_get_schedule( $params, $action, $request = false ) {
		Log::info( __( 'The Hub has requested info for the current schedule.', 'snapshot' ) );

		$current_schedule = get_site_option( 'wp_snapshot_backup_schedule' );

		$response = (object) array(
			'current_schedule'     => $current_schedule,
			'site_timezone_string' => get_site_option( 'timezone_string' ),
			'site_gmt_offset'      => get_site_option( 'gmt_offset' ),
		);

		return $this->send_response_success( $response, $request );
	}

	/**
	 * Sets schedule both locally and service-side.
	 *
	 * @param object $params Parameters of the current request.
	 * @param string $action Current action.
	 * @param object $request Current request.
	 */
	public function json_hub_end_set_schedule( $params, $action, $request = false ) {
		$args = (array) $params;

		// Detect active schedule or not.
		$active_schedule = get_site_option( 'wp_snapshot_backup_schedule' );

		// Only if there's no valid schedule_id, do we create new schedule, in all other cases (even when stored schedule is inactive, we update).
		if ( isset( $active_schedule['schedule_id'] ) && $active_schedule['schedule_id'] ) {
			$schedule_action = 'update';
		} else {
			$schedule_action = 'create';
		}

		$args['schedule_action'] = $schedule_action;
		$args['new_schedule']    = isset( $args['new_schedule'] ) ? json_encode( $args['new_schedule'] ) : null;

		$request_model = new Model\Request\Schedule();
		$request_data  = $request_model->validate_schedule_data( $args );

		if ( is_wp_error( $request_data ) ) {
			return $this->send_response_error( $request_data, $request );
		}

		$hub_date_time_zone = ! empty( $args['hub_timezone_string'] ) ? new \DateTimeZone( $args['hub_timezone_string'] ) : Model\Schedule::convert_to_DateTimeZone( $args['hub_gmt_offset'] );

		if ( ! empty( $request_data['data']['frequency'] ) ) {
			$converted = Model\Schedule::convert_timezone(
				$request_data['data']['frequency'],
				$hub_date_time_zone,
				new \DateTimeZone( 'UTC' ),
				$request_data['data']['time'],
				isset( $request_data['data']['frequency_weekday'] ) ? $request_data['data']['frequency_weekday'] : null,
				isset( $request_data['data']['frequency_monthday'] ) ? $request_data['data']['frequency_monthday'] : null
			);

			$request_data['data']['time']               = $converted['time'];
			$request_data['data']['frequency_weekday']  = $converted['weekday'];
			$request_data['data']['frequency_monthday'] = $converted['monthday'];
		}

		$schedule_model = new Model\Schedule( $request_data['data'] );

		$args                   = array();
		$args['request_model']  = $request_model;
		$args['schedule_model'] = $schedule_model;
		$args['action']         = $request_data['schedule_action'];

		$task = new Task\Request\Schedule();

		$api_response = $task->apply( $args );

		if ( $task->has_errors() ) {
			foreach ( $task->get_errors() as $error ) {
				return $this->send_response_error( $error, $request );
			}
		}

		$response = (object) array(
			'api_response' => $api_response,
			'schedule'     => Model\Schedule::get_schedule_info(),
		);

		return $this->send_response_success( $response, $request );
	}

	/**
	 * Triggers a new manual backup.
	 *
	 * @param object $params Parameters of the current request.
	 * @param string $action Current action.
	 * @param object $request Current request.
	 */
	public function json_hub_end_start_backup( $params, $action, $request = false ) {
		Log::info( __( 'The Hub requested to trigger a new backup.', 'snapshot' ) );
		$data = (array) $params;

		$task = new Task\Request\Manual();

		$validated_params = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_params ) ) {
			return $this->send_response_error( $validated_params, $request );
		}

		$model = new Model\Request\Manual();

		$args          = $validated_params;
		$args['model'] = $model;
		$result        = $task->apply( $args );

		if ( $task->has_errors() ) {
			$errors = array();
			foreach ( $task->get_errors() as $error ) {
				$errors[] = $error;
				Log::error( $error->get_error_message() );
				return $this->send_response_error( $error, $request );
			}
		}

		$response = (object) array(
			'backup_running' => $result,
		);

		Log::info( __( 'Communication with the service API, in order to create manual backup, was successful.', 'snapshot' ) );

		return $this->send_response_success( $response, $request );
	}

	/**
	 * Deletes all backups for the site.
	 *
	 * @param object $params Parameters of the current request.
	 * @param string $action Current action.
	 * @param object $request Current request.
	 */
	public function json_hub_end_delete_backups( $params, $action, $request = false ) {
		$task = new Task\Request\Delete();

		$args                  = array();
		$args['request_model'] = new Model\Request\Delete();
		$task->apply( $args );

		if ( $task->has_errors() ) {
			foreach ( $task->get_errors() as $error ) {
				return $this->send_response_error( $error, $request );
			}
		}

		$response = (object) array(
			'backups_deleted' => true,
		);

		return $this->send_response_success( $response, $request );
	}

	/**
	 * Deletes transient of the backup listing.
	 *
	 * @param object $params Parameters of the current request.
	 * @param string $action Current action.
	 * @param object $request Current request.
	 */
	public function json_hub_end_delete_cache( $params, $action, $request = false ) {
		delete_transient( 'snapshot_listed_backups' );

		$response = (object) array(
			'cache_deleted' => true,
		);

		return $this->send_response_success( $response, $request );
	}

	/**
	 * Deletes transient of the "extra security step" option.
	 *
	 * @param object $params Parameters of the current request.
	 * @param string $action Current action.
	 * @param object $request Current request.
	 */
	public function json_hub_end_delete_settings_cache( $params, $action, $request = false ) {
		delete_transient( 'snapshot_extra_security_step' );

		$response = (object) array(
			'cache_deleted' => true,
		);

		return $this->send_response_success( $response, $request );
	}
}