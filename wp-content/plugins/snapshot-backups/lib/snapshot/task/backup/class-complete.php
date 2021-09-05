<?php // phpcs:ignore
/**
 * Completed backup email notifications.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Task\Backup;

use WPMUDEV\Snapshot4\Task;
use WPMUDEV\Snapshot4\Helper\Template;
use WPMUDEV\Snapshot4\Helper\Log;

/**
 * Finish backup task class
 */
class Complete extends Task {

	/**
	 * Send email notifications when a backup completes
	 *
	 * @param array $args Task args.
	 */
	public function apply( $args = array() ) {
		foreach ( $args['recipients'] as $recipient ) {
			$this->send(
				$recipient['email'],
				$recipient['name'],
				$args['frequency'],
				$args['backup_id']
			);
		}
	}

	/**
	 * Send email to specified recipient when a backup completes
	 *
	 * @param string $email     Recipient email address.
	 * @param string $name      Recipient first name.
	 * @param string $frequency Backup frequency.
	 * @param string $backup_id Backup ID.
	 */
	private function send( $email, $name, $frequency, $backup_id ) {
		$site_host_html = wp_parse_url( get_site_url(), PHP_URL_HOST );

		$params = array(
			'name'       => $name,
			'type'       => 'manual' === $frequency
				? __( 'The manual', 'snapshot' )
				: __( 'The scheduled', 'snapshot' ),
			'site'       => $site_host_html,
			'backup_url' => network_admin_url() . 'admin.php?page=snapshot-backups#backups-' . $backup_id,
		);

		/* translators: %s - website URL */
		$subject  = sprintf( __( 'The backup for %s was created and stored successfully.', 'snapshot' ), $site_host_html );
		$template = new Template();
		ob_start();
		$template->render( 'mail/backup-complete', $params );
		$message = ob_get_clean();

		$from        = 'noreply@' . $site_host_html;
		$from_name   = 'WPMU DEV Team';
		$from_header = "From: $from_name <$from>";

		$result = wp_mail( $email, $subject, $message, array( 'Content-Type: text/html', $from_header ) );
		if ( ! $result ) {
			/* translators: %s - "mail to" address */
			Log::error( sprintf( __( 'Unable to send email to %s', 'snapshot' ), $email ), array(), $backup_id );
		}
	}
}