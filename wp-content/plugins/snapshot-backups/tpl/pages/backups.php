<?php // phpcs:ignore
/**
 * Main backups page.
 *
 * @package snapshot
 */

$assets = new \WPMUDEV\Snapshot4\Helper\Assets();
wp_nonce_field( 'snapshot_list_backups', '_wpnonce-list-backups' );
wp_nonce_field( 'snapshot_backup_progress', '_wpnonce-backup-progress' );
wp_nonce_field( 'snapshot_get_schedule', '_wpnonce-get-schedule' );
wp_nonce_field( 'snapshot_delete_backup', '_wpnonce-delete-backup' );
wp_nonce_field( 'snapshot_export_backup', '_wpnonce-export-backup' );
wp_nonce_field( 'save_snapshot_settings', '_wpnonce-save_snapshot_settings' );
wp_nonce_field( 'snapshot_get_backup_log', '_wpnonce-get-backup-log' );
wp_nonce_field( 'snapshot_change_region', '_wpnonce-snapshot_change_region' );
wp_nonce_field( 'snapshot_delete_all_backups', '_wpnonce-snapshot_delete_all_backups' );
wp_nonce_field( 'snapshot_check_if_region', '_wpnonce-populate_snapshot_region' );
wp_nonce_field( 'snapshot_check_wpmudev_password', '_wpnonce-check_wpmudev_password' );
wp_nonce_field( 'snapshot_check_can_delete_backup', '_wpnonce-check_can_delete_backup' );

/* translators: %s - Admin name */
$admin_name = sprintf( __( '%s, you haven\'t created any backups yet. Let\'s get started.', 'snapshot' ), wp_get_current_user()->display_name );

/* translators: %1$s - File example #1, %2$s - File example #2 */
$exclusions_explained = sprintf( __( 'Use relative paths to the file or folder. For example %1$s or %2$s. Press enter to add each exclusion.', 'snapshot' ), '<strong>/wp-content/custom-folder/</strong>', '<strong>/file.php</strong>' );

?>
<input type="hidden" name="snapshot-php-version" id="snapshot-php-version" value="<?php echo esc_attr( $compat_php_version ); ?>">
<div class="sui-wrap snapshot-page-backups">
	<div class="sui-header">
		<h1 class="sui-header-title"><?php esc_html_e( 'Snapshot Backups', 'snapshot' ); ?></h1>
		<div class="sui-actions-right">
			<a href="https://premium.wpmudev.org/docs/wpmu-dev-plugins/snapshot-4-0/" target="_blank" class="sui-button sui-button-ghost">
				<i class="sui-icon-academy" aria-hidden="true"></i>
				<?php esc_html_e( 'Documentation', 'snapshot' ); ?>
			</a>
		</div>
	</div>
	<?php
	$this->render(
		'common/v3-prompt',
		array(
			'active_v3' => $active_v3,
			'v3_local'  => $v3_local,
			'assets'    => $assets,
		)
	);
	?>

	<div class="sui-box sui-summary snapshot-backups-summary">

		<div class="sui-summary-image-space" aria-hidden="true"></div>

		<div class="sui-summary-segment">

			<div class="sui-summary-details snapshot-backups-number">

				<span class="sui-summary-large"></span>
				<i class="sui-icon-loader sui-loading snapshot-loading" aria-hidden="true"></i>
				<span class="sui-summary-sub"><?php esc_html_e( 'Backups available', 'snapshot' ); ?></span>

			</div>

		</div>

		<div class="sui-summary-segment">

			<ul class="sui-list">

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Last backup', 'snapshot' ); ?></span>
					<span class="sui-list-detail"><i class="sui-icon-loader sui-loading snapshot-loading" aria-hidden="true"></i><span class="snapshot-last-backup"></span></span>
				</li>

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Next scheduled backup', 'snapshot' ); ?></span>
					<span class="sui-list-detail"><span class="snapshot-next-backup"><?php echo esc_html( $next_expected_backup ); ?></span></span>
				</li>

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Backup schedule', 'snapshot' ); ?></span>
					<span class="sui-list-detail" id="snapshot-backup-schedule" data-modal-data="<?php echo esc_attr( wp_json_encode( $schedule_modal_data ) ); ?>"><a href="#" style="margin-right: 15px;">Manage</a> <span><?php echo esc_html( $schedule_frequency ); ?></span></span>
				</li>

			</ul>

		</div>

	</div>

	<div class="sui-row-with-sidenav snapshot-page-main">
		<div class="sui-sidenav">

			<ul class="sui-vertical-tabs sui-sidenav-hide-md">
				<li class="sui-vertical-tab current snapshot-vertical-backups">
					<a href="#backups"><?php esc_html_e( 'Backups', 'snapshot' ); ?></a>
				</li>
				<li class="sui-vertical-tab snapshot-vertical-logs">
					<a href="#logs"><?php esc_html_e( 'Logs', 'snapshot' ); ?></a>
				</li>
				<li class="sui-vertical-tab snapshot-vertical-settings">
					<a href="#settings"><?php esc_html_e( 'Settings', 'snapshot' ); ?></a>
				</li>
				<li class="sui-vertical-tab snapshot-vertical-notifications">
					<a href="#notifications"><?php esc_html_e( 'Notifications', 'snapshot' ); ?></a>
				</li>
			</ul>

			<div class="sui-sidenav-hide-lg">
				<select class="sui-mobile-nav" style="display: none;">
					<option value="backups" selected="selected"><?php esc_html_e( 'Backups', 'snapshot' ); ?></option>
					<option value="logs"><?php esc_html_e( 'Logs', 'snapshot' ); ?></option>
					<option value="settings"><?php esc_html_e( 'Settings', 'snapshot' ); ?></option>
					<option value="notifications"><?php esc_html_e( 'Notifications', 'snapshot' ); ?></option>
				</select>
			</div>

		</div>
		<div class="sui-box snapshot-list-backups">

			<div class="sui-box-header snapshot-has-backups-title" style="display: none;">
				<h2 class="sui-box-title"><?php esc_html_e( 'Available backups', 'snapshot' ); ?></h2>
				<div class="sui-actions-right">
					<button <?php echo $disable_backup_button ? 'disabled' : ''; ?> class="sui-button sui-button-blue button-create-backup snapshot-not-cooldown" id="button-create-backup" onclick="jQuery(window).trigger('snapshot:backup_modal'); return false;">
						<?php esc_html_e( 'Backup now', 'snapshot' ); ?>
					</button>
					<div class="sui-tooltip sui-tooltip-constrained sui-tooltip-top-left-mobile snapshot-cooldown" style="--tooltip-width: 174px; display: none; margin-right: 10px;" data-tooltip="<?php esc_html_e( 'Snapshot is just catching his breath. You can run another backup in a minute.', 'snapshot' ); ?>">
						<button class="sui-button sui-button-blue" disabled>
							<?php esc_html_e( 'Backup now', 'snapshot' ); ?>
						</button>
					</div>
				</div>
			</div>

			<div class="sui-box-body api-error" style="display: none;">
				<div class="sui-notice sui-notice-error">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-warning-alert sui-md" aria-hidden="true"></span>
							<p>We were unable to fetch backup data from the API due to a connection problem. Give it another try below, or <a href="https://premium.wpmudev.org/hub/support/#get-support" target="_blank">contact our support team</a> if the problem persists.</p>
						</div>
					</div>
				</div>
				<button class="sui-button sui-button-ghost" role="button" id="button-reload-backups"><i class="sui-icon-refresh" aria-hidden="true"></i><?php esc_html_e( 'Reload', 'snapshot' ); ?></button>
			</div>

			<div class="sui-box-body snapshot-no-backups">
				<div class="sui-message">

					<img src="<?php echo esc_attr( $assets->get_asset( 'img/snapshot-backups-no-backups.svg' ) ); ?>"
						class="sui-image"
						aria-hidden="true" />

					<div class="sui-message-content">
						<p><?php echo esc_html( $admin_name ); ?></p>
						<p>
							<button class="sui-button sui-button-blue" onclick="jQuery(window).trigger('snapshot:backup_modal');">
								<?php esc_html_e( 'Backup now', 'snapshot' ); ?>
							</button>
						</p>
					</div>

				</div>
			</div>

			<div class="sui-box-body snapshot-backup-list-loader snapshot-loading">
				<div class="sui-message">

					<div class="sui-message-content">
						<p><i class="sui-icon-loader sui-loading" aria-hidden="true"></i> <?php esc_html_e( 'Loading backups...', 'snapshot' ); ?></p>
					</div>

				</div>
			</div>

			<div class="snapshot-listed-backups">
				<div class="sui-box-body snapshot-listed-backups-header">
					<p>
						<?php esc_html_e( 'Here are all your available backups. You can restore from them at any time.', 'snapshot' ); ?>
					</p>
				</div>

				<table class="sui-table sui-table-flushed sui-accordion">
					<thead>
						<tr class="sui-hidden-xs">
							<th style=" width: 33%; "><?php esc_html_e( 'Title', 'snapshot' ); ?></th>
							<th style=" width: 20%; "><?php esc_html_e( 'Storage', 'snapshot' ); ?></th>
							<th style=" width: 27%; "><?php esc_html_e( 'Export Destination', 'snapshot' ); ?></th>
							<th style=" width: 20%; "><?php esc_html_e( 'Frequency', 'snapshot' ); ?></th>
						</tr>
						<tr class="sui-hidden-sm sui-hidden-md sui-hidden-lg">
							<th colspan="4" style="height: 0;"></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
				<div style="height: 30px;"></div>
			</div>

		</div>

		<div class="sui-box snapshot-logs logs-list" style="display: none;" data-logs-loaded="<?php echo intval( ! $loading_logs ); ?>">

			<?php
			$this->render(
				'pages/backups/log-tab-content',
				array(
					'logs'    => $logs,
					'loading' => $loading_logs,
				)
			);
			?>

		</div>

		<div class="sui-box snapshot-backups-settings" style="display: none;">
		<form method="post" id="wps-settings">
			<input type="hidden" name="action" value="save_snapshot_settings">
			<input type="hidden" name="exclusions_settings" value="1">

			<div class="sui-box-header">
				<h2 class="sui-box-title"><?php esc_html_e( 'Settings', 'snapshot' ); ?></h2>
			</div>

			<div class="sui-box-body">

				<div class="sui-box-settings-row">

					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label"><?php esc_html_e( 'Exclusions', 'snapshot' ); ?></span>
						<span class="sui-description"><?php esc_html_e( 'Define files or folders you want to exclude from manual or scheduled backups.', 'snapshot' ); ?></span>
					</div>

					<div class="sui-box-settings-col-2 snapshot-exclusions-settings-box" >
						<div class="sui-form-field">
							<div class="sui-accordion">
								<div class="sui-accordion-item">

									<div class="sui-accordion-item-header">

										<div class="sui-accordion-item-title">

											<p class="sui-description snapshot-exclusion-titles">
												<span class="snapshot-exclusion-title"><?php esc_html_e( 'Exclude Large-Size folders', 'snapshot' ); ?></span></br>
												<?php esc_html_e( 'Enable this to exclude large folders and other plugins\' backup files, which can generate issues during backup.', 'snapshot' ); ?>
											</p>
										</div>

										<div class="sui-accordion-col-auto">
											<label for="snapshot-default-exclusions" class="sui-toggle sui-accordion-item-action">
												<input type="checkbox" id="snapshot-default-exclusions" name="snapshot-default-exclusions" <?php checked( $default_exclusions, true ); ?> >
												<span aria-hidden="true" class="sui-toggle-slider"></span>
												<span class="sui-screen-reader-text"><?php esc_html_e( 'Toggle', 'snapshot' ); ?></span>
											</label>
											<button type="button" class="sui-button-icon sui-accordion-open-indicator" aria-label="Open item">
												<span class="sui-icon-chevron-down" aria-hidden="true"></span>
											</button>
										</div>

									</div>

									<div class="sui-accordion-item-body">

										<div class="sui-box">

											<div class="sui-box-body">

												<p><?php esc_html_e( 'The following folders will be excluded from backups:', 'snapshot' ); ?></p>
												<ul>
													<li><?php echo esc_html( '/error_log' ); ?></li>
													<li><?php echo esc_html( '/wp-snapshots' ); ?></li>
													<li><?php echo esc_html( '/wp-content/backups-dup-lite' ); ?></li>
													<li><?php echo esc_html( '/wp-content/cache' ); ?></li>
													<li><?php echo esc_html( '/wp-content/debug.log' ); ?></li>
													<li><?php echo esc_html( '/wp-content/et-cache' ); ?></li>
													<li><?php echo esc_html( '/wp-content/updraft' ); ?></li>
													<li><?php echo esc_html( '/wp-content/wphb-cache' ); ?></li>
													<li><?php echo esc_html( '/wp-content/wphb-logs' ); ?></li>
													<li><?php echo esc_html( '/wp-content/uploads/ai1wm-backups' ); ?></li>
													<li><?php echo esc_html( '/wp-content/uploads/shipper' ); ?></li>
													<li><?php echo esc_html( '/wp-content/uploads/snapshot' ); ?></li>
												</ul>

												<p><?php echo wp_kses_post( 'Note: if you only want to exclude one or various folders, you can disable <strong>Exclude Large-Size folders</strong> and add the specific folders in the Global File Exclusions setting below.', 'snapshot' ); ?></p>

											</div>

										</div>

									</div>

								</div>

							</div>
						</div>
						<div class="sui-form-field snapshot-global-exclusions-field">

							<p class="sui-description snapshot-exclusion-titles">
									<span class="snapshot-exclusion-title"><?php esc_html_e( 'Global File Exclusions', 'snapshot' ); ?></span></br>
									<?php esc_html_e( 'Define which specific files or folders you want to exclude from backups.', 'snapshot' ); ?>
							</p>
							<label for="snapshot-file-exclusions" class="sui-screen-reader-text"><?php esc_html_e( 'Global file exclusions', 'snapshot' ); ?></label>
							<input type="text" placeholder="<?php esc_html_e( 'Enter file or folder URLs ', 'snapshot' ); ?>" id="snapshot-file-exclusions" class="sui-form-control">

							<p>
								<small><?php echo wp_kses_post( $exclusions_explained ); ?></small>
							</p>

							<div class="sui-pagination-active-filters" id="global-exclusions">
							<?php foreach ( $global_exclusions as $value ) { ?>
								<span class="sui-active-filter" data-value="<?php echo esc_attr( $value ); ?>"><i class="sui-icon-page sui-sm" aria-hidden="true"></i><?php echo esc_html( $value ); ?><span class="sui-active-filter-remove"></span></span>
							<?php } ?>
							</div>

						</div>

					</div>

				</div>

				<div class="sui-box-settings-row">

					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label"><?php esc_html_e( 'Storage Limit', 'snapshot' ); ?></span>
						<span class="sui-description"><?php esc_html_e( 'We will keep the latest 30 backups before removing the older ones.', 'snapshot' ); ?></span>
					</div>

					<div class="sui-box-settings-col-2">
						<div class="sui-notice">
							<div class="sui-notice-content">
								<div class="sui-notice-message">
									<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
									<p><?php esc_html_e( 'Currently the limit can\'t be changed.', 'snapshot' ); ?></p>
								</div>
							</div>
						</div>
					</div>

				</div>

				<div class="sui-box-settings-row">

					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label"><?php esc_html_e( 'Storage region', 'snapshot' ); ?></span>
						<span class="sui-description"><?php esc_html_e( 'Choose which data center you want to use to store your WPMU DEV backups.', 'snapshot' ); ?></span>
					</div>

					<div class="sui-box-settings-col-2">
						<i class="sui-icon-loader sui-loading snapshot-region-loading" aria-hidden="true" ></i>
						<div class="sui-form-field snapshot-region-radio" role="radiogroup" style="display:none;">
							<label for="backup-region-us" class="sui-radio sui-radio-stacked snapshot-region-label">
								<input
									type="radio"
									name="snapshot-backup-region"
									id="backup-region-us"
									aria-labelledby="label-backup-region-us"
									value="US"
								/>
								<span aria-hidden="true"></span>
								<span id="label-backup-region-us"><?php esc_html_e( 'United States', 'snapshot' ); ?></span>
							</label>
							<span class="sui-description snapshot-region-description"><?php esc_html_e( 'Recommended for better performance', 'snapshot' ); ?></span>

							<label for="backup-region-eu" class="sui-radio sui-radio-stacked snapshot-region-label">
								<input
									type="radio"
									name="snapshot-backup-region"
									id="backup-region-eu"
									aria-labelledby="label-backup-region-eu"
									value="EU"
								/>
								<span aria-hidden="true"></span>
								<span id="label-backup-region-eu"><?php esc_html_e( 'Europe', 'snapshot' ); ?></span>
							</label>
							<span class="sui-description snapshot-region-description"><?php esc_html_e( 'EU data protection directive compliant', 'snapshot' ); ?></span>

							<div
								role="alert"
								id="snapshot-region-notice"
								class="sui-notice"
								aria-live="assertive"
							>
								<div class="sui-notice-content">
									<div class="sui-notice-message">

										<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>

										<p><?php esc_html_e( 'If you switch to a new region, your existing backups will be deleted, and any new backups will be stored in the newly selected region.', 'snapshot' ); ?></p>

									</div>
								</div>
							</div>
						</div>
					</div>

				</div>

				<div class="sui-box-settings-row">
					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label"><?php esc_html_e( 'Delete Backups', 'snapshot' ); ?></span>
						<span class="sui-description"><?php esc_html_e( 'You can manually delete all the remote backups.', 'snapshot' ); ?></span>
					</div>
					<div class="sui-box-settings-col-2">
						<div class="sui-form-field">
							<button class="sui-button sui-button-ghost sui-button-red" id="snapshot-settings-delete-backups-confirm">
								<span class="sui-loading-text"><i class="sui-icon-trash" aria-hidden="true"></i><?php esc_html_e( 'Delete', 'snapshot' ); ?></span>
								<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
							</button>
						</div>
					</div>
				</div>

			</div>

			<div class="sui-box-footer">
				<div class="sui-actions-right">
					<button type="submit" class="sui-button sui-button-blue">
						<i class="sui-icon-save" aria-hidden="true"></i>
						<?php esc_html_e( 'Save changes', 'snapshot' ); ?>
					</button>
				</div>
			</div>

		</form>
		</div>

		<div class="sui-box snapshot-notifications" style="display: none;">
			<form method="post" id="wps-notifications">
				<?php wp_nonce_field( 'save_snapshot_settings' ); ?>

				<div class="sui-box-header">
					<h2 class="sui-box-title"><?php esc_html_e( 'Notifications', 'snapshot' ); ?></h2>
				</div>

				<div class="sui-box-body">

					<p><?php esc_html_e( 'Get notified when manual or scheduled backups fail.', 'snapshot' ); ?></p>

					<div class="sui-notice email-notification-notice <?php echo 'success' === $email_settings['notice_type'] ? 'sui-notice-success' : ''; ?>">
						<div class="sui-notice-content">
							<div class="sui-notice-message">
								<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
								<p><?php echo esc_html( $email_settings['notice_text'] ); ?></p>
							</div>
						</div>
					</div>

					<div class="sui-box-settings-row">

						<div class="sui-box-settings-col-1">
							<span class="sui-settings-label"><?php esc_html_e( 'Email Notifications', 'snapshot' ); ?></span>
							<span class="sui-description"><?php esc_html_e( 'Choose who should receive an email when backups fail.', 'snapshot' ); ?></span>
						</div>

						<div class="sui-box-settings-col-2">
							<div class="sui-form-field">

								<label class="sui-toggle">
									<input
										type="checkbox"
										id="snapshot-notifications-send-email"
										aria-labelledby="snapshot-notifications-recipients-label"
										aria-controls="snapshot-notification-recipients"
										<?php echo $email_settings['email_settings']['on_fail_send'] ? 'checked' : ''; ?>
									>
									<span class="sui-toggle-slider" aria-hidden="true"></span>
									<span id="snapshot-notifications-recipients-label" class="sui-toggle-label"><?php esc_html_e( 'Send an email when backup fails', 'snapshot' ); ?></span>
								</label>

								<div
									tabindex="0"
									id="snapshot-notification-recipients"
									class="sui-toggle-content sui-border-frame"
									aria-label="<?php esc_attr_e( 'Send an email when backup fails', 'snapshot' ); ?>"
									style="<?php echo $email_settings['email_settings']['on_fail_send'] ? '' : 'display: none;'; ?>"
								>
									<div class="sui-recipients">
										<label class="sui-label"><?php esc_html_e( 'Recipients', 'snapshot' ); ?></label>

										<div class="sui-notice sui-notice-warning email-notification-notice-empty" style="display: none;">
											<div class="sui-notice-content">
												<div class="sui-notice-message">
													<span class="sui-notice-icon sui-icon-warning-alert sui-md" aria-hidden="true"></span>
													<p><?php esc_html_e( 'You\'ve removed all recipients. If you save without a recipient, we\'ll automatically turn off the notification.', 'snapshot' ); ?></p>
												</div>
											</div>
										</div>

										<?php foreach ( $email_settings['email_settings']['on_fail_recipients'] as $recipient ) { ?>
											<div class="sui-recipient">
												<span class="sui-recipient-name"><?php echo esc_html( $recipient['name'] ); ?></span>
												<span class="sui-recipient-email"><?php echo esc_html( $recipient['email'] ); ?></span>
												<button type="button" class="sui-button-icon snapshot-remove-recipient">
													<i class="sui-icon-trash" aria-hidden="true"></i>
												</button>
											</div>
										<?php } ?>
									</div>

									<button type="button" role="button" class="sui-button sui-button-ghost snapshot-add-recipient">
										<i class="sui-icon-plus" aria-hidden="true"></i>
										<?php esc_html_e( 'Add Recipient', 'snapshot' ); ?>
									</button>

								</div>

							</div>
						</div>

					</div>

				</div>

				<div class="sui-box-footer">
					<div class="sui-actions-right">
						<button type="submit" class="sui-button sui-button-blue">
							<i class="sui-icon-save" aria-hidden="true"></i>
							<?php esc_html_e( 'Save changes', 'snapshot' ); ?>
						</button>
					</div>
				</div>

			</form>
		</div>

	</div>

	<?php

	// Snapshot getting started dialog.
	$this->render(
		'modals/welcome-activation',
		array(
			'errors'            => $errors,
			'welcome_modal'     => $welcome_modal,
			'welcome_modal_alt' => $welcome_modal_alt,
		)
	);

	$this->render( 'modals/create-manual-backup' );
	$this->render( 'modals/log' );
	$this->render( 'modals/cancel-backup' );
	$this->render( 'modals/restore-backup' );
	$this->render( 'modals/backups-region-change' );
	$this->render( 'modals/settings-delete-backups' );
	$this->render( 'modals/confirm-v3-uninstall' );
	$this->render( 'modals/requirements-check-failure' );
	$this->render( 'modals/requirements-check-success' );
	$this->render( 'modals/notification-add-recipient' );
	$this->render( 'modals/confirm-wpmudev-password' );
	$this->render( 'modals/delete-backup' );

	$this->render( 'common/footer' );

	?>

</div> <?php // .sui-wrap ?>