<?php // phpcs:ignore
/**
 * Restore backup modal.
 *
 * @package snapshot
 */

$assets = new \WPMUDEV\Snapshot4\Helper\Assets();
?>
<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="modal-snapshot-restore-backup"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="modal-snapshot-restore-backup-title"
		aria-describedby="modal-snapshot-restore-backup-description"
	>
		<div class="sui-box">
			<form id="form-snapshot-restore-backup">

				<?php wp_nonce_field( 'snapshot_trigger_backup_restore', '_wpnonce-snapshot_trigger_backup_restore' ); ?>
				<?php wp_nonce_field( 'snapshot_cancel_backup_restore', '_wpnonce-snapshot_cancel_backup_restore' ); ?>

				<div class="sui-box-header sui-flatten sui-content-center">
					<figure class="sui-box-banner" role="banner" aria-hidden="true">
						<img
							src="<?php echo esc_attr( $assets->get_asset( 'img/modal-banner-restore-backup.png' ) ); ?>"
							srcset="<?php echo esc_attr( $assets->get_asset( 'img/modal-banner-restore-backup.png' ) ); ?> 1x, <?php echo esc_attr( $assets->get_asset( 'img/modal-banner-restore-backup@2x.png' ) ); ?> 2x"
						/>
					</figure>
					<button class="sui-button-icon sui-button-float--right" data-modal-close>
						<i class="sui-icon-close sui-md" aria-hidden="true"></i>
					</button>
					<h3 class="sui-box-title sui-lg" id="modal-snapshot-restore-backup-title"><?php esc_html_e( 'Restore Backup' ); ?></h3>
					<span id="modal-snapshot-restore-backup-description" class="sui-description"><?php esc_html_e( 'This is the default directory where your website will be restored to.' ); ?></span>
				</div>

				<div class="sui-box-body">
					<input type="hidden" name="backup_id">
					<div class="sui-form-field">
						<label for="restore-backup-path" id="restore-backup-path-title" class="sui-label"><?php esc_html_e( 'Default directory', 'snapshot' ); ?></label>
						<input class="sui-form-control" name="restore_rootpath" autocomplete="off" id="restore-backup-path" aria-labelledby="restore-backup-path-title" aria-describedby="restore-backup-path-description" disabled="disabled">
						<i class="sui-icon-folder-open sui-md" aria-hidden="true"></i>

						<div class="sui-block-content-center">
							<button type="submit" class="sui-button sui-button-blue"><?php esc_html_e( 'Restore', 'snapshot' ); ?></button>
						</div>
					</div>
				</div>

				<div class="sui-box-footer sui-flatten sui-lg sui-content-center"></div>

			</form>
		</div>
	</div>
</div>