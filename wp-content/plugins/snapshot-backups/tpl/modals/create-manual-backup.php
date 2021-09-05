<?php // phpcs:ignore
/**
 * Modal for creating a manual backup.
 *
 * @package snapshot
 */

$assets = new \WPMUDEV\Snapshot4\Helper\Assets();
?>

<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="modal-snapshot-create-manual-backup"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="modal-snapshot-create-manual-backup-title"
		aria-describedby="modal-snapshot-create-manual-backup-description"
	>
		<div class="sui-box">

			<div class="sui-box-header sui-flatten sui-content-center">
				<figure class="sui-box-banner" role="banner" aria-hidden="true">
					<img
						src="<?php echo esc_attr( $assets->get_asset( 'img/modal-banner-create-manual-backup.png' ) ); ?>"
						srcset="<?php echo esc_attr( $assets->get_asset( 'img/modal-banner-create-manual-backup.png' ) ); ?> 1x, <?php echo esc_attr( $assets->get_asset( 'img/modal-banner-create-manual-backup@2x.png' ) ); ?> 2x"
					/>
				</figure>
				<button class="sui-button-icon sui-button-float--right" data-modal-close>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
				</button>
				<h3 class="sui-box-title sui-lg" id="modal-snapshot-edit-schedule-title"><?php esc_html_e( 'Create Backup' ); ?></h3>
				<span id="modal-snapshot-edit-schedule-description" class="sui-description"><?php esc_html_e( 'Add a title to your backup to distinguish between scheduled backups.' ); ?></span>
			</div>

			<div class="sui-box-body">
				<form method="post" id="form-snapshot-create-manual-backup">

					<?php wp_nonce_field( 'snapshot_backup_create_manual', '_wpnonce-snapshot_backup_create_manual' ); ?>
					<div class="sui-form-field">
						<label class="sui-label" for="manual-backup-name">
							<?php esc_html_e( 'Backup title', 'snapshot' ); ?> (<?php esc_html_e( 'Optional', 'snapshot' ); ?>)
						</label>
						<input autocomplete="off" type="text" name="backup_name" class="sui-form-control" id="manual-backup-name" placeholder="<?php esc_html_e( 'E.g. Snapshot', 'snapshot' ); ?>">
					</div>

					<div class="sui-form-field" style="display: none;">
						<label for="manual-backup-comment" class="sui-label">
							<?php esc_html_e( 'Backup Comment', 'snapshot' ); ?> (<?php esc_html_e( 'Optional', 'snapshot' ); ?>)
						</label>
						<textarea name="backup_description" id="manual-backup-comment" rows="3" class="sui-form-control" placeholder="<?php esc_attr_e( 'E.g. Backup before changing site design', 'snapshot' ); ?>"></textarea>
					</div>

					<div class="sui-block-content-center">
						<label for="snapshot-manual-apply-exclusions" class="sui-checkbox sui-checkbox-stacked apply-exclusions">
							<input type="checkbox" id="snapshot-manual-apply-exclusions" name="apply_exclusions">
							<span aria-hidden="true"></span>
							<span><small>Apply global file exclusions set in <a id="snapshot-button-backups-settings" href="#">Settings</a> page.<span style="margin-left: 5px;" class="sui-tooltip sui-tooltip-constrained" data-tooltip="<?php esc_attr_e( 'You can exclude the files you don\'t want in the backup by adding them as global exclusions in the Settings page.', 'snapshot' ); ?>"><span class="sui-notice-icon sui-icon-info sui-sm" aria-hidden="true"></span></span></small></span>
						</label>

					<div id="snapshot-notice-tpd-backup" class="sui-notice" >

						<div class="sui-notice-content">

							<div class="sui-notice-message">

								<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>

								<?php /* translators: %s - Link for Destination page */ ?>
								<p><?php echo wp_kses_post( sprintf( __( 'Note: The full backup copy will be exported to all the destinations that are connected and active on the <a href="%s">Destinations page</a>.', 'snapshot' ), network_admin_url() . 'admin.php?page=snapshot-destinations' ) ); ?></p>

							</div>

						</div>

					</div>

						<button type="submit" class="sui-button sui-button-blue">
							<span class="sui-button-text-default">
								<?php esc_html_e( 'Run backup', 'snapshot' ); ?>
							</span>
							<span class="sui-button-text-onload">
								<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
								<?php esc_html_e( 'Starting backup', 'snapshot' ); ?>
							</span>
						</button>
					</div>

				</form>
			</div>

		</div>
	</div>
</div>