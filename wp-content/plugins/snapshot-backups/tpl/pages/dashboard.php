<?php // phpcs:ignore
/**
 * Dashboard page.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Model\Env;

$assets = new \WPMUDEV\Snapshot4\Helper\Assets();
wp_nonce_field( 'snapshot_list_backups', '_wpnonce-list-backups' );
wp_nonce_field( 'save_snapshot_settings', '_wpnonce-save_snapshot_settings' );
wp_nonce_field( 'snapshot_get_storage', '_wpnonce-snapshot_get_storage' );
wp_nonce_field( 'snapshot_list_hosting_backups', '_wpnonce-list-hosting-backups' );
wp_nonce_field( 'snapshot_get_destinations', '_wpnonce-snapshot-get-destinations' );
wp_nonce_field( 'snapshot_update_destination', '_wpnonce-snapshot-update-destination' );

$has_hosting_backups = Env::is_wpmu_hosting();

?>
<div class="sui-wrap snapshot-page-dashboard">
	<div class="sui-header">
		<h1 class="sui-header-title"><?php esc_html_e( 'Dashboard', 'snapshot' ); ?></h1>
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
	<div class="sui-box sui-summary snapshot-dashboard-summary">

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
					<span class="sui-list-label"><?php esc_html_e( 'Active destinations', 'snapshot' ); ?></span>
					<span class="sui-list-detail"><i class="sui-icon-loader sui-loading snapshot-destinations-number-loading" aria-hidden="true"></i><span class="snapshot-destinations-number"></span></span>
				</li>

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Last backup', 'snapshot' ); ?></span>
					<span class="sui-list-detail"><i class="sui-icon-loader sui-loading snapshot-loading" aria-hidden="true"></i><span class="snapshot-last-backup"></span></span>
				</li>

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'WPMU DEV storage space', 'snapshot' ); ?></span>
					<div class="snapshot-current-stats">
						<div class="sui-progress">
							<i class="sui-icon-loader sui-loading snapshot-storage-loading" aria-hidden="true"></i>
							<div class="sui-progress-bar wpmudev-snapshot-storage" aria-hidden="true" style="display: none;">
								<span style="width: 0%;"></span>
							</div>
						</div>
						<div class="used-space" style="display: none;"></div>
					</div>
				</li>

			</ul>

		</div>

	</div>

	<div class="sui-row">
		<div class="sui-col-lg-6">
			<div class="sui-box snapshot-dashboard-backups">

				<div class="sui-box-header">
					<h3 class="sui-box-title">
					<i class="sui-icon-snapshot" aria-hidden="true"></i> <?php esc_html_e( 'Snapshot Backups', 'snapshot' ); ?>
					</h3>
				</div>

				<div class="sui-box-body api-error">
					<div class="sui-notice sui-notice-error">
						<div class="sui-notice-content">
							<div class="sui-notice-message">
								<span class="sui-notice-icon sui-icon-warning-alert sui-md" aria-hidden="true"></span>
								<p><?php echo wp_kses_post( sprintf( 'We were unable to fetch backup data from the API due to a connection problem. Give it another try below, or <a href="%s" target="_blank">contact our support team</a> if the problem persists.', 'https://premium.wpmudev.org/hub/support/#get-support' ) ); ?></p>
							</div>
						</div>
					</div>
					<button class="sui-button sui-button-ghost" role="button" id="button-reload-backups"><i class="sui-icon-refresh" aria-hidden="true"></i><?php esc_html_e( 'Reload', 'snapshot' ); ?></button>
				</div>

				<div class="sui-message snapshot-backup-list-loader snapshot-loading">
					<div class="sui-message-content">
						<p><i class="sui-icon-loader sui-loading" aria-hidden="true"></i> <?php esc_html_e( 'Loading backups...', 'snapshot' ); ?></p>
					</div>
				</div>

				<div class="sui-box-body">
					<p>
						<?php esc_html_e( 'Here are your latest backups. Check backups page for the full list of available backups.', 'snapshot' ); ?>
					</p>
				</div>
				<div class="snapshot-listed-backups">
					<table class="sui-table sui-table-flushed">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title', 'snapshot' ); ?></th>
								<th><?php esc_html_e( 'Destination', 'snapshot' ); ?></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>

				<div class="sui-box-footer">
					<a href="<?php echo esc_attr( network_admin_url() . 'admin.php?page=snapshot-backups' ); ?>" class="sui-button sui-button-ghost"><i class="sui-icon-eye" aria-hidden="true"></i><?php esc_html_e( 'View all', 'snapshot' ); ?></a>
					<a <?php echo $disable_backup_button ? 'disabled' : ''; ?> href="?page=snapshot-backups#create-backup" class="sui-button sui-button-blue snapshot-not-cooldown"><?php esc_html_e( 'Backup now', 'snapshot' ); ?></a>
					<div class="sui-tooltip sui-tooltip-constrained sui-tooltip-top-left-mobile snapshot-cooldown" style="--tooltip-width: 174px; display: none;" data-tooltip="<?php esc_html_e( 'Snapshot is just catching his breath. You can run another backup in a minute.', 'snapshot' ); ?>">
						<button class="sui-button sui-button-blue" disabled>
							<?php esc_html_e( 'Backup now', 'snapshot' ); ?>
						</button>
					</div>
				</div>

				<div class="sui-box sui-message snapshot-listed-backups-empty">

					<img src="<?php echo esc_attr( $assets->get_asset( 'img/snapshot-dashboard-hero-backups.svg' ) ); ?>"
						class="sui-image"
						aria-hidden="true" />

					<div class="sui-message-content">
						<p><?php esc_html_e( 'Create full backups and send them to your connected destinations. Let\'s go!', 'snapshot' ); ?></p>
						<p>
							<a href="?page=snapshot-backups#create-backup" class="sui-button sui-button-blue">
								<?php
								esc_html_e( 'Backup now', 'snapshot' );
								?>
							</a>
						</p>
					</div>

				</div>
			</div>
		</div>

	<?php if ( $has_hosting_backups ) { ?>
		<div class="sui-col-lg-6">
			<div class="sui-box snapshot-dashboard-hosting-backups">
				<div class="sui-box-header">
					<h3 class="sui-box-title">
						<i class="sui-icon-cloud" aria-hidden="true"></i>
						<?php esc_html_e( 'Hosting Backups', 'snapshot' ); ?>
					</h3>
				</div>
				<div class="sui-box-body">
					<p class="body-description" style="display: none; margin-bottom: 5px;"><?php esc_html_e( 'Here are your latest hosting backups. Check the Hosting Backups page for the full list of available backups.', 'snapshot' ); ?></p>

					<div class="api-error" style="display: none;">
						<div class="sui-notice sui-notice-error" style="margin-bottom: 10px;">
							<div class="sui-notice-content">
								<div class="sui-notice-message">
									<span class="sui-notice-icon sui-icon-warning-alert sui-md" aria-hidden="true"></span>
									<p><?php echo wp_kses_post( sprintf( 'We were unable to fetch backup data from the API due to a connection problem. Give it another try below, or <a href="%s" target="_blank">contact our support team</a> if the problem persists.', 'https://premium.wpmudev.org/hub/support/#get-support' ) ); ?></p>
								</div>
							</div>
						</div>
						<button class="sui-button sui-button-ghost" role="button" id="button-reload-hosting-backups"><i class="sui-icon-refresh" aria-hidden="true"></i><?php esc_html_e( 'Reload', 'snapshot' ); ?></button>
					</div>

					<div class="sui-message snapshot-hosting-backup-list-loader snapshot-loading">
						<div class="sui-message-content">
							<p><i class="sui-icon-loader sui-loading" aria-hidden="true"></i> <?php esc_html_e( 'Loading backups...', 'snapshot' ); ?></p>
						</div>
					</div>
				</div>

				<table class="sui-table sui-table-flushed snapshot-listed-hosting-backups" style="display: none;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Title', 'snapshot' ); ?></th>
							<th><?php esc_html_e( 'Destination', 'snapshot' ); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>

				<div class="sui-box-footer" style="display: none;">
					<a class="sui-button sui-button-ghost" href="<?php echo esc_attr( network_admin_url() . 'admin.php?page=snapshot-hosting-backups' ); ?>">
						<i class="sui-icon-eye" aria-hidden="true"></i>
						<?php esc_html_e( 'View all', 'snapshot' ); ?>
					</a>
				</div>
			</div>
		</div>

	</div>
	<div class="sui-row">
	<?php } ?>

		<div class="sui-col-lg-6">

			<div class="sui-box snapshot-dashboard-destinations">

				<div class="sui-box-header">
					<h3 class="sui-box-title">
					<i class="sui-icon-cloud" aria-hidden="true"></i> <?php esc_html_e( 'Destinations', 'snapshot' ); ?>
					</h3>
				</div>

				<div class="sui-box-body">
					<p><?php esc_html_e( 'View and manage your available destinations. After each backup, the WPMU DEV API will send a full site backup to all enabled third-party destinations.', 'snapshot' ); ?></p>
				</div>

				<div>
					<table class="sui-table sui-table-flushed">
						<thead>
							<tr class="sui-hidden-xs sui-hidden-sm">
								<th><?php esc_html_e( 'Destination', 'snapshot' ); ?></th>
								<th width="60"></th>
							</tr>
							<tr class="sui-hidden-md sui-hidden-lg">
								<th colspan="6" style="height: 0; padding: 0;"></th>
							</tr>
						</thead>
						<tbody>
							<tr class="destination-row">
								<td class="sui-table-item-title sui-hidden-xs sui-hidden-sm row-icon row-icon-wpmudev">
									<div class="tooltip-container">
										<div class="tooltip-background"></div>
										<div class="tooltip-block"></div><?php esc_html_e( 'WPMU DEV', 'snapshot' ); ?>
									</div>
								</td>

								<td colspan="5" class="sui-table-item-title first-child sui-hidden-md sui-hidden-lg mobile-row">
									<div class="destination-name"><i class="sui-icon-wpmudev-logo"></i><?php esc_html_e( 'WPMU DEV', 'snapshot' ); ?></div>
								</td>

								<td></td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="snapshot-loader">
					<p><i class="sui-icon-loader sui-loading" aria-hidden="true"></i><span class="loader-text"><?php esc_html_e( 'Loading destinations...', 'snapshot' ); ?></span></p>
				</div>

				<div class="sui-box-footer sui-space-between">
					<a href="<?php echo esc_attr( network_admin_url() . 'admin.php?page=snapshot-destinations' ); ?>" class="sui-button sui-button-ghost">
						<i class="sui-icon-eye" aria-hidden="true"></i>
						<?php esc_html_e( 'View all', 'snapshot' ); ?>
					</a>

					<a href="<?php echo esc_attr( network_admin_url() . 'admin.php?page=snapshot-destinations#add-destination' ); ?>" class="sui-button sui-button-blue">
						<i class="sui-icon-plus" aria-hidden="true"></i>
						<?php esc_html_e( 'Add Destination', 'snapshot' ); ?>
					</a>
				</div>

			</div>
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

	$this->render( 'modals/confirm-v3-uninstall' );
	$this->render( 'common/footer' );
	?>

</div> <?php // .sui-wrap ?>