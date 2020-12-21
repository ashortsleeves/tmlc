<?php // phpcs:ignore
/**
 * Hosting backups page.
 *
 * @package snapshot
 */

$assets = new \WPMUDEV\Snapshot4\Helper\Assets();

wp_nonce_field( 'snapshot_list_hosting_backups', '_wpnonce-list-hosting-backups' );
wp_nonce_field( 'snapshot_download_hosting_backup', '_wpnonce-download-hosting-backup' );

?>
<div class="sui-wrap snapshot-page-hosting-backups">
	<div class="sui-header">
		<h1 class="sui-header-title"><?php esc_html_e( 'Hosting Backups', 'snapshot' ); ?></h1>
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

	<div class="sui-box sui-summary snapshot-hosting-backups-summary">

		<div class="sui-summary-image-space" aria-hidden="true"></div>

		<div class="sui-summary-segment">

			<div class="sui-summary-details snapshot-backups-number">

				<span class="sui-summary-large snapshot-hosting-backup-count"></span>
				<i class="sui-icon-loader sui-loading snapshot-loading" aria-hidden="true"></i>
				<span class="sui-summary-sub"><?php esc_html_e( 'Backups available', 'snapshot' ); ?></span>

			</div>

		</div>

		<div class="sui-summary-segment">

			<ul class="sui-list">

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Last backup', 'snapshot' ); ?></span>
					<span class="sui-list-detail"><i class="sui-icon-loader sui-loading snapshot-loading" aria-hidden="true"></i><span class="snapshot-last-hosting-backup"></span></span>
				</li>

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Next scheduled backup', 'snapshot' ); ?></span>
					<span class="sui-list-detail"><i class="sui-icon-loader sui-loading snapshot-loading" aria-hidden="true"></i><span class="snapshot-next-hosting-backup"></span></span>
				</li>

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Backup schedule', 'snapshot' ); ?></span>
					<span class="sui-list-detail">
						<i class="sui-icon-loader sui-loading snapshot-loading" aria-hidden="true"></i>
						<span class="snapshot-hosting-backup-schedule sui-tooltip sui-tooltip-top-right sui-tooltip-constrained"></span>
					</span>
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
				<li class="sui-vertical-tab snapshot-vertical-settings">
					<a href="#settings"><?php esc_html_e( 'Settings', 'snapshot' ); ?></a>
				</li>
			</ul>

			<div class="sui-sidenav-hide-lg">
				<select class="sui-mobile-nav" style="display: none;">
					<option value="backups" selected="selected"><?php esc_html_e( 'Backups', 'snapshot' ); ?></option>
					<option value="settings"><?php esc_html_e( 'Settings', 'snapshot' ); ?></option>
				</select>
			</div>

		</div>

		<div class="sui-box snapshot-hosting-backups-backups">
			<div class="sui-box-header">
				<h2 class="sui-box-title"><?php esc_html_e( 'Available backups', 'snapshot' ); ?></h2>
			</div>
			<div class="sui-box-body" style="padding-bottom: 0;">
				<p><?php esc_html_e( 'Here are all of your available hosting backups.', 'snapshot' ); ?></p>

				<div class="api-error" style="display: none;">
					<div class="sui-notice sui-notice-error" style="margin-bottom: 10px;">
						<div class="sui-notice-content">
							<div class="sui-notice-message">
								<span class="sui-notice-icon sui-icon-warning-alert sui-md" aria-hidden="true"></span>
								<p><?php echo wp_kses_post( sprintf( 'We were unable to fetch backup data from the API due to a connection problem. Give it another try below, or <a href="%s" target="_blank">contact our support team</a> if the problem persists.', 'https://premium.wpmudev.org/hub/support/#get-support' ) ); ?></p>
							</div>
						</div>
					</div>
					<button class="sui-button sui-button-ghost reload-backups" role="button"><i class="sui-icon-refresh" aria-hidden="true"></i><?php esc_html_e( 'Reload', 'snapshot' ); ?></button>
				</div>

				<div class="sui-message snapshot-backup-list-loader">
					<div class="sui-message-content">
						<p><i class="sui-icon-loader sui-loading" aria-hidden="true"></i> <?php esc_html_e( 'Loading backups...', 'snapshot' ); ?></p>
					</div>
				</div>
			</div>

			<table class="sui-table sui-table-flushed sui-accordion snapshot-hosting-backups-table" style="display: none;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'snapshot' ); ?></th>
						<th><?php esc_html_e( 'Destination', 'snapshot' ); ?></th>
						<th><?php esc_html_e( 'Time', 'snapshot' ); ?></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>

			<div style="height: 30px;"></div>

		</div>

		<div class="sui-box snapshot-hosting-backups-settings" style="display: none;">
			<div class="sui-box-header">
				<h2 class="sui-box-title"><?php esc_html_e( 'Settings', 'snapshot' ); ?></h2>
			</div>
			<div class="sui-box-body">
				<div class="sui-box-settings-row">
					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label"><?php esc_html_e( 'Storage Limit', 'snapshot' ); ?></span>
						<span class="sui-description"><?php esc_html_e( 'Hosting backups are stored for 30 days before being removed.', 'snapshot' ); ?></span>
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