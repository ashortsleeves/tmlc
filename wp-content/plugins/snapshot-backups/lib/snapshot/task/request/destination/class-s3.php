<?php // phpcs:ignore
/**
 * S3 Destination backup task.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Request\Destination;

use WPMUDEV\Snapshot4\Task;

/**
 * S3 Destination backup task class.
 */
class S3 extends Task\Request\Destination {

	/**
	 * Required request parameters, with their sanitization method
	 *
	 * @var array
	 */
	protected $required_params = array(
		'tpd_accesskey' => 'sanitize_text_field',
		'tpd_secretkey' => 'sanitize_text_field',
		'tpd_region'    => 'sanitize_text_field',
		'tpd_action'    => 'sanitize_text_field',
	);

	/**
	 * Constructor
	 *
	 * @param string $action Action to be performed for the destination.
	 */
	public function __construct( $action ) {
		if ( 'test_connection_final' === $action ) {
			$required_params            = $this->required_params;
			$additional_required_params = array(
				'tpd_path'  => 'sanitize_text_field',
				'tpd_name'  => 'sanitize_text_field',
				'tpd_limit' => 'intval',
				'tpd_save'  => 'intval',
				'tpd_type'  => 'sanitize_text_field',
			);

			$this->required_params = array_merge( $required_params, $additional_required_params );
		} elseif ( 'load_buckets' === $action ) {
			$this->required_params = array_merge(
				$this->required_params,
				array(
					'tpd_type' => 'sanitize_text_field',
				)
			);
		}
	}

	/**
	 * Request for destination.
	 *
	 * @param array $args Arguments coming from the ajax call.
	 */
	public function apply( $args = array() ) {
		$request_model = $args['request_model'];

		$ok_codes      = $request_model->get( 'ok_codes' );
		$empty_for_404 = false;
		switch ( $args['tpd_action'] ) {
			case 'load_buckets':
				$data     = array(
					'tpd_accesskey' => $args['tpd_accesskey'],
					'tpd_secretkey' => $args['tpd_secretkey'],
					'tpd_region'    => $args['tpd_region'],
					'tpd_type'      => $args['tpd_type'],
				);
				$response = $request_model->load_buckets( $data );
				break;
			case 'test_connection_final':
				$data     = array(
					'aws_storage'   => 1,
					'tpd_accesskey' => $args['tpd_accesskey'],
					'tpd_secretkey' => $args['tpd_secretkey'],
					'tpd_region'    => $args['tpd_region'],
					'tpd_path'      => $args['tpd_path'],
					'tpd_name'      => $args['tpd_name'],
					'tpd_limit'     => $args['tpd_limit'],
					'tpd_save'      => $args['tpd_save'],
					'tpd_type'      => $args['tpd_type'],
				);
				$response = $request_model->test_connection_final( $data );
				break;
			default:
				break;
		}
		$request_model->set( 'ok_codes', $ok_codes );

		$request_model->add_errors( $this );

		$result = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $empty_for_404 && 404 === wp_remote_retrieve_response_code( $response ) ) {
			$result = array();
		}

		return $result;
	}
}