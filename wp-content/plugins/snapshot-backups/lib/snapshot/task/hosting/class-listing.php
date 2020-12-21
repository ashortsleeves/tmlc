<?php // phpcs:ignore
/**
 * Listing of hosting backups.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Hosting;

/**
 * Listing hosting backups requesting class
 */
class Listing extends Common {

	const CONTEXT_AUTOMATE = 'Automate Update';

	/**
	 * Required request parameters, with their sanitization method
	 *
	 * @var array
	 */
	protected $required_params = array();

	/**
	 * Places the request calls to the Hosting API for processing the listed backups.
	 *
	 * @param array $args Arguments coming from the ajax call.
	 */
	public function apply( $args = array() ) {
		$backups = null;

		$response = $this->request( 'get', 'backups' );
		if ( ! is_wp_error( $response ) ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			if ( 200 === $response_code ) {
				$backups = json_decode( $response_body, true );
			}
		}

		$backups = apply_filters(
			'snapshot4_hosting_backups',
			$backups
		);

		if ( is_null( $backups ) ) {
			return;
		}

		$result = array();
		foreach ( $backups as $item ) {
			$timestamp = $this->parse_time( $item['creation_time'] );
			$result[]  = array(
				'key'               => $item['Key'],
				'domain'            => $item['domain'],
				'is_automate'       => self::CONTEXT_AUTOMATE === $item['context'],
				'size'              => $item['size'],
				'created_at'        => $timestamp,
				/* translators: %s - difference between two timestamps */
				'created_ago_human' => sprintf( __( '%s ago', 'snapshot' ), human_time_diff( $timestamp ) ),
				'backup_id'         => explode( '@', $item['Key'] )[1],
			);
		}

		usort(
			$result,
			function ( $item1, $item2 ) {
				return $item2['created_at'] - $item1['created_at'];
			}
		);

		return $result;
	}

	/**
	 * Parse time from string
	 *
	 * @param string $time Time in 'Y-m-d\TH:i:sP' format.
	 * @return int|null
	 */
	private function parse_time( $time ) {
		$dt = \DateTime::createFromFormat( \DateTime::ATOM, $time );
		return $dt ? $dt->getTimestamp() : null;
	}
}