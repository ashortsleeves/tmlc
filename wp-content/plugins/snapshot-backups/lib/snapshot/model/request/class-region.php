<?php // phpcs:ignore
/**
 * Snapshot models: Get/set region request model
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Model\Request;

use WPMUDEV\Snapshot4\Model;

/**
 * Get/set region request model class
 */
class Region extends Model\Request {
	const DEFAULT_ERROR = 'snapshot_region_service_unreachable';

	/**
	 * Set region request endpoint
	 *
	 * @var string
	 */
	protected $endpoint = 'creds';

	/**
	 * Returns action string for logger
	 *
	 * @return string
	 */
	protected function get_action_string() {
		if ( 'set' === $this->get( 'action' ) ) {
			return __( 'set region', 'snapshot' );
		}

		return __( 'get region', 'snapshot' );
	}

	/**
	 * Make request to get the stored region from system-side.
	 *
	 * @return array|mixed|object
	 */
	public function get_region() {
		$method         = 'get';
		$this->endpoint = 'credsls';
		$path           = $this->get_api_url();

		$data = array();

		$this->request( $path, $data, $method );

		if ( 404 === $this->get_response_code() ) {
			// No region set yet.
			return null;
		}

		$response = json_decode( $this->response_body, true );

		return isset( $response['bu_region'] ) ? $response['bu_region'] : null;
	}

	/**
	 * Make request to set the stored region system-side.
	 *
	 * @param string $region Region to be stored.
	 *
	 * @return array|mixed|object
	 */
	public function set_region( $region ) {
		$method = 'post';
		$path   = $this->get_api_url();

		$data              = array();
		$data['bu_region'] = $region;

		$response = $this->request( $path, $data, $method );

		return $response;
	}
}