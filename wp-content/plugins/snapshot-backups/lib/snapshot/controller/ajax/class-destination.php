<?php // phpcs:ignore
/**
 * Snapshot controllers: Destination AJAX controller class
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Controller\Ajax;

use WPMUDEV\Snapshot4\Controller;
use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Helper;
use WPMUDEV\Snapshot4\Helper\Log;

/**
 * Destination AJAX controller class
 */
class Destination extends Controller\Ajax {

	/**
	 * Boots the controller and sets up event listeners.
	 */
	public function boot() {
		if ( ! is_admin() ) {
			return false;
		}

		// Request the service actions regarding destinations.
		add_action( 'wp_ajax_snapshot-get_destinations', array( $this, 'json_get_destinations' ) );
		add_action( 'wp_ajax_snapshot-delete_destination', array( $this, 'json_delete_destination' ) );
		add_action( 'wp_ajax_snapshot-update_destination', array( $this, 'json_update_destination' ) );
		add_action( 'wp_ajax_snapshot-activate_destination', array( $this, 'json_activate_destination' ) );
	}

	/**
	 * Handles requesting the service for a destination list.
	 */
	public function json_get_destinations() {
		$this->do_request_sanity_check( 'snapshot_get_destinations', self::TYPE_GET );

		$is_destination_page = isset( $_GET['destination_page'] ) ? intval( $_GET['destination_page'] ) : 0; // phpcs:ignore

		$data = array(
			'tpd_action' => 'get_destinations',
		);

		$task = new Task\Request\Destination( $data['tpd_action'] );

		$validated_data = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_data ) ) {
			wp_send_json_error( $validated_data );
		}

		$args                  = $validated_data;
		$args['request_model'] = new Model\Request\Destination();
		$result                = $task->apply( $args );

		if ( $task->has_errors() ) {
			foreach ( $task->get_errors() as $error ) {
				Log::error( $error->get_error_message() );
			}
			wp_send_json_error();
		}

		if ( $is_destination_page ) {
			$template = new Helper\Template();
			$schedule = Model\Schedule::get_schedule_info()['text'];

			foreach ( $result as $key => $item ) {
				$item['schedule'] = $schedule;
				$item['tpd_type'] = isset( $item['tpd_type'] ) ? $item['tpd_type'] : null;

				switch ( $item['tpd_type'] ) {
					case 'backblaze':
						$item['view_url'] = 'https://secure.backblaze.com/b2_buckets.htm';
						break;
					case 'digitalocean':
						$full_path        = explode( '/', $item['tpd_path'], 2 );
						$bucket           = $full_path[0];
						$item['view_url'] = 'https://cloud.digitalocean.com/spaces/' . $bucket . '/';
						break;
					case 'googlecloud':
						$item['view_url'] = 'https://console.cloud.google.com/storage/browser';
						break;
					case 'wasabi':
						$item['view_url'] = 'https://console.wasabisys.com';
						break;
					case 'gdrive':
						$item['view_url'] = 'https://drive.google.com/drive/u/1/folders/' . $item['tpd_path'] . '/';
						break;
					default:
						$item['view_url'] = 'https://console.aws.amazon.com/s3/buckets/' . $item['tpd_path'] . '/';
						break;
				}
				ob_start();
				$template->render( 'pages/destinations/row', $item );
				$result[ $key ]['html_row'] = ob_get_clean();
			}
		}

		wp_send_json_success(
			array(
				'destinations' => $result,
			)
		);
	}

	/**
	 * Handles requesting the service for a destination delete.
	 */
	public function json_delete_destination() {
		$this->do_request_sanity_check( 'snapshot_delete_destination', self::TYPE_POST );

		$data = array(
			'tpd_action' => 'delete_destination',
			'tpd_id'     => isset( $_POST['tpd_id'] ) ? $_POST['tpd_id'] : null, // phpcs:ignore
		);

		$task = new Task\Request\Destination( $data['tpd_action'] );

		$validated_data = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_data ) ) {
			wp_send_json_error( $validated_data );
		}

		$args                  = $validated_data;
		$args['request_model'] = new Model\Request\Destination();
		$task->apply( $args );

		if ( $task->has_errors() ) {
			foreach ( $task->get_errors() as $error ) {
				Log::error( $error->get_error_message() );
			}
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * Handles requesting the service for a destination update.
	 */
	public function json_update_destination() {
		$this->do_request_sanity_check( 'snapshot_update_destination', self::TYPE_POST );

		$data = array(
			'tpd_action'    => 'update_destination',
			'tpd_id'        => isset( $_POST['tpd_id'] ) ? $_POST['tpd_id'] : null, // phpcs:ignore
			'tpd_name'      => isset( $_POST['tpd_name'] ) ? $_POST['tpd_name'] : null, // phpcs:ignore
			'tpd_accesskey' => isset( $_POST['tpd_accesskey'] ) ? $_POST['tpd_accesskey'] : null, // phpcs:ignore
			'tpd_secretkey' => isset( $_POST['tpd_secretkey'] ) ? $_POST['tpd_secretkey'] : null, // phpcs:ignore
			'tpd_region'    => isset( $_POST['tpd_region'] ) ? $_POST['tpd_region'] : null, // phpcs:ignore
			'tpd_bucket'    => isset( $_POST['tpd_bucket'] ) ? $_POST['tpd_bucket'] : null, // phpcs:ignore
			'tpd_path'      => isset( $_POST['tpd_path'] ) ? $_POST['tpd_path'] : null, // phpcs:ignore
			'tpd_limit'     => isset( $_POST['tpd_limit'] ) ? $_POST['tpd_limit'] : null, // phpcs:ignore
			'tpd_type'      => isset( $_POST['tpd_type'] ) ? $_POST['tpd_type'] : null, // phpcs:ignore
		);

		// Update destination details.
		$task = new Task\Request\Destination( $data['tpd_action'], $data['tpd_type'] );

		$validated_data = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_data ) ) {
			wp_send_json_error( $validated_data );
		}

		$args                  = $validated_data;
		$args['request_model'] = new Model\Request\Destination();
		$result                = $task->apply( $args );

		if ( $task->has_errors() ) {
			foreach ( $task->get_errors() as $error ) {
				Log::error( $error->get_error_message() );
			}
			wp_send_json_error(
				array(
					'api_response' => $result,
				)
			);
		}

		wp_send_json_success();
	}

	/**
	 * Handles requesting the service for a destination activation.
	 */
	public function json_activate_destination() {
		$this->do_request_sanity_check( 'snapshot_update_destination', self::TYPE_POST );

		$data = array(
			'tpd_action'  => 'activate_destination',
			'tpd_id'      => isset( $_POST['tpd_id'] ) ? $_POST['tpd_id'] : null, // phpcs:ignore
			'aws_storage' => isset( $_POST['aws_storage'] ) ? intval( $_POST['aws_storage'] ) : null, // phpcs:ignore
		);

		$task = new Task\Request\Destination( $data['tpd_action'] );

		$validated_data = $task->validate_request_data( $data );
		if ( is_wp_error( $validated_data ) ) {
			wp_send_json_error( $validated_data );
		}

		$args                  = $validated_data;
		$args['request_model'] = new Model\Request\Destination();
		$result                = $task->apply( $args );

		if ( $task->has_errors() ) {
			foreach ( $task->get_errors() as $error ) {
				Log::error( $error->get_error_message() );
			}
			wp_send_json_error();
		}

		wp_send_json_success(
			array(
				'api_response' => $result,
			)
		);
	}

}