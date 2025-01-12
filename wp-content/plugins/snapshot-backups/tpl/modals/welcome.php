<?php // phpcs:ignore
/**
 * Welcome modal.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Helper;
use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Model\Env;

$button_class = ! empty( $button_class ) ? $button_class : 'sui-button-ghost';
$modal_title  = ! empty( $modal_title ) ? $modal_title : '';
$message      = ! empty( $message ) ? $message : '';
$message2     = ! empty( $message2 ) ? $message2 : '';
$button       = ! empty( $button ) ? $button : '';

$assets = new \WPMUDEV\Snapshot4\Helper\Assets();

$schedule  = Model\Schedule::get_schedule_info();
$frequency = $schedule['values']['frequency'];

$has_hosting_backups = Env::is_wpmu_hosting();

if ( $has_hosting_backups ) {
	/* translators: %s - Admin name */
	$message = sprintf( __( '%s, welcome to the hottest backup plugin for WordPress. We\'ve detected you\'re hosting this website with us. Great! Both Hosting and Snapshot backups are available within the plugin.', 'snapshot' ), wp_get_current_user()->display_name );
}

?>

<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="snapshot-welcome-dialog"
		class="sui-modal-content"
		aria-modal="true"
	>

		<div class="sui-modal-slide sui-active sui-loaded" id="snapshot-welcome-dialog-slide-1" data-modal-size="md">
			<div class="sui-box">

				<div class="sui-box-header sui-flatten sui-content-center">

					<div class="sui-box-banner" role="banner" aria-hidden="true"></div>

					<h3 class="sui-box-title sui-lg"><?php echo esc_html( $modal_title ); ?></h3>
					<span class="sui-description"><?php echo esc_html( $message ); ?></span>

				</div>

				<div class="sui-box-body">
					<?php
					wp_nonce_field( 'reactivate_snapshot_schedule', '_wpnonce-reactivate_snapshot_schedule' );
					wp_nonce_field( 'snapshot_check_if_region', '_wpnonce-snapshot_check_if_region' );
					?>

					<?php if ( $has_hosting_backups ) { ?>
					<div class="hosting-backups-description">
						<p class="list-header"><strong><span class="bullet">•</span><?php esc_html_e( 'Hosting Backups', 'snapshot' ); ?></strong></p>
						<p><?php esc_html_e( 'Hosting backups run nightly on a 30 day storage cycle. Backups are available to download within the plugin whereas restoring and any additional configuration is done via the Hub.', 'snapshot' ); ?></p>

						<p class="list-header" style="margin-top: 20px;"><strong><span class="bullet">•</span><?php esc_html_e( 'Snapshot Backups', 'snapshot' ); ?></strong></p>
						<p><?php esc_html_e( 'Snapshot backups can be scheduled and stored for 50 days on WPMU DEV\'s Storage Cloud.', 'snapshot' ); ?></p>
					</div>
					<?php } ?>

					<div class="sui-notice sui-notice-error on-error" style="display: none;">
						<div class="sui-notice-content">
							<div class="sui-notice-message">
								<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
								<?php /* translators: %s - link */ ?>
								<p><?php echo wp_kses_post( sprintf( __( 'A connection with the API couldn\'t be established. Give it another try below, and if you continue having connection issues, our <a href="%s" target="_blank">support team</a> is ready to help.', 'snapshot' ), 'https://premium.wpmudev.org/hub/support/#get-support' ) ); ?></p>
							</div>
						</div>
					</div>
					<div class="sui-block-content-center on-error" style="display: none;">
						<button class="sui-button sui-button-ghost snapshot-get-started" role="button" onclick="jQuery(window).trigger('snapshot:check_if_region_modal')">
							<span class="sui-button-text-default">
								<i class="sui-icon-refresh" aria-hidden="true"></i>
								<?php esc_html_e( 'Reload', 'snapshot' ); ?>
							</span>
							<span class="sui-button-text-onload">
								<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
								<?php esc_html_e( 'Reload', 'snapshot' ); ?>
							</span>
						</button>
					</div>

					<div class="sui-block-content-center on-success">
						<button class="sui-button <?php echo sanitize_html_class( $button_class ); ?> snapshot-get-started" onclick="jQuery(window).trigger('snapshot:check_if_region_modal')" >
							<span class="sui-button-text-default"><?php echo esc_html( $button ); ?></span>
							<span class="sui-button-text-onload">
								<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
								<?php echo esc_html( $button ); ?>
							</span>
						</button>

						<?php if ( $has_hosting_backups ) { ?>
						<!--<p><small><strong><a class="hosting-backups-link" href="<?php echo esc_attr( network_admin_url() . 'admin.php?page=snapshot-hosting-backups' ); ?>"><?php esc_html_e( 'View Hosting Backups', 'snapshot' ); ?></a></strong></small></p>-->
						<?php } ?>
					</div>
				</div>

			</div>
		</div>

		<div class="sui-modal-slide sui-loaded" id="snapshot-welcome-dialog-slide-2" data-modal-size="md">
			<div class="sui-box">

				<div class="sui-box-header sui-flatten sui-content-center">

					<div class="sui-box-banner" role="banner" aria-hidden="true"></div>

					<h3 class="sui-box-title sui-lg"><?php echo esc_html( __( 'Welcome to Snapshot Pro', 'snapshot' ) ); ?></h3>
					<span class="sui-description"><?php echo esc_html( __( 'Please choose the backup storage region to continue.', 'snapshot' ) ); ?></span>

				</div>

				<div class="sui-box-body">
					<div class="sui-notice sui-notice-error on-error" style="display: none;">
						<div class="sui-notice-content">
							<div class="sui-notice-message">
								<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
								<?php /* translators: %s - link */ ?>
								<p><?php echo wp_kses_post( sprintf( __( 'We were unable to proceed due to a connection problem. Please choose the storage region again, or <a href="%s" target="_blank">contact our support team</a> if the problem persists.', 'snapshot' ), 'https://premium.wpmudev.org/hub/support/#get-support' ) ); ?></p>
							</div>
						</div>
					</div>

					<form method="post" id="onboarding-region">
						<?php
						wp_nonce_field( 'save_snapshot_region', '_wpnonce-save_snapshot_region' );
						?>
						<div class="sui-form-field">

							<label for="onboarding-select-region" id="label-onboarding-select-region" class="sui-label"><?php echo esc_html( __( 'Storage Region', 'snapshot' ) ); ?></label>

							<select id="onboarding-select-region" placeholder="Choose storage region" aria-labelledby="label-onboarding-select-region" aria-describedby="description-onboarding-select-region">
								<option value="us"><?php echo esc_html( __( 'United States (better performance, recommended)', 'snapshot' ) ); ?></option>
								<option value="eu"><?php echo esc_html( __( 'Europe (EU data protection directive compliant)', 'snapshot' ) ); ?></option>
							</select>

						</div>

						<div class="sui-box-footer sui-flatten sui-lg sui-content-center">
							<button type="button" id="snapshot-set-initial-region" class="sui-button" onclick="jQuery(window).trigger('snapshot:save_region')">
								<span class="sui-button-text-default"><?php esc_html_e( 'Continue', 'snapshot' ); ?></span>
								<span class="sui-button-text-onload">
									<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
									<?php esc_html_e( 'Continue', 'snapshot' ); ?>
								</span>
							</button>
						</div>
					</form>
				</div>

			</div>
		</div>

		<div class="sui-modal-slide sui-loaded" id="snapshot-welcome-dialog-slide-3" data-modal-size="md">
			<div class="sui-box">

				<div class="sui-box-header sui-flatten sui-content-center">

					<div class="sui-box-banner" role="banner" aria-hidden="true"></div>
					<button class="sui-button-icon sui-button-float--right close-modal">
						<i class="sui-icon-close sui-md" aria-hidden="true"></i>
					</button>
					<button class="sui-button-icon sui-button-float--left hide-when-region-selected" data-modal-slide="snapshot-welcome-dialog-slide-2">
						<i class="sui-icon-chevron-left sui-md" aria-hidden="true"></i>
						<span class="sui-screen-reader-text"><?php esc_html_e( 'Back to Choose Region' ); ?></span>
					</button>

					<h3 class="sui-box-title sui-lg"><?php esc_html_e( 'Welcome to Snapshot Pro', 'snapshot' ); ?></h3>
					<span class="sui-description"><?php esc_html_e( 'Choose your backup schedule.', 'snapshot' ); ?></span>

				</div>

				<form method="post" id="onboarding-schedule" data-show-schedule-notice="true">

					<?php wp_nonce_field( 'backup_schedule' ); ?>
					<input type="hidden" name="schedule_action" value="<?php echo esc_attr( $schedule['schedule_action'] ); ?>">
					<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
					<input type="hidden" name="files" value="<?php echo esc_attr( $files ); ?>">
					<input type="hidden" name="tables" value="<?php echo esc_attr( $tables ); ?>">

					<div class="sui-box-body sui-lg">

						<div class="sui-flushed">
							<div class="sui-box-settings-col-2">

								<div class="sui-tabs sui-side-tabs">

									<div data-tabs>
										<div class="<?php echo 'daily' === $frequency ? 'active' : ''; ?>" data-frequency="daily"><?php esc_html_e( 'Daily', 'snapshot' ); ?></div>
										<div class="<?php echo ( 'weekly' === $frequency || ! $frequency ) ? 'active' : ''; ?>" data-frequency="weekly"><?php esc_html_e( 'Weekly', 'snapshot' ); ?></div>
										<div class="<?php echo 'monthly' === $frequency ? 'active' : ''; ?>" data-frequency="monthly"><?php esc_html_e( 'Monthly', 'snapshot' ); ?></div>
										<div class="" data-frequency=""><?php esc_html_e( 'None', 'snapshot' ); ?></div>
									</div>

									<div data-panes>

										<div class="sui-tab-boxed <?php echo 'daily' === $frequency ? 'active' : ''; ?>">
											<label for="snapshot-welcome-daily-time" class="sui-label"><?php esc_html_e( 'Time of the day', 'snapshot' ); ?></label>
											<select id="snapshot-welcome-daily-time" name="daily_time">
											<?php foreach ( Helper\Datetime::get_hour_list() as $value => $text ) { ?>
											<option  <?php echo $value === $schedule['values']['time'] ? 'selected' : ''; ?> value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $text ); ?></option>
											<?php } ?>
											</select>
										</div>

										<div class="sui-tab-boxed <?php echo ( 'weekly' === $frequency || ! $frequency ) ? 'active' : ''; ?>">
											<div class="sui-row">
												<div class="sui-col-sm-6">
													<label for="snapshot-welcome-weekly-dow" class="sui-label"><?php esc_html_e( 'Day of the week', 'snapshot' ); ?></label>
													<select id="snapshot-welcome-weekly-dow" name="frequency_weekday">
														<option <?php echo 1 === $schedule['values']['frequency_weekday'] ? 'selected' : ''; ?> value="1"><?php esc_html_e( 'Sunday', 'snapshot' ); ?></option>
														<option <?php echo ( 2 === $schedule['values']['frequency_weekday'] || ! $schedule['values']['frequency_weekday'] ) ? 'selected' : ''; ?> value="2"><?php esc_html_e( 'Monday', 'snapshot' ); ?></option>
														<option <?php echo 3 === $schedule['values']['frequency_weekday'] ? 'selected' : ''; ?> value="3"><?php esc_html_e( 'Tuesday', 'snapshot' ); ?></option>
														<option <?php echo 4 === $schedule['values']['frequency_weekday'] ? 'selected' : ''; ?> value="4"><?php esc_html_e( 'Wednesday', 'snapshot' ); ?></option>
														<option <?php echo 5 === $schedule['values']['frequency_weekday'] ? 'selected' : ''; ?> value="5"><?php esc_html_e( 'Thursday', 'snapshot' ); ?></option>
														<option <?php echo 6 === $schedule['values']['frequency_weekday'] ? 'selected' : ''; ?> value="6"><?php esc_html_e( 'Friday', 'snapshot' ); ?></option>
														<option <?php echo 7 === $schedule['values']['frequency_weekday'] ? 'selected' : ''; ?> value="7"><?php esc_html_e( 'Saturday', 'snapshot' ); ?></option>
													</select>
												</div>
												<div class="sui-col-sm-6">
													<label for="snapshot-welcome-weekly-time" class="sui-label"><?php esc_html_e( 'Time of the day', 'snapshot' ); ?></label>
													<select id="snapshot-welcome-weekly-time" name="weekly_time">
													<?php foreach ( Helper\Datetime::get_hour_list() as $value => $text ) { ?>
														<?php $w_time = isset( $schedule['values']['time'] ) ? $schedule['values']['time'] : '00:00'; ?>
													<option <?php echo $value === $w_time ? 'selected' : ''; ?> value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $text ); ?></option>
													<?php } ?>
													</select>
												</div>
											</div>
										</div>

										<div class="sui-tab-boxed <?php echo 'monthly' === $frequency ? 'active' : ''; ?>">
											<div class="sui-row">
												<div class="sui-col-sm-6">
													<label for="snapshot-welcome-monthly-day" class="sui-label"><?php esc_html_e( 'Day of the month', 'snapshot' ); ?></label>
													<select id="snapshot-welcome-monthly-day" name="frequency_monthday">
													<?php foreach ( range( 1, 28 ) as $day ) { ?>
														<option <?php echo $day === $schedule['values']['frequency_monthday'] ? 'selected' : ''; ?> value="<?php echo esc_attr( $day ); ?>"><?php echo esc_html( $day ); ?></option>
													<?php } ?>
													</select>
												</div>
												<div class="sui-col-sm-6">
													<label for="snapshot-welcome-monthly-time" class="sui-label"><?php esc_html_e( 'Time of the day', 'snapshot' ); ?></label>
													<select id="snapshot-welcome-monthly-time" name="monthly_time">
													<?php foreach ( Helper\Datetime::get_hour_list() as $value => $text ) { ?>
													<option <?php echo $value === $schedule['values']['time'] ? 'selected' : ''; ?> value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $text ); ?></option>
													<?php } ?>
													</select>
												</div>
											</div>
										</div>

										<div></div>

									</div>

								</div>
							</div>
						</div>

					</div>

					<div class="sui-box-footer sui-flatten sui-lg sui-content-center">
						<button type="submit" class="sui-button sui-button-blue" aria-live="polite">
							<span class="sui-button-text-default"><?php esc_html_e( 'Save', 'snapshot' ); ?></span>
							<span class="sui-button-text-onload">
								<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
								<?php esc_html_e( 'Saving', 'snapshot' ); ?>
							</span>
						</button>
					</div>

				</form>

			</div>
		</div>

	</div>
</div>