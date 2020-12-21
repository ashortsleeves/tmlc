<?php // phpcs:ignore
/**
 * Get/Set region task.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Request;

use WPMUDEV\Snapshot4\Task;

/**
 * Gets/sets region from/to the service task class.
 */
class Region extends Task {

	/**
	 * Required request parameters, with their sanitization method
	 *
	 * @var array
	 */
	protected $required_params = array(
		'action' => self::class . '::validate_action',
	);

	/**
	 * Validates region action.
	 *
	 * @param string $action Action coming from controller.
	 *
	 * @return string
	 */
	public static function validate_action( $action ) {
		return ( 'get' === $action || 'set' === $action ) ? $action : null;
	}

	/**
	 * Get/Set region.
	 *
	 * @param array $args Arguments coming from the ajax call.
	 */
	public function apply( $args = array() ) {
		$request_model = $args['request_model'];
		$action        = $args['action'];
		$region        = isset( $args['region'] ) ? $args['region'] : null;

		$request_model->set( 'action', $action );

		if ( 'get' === $action ) {
			// site id *** have no region set yet.
			$request_model->set( 'ok_codes', array( 404 ) );

			$region = $request_model->get_region();

			if ( $request_model->add_errors( $this ) ) {
				return false;
			}

			return $region;
		}

		if ( 'set' === $action ) {
			$response = $request_model->set_region( $region );

			if ( $request_model->add_errors( $this ) ) {
				return false;
			}

			$result = json_decode( wp_remote_retrieve_body( $response ), true );
			return isset( $result['bu_region'] ) ? $result['bu_region'] : null;
		}

	}
}