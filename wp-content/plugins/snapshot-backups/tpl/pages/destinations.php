<?php // phpcs:ignore
/**
 * Main destinations page.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Task;

$assets = new \WPMUDEV\Snapshot4\Helper\Assets();
wp_nonce_field( 'save_snapshot_settings', '_wpnonce-save_snapshot_settings' );
wp_nonce_field( 'snapshot_list_backups', '_wpnonce-list-backups' );
wp_nonce_field( 'snapshot_get_storage', '_wpnonce-snapshot_get_storage' );
wp_nonce_field( 'snapshot_get_destinations', '_wpnonce-snapshot-get-destinations' );
wp_nonce_field( 'snapshot_delete_destination', '_wpnonce-snapshot-delete-destination' );
?>
<div class="sui-wrap snapshot-page-destinations">
	<div class="sui-header">
		<h1 class="sui-header-title"><?php esc_html_e( 'Destinations', 'snapshot' ); ?></h1>
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

	<div class="sui-box sui-summary snapshot-destinations-summary">

		<div class="sui-summary-image-space" aria-hidden="true"></div>

		<div class="sui-summary-segment">

			<div class="sui-summary-details">

				<span class="sui-summary-large" style="visibility: hidden;">1</span>
				<i class="sui-icon-loader sui-loading" aria-hidden="true" style="position: relative; left: -25px;"></i>
				<span class="sui-summary-sub"><span class="singular"><?php esc_html_e( 'Destination', 'snapshot' ); ?></span><span class="plural" style="display: none;"><?php esc_html_e( 'Destinations', 'snapshot' ); ?></span></span>

			</div>

		</div>

		<div class="sui-summary-segment">

			<ul class="sui-list">

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Last backup destination', 'snapshot' ); ?></span>
					<span class="sui-list-detail"><?php esc_html_e( 'WPMU DEV', 'snapshot' ); ?></span></span>
				</li>

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'WPMU DEV storage space', 'snapshot' ); ?></span>
					<!--<span class="sui-list-detail">-->
					<div class="snapshot-current-stats">
						<div class="sui-progress">
							<i class="sui-icon-loader sui-loading snapshot-storage-loading" aria-hidden="true"></i>
							<div class="sui-progress-bar wpmudev-snapshot-storage" aria-hidden="true" style="display: none;">
								<span style="width: 0%;"></span>
							</div>
						</div>
						<div class="used-space" style="display: none;"></div>
					</div>
					<!--</span>-->
				</li>

			</ul>

		</div>

	</div>

	<div class="sui-box snapshot-destinations">

		<div class="sui-box-header">
			<h3 class="sui-box-title"><?php esc_html_e( 'Destinations', 'snapshot' ); ?></h3>
			<div class="sui-actions-right">

				<button class="sui-button sui-button-blue" id="snapshot-add-destination">
					<i class="sui-icon-plus" aria-hidden="true"></i>
					<?php esc_html_e( 'Add destination', 'snapshot' ); ?>
				</button>

			</div>
		</div>

		<div class="sui-box-body">
			<p><?php esc_html_e( 'View and manage your available destinations. After each backup, the WPMU DEV API will send a full site backup to all enabled third-party destinations.', 'snapshot' ); ?></p>
		</div>
		<table class="sui-table sui-table-flushed">
			<thead>
				<tr class="sui-hidden-xs sui-hidden-sm">
					<th><?php esc_html_e( 'Name', 'snapshot' ); ?></th>
					<th><?php esc_html_e( 'Directory', 'snapshot' ); ?></th>
					<th><?php esc_html_e( 'Schedule', 'snapshot' ); ?></th>
					<th><?php esc_html_e( 'Exported Backups', 'snapshot' ); ?></th>
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

					<td class="sui-hidden-xs sui-hidden-sm"></td>
					<td class="sui-hidden-xs sui-hidden-sm"><?php echo esc_html( $schedule_frequency ); ?></td>
					<td class="sui-hidden-xs sui-hidden-sm"><i class="sui-icon-loader sui-loading snapshot-loading" aria-hidden="true"></i><span class="wpmudev-backup-count"></span></td>

					<td colspan="5" class="sui-table-item-title first-child sui-hidden-md sui-hidden-lg mobile-row">
						<div class="destination-name"><i class="sui-icon-wpmudev-logo"></i><?php esc_html_e( 'WPMU DEV', 'snapshot' ); ?></div>
						<div class="sui-row destination-cells">
							<div class="sui-col-xs-6">
								<div class="sui-table-item-title"><?php esc_html_e( 'Directory', 'snapshot' ); ?></div>
								<div class="sui-table-item-title destination-path"><!--i class="sui-icon-folder sui-md" aria-hidden="true"></i><span></span--></div>
							</div>

							<div class="sui-col-xs-6">
								<div class="sui-table-item-title"><?php esc_html_e( 'Schedule', 'snapshot' ); ?></div>
								<div class="sui-table-item-title"><?php echo esc_html( $schedule_frequency ); ?></div>
							</div>

							<div class="sui-col-xs-6">
								<div class="sui-table-item-title"><?php esc_html_e( 'Exported Backups', 'snapshot' ); ?></div>
								<div class="sui-table-item-title backup-count"><i class="sui-icon-loader sui-loading snapshot-loading" aria-hidden="true"></i><span class="wpmudev-backup-count"></span></div>
							</div>
						</div>
					</td>

					<td></td>
				</tr>
			</tbody>
		</table>


		<div class="sui-box-footer">
			<div class="snapshot-loader">
				<p><i class="sui-icon-loader sui-loading" aria-hidden="true"></i><span class="loader-text"><?php esc_html_e( 'Loading destinations...', 'snapshot' ); ?></span></p>
			</div>

			<div class="api-error" style="display: none;">
				<div class="sui-notice sui-notice-error">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-warning-alert sui-md" aria-hidden="true"></span>
							<?php /* translators: %s - Link for support */ ?>
							<p><?php echo wp_kses_post( sprintf( __( 'We were unable to list the destinations due to a connection problem. Give it another try below, or <a href="%s" target="_blank">contact our support team</a> if the problem persists.', 'snapshot' ), Task\Backup\Fail::URL_CONTACT_SUPPORT ) ); ?></p>
						</div>
					</div>
				</div>
				<button class="sui-button sui-button-ghost" role="button" id="button-reload-destinations"><i class="sui-icon-refresh" aria-hidden="true"></i><?php esc_html_e( 'Reload', 'snapshot' ); ?></button>
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
	$this->render(
		'modals/add-destination',
		array(
			'auth_url' => $auth_url,
		)
	);
	$this->render( 'modals/destinations-delete' );
	$this->render( 'modals/destination-s3-edit' );
	$this->render( 'modals/destination-gdrive-edit' );
	$this->render( 'common/footer' );

	?>

</div> <?php // .sui-wrap ?>