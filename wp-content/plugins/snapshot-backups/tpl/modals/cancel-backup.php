<?php // phpcs:ignore
/**
 * Modal for confirming backup cancel.
 *
 * @package snapshot
 */

wp_nonce_field( 'snapshot_cancel_backup', '_wpnonce-snapshot_cancel_backup' );
?>
<div class="sui-modal sui-modal-sm">
	<div
		role="dialog"
		id="snapshot-modal-cancel-backup"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="snapshot-modal-cancel-backup-title"
		aria-describedby="snapshot-modal-cancel-backup-description"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<button class="sui-button-icon sui-button-float--right snapshot-cancel-backup-cancel" data-modal-close="">
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close', 'snapshot' ); ?></span>
				</button>

				<h3 id="snapshot-modal-cancel-backup-title" class="sui-box-title sui-lg"><?php esc_html_e( 'Cancel Backup', 'snapshot' ); ?></h3>
				<p id="snapshot-modal-cancel-backup-description" class="sui-description"><?php esc_html_e( 'Are you sure you want to cancel the backup?', 'snapshot' ); ?></p>

			</div>

			<div class="sui-box-footer sui-flatten sui-content-center">
				<button class="sui-button sui-button-ghost snapshot-cancel-backup-cancel" data-modal-close=""><?php esc_html_e( 'Go Back', 'snapshot' ); ?></button>
				<button class="sui-button sui-button-ghost sui-button-red" id="snapshot-cancel-backup" aria-live="polite">
					<span class="sui-button-text-default"><?php esc_html_e( 'Cancel backup', 'snapshot' ); ?></span>
					<span class="sui-button-text-onload">
						<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
						<?php esc_html_e( 'Cancelling backup', 'snapshot' ); ?>
					</span>
				</button>
			</div>

		</div>
	</div>
</div>