<?php // phpcs:ignore
/**
 * Snapshot models: Google Drive destination requests model
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Model\Request\Destination;

use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Helper\Api;
use WPMUDEV\Snapshot4\Model\Env;

/**
 * Destination requests model class
 */
class Googledrive extends Model\Request\Destination {

	/**
	 * Sends auth code to retrieve access tokens for Google Drive destination.
	 *
	 * @param array $data Config data.
	 *
	 * @return array|mixed|object tokens for that google account if auth flow was successful.
	 */
	public function generate_tokens( $data ) {
		$method         = 'post';
		$this->endpoint = 'generatetoken';
		$path           = $this->get_api_url();

		$response = $this->request( $path, $data, $method );

		return $response;
	}

	/**
	 * Creates Oauth link.
	 *
	 * @return string Oauth link.
	 */
	public static function create_oauth_link() {
		require_once dirname( SNAPSHOT_PLUGIN_FILE ) . '/vendor/autoload.php';

		$client = new \Google_Client();
		$client->setClientId( '632110916777-rc8t4tn0jf4heaiv4ln0ml3b87clmhod.apps.googleusercontent.com' );
		$client->setAccessType( 'offline' );
		$client->setRedirectUri( 'https://wpmudev.com/api/snapshot/v2/gdrive-handler' );
		$client->setApprovalPrompt( 'force' );
		$client->setScopes(
			array(
				\Google_Service_Drive::DRIVE_FILE,
				'openid',
				'https://www.googleapis.com/auth/userinfo.email',
			)
		);

		$site_hash = hash_hmac( 'sha256', untrailingslashit( network_site_url() ), Api::get_api_key() );

		$redirect_after_login = add_query_arg( 'snapshot_gdrive_nonce', wp_create_nonce( 'snapshot_gd_connection' ), network_admin_url() . 'admin.php?page=snapshot-destinations&snapshot_action=google-auth&snapshot_site_id=' . Api::get_site_id() . '&snapshot_site_hash=' . $site_hash );
		$client->setState( urlencode($redirect_after_login ) ); // phpcs:ignore
		$auth_url = filter_var( $client->createAuthUrl(), FILTER_SANITIZE_URL );

		return $auth_url;
	}
}