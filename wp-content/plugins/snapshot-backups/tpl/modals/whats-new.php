<?php // phpcs:ignore
/**
 * "New: Amazon S3 Integration" modal.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Helper\Assets;
use WPMUDEV\Snapshot4\Helper\Api;

$assets = new Assets();

wp_nonce_field( 'snapshot_whats_new_seen', '_wpnonce-whats_new_seen' );

$hub_link = 'https://wpmudev.com/hub2/site/' . Api::get_site_id() . '/backups';
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
						src="<?php echo esc_attr( $assets->get_asset( 'img/modal-whats-new-snapshot-tutorials.png' ) ); ?>"
						srcset="<?php echo esc_attr( $assets->get_asset( 'img/modal-whats-new-snapshot-tutorials.png' ) ); ?> 1x, <?php echo esc_attr( $assets->get_asset( 'img/modal-whats-new-snapshot-tutorials@2x.png' ) ); ?> 2x"
					/>
				</figure>

				<button class="sui-button-icon sui-button-float--right" data-modal-close>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
				</button>

				<div class="sui-box-title sui-lg" style="padding: 0 10px; white-space: normal;"><?php esc_html_e( 'Welcome Snapshot Tutorials!', 'snapshot' ); ?></div>
				<?php /* translators: %s - Link to the Hub */ ?>
				<p class="sui-description"><?php esc_html_e( 'Want to get the most out of Snapshot? With our new Tutorials section, all of our tutorial articles are right at your fingertips. Whether you want a quick run-through of configuring Snapshot, or you want to get into the details of a particular feature, our blogs offer a wealth of information.', 'snapshot' ); ?></p>
			</div>

			<div class="sui-box-body sui-content-center" style="padding-bottom: 50px;">
				<a class="sui-button" href="<?php echo esc_attr( network_admin_url() . 'admin.php?page=snapshot-tutorials' ); ?>" id="snapshot-whats-new-modal-button-ok" data-modal-close><?php esc_html_e( 'View Tutorials', 'snapshot' ); ?></a>
			</div>

		</div>
	</div>
</div>