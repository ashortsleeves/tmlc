<?php // phpcs:ignore
/**
 * Settings page.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Helper\Api;

$assets = new \WPMUDEV\Snapshot4\Helper\Assets();
wp_nonce_field( 'save_snapshot_settings', '_wpnonce-save_snapshot_settings' );
wp_nonce_field( 'reset_snapshot_settings', '_wpnonce-reset_snapshot_settings' );
?>
<div class="sui-wrap snapshot-page-settings">

	<div class="sui-header">
		<h1 class="sui-header-title"><?php esc_html_e( 'Settings', 'snapshot' ); ?></h1>
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

	<div class="sui-row-with-sidenav snapshot-page-main">

		<div class="sui-sidenav">
			<ul class="sui-vertical-tabs sui-sidenav-hide-md">
				<li class="sui-vertical-tab current snapshot-vertical-api-key">
					<a href="#" data-tab="api-key"><?php esc_attr_e( 'General', 'snapshot' ); ?></a>
				</li>
				<li class="sui-vertical-tab snapshot-vertical-data-and-settings">
					<a href="#" data-tab="data-and-settings"><?php esc_attr_e( 'Data & Settings', 'snapshot' ); ?></a>
				</li>
			</ul>

			<div class="sui-sidenav-hide-lg">
				<select class="sui-mobile-nav" style="display: none;">
					<option value="api-key" selected="selected"><?php esc_attr_e( 'General', 'snapshot' ); ?></option>
					<option value="data-and-settings"><?php esc_attr_e( 'Data & Settings', 'snapshot' ); ?></option>
				</select>
			</div>
		</div>

		<div class="sui-box snapshot-tab-api-key">
		<form id="snapshot-settings-save-tab-1">

			<div class="sui-box-header">
				<h2 class="sui-box-title"><?php esc_html_e( 'General', 'snapshot' ); ?></h2>
			</div>

			<div class="sui-box-body">

				<div class="sui-box-settings-row">
					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label"><?php esc_html_e( 'Snapshot API Key', 'snapshot' ); ?></span>
						<span class="sui-description"><?php esc_html_e( 'This is your Snapshot API Key.', 'snapshot' ); ?></span>
					</div>
					<div class="sui-box-settings-col-2">
						<div class="sui-form-field">
							<label for="snapshot-api-key" class="sui-label"><?php esc_html_e( 'Your Snapshot API Key', 'snapshot' ); ?></label>
							<div class="sui-with-button sui-with-button-inside">
								<input type="text" id="snapshot-api-key" class="sui-form-control" readonly value="<?php echo esc_attr( Api::get_api_key() ); ?>">
								<a class="sui-button" id="snapshot-settings-copy-api-key">
									<i class="sui-icon-copy" aria-hidden="true"></i>
									<?php esc_html_e( 'Copy', 'snapshot' ); ?>
								</a>
							</div>
						</div>
					</div>
				</div>

				<div class="sui-box-settings-row">
					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label"><?php esc_html_e( 'Site ID', 'snapshot' ); ?></span>
						<span class="sui-description"><?php esc_html_e( 'This is your website\'s site ID.', 'snapshot' ); ?></span>
					</div>
					<div class="sui-box-settings-col-2">
						<div class="sui-form-field">
							<label for="snapshot-site-id" class="sui-label"><?php esc_html_e( 'Site ID', 'snapshot' ); ?></label>
							<div class="sui-with-button sui-with-button-inside">
								<input type="text" id="snapshot-site-id" class="sui-form-control" readonly value="<?php echo esc_attr( Api::get_site_id() ); ?>">
								<a class="sui-button" id="snapshot-settings-copy-site-id">
									<i class="sui-icon-copy" aria-hidden="true"></i>
									<?php esc_html_e( 'Copy', 'snapshot' ); ?>
								</a>
							</div>
						</div>
					</div>
				</div>

			</div>

		</form>
		</div>

		<div class="sui-box snapshot-tab-data-and-settings" style="display: none;">
		<form id="snapshot-settings-save-tab-2">

			<div class="sui-box-header">
				<h2 class="sui-box-title"><?php esc_html_e( 'Data & Settings', 'snapshot' ); ?></h2>
			</div>

			<div class="sui-box-body">

				<div class="sui-box-settings-row">
					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label"><?php esc_html_e( 'Uninstall', 'snapshot' ); ?></span>
						<span class="sui-description"><?php esc_html_e( 'When uninstalling the plugin, what would you like to do with the settings?', 'snapshot' ); ?></span>
					</div>
					<div class="sui-box-settings-col-2">
						<div style="margin-bottom: 5px;">
							<span class="sui-settings-label"><?php esc_html_e( 'Settings', 'snapshot' ); ?></span>
							<span class="sui-description"><?php esc_html_e( 'Choose whether to save your settings for next time, or reset them.', 'snapshot' ); ?></span>
						</div>
						<div class="sui-side-tabs">
							<div class="sui-tabs-menu">
								<label class="sui-tab-item <?php echo ! $remove_on_uninstall ? 'active' : ''; ?>">
									<input type="radio" name="remove_on_uninstall" value="0" <?php echo ! $remove_on_uninstall ? 'checked' : ''; ?>>
									<?php esc_html_e( 'Keep', 'snapshot' ); ?>
								</label>
								<label class="sui-tab-item <?php echo $remove_on_uninstall ? 'active' : ''; ?>">
									<input type="radio" name="remove_on_uninstall" value="1" <?php echo $remove_on_uninstall ? 'checked' : ''; ?>>
									<?php esc_html_e( 'Remove', 'snapshot' ); ?>
								</label>
							</div>
						</div>
					</div>
				</div>

				<div class="sui-box-settings-row">
					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label"><?php esc_html_e( 'Reset settings', 'snapshot' ); ?></span>
						<span class="sui-description"><?php esc_html_e( 'Needing to start fresh? Use this button to roll back to the default settings.', 'snapshot' ); ?></span>
					</div>
					<div class="sui-box-settings-col-2">
						<div class="sui-form-field">
							<button class="sui-button sui-button-ghost" id="snapshot-settings-reset-settings-confirm">
								<i class="sui-icon-undo" aria-hidden="true"></i>
								<?php esc_html_e( 'Reset', 'snapshot' ); ?>
							</button>
							<p><small><?php esc_html_e( 'Note this will instantly reset all setting back to their defaults, and wipe any destinations you have active. It won’t delete existing backups.', 'snapshot' ); ?></small></p>
						</div>
					</div>
				</div>

			</div>

			<div class="sui-box-footer">
				<div class="sui-actions-right">
					<button class="sui-button sui-button-blue" type="submit">
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

	$this->render( 'modals/settings-reset-settings' );
	$this->render( 'modals/confirm-v3-uninstall' );

	$this->render( 'common/footer' );

	?>

</div> <?php // .sui-wrap ?>