<?php // phpcs:ignore
/**
 * Failed backup email notifications.
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
class Fail extends Task {

	const ERROR_HUB_INFO_RESPONDED_INVALID_URI      = 'snapshot_failed_HubInfoRespondedInvalidURI';
	const ERROR_HUB_INFO_RESPONDED_ERROR            = 'snapshot_failed_HubInfoRespondedError';
	const ERROR_USER_INFO_NOT_RETURN                = 'snapshot_failed_UserInfoNotReturn';
	const ERROR_STORAGE_LIMIT                       = 'snapshot_failed_storage_limit';
	const ERROR_FETCH_FILESLIST                     = 'snapshot_failed_FetchFileslist';
	const ERROR_SITE_NOT_RESPONDED_ZIPSTREAM_ERROR  = 'snapshot_failed_SiteNotRespondedZipstreamError';
	const ERROR_ZIPSTREAM_FILE_MISSING              = 'snapshot_failed_ZipstreamFileMissing';
	const ERROR_SITE_NOT_RESPONDED_LARGE_FILE_ERROR = 'snapshot_failed_SiteNotRespondedLargeFileError';
	const ERROR_WRAPPER_DBLIST_FETCH                = 'snapshot_failed_Wrapper_DBlistFetch';
	const ERROR_WRAPPER_TABLE_ZIPSTREAM             = 'snapshot_failed_Wrapper_TableZipstream';
	const ERROR_TOO_LARGE_TABLE_HANGED              = 'snapshot_failed_TooLargeTableHanged';
	const ERROR_SITE_NOT_RESPONDED_ERROR            = 'snapshot_failed_SiteNotRespondedError';
	const ERROR_UNKNOWN_ERROR                       = 'snapshot_failed_UnknownError';
	const ERROR_GENERIC_ERROR                       = 'snapshot_failed_genericError';
	const ERROR_NONCE_FAILED                        = 'snapshot_failed_nonce_failed';
	const ERROR_EXPORT_FAILED                       = 'export_failed';

	const URL_CONTACT_SUPPORT     = 'https://wpmudev.com/hub2/support#get-support';
	const URL_ADD_STORAGE_SPACE   = 'https://wpmudev.com/hub/account/#dash2-modal-add-storage';
	const URL_GUIDE_BACKUP_FAILED = 'https://wpmudev.com/docs/wpmu-dev-plugins/snapshot-4-0/#backup-failed';

	/**
	 * Send email notifications when a backup fails
	 *
	 * @param array $args Task args.
	 */
	public function apply( $args = array() ) {
		foreach ( $args['recipients'] as $recipient ) {
			$this->send(
				$recipient['email'],
				$recipient['name'],
				$args['service_error'],
				$args['timestamp'],
				$args['backup_type'],
				$args['backup_id'],
				isset( $args['error_message'] ) ? $args['error_message'] : null
			);
		}
	}

	/**
	 * Send email to specified recipient when a backup fails
	 *
	 * @param string $email             Recipient email address.
	 * @param string $name              Recipient first name.
	 * @param string $service_error     Service's backup error message.
	 * @param int    $timestamp         Error time.
	 * @param string $backup_type       Type of backup ("scheduled" or "manual").
	 * @param string $backup_id         Backup ID.
	 * @param string $error_message     Custom error message.
	 */
	private function send( $email, $name, $service_error, $timestamp, $backup_type, $backup_id, $error_message = null ) {
		$site_host_html = wp_parse_url( get_site_url(), PHP_URL_HOST );

		$dt = new \DateTime();
		$dt->setTimestamp( $timestamp );
		$dt->setTimezone( wp_timezone() );
		$time_human = $dt->format( 'd-M-Y H:i:s' );

		$params = array(
			'name'   => $name,
			'error1' => "[$time_human]",
			/* translators: %s - Service's backup error message */
			'error2' => sprintf( __( 'ERROR: %s', 'snapshot' ), ( $error_message ? $error_message : $service_error ) ),
		);

		$params += $this->get_texts( $service_error, $backup_type, $backup_id );

		/* translators: %s - website URL */
		$subject  = sprintf( __( 'The backup for %s failed to create.', 'snapshot' ), $site_host_html );
		$template = new Template();
		ob_start();
		$template->render( 'mail/backup-fail', $params );
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

	/**
	 * Returns texts for email depending on $service_error and $backup_type
	 *
	 * @param string $service_error Service's backup error message.
	 * @param string $backup_type   Type of backup ("scheduled" or "manual").
	 * @param string $backup_id     Backup ID.
	 * @return array
	 */
	private function get_texts( $service_error, $backup_type, $backup_id ) {
		$site_host_html = esc_attr( wp_parse_url( get_site_url(), PHP_URL_HOST ) );
		$backups_url    = network_admin_url() . 'admin.php?page=snapshot-backups#backups-' . $backup_id;
		$logs_url       = network_admin_url() . 'admin.php?page=snapshot-backups#logs-' . $backup_id;

		$result = array(
			'p1_html'          => '',
			'p2_html'          => '',
			'button_link'      => $backups_url,
			'button_text'      => __( 'Check the backup', 'snapshot' ),
			'bottom_link'      => '',
			'bottom_link_text' => '',
		);

		$backup_type_html = esc_html( $backup_type . ' backup' );
		if ( 'scheduled' === $backup_type ) {
			$backup_type_html = esc_html__( 'scheduled backup', 'snapshot' );
		} elseif ( 'manual' === $backup_type ) {
			$backup_type_html = esc_html__( 'manual backup', 'snapshot' );
		} elseif ( empty( $backup_type ) ) {
			$backup_type_html = esc_html__( 'backup', 'snapshot' );
		}

		switch ( $service_error ) {
			case self::ERROR_HUB_INFO_RESPONDED_INVALID_URI:
			case self::ERROR_HUB_INFO_RESPONDED_ERROR:
			case self::ERROR_USER_INFO_NOT_RETURN:
				/* translators: %1$s - manual/scheduled backup, %2$s - site domain name */
				$result['p1_html'] = sprintf( __( 'The %1$s for %2$s failed due to the API connection with The Hub. The following is the error log the backup shows:', 'snapshot' ), $backup_type_html, $site_host_html );
				/* translators: %s - support link */
				$result['p2_html'] = sprintf( __( 'We recommend re-running a backup and <a href="%s">contacting support</a> if the issue persists.', 'snapshot' ), self::URL_CONTACT_SUPPORT );
				break;
			case self::ERROR_STORAGE_LIMIT:
				$result['p1_html'] = __( 'Snapshot wasn\'t able to create a backup due to insufficient space on your storage. Here is the error log the backup shows:', 'snapshot' );
				/* translators: %s - support link */
				$result['p2_html'] = sprintf( __( 'We recommend adding storage space and to re-try running a backup. If you weren\'t able to add storage space, you can <a href="%s">contact our support</a> team for assistance.', 'snapshot' ), self::URL_CONTACT_SUPPORT );

				$result['button_link'] = self::URL_ADD_STORAGE_SPACE;
				$result['button_text'] = __( 'Add storage space', 'snapshot' );

				$result['bottom_link']      = $backups_url;
				$result['bottom_link_text'] = __( 'View the backup', 'snapshot' );
				break;
			case self::ERROR_FETCH_FILESLIST:
				/* translators: %1$s - manual/scheduled backup, %2$s - site domain name */
				$result['p1_html'] = sprintf( __( 'The %1$s for %2$s failed while trying to iterate your site\'s files and build the file list. Here is the error log the backup shows:', 'snapshot' ), $backup_type_html, $site_host_html );
				/* translators: %s - support link */
				$result['p2_html'] = sprintf( __( 'We recommend you re-try running a backup and <a href="%s">contact support</a> if the issue persists.', 'snapshot' ), self::URL_CONTACT_SUPPORT );
				break;
			case self::ERROR_SITE_NOT_RESPONDED_ZIPSTREAM_ERROR:
				/* translators: %1$s - manual/scheduled backup, %2$s - site domain name */
				$result['p1_html'] = sprintf( __( 'The %1$s for %2$s failed while trying to send files to our API. Here is the error log the backup shows:', 'snapshot' ), $backup_type_html, $site_host_html );
				/* translators: %s - support link */
				$result['p2_html'] = sprintf( __( 'We recommend you re-try running a backup and <a href="%s">contact support</a> if the issue persists.', 'snapshot' ), self::URL_CONTACT_SUPPORT );
				break;
			case self::ERROR_ZIPSTREAM_FILE_MISSING:
				/* translators: %1$s - manual/scheduled backup, %2$s - site domain name */
				$result['p1_html'] = sprintf( __( 'The %1$s for %2$s failed while trying to send files to our API because a file was renamed or deleted during the backup process. Here is the error log the backup shows:', 'snapshot' ), $backup_type_html, $site_host_html );
				/* translators: %s - support link */
				$result['p2_html'] = sprintf( __( 'We recommend checking for any folders that contain files that are rapidly changing (most often a cache folder) and re-try running a backup with that folder excluded. You can <a href="%s">contact support</a> if the issue persists.', 'snapshot' ), self::URL_CONTACT_SUPPORT );
				break;
			case self::ERROR_SITE_NOT_RESPONDED_LARGE_FILE_ERROR:
				/* translators: %1$s - manual/scheduled backup, %2$s - site domain name */
				$result['p1_html'] = sprintf( __( 'The %1$s for %2$s failed while trying to back up a large file over 100MB. Here is the error log the backup shows:', 'snapshot' ), $backup_type_html, $site_host_html );
				/* translators: %1$s - URL to logs, %2$s - support link */
				$result['p2_html'] = sprintf( __( 'We recommend <a href="%1$s">checking the logs</a>, excluding that file, and re-running a backup. You can <a href="%2$s">contact our support</a> team if the issue persists.', 'snapshot' ), $logs_url, self::URL_CONTACT_SUPPORT );
				break;
			case self::ERROR_WRAPPER_DBLIST_FETCH:
				/* translators: %1$s - manual/scheduled backup, %2$s - site domain name */
				$result['p1_html'] = sprintf( __( 'The %1$s for %2$s failed while trying to iterate the db\'s tables in order to build the table list. Here is the error log the backup shows:', 'snapshot' ), $backup_type_html, $site_host_html );
				/* translators: %s - support link */
				$result['p2_html'] = sprintf( __( 'We recommend re-running a backup and <a href="%s">contacting our support</a> team if the issue persists.', 'snapshot' ), self::URL_CONTACT_SUPPORT );
				break;
			case self::ERROR_WRAPPER_TABLE_ZIPSTREAM:
				/* translators: %1$s - manual/scheduled backup, %2$s - site domain name */
				$result['p1_html'] = sprintf( __( 'The %1$s for %2$s failed while trying to send a db table to our API. Here is the error log the backup shows:', 'snapshot' ), $backup_type_html, $site_host_html );
				/* translators: %s - support link */
				$result['p2_html'] = sprintf( __( 'We recommend re-running a backup and <a href="%s">contacting our support</a> team if the issue persists.', 'snapshot' ), self::URL_CONTACT_SUPPORT );
				break;
			case self::ERROR_TOO_LARGE_TABLE_HANGED:
				/* translators: %1$s - manual/scheduled backup, %2$s - site domain name */
				$result['p1_html'] = sprintf( __( 'The %1$s for %2$s failed while trying to send a db table to our API. Here is the error log the backup shows:', 'snapshot' ), $backup_type_html, $site_host_html );
				/* translators: %1$s - URL to logs, %2$s - support link */
				$result['p2_html'] = sprintf( __( 'We recommend <a href="%1$s">checking the logs</a> to see which table that was, in case it makes sense to exclude it from the backup and <a href="%2$s">contact our support</a> team to do so.', 'snapshot' ), $logs_url, self::URL_CONTACT_SUPPORT );
				break;
			case self::ERROR_SITE_NOT_RESPONDED_ERROR:
				/* translators: %1$s - manual/scheduled backup, %2$s - site domain name */
				$result['p1_html'] = sprintf( __( 'The %1$s for %2$s failed. Here is the error log the backup shows:', 'snapshot' ), $backup_type_html, $site_host_html );
				/* translators: %s - support link */
				$result['p2_html'] = sprintf( __( 'We recommend re-running a backup and <a href="%s">contacting our support</a> team if the issue persists.', 'snapshot' ), self::URL_CONTACT_SUPPORT );
				break;
			case self::ERROR_EXPORT_FAILED:
				/* translators: %1$s - manual/scheduled backup, %2$s - site domain name */
				$result['p1_html'] = sprintf( __( 'The %1$s for %2$s failed. Here is the error log the backup shows:', 'snapshot' ), $backup_type_html, $site_host_html );
				/* translators: %s - support link */
				$result['p2_html'] = sprintf( __( 'We recommend re-running a backup and <a href="%s">contacting our support</a> team if the issue persists.', 'snapshot' ), self::URL_CONTACT_SUPPORT );
				break;
			case self::ERROR_UNKNOWN_ERROR:
			default:
				/* translators: %1$s - manual/scheduled backup, %2$s - site domain name */
				$result['p1_html'] = sprintf( __( 'The %1$s for %2$s failed. Here is the error log the backup shows:', 'snapshot' ), $backup_type_html, $site_host_html );
				/* translators: %s - support link */
				$result['p2_html'] = sprintf( __( 'We recommend re-running a backup and <a href="%s">contacting our support</a> team if the issue persists.', 'snapshot' ), self::URL_CONTACT_SUPPORT );
				break;
		}

		return $result;
	}
}