<?php // phpcs:ignore
/**
 * Notification in all WP pages for prompting to uninstall Snapshot v3.
 *
 * @package snapshot
 */

wp_nonce_field( 'snapshot_uninstall_snapshot_v3', '_wpnonce-uninstall_snapshot_v3' );
wp_nonce_field( 'snapshot_dismiss_uninstall_notice', '_wpnonce-snapshot_dismiss_uninstall_notice' );
?>
<!-- Uninstall confirmation modal -->
<div id="confirm-v3-uninstall" style="display:none;">
	<h3><?php esc_html_e( 'Your local Snapshots will be removed!', 'snapshot' ); ?></h3>
	<p>
		<?php esc_html_e( 'Are you sure you want to uninstall the old Snapshot plugin? All your local Snapshots will be removed after you uninstall the plugin.', 'snapshot' ); ?>
	</p>
</div>
<!-- End of modal -->

<div class="notice notice-success is-dismissible snapshot-uninstall-v3-success" style="display:none;">
	<p style=""><?php esc_html_e( 'You uninstalled the old version of Snapshot successfully.', 'snapshot' ); ?></p>
</div>
<div class="notice notice-error is-dismissible snapshot-uninstall-prompt" style="padding: 20px 15px; border-left: 1px solid #FF6D6D;">
<form>
	<?php wp_nonce_field( 'snapshot_admin_notice_v4', '_wpnonce-snapshot_admin_notice' ); ?>

	<div style="position: relative;">
		<div style="position: absolute; width: 100px; height: 125px; background-color: #FFEDED; background-repeat: no-repeat; background-position: center; background-image: url('<?php echo esc_attr( $assets->get_asset( 'img/notice-background.svg' ) ); ?>');"></div>
		<div style="padding-left: 120px; padding-right: 30px;">
			<div style="min-height: 80px;">
				<h3><?php esc_html_e( 'Uninstall the old Snapshot', 'snapshot' ); ?></h3>

				<?php /* translators: %s - link for Feature Request modal in DEV site */ ?>
				<p style="margin: 0 0 10px 0; padding: 0; "><?php echo wp_kses_post( sprintf( __( 'You\'re using the new Snapshot v4 plugin, but it looks like you still have the old Snapshot plugin installed. You can safely use Snapshot v3 and v4 side by side, please let us know <a href="%s" target="_blank">here</a> if you have any suggestions or feature requests for v4, as it will be our focus going forward!', 'snapshot' ), 'https://premium.wpmudev.org/hub/support/#get-support' ) ); ?></p>

				<?php /* translators: %s - link for Snapshot 4.0 migration Q&A in DEV site */ ?>
				<p style="margin: 0; padding: 0; "><?php echo wp_kses_post( sprintf( __( 'Note: <strong>Your local Snapshots will be removed after uninstalling the plugin</strong>, and Snapshot v4 does not currently support all v3 features. Learn more in our <a href="%s" target="_blank">Snapshot 4.0 migration Q&A</a>.', 'snapshot' ), 'https://premium.wpmudev.org/docs/wpmu-dev-plugins/snapshot-4-0/#faq' ) ); ?></p>
			</div>
			<div style="margin-top: 10px;">
			<?php
			if ( empty( $v3_local ) ) {
				?>
				<button class="button button-primary snapshot-uninstall-v3-admin"><?php esc_html_e( 'Uninstall', 'snapshot' ); ?></button>
				<?php
			} else {
				?>
				<button class="button button-primary snapshot-uninstall-v3-admin-confirm"><?php esc_html_e( 'Uninstall', 'snapshot' ); ?></button>
				<?php
			}
			?>
			</div>
		</div>
	</div>

</form>
</div>