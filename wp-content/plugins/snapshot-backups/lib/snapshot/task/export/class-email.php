<?php // phpcs:ignore
/**
 * Sends email with export link retrieved from the service.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Export;

use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Helper;
use WPMUDEV\Snapshot4\Helper\Log;

/**
 * Send export email task class
 */
class Email extends Task {

	const ERR_STRING_REQUEST_PARAMS = 'Request for sending export email was not successful';

	/**
	 * Required request parameters, with their sanitization method
	 *
	 * @var array
	 */
	protected $required_params = array(
		'snapshot_id'   => 'sanitize_text_field',
		'export_link'   => self::class . '::validate_export_link',
		'email_account' => self::class . '::validate_email_account',
		'display_name'  => self::class . '::validate_display_name',
	);

	/**
	 * Validates export link coming from the service.
	 *
	 * @param string $export_link Link to export backup.
	 *
	 * @return string
	 */
	public static function validate_export_link( $export_link ) {
		return filter_var( $export_link, FILTER_VALIDATE_URL );
	}

	/**
	 * Validates email_account coming from the service.
	 *
	 * @param string $email_account Recipient email account.
	 *
	 * @return string
	 */
	public static function validate_email_account( $email_account ) {
		return filter_var( $email_account, FILTER_VALIDATE_EMAIL );
	}

	/**
	 * Validates display_name coming from the service.
	 *
	 * @param string $display_name Name to be displayed in the email.
	 *
	 * @return string
	 */
	public static function validate_display_name( $display_name ) {
		return empty( $display_name ) ? $display_name : sanitize_text_field( $display_name );
	}

	/**
	 * Runs over the site's files and returns all info to the controller.
	 *
	 * @param array $args Info about what time the file iteration started and its timelimit.
	 */
	public function apply( $args = array() ) {
		$model = $args['model'];

		if ( isset( $model->get( 'export' )['email_account'] ) && ! empty( $model->get( 'export' )['email_account'] ) ) {
			$mail_to = $model->get( 'export' )['email_account'];
		} else {
			$mail_to = get_site_option( 'wdp_un_auth_user' );
		}

		if ( empty( $mail_to ) ) {
			Log::error( __( 'Unable to send email because user\'s email is empty', 'snapshot' ) );
			return new \WP_Error( 'empty_wdp_un_auth_user', 'unable to send email because user\'s email is empty' );
		}

		if ( isset( $model->get( 'export' )['display_name'] ) && ! empty( $model->get( 'export' )['display_name'] ) ) {
			$name = $model->get( 'export' )['display_name'];
		} else {
			$wdp_un_profile_data = get_site_option( 'wdp_un_profile_data' );
			$name                = isset( $wdp_un_profile_data['profile']['name'] ) ? $wdp_un_profile_data['profile']['name'] : '';
		}

		$website_url = wp_parse_url( get_site_url(), PHP_URL_HOST );

		/* translators: %s - website URL */
		$subject  = sprintf( __( 'The backup for %s is ready to download!', 'snapshot' ), $website_url );
		$template = new Helper\Template();
		ob_start();
		$template->render(
			'mail/backup-export-done',
			array(
				'name'        => $name,
				'website_url' => $website_url,
				'export_link' => $model->get( 'export' )['export_link'],
			)
		);
		$message = ob_get_clean();

		$from        = 'noreply@' . wp_parse_url( get_site_url(), PHP_URL_HOST );
		$from_name   = 'Snapshot';
		$from_header = "From: $from_name <$from>";

		$result = wp_mail( $mail_to, $subject, $message, array( 'Content-Type: text/html', $from_header ) );
		if ( ! $result ) {
			Log::error( __( 'Unable to send email', 'snapshot' ) );
			return new \WP_Error( 'send_mail_error', 'unable to send email' );
		}
	}
}