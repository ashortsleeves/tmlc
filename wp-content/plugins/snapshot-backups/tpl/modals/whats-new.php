<?php // phpcs:ignore
/**
 * "New: Amazon S3 Integration" modal.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Helper\Assets;

$assets = new Assets();

wp_nonce_field( 'snapshot_whats_new_seen', '_wpnonce-whats_new_seen' );
?>
<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="snapshot-whats-new-modal"
		class="sui-modal-content"
		aria-modal="true"
	>
		<div class="sui-box">

			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<figure class="sui-box-banner" aria-hidden="true">
					<img
						src="<?php echo esc_attr( $assets->get_asset( 'img/modal-new-destination-google-drive.png' ) ); ?>"
						srcset="<?php echo esc_attr( $assets->get_asset( 'img/modal-new-destination-google-drive.png' ) ); ?> 1x, <?php echo esc_attr( $assets->get_asset( 'img/modal-new-destination-google-drive@2x.png' ) ); ?> 2x"
					/>
				</figure>

				<button class="sui-button-icon sui-button-float--right" data-modal-close>
					<i class="sui-icon-close sui-md" aria-hidden="true"></i>
				</button>

				<div class="sui-box-title sui-lg"><?php esc_html_e( 'New: Google Drive Integration!', 'snapshot' ); ?></div>
				<?php /* translators: %s - Link to open "Add destination" modal */ ?>
				<p class="sui-description"><?php echo wp_kses_post( sprintf( __( 'Been missing more storage options? We have you covered! You can now fully <a href="%s">integrate Google Drive</a> as an export destination where you can store your backups. And there is still more in this release:', 'snapshot' ), esc_attr( network_admin_url() . 'admin.php?page=snapshot-destinations#add-destination' ) ) ); ?></p>
			</div>

			<div class="sui-box-body" style="padding-top: 40px; padding-bottom: 15px;">
				<p class="list-header"><strong><span class="bullet">•</span><?php esc_html_e( 'Exclude larger size folders by default', 'snapshot' ); ?></strong></p>
				<p class="sui-description"><?php esc_html_e( 'Exclude large folders and other plugins\' backup files, which can generate errors during a backup. This creates a better chance for the backup to succeed.', 'snapshot' ); ?></p>
			</div>

			<div class="sui-box-body sui-content-center" style="padding-bottom: 50px;">
				<button class="sui-button" id="snapshot-whats-new-modal-button-ok" data-modal-close><?php esc_html_e( 'Got it', 'snapshot' ); ?></button>
			</div>

		</div>
	</div>
</div>