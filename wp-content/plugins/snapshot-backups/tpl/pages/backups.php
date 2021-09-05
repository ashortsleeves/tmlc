<?php // phpcs:ignore
/**
 * Main backups page.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Helper\Assets;
use WPMUDEV\Snapshot4\Helper\Settings;

$assets = new Assets();
wp_nonce_field( 'snapshot_list_backups', '_wpnonce-list-backups' );
wp_nonce_field( 'snapshot_backup_progress', '_wpnonce-backup-progress' );
wp_nonce_field( 'snapshot_get_schedule', '_wpnonce-get-schedule' );
wp_nonce_field( 'snapshot_delete_backup', '_wpnonce-delete-backup' );
wp_nonce_field( 'snapshot_export_backup', '_wpnonce-export-backup' );
wp_nonce_field( 'save_snapshot_settings', '_wpnonce-save_snapshot_settings' );
wp_nonce_field( 'snapshot_get_backup_log', '_wpnonce-get-backup-log' );
wp_nonce_field( 'snapshot_change_region', '_wpnonce-snapshot_change_region' );
wp_nonce_field( 'snapshot_change_storage_limit', '_wpnonce-snapshot_change_storage_limit' );
wp_nonce_field( 'snapshot_delete_all_backups', '_wpnonce-snapshot_delete_all_backups' );
wp_nonce_field( 'snapshot_check_if_region', '_wpnonce-populate_snapshot_region' );
wp_nonce_field( 'snapshot_check_wpmudev_password', '_wpnonce-check_wpmudev_password' );
wp_nonce_field( 'snapshot_check_can_delete_backup', '_wpnonce-check_can_delete_backup' );
wp_nonce_field( 'snapshot_get_storage', '_wpnonce-snapshot_get_storage' );

/* translators: %s - Admin name */
$admin_name = sprintf( __( '%s, you haven\'t created any backups yet. Let\'s get started.', 'snapshot' ), wp_get_current_user()->display_name );

/* translators: %1$s - File example #1, %2$s - File example #2 */
$exclusions_explained = sprintf( __( 'Use relative paths to the file or folder. For example %1$s or %2$s. Press enter to add each exclusion.', 'snapshot' ), '<strong>/wp-content/custom-folder/</strong>', '<strong>/file.php</strong>' );

$show_clear = false;
if ( $global_exclusions && is_array( $global_exclusions ) ) {
	if ( count( $global_exclusions ) >= 2 ) {
		$show_clear = true;
	}
	$global_exclusions = implode( "\n", $global_exclusions );
} else {
	$global_exclusions = '';
}
?>
<input type="hidden" name="snapshot-php-version" id="snapshot-php-version" value="<?php echo esc_attr( $compat_php_version ); ?>">
<div class="sui-wrap snapshot-page-backups">
	<div class="sui-header">
		<h1 class="sui-header-title"><?php esc_html_e( 'Snapshot Backups', 'snapshot' ); ?></h1>
		<?php if ( ! Settings::get_branding_hide_doc_link() ) { ?>
			<div class="sui-actions-right">
				<a href="https://wpmudev.com/docs/wpmu-dev-plugins/snapshot-4-0/?utm_source=snapshot&utm_medium=plugin&utm_campaign=snapshot_backups_docs#backups" target="_blank" class="sui-button sui-button-ghost">
					<span class="sui-icon-academy" aria-hidden="true"></span>
					<?php esc_html_e( 'Documentation', 'snapshot' ); ?>
				</a>
			</div>
		<?php } ?>
	</div>
	<?php
	$this->render(
		'common/v3-prompt',
		array(
			'active_v3'          => $active_v3,
			'v3_local'           => $v3_local,
			'assets'             => $assets,
			'is_branding_hidden' => $is_branding_hidden,
		)
	);
	?>

	<div class="sui-box sui-summary snapshot-backups-summary<?php echo esc_html( $sui_branding_class ); ?>">

		<div class="sui-summary-image-space" aria-hidden="true" style="background-image: url( '<?php echo esc_url( apply_filters( 'wpmudev_branding_hero_image', '' ) ); ?>' )"></div>

		<div class="sui-summary-segment">

			<div class="sui-summary-details snapshot-backups-number">

				<span class="sui-summary-large"></span>
				<span class="sui-icon-loader sui-loading snapshot-loading" aria-hidden="true"></span>
				<span class="sui-summary-sub"><?php esc_html_e( 'Backups available', 'snapshot' ); ?></span>

				<span class="sui-summary-detail">
					<div class="snapshot-storage-widget" style="visibility: hidden;">
						<div style="">
							<?php /* translators: %1$s - Used space, %2$s - Total space */ ?>
							<div class="snapshot-storage-used"><span class="snapshot-storage-used-value"></span> <span class="snapshot-storage-used-label"><?php esc_html_e( 'Storage space', 'snapshot' ); ?></span></div>
							<div class="snapshot-storage-progress">
								<div class="sui-progress">
									<div class="sui-progress-bar" aria-hidden="true">
										<span style="width: 50%"></span>
									</div>
								</div>
								<?php if ( ! Settings::get_branding_hide_doc_link() ) { ?>
								<div class="snapshot-storage-progress-manage storage-action"><a href="https://wpmudev.com/hub/account/" target="_blank"><?php esc_html_e( 'Manage', 'snapshot' ); ?></a></div>
								<div class="snapshot-storage-progress-add storage-action"><a href="https://wpmudev.com/hub/account/#dash2-modal-add-storage" target="_blank"><?php esc_html_e( 'Add Storage Space', 'snapshot' ); ?></a></div>
								<?php } ?>
							</div>
						</div>
					</div>
				</span>

			</div>

		</div>

		<div class="sui-summary-segment">

			<ul class="sui-list">

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Last backup', 'snapshot' ); ?></span>
					<span class="sui-list-detail"><span class="sui-icon-loader sui-loading snapshot-loading" aria-hidden="true"></span><span class="snapshot-last-backup"></span></span>
				</li>

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Next scheduled backup', 'snapshot' ); ?></span>
					<span class="sui-list-detail"><span class="snapshot-next-backup"></span><span class="sui-icon-loader sui-loading snapshot-loading-schedule" aria-hidden="true"></span></span>
				</li>

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Backup schedule', 'snapshot' ); ?></span>
					<span class="sui-list-detail" id="snapshot-backup-schedule" data-modal-data="{}"><a href="#" style="margin-right: 15px; display: none;" class="button-manage">Manage</a> <span class="snapshot-schedule-frequency"></span><span class="sui-icon-loader sui-loading snapshot-loading-schedule" aria-hidden="true"></span></span>
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

			<div class="sui-sidenav-hide-lg" style="margin-bottom: 20px;">
				<select class="sui-select sui-mobile-nav" style="display: none;">
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
					<div class="sui-tooltip sui-tooltip-constrained sui-tooltip-top-left-mobile snapshot-cooldown" style="--tooltip-width: 174px; display: none; margin-right: 10px;" data-tooltip="<?php Settings::get_branding_hide_doc_link() ? esc_html_e( 'The backup plugin is just catching his breath. You can run another backup in a minute.', 'snapshot' ) : esc_html_e( 'Snapshot is just catching his breath. You can run another backup in a minute.', 'snapshot' ); ?>">
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
							<?php if ( Settings::get_branding_hide_doc_link() ) { ?>
								<p><?php esc_html_e( 'We were unable to fetch backup data from the API due to a connection problem. Give it another try below, or contact support if the problem persists.', 'snapshot' ); ?></p>
							<?php } else { ?>
								<?php /* translators: %s - Link for support */ ?>
								<p><?php echo wp_kses_post( sprintf( __( 'We were unable to fetch backup data from the API due to a connection problem. Give it another try below, or <a href="%s" target="_blank">contact our support team</a> if the problem persists.', 'snapshot' ), 'https://wpmudev.com/hub2/support#get-support' ) ); ?></p>
							<?php } ?>
						</div>
					</div>
				</div>
				<button class="sui-button sui-button-ghost" role="button" id="button-reload-backups"><span class="sui-icon-refresh" aria-hidden="true"></span><?php esc_html_e( 'Reload', 'snapshot' ); ?></button>
			</div>

			<div class="sui-box-body snapshot-no-backups">
				<div class="sui-message">

					<img class="snapshot-no-backups-hero <?php echo ! empty( $is_branding_hidden ) ? esc_html( 'snapshot-hidden-branding' ) : esc_html( '' ); ?>" src="<?php echo esc_attr( $assets->get_asset( 'img/snapshot-backups-no-backups.svg' ) ); ?>"
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
						<p><span class="sui-icon-loader sui-loading" aria-hidden="true"></span> <?php esc_html_e( 'Loading backups...', 'snapshot' ); ?></p>
					</div>

				</div>
			</div>

			<div class="snapshot-listed-backups">
				<div class="sui-box-body snapshot-listed-backups-header">
					<p>
						<?php esc_html_e( 'Here are all of your available manual and scheduled backups, retained for up to 50 days. You can restore from them while they’re available.', 'snapshot' ); ?>
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
					'logs'               => $logs,
					'loading'            => $loading_logs,
					'is_branding_hidden' => $is_branding_hidden,
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
													<li><?php echo esc_html( '/wp-content/ai1wm-backups' ); ?></li>
													<li><?php echo esc_html( '/wp-content/uploads/shipper' ); ?></li>
													<li><?php echo esc_html( '/wp-content/uploads/snapshot' ); ?></li>
													<li><?php echo esc_html( '/wp-content/uploads/snapshots' ); ?></li>
													<li><?php echo esc_html( '/wp-content/uploads/wp-defender/defender.log' ); ?></li>
												</ul>

												<p><?php echo wp_kses_post( 'Note: if you only want to exclude one or various folders, you can disable <strong>Exclude Large-Size folders</strong> and add the specific folders in the Global File Exclusions setting below.', 'snapshot' ); ?></p>

											</div>

										</div>

									</div>

								</div>

							</div>
						</div>
						<div class="snapshot-global-exclusions-field">

							<div class="sui-form-field">
								<p class="sui-description snapshot-exclusion-titles">
									<span class="snapshot-exclusion-title"><?php esc_html_e( 'Global File Exclusions', 'snapshot' ); ?></span></br>
									<?php esc_html_e( 'Define which specific files or folders you want to exclude from backups.', 'snapshot' ); ?>
								</p>
							</div>
							<div class="sui-form-field">
								<label for="snapshot-file-exclusions" id="snapshot-file-exclusions-label" class="sui-screen-reader-text sui-label"><?php esc_html_e( 'Global file exclusions', 'snapshot' ); ?></label>
								<textarea
									placeholder="<?php esc_html_e( 'Enter file or folder URLs ', 'snapshot' ); ?>"
									id="snapshot-file-exclusions"
									class="sui-multistrings"
									aria-labelledby="snapshot-file-exclusions-label"
									aria-describedby="snapshot-file-exclusions-description"
								><?php echo wp_kses_post( $global_exclusions ); ?></textarea>
								<p class="sui-description" id="snapshot-file-exclusions-description">
									<span class="snapshot-clear-undo" style="display: <?php echo $show_clear ? 'inline' : 'none'; ?>">
										<a href="#" class="clear-exclusions" data-custom-event="clear-exclusions">
											<strong><?php esc_html_e( 'Clear exclusions', 'snapshot' ); ?></strong>
										</a>
										<a href="#" class="undo-exclusions" style="display: none;" data-custom-event="undo-exclusions">
											<i class="sui-icon-undo" aria-hidden="true"></i>
											<strong><?php esc_html_e( 'Undo', 'snapshot' ); ?></strong>
										</a> |
									</span>
									<span class="tpd-description"><?php echo wp_kses_post( $exclusions_explained ); ?></span>
								</p>
							</div>
						</div>

					</div>

				</div>

				<div class="sui-box-settings-row">

					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label"><?php esc_html_e( 'Storage Limit', 'snapshot' ); ?></span>
						<span class="sui-description"><?php esc_html_e( 'Choose the number of backups you wish to keep for manual and scheduled backups each. When this number is reached, older backups will be removed as new ones are added.', 'snapshot' ); ?></span>
					</div>

					<div class="sui-box-settings-col-2">
						<span class="sui-settings-label"><?php esc_html_e( 'Set storage limit', 'snapshot' ); ?></span>
						<?php /* translators: %s - WPMU DEV URL */ ?>
						<span class="sui-description"><?php echo wp_kses_post( sprintf( __( 'Snapshot backups are <a href="%s" target="_blank">incremental</a>, allowing you to back up your site more frequently. Configure a storage limit, up to 30 manual backups and 30 scheduled backups (60 in total), and keep them until you reach our 50-days expiry policy for backups.', 'snapshot' ), 'https://wpmudev.com/docs/wpmu-dev-plugins/snapshot-4-0/#incremental-backups' ) ); ?></span>
						<span class="sui-icon-loader sui-loading snapshot-storage-limit-loading" aria-hidden="true" style=" margin-top: 34px; "></span>

						<div class="snapshot-storage-limit-input" style="display:none;">
							<div class="sui-form-field sui-input-md" style="max-width: 140px;margin-bottom: 10px;">
								<label for="snapshot-backup-limit" id="snapshot-backup-limit-label" class="sui-label" style=" margin-top: 15px; "><?php esc_html_e( 'Backups', 'snapshot' ); ?></label>
								<div class="sui-with-button sui-with-button-inside">
									<input type="text" id="snapshot-backup-limit" name="snapshot-backup-limit" aria-labelledby="snapshot-backup-limit-label" class="sui-form-control sui-input-md">
									<button type="button" id="snapshot-backup-limit-button" aria-live="polite" class="sui-button sui-button-blue" style=" min-width: 60px; " disabled="disabled">
										<span class="sui-loading-text"><?php esc_html_e( 'Save', 'snapshot' ); ?></span>
										<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
									</button>
								</div>
							</div>
							<span id="error-snapshot-backup-limit" class="sui-error-message" style="display: none; margin-top: -2px;" role="alert"></span>
							<span class="sui-description"><?php esc_html_e( 'Note: Set a number of backups between 1 and 30.', 'snapshot' ); ?></span>
						</div>

					</div>
				</div>

				<div class="sui-box-settings-row">

					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label"><?php esc_html_e( 'Storage region', 'snapshot' ); ?></span>
						<span class="sui-description"><?php echo esc_html( Settings::get_branding_hide_doc_link() ? __( 'Choose which data center you want to use to store your backups.', 'snapshot' ) : __( 'Choose which data center you want to use to store your WPMU DEV backups.', 'snapshot' ) ); ?></span>
					</div>

					<div class="sui-box-settings-col-2">
						<span class="sui-icon-loader sui-loading snapshot-region-loading" aria-hidden="true" ></span>
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
						<span class="sui-description"><?php esc_html_e( 'You can manually delete all the backups stored on WPMU DEV Storage.', 'snapshot' ); ?></span>
					</div>
					<div class="sui-box-settings-col-2">
						<div class="sui-form-field">
							<button class="sui-button sui-button-ghost sui-button-red" id="snapshot-settings-delete-backups-confirm">
								<span class="sui-loading-text"><i class="sui-icon-trash" aria-hidden="true"></i><?php esc_html_e( 'Delete', 'snapshot' ); ?></span>
								<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
							</button>
						</div>
					</div>
				</div>

			</div>

			<div class="sui-box-footer">
				<div class="sui-actions-right">
					<input type="hidden" name="existing_backup_limit" id="existing_backup_limit">
					<button type="submit" class="sui-button sui-button-blue">
						<span class="sui-icon-save" aria-hidden="true"></span>
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

					<p><?php esc_html_e( 'Get notified when manual or scheduled backups fail or complete.', 'snapshot' ); ?></p>

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
							<span class="sui-description"><?php esc_html_e( 'Choose when you want to get notified and who should receive an email.', 'snapshot' ); ?></span>
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
									<span id="snapshot-notifications-recipients-label" class="sui-toggle-label"><?php esc_html_e( 'Enable notifications', 'snapshot' ); ?></span>
								</label>
								<div
									tabindex="0"
									id="snapshot-notification-recipients"
									class="sui-toggle-content sui-border-frame"
									aria-label="<?php esc_attr_e( 'Send an email when backups fail or complete', 'snapshot' ); ?>"
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
													<span class="sui-icon-trash" aria-hidden="true"></span>
												</button>
											</div>
										<?php } ?>
									</div>

									<button type="button" role="button" class="sui-button sui-button-ghost snapshot-add-recipient">
										<span class="sui-icon-plus" aria-hidden="true"></span>
										<?php esc_html_e( 'Add Recipient', 'snapshot' ); ?>
									</button>


									<div class="sui-form-field notification-options">
										<span class="sui-description"><?php esc_html_e( 'Choose when you want to get a notification:', 'snapshot' ); ?></span>
										<label for="snapshot-backup-fails" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
											<input
													type="checkbox"
													id="snapshot-backup-fails"
													aria-labelledby="snapshot-backup-fails-label"
													<?php echo $email_settings['email_settings']['notify_on_fail'] ? 'checked' : ''; ?>
											>
											<span aria-hidden="true"></span>
											<span id="snapshot-backup-fails-label"><?php esc_html_e( 'When a backup fails', 'snapshot' ); ?></span>
										</label>
										<label for="snapshot-backup-completes" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
											<input
												type="checkbox"
												id="snapshot-backup-completes"
												aria-labelledby="snapshot-backup-completes-label"
												<?php echo $email_settings['email_settings']['notify_on_complete'] ? 'checked' : ''; ?>
											>
											<span aria-hidden="true"></span>
											<span id="snapshot-backup-completes-label"><?php esc_html_e( 'When a backup completes', 'snapshot' ); ?></span>
										</label>
									</div>

								</div>

							</div>
						</div>

					</div>

				</div>

				<div class="sui-box-footer">
					<div class="sui-actions-right">
						<button type="submit" class="sui-button sui-button-blue">
							<span class="sui-icon-save" aria-hidden="true"></span>
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
			'errors'             => $errors,
			'welcome_modal'      => $welcome_modal,
			'welcome_modal_alt'  => $welcome_modal_alt,
			'is_branding_hidden' => $is_branding_hidden,
		)
	);

	$this->render( 'modals/create-manual-backup' );
	$this->render( 'modals/edit-backup' );
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