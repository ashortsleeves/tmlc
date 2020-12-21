<?php // phpcs:ignore
/**
 * Expanded row with backup details.
 *
 * @package snapshot
 */

?>
<tr class="sui-accordion-item-content snapshot-details-row <?php echo $last_snap ? ' snapshot-last-backup' : ''; ?>" data-backup_id="<?php echo esc_attr( $snapshot_id ); ?>">
	<td colspan="4">
		<div class="sui-box snapshot-non-restoration">
			<div class="sui-box-body">
				<?php if ( $add_export_notice ) { ?>
				<div class="sui-row">
					<?php /* translators: %s - Link for Destination page */ ?>
					<div class="sui-col"><span class="tpd-description"><?php echo wp_kses_post( sprintf( __( 'The backup is stored on the WPMU DEV Cloud and a full copy of the backup is exported to the connected and active <a href="%s">destinations</a>.', 'snapshot' ), esc_attr( network_admin_url() . 'admin.php?page=snapshot-destinations' ) ) ); ?></span></div>
				</div>
				<?php } ?>

				<div class="sui-row">
					<?php echo wp_kses_post( $export_details ); ?>
					<?php if ( 'automate' !== $backup_type ) { ?>
					<div class="sui-col-md-3 sui-col-xs-6">
						<span class="sui-settings-label"><?php esc_html_e( 'Current Schedule', 'snapshot' ); ?></span>
						<span class="sui-description"><a href="#" class="open-edit-schedule" data-modal-data="<?php echo esc_attr( wp_json_encode( $schedule_info ) ); ?>"><span class="schedule"><?php echo esc_html( $schedule_info['text'] ); ?></span><i class="sui-icon-pencil sui-sm right" aria-hidden="true"></i></a></span>
					</div>
					<div class="sui-col-md-3 sui-col-xs-6">
						<span class="sui-settings-label"><?php esc_html_e( 'Global Exclusions', 'snapshot' ); ?></span>
						<?php
						if ( 0 === count( $global_exclusions ) ) {
							?>
							<span class="sui-description"><?php esc_html_e( 'No exclusion set', 'snapshot' ); ?></span>
							<?php
						}
						foreach ( $global_exclusions as $value ) {
							?>
						<div class="sui-description">
							<div class="sui-tooltip sui-tooltip-top-left" data-tooltip="<?php echo esc_attr( $value ); ?>">
								<div class="ellipsis">
									<i class="sui-icon-page sui-sm left" aria-hidden="true"></i>
									<?php echo esc_html( $value ); ?>
								</div>
							</div>
						</div>
						<?php } ?>
					</div>
					<?php } ?>
					<div class="sui-col-md-3 sui-col-xs-6">
						<span class="sui-settings-label"><?php esc_html_e( 'Date', 'snapshot' ); ?></span>
						<span class="sui-description"><?php echo esc_html( $date ); ?></span>
					</div>
					<div class="sui-col-md-3 sui-col-xs-6">
						<?php
						/* translators: %s - icon */
						?>
						<span class="sui-settings-label">
							<span class="snapshot-tooltip-size"><?php esc_html_e( 'Export size', 'snapshot' ); ?></span>
							<span class="snapshot-icon-tooltip sui-tooltip sui-tooltip-constrained" style="margin-left: 5px;" data-tooltip="<?php esc_attr_e( 'Sizes shown refer to the size of the backup once exported. The actual size that is counted against your storage usage should be smaller, as these are incremental backups, not full site ones.', 'snapshot' ); ?>"><i class="sui-icon-info sui-sm" aria-hidden="true"></i></span>
							<span class="snapshot-icon-tooltip2 sui-tooltip sui-tooltip-constrained" style="margin-left: 5px;display: none;" data-tooltip="<?php esc_attr_e( 'Sizes shown refer to the size of the backup once exported. The actual size that is counted against your storage usage should be smaller, as these are incremental backups, not full site ones.', 'snapshot' ); ?>"><i class="sui-icon-info sui-sm" aria-hidden="true"></i></span>
						</span>
						<span class="sui-description"><?php echo esc_html( $size ); ?></span>
					</div>
				</div>
			</div>
			<div class="sui-box-footer">
				<div class="sui-actions-right snapshot-delete-backup">
					<button class="sui-button-icon sui-button-red sui-button-outlined sui-tooltip" data-tooltip="<?php esc_attr_e( 'Delete', 'snapshot' ); ?>" onclick="jQuery(window).trigger('snapshot:delete_backup', ['<?php echo esc_attr( $snapshot_id ); ?>'])">
						<i class="sui-icon-trash" aria-hidden="true"></i>
						<span class="sui-screen-reader-text"><?php esc_html_e( 'Delete', 'snapshot' ); ?></span>
					</button>
				</div>
				<div class="sui-actions-right sui-tooltip sui-tooltip-constrained sui-tooltip-top-left-mobile snapshot-delete-backup-inactive" style="--tooltip-width: 128px;" data-tooltip="<?php esc_attr_e( 'You can only delete the last backup.', 'snapshot' ); ?>">
					<button class="sui-button-icon sui-button-outlined" disabled>
						<i class="sui-icon-trash" aria-hidden="true"></i>
						<span class="sui-screen-reader-text"><?php esc_html_e( 'Delete', 'snapshot' ); ?></span>
					</button>
				</div>

				<div class="sui-actions-left">
					<button class="sui-button-icon sui-button-default sui-button-outlined sui-tooltip snapshot-restore-backup" data-tooltip="<?php esc_attr_e( 'Restore', 'snapshot' ); ?>" onclick="jQuery(window).trigger('snapshot:restore_backup', ['<?php echo esc_attr( $snapshot_id ); ?>'])">
						<i class="sui-icon-refresh" aria-hidden="true"></i>
						<span class="sui-screen-reader-text"><?php esc_html_e( 'Restore', 'snapshot' ); ?></span>
					</button>
				</div>

				<div class="sui-content-right" style="width: 100%; display: flex; justify-content: flex-end;">
					<button class="sui-button sui-button-ghost view-log" data-backup-id="<?php echo esc_attr( $snapshot_id ); ?>">
						<i class="sui-icon-eye" aria-hidden="true"></i>
						<?php esc_html_e( 'View logs', 'snapshot' ); ?>
					</button>
					<button class="sui-button sui-button-blue" onclick="jQuery(window).trigger('snapshot:export_backup', ['<?php echo esc_attr( $snapshot_id ); ?>'])">
						<i class="sui-icon-download" aria-hidden="true"></i>
						<?php esc_html_e( 'Download', 'snapshot' ); ?>
					</button>
				</div>
			</div>
		</div>

		<div class="sui-box snapshot-restoration step-0">
			<div class="sui-box-body sui-hidden-xs"> <!-- Desktop -->

				<div class="progressbar-header">
					<p><?php esc_html_e( 'Restoration is in progress', 'snapshot' ); ?></p>
					<div class="restoration-step">
						<p class="only-step-0"><?php esc_html_e( 'Step 0/5 ', 'snapshot' ); ?></p>
						<p class="only-step-1"><?php esc_html_e( 'Step 1/5 ', 'snapshot' ); ?></p>
						<p class="only-step-2"><?php esc_html_e( 'Step 2/5 ', 'snapshot' ); ?></p>
						<p class="only-step-3"><?php esc_html_e( 'Step 3/5 ', 'snapshot' ); ?></p>
						<p class="only-step-4"><?php esc_html_e( 'Step 4/5 ', 'snapshot' ); ?></p>
						<p class="only-step-5"><?php esc_html_e( 'Step 5/5 ', 'snapshot' ); ?></p>
					</div>
				</div>

				<section>
					<div class="progressbar-container">
						<div class="progressbar-status">
							<div role="alert" class="sui-screen-reader-text" aria-live="assertive">
								<p><?php esc_html_e( 'Restoration progress at 0%', 'snapshot' ); ?></p>
							</div>
						</div>
						<ul class="progress-circles" aria-hidden="true">
							<li class="circle sui-tooltip ci-step-1" data-tooltip="<?php esc_attr_e( 'Backup exported', 'snapshot' ); ?>">
								<i class="sui-icon-check"></i>
							</li>
							<li class="circle sui-tooltip ci-step-2" data-tooltip="<?php esc_attr_e( 'Backup downloaded', 'snapshot' ); ?>">
								<i class="sui-icon-check"></i>
							</li>
							<li class="circle sui-tooltip ci-step-3" data-tooltip="<?php esc_attr_e( 'Files have been successfully restored', 'snapshot' ); ?>">
								<i class="sui-icon-check"></i>
							</li>
							<li class="circle sui-tooltip ci-step-4" data-tooltip="<?php esc_attr_e( 'Database has been successfully restored', 'snapshot' ); ?>">
								<i class="sui-icon-check"></i>
							</li>
						</ul>
					</div>
				</section>

				<div class="progress-title">
					<p><span class="lt-step-1"><?php esc_html_e( 'Exporting backup', 'snapshot' ); ?></span><span class="on-step-1"><?php esc_html_e( 'Backup exported', 'snapshot' ); ?></span></p>
					<p><span class="lt-step-2"><?php esc_html_e( 'Download backup', 'snapshot' ); ?></span><span class="on-step-2"><?php esc_html_e( 'Backup downloaded', 'snapshot' ); ?></span></p>
					<p><span class="lt-step-3"><?php esc_html_e( 'Restore files', 'snapshot' ); ?></span><span class="on-step-3"><?php esc_html_e( 'Files restored', 'snapshot' ); ?></span></p>
					<p><span class="lt-step-4"><?php esc_html_e( 'Restore database', 'snapshot' ); ?></span><span class="on-step-4"><?php esc_html_e( 'Database restored', 'snapshot' ); ?></span></p>
					<p><span class="lt-step-5"><?php esc_html_e( 'Finalize restoration', 'snapshot' ); ?></span><span class="on-step-5"><?php esc_html_e( 'Finalizing restoration', 'snapshot' ); ?></span></p>
				</div>

			</div>

			<div class="sui-box-body sui-hidden-sm sui-hidden-md sui-hidden-lg"> <!-- Mobile -->
				<div class="sui-row">
					<div class="sui-col">
						<div class="sui-table-item-title"><?php esc_html_e( 'Destination', 'snapshot' ); ?></div>
						<div class="sui-table-item-title gray">
							<i class="sui-icon-wpmudev-logo" aria-hidden="true"></i>
							<?php echo esc_html( $destination_text ); ?>
						</div>
					</div>
					<div class="sui-col">
						<div class="sui-table-item-title"><?php esc_html_e( 'Frequency', 'snapshot' ); ?></div>
						<div class="sui-table-item-title gray"><?php echo esc_html( $frequency_human ); ?></div>
					</div>
				</div>

				<div class="progressbar-container mobile">
					<div class="progressbar mobile-0 lt-step-1"></div>
					<div class="progressbar mobile-100 on-step-1"></div>
					<div class="progress-circles mobile"><div class="circle active on-step-1"></div></div>
				</div>
				<div class="progress-title mobile">
					<p><span class="lt-step-1"><?php esc_html_e( 'Exporting backup', 'snapshot' ); ?></span><span class="on-step-1"><?php esc_html_e( 'Backup exported', 'snapshot' ); ?></span></p>
				</div>

				<div class="progressbar-container mobile">
					<div class="progressbar mobile-0 lt-step-2"></div>
					<div class="progressbar mobile-100 on-step-2"></div>
					<div class="progress-circles mobile"><div class="circle active on-step-2"></div></div>
				</div>
				<div class="progress-title mobile">
					<p><span class="lt-step-2"><?php esc_html_e( 'Download backup', 'snapshot' ); ?></span><span class="on-step-2"><?php esc_html_e( 'Backup downloaded', 'snapshot' ); ?></span></p>
				</div>

				<div class="progressbar-container mobile">
					<div class="progressbar mobile-0 lt-step-3"></div>
					<div class="progressbar mobile-100 on-step-3"></div>
					<div class="progress-circles mobile"><div class="circle active on-step-3"></div></div>
				</div>
				<div class="progress-title mobile">
					<p><span class="lt-step-3"><?php esc_html_e( 'Restore files', 'snapshot' ); ?></span><span class="on-step-3"><?php esc_html_e( 'Files restored', 'snapshot' ); ?></span></p>
				</div>

				<div class="progressbar-container mobile">
					<div class="progressbar mobile-0 lt-step-4"></div>
					<div class="progressbar mobile-100 on-step-4"></div>
					<div class="progress-circles mobile"><div class="circle active on-step-4"></div></div>
				</div>
				<div class="progress-title mobile">
					<p><span class="lt-step-4"><?php esc_html_e( 'Restore database', 'snapshot' ); ?></span><span class="on-step-4"><?php esc_html_e( 'Database restored', 'snapshot' ); ?></span></p>
				</div>

				<div class="progressbar-container mobile">
					<div class="progressbar mobile-0 lt-step-5"></div>
					<div class="progressbar mobile-100 on-step-5"></div>
					<div class="progress-circles mobile"><div class="circle active on-step-5"></div></div>
				</div>
				<div class="progress-title mobile">
					<p><span class="lt-step-5"><?php esc_html_e( 'Finalize restoration', 'snapshot' ); ?></span><span class="on-step-5"><?php esc_html_e( 'Finalizing restoration', 'snapshot' ); ?></span></p>
				</div>
			</div>

			<div class="sui-box-footer" style="display: flex; justify-content: space-between;">
				<button class="sui-button sui-button-ghost cancel-restore" data-backup-id="<?php echo esc_attr( $snapshot_id ); ?>" onclick="jQuery(window).trigger('snapshot:cancel_restore', ['<?php echo esc_attr( $snapshot_id ); ?>'])">
					<?php esc_html_e( 'Cancel', 'snapshot' ); ?>
				</button>

				<button class="sui-button sui-button-ghost view-log-in-modal" data-backup-id="<?php echo esc_attr( $snapshot_id ); ?>">
					<i class="sui-icon-eye" aria-hidden="true"></i>
					<?php esc_html_e( 'View logs', 'snapshot' ); ?>
				</button>
			</div>
		</div>
	</td>
</tr>