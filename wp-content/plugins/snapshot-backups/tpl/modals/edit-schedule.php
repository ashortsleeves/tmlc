<?php // phpcs:ignore
/**
 * Modal for editing an existing schedule.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Helper;

$assets = new \WPMUDEV\Snapshot4\Helper\Assets();
?>

<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="modal-snapshot-edit-schedule"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="modal-snapshot-edit-schedule-title"
		aria-describedby="modal-snapshot-edit-schedule-description"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center">
				<figure class="sui-box-banner" role="banner" aria-hidden="true">
					<img
						src="<?php echo esc_attr( $assets->get_asset( 'img/modal-banner-create-backup.png' ) ); ?>"
						srcset="<?php echo esc_attr( $assets->get_asset( 'img/modal-banner-create-backup.png' ) ); ?> 1x, <?php echo esc_attr( $assets->get_asset( 'img/modal-banner-create-backup@2x.png' ) ); ?> 2x"
					/>
				</figure>
				<button class="sui-button-icon sui-button-float--right" onclick="jQuery(window).trigger('snapshot:close_modal')">
					<i class="sui-icon-close sui-md" aria-hidden="true"></i>
				</button>
				<h3 class="sui-box-title sui-lg" id="modal-snapshot-edit-schedule-title"><?php echo esc_html( $modal_title ); ?></h3>
				<span id="modal-snapshot-edit-schedule-description" class="sui-description"><?php echo esc_html( $message ); ?></span>
			</div>
			<div class="sui-box-body">

				<form method="post" id="form-snapshot-schedule">

					<?php wp_nonce_field( 'snapshot_backup_schedule', '_wpnonce-backup_schedule' ); ?>
					<input type="hidden" name="schedule_action" value="create">
					<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
					<input type="hidden" name="files" value="<?php echo esc_attr( $files ); ?>">
					<input type="hidden" name="tables" value="<?php echo esc_attr( $tables ); ?>">

					<div class="sui-flushed">
						<div class="sui-box-settings-col-2">

							<div class="sui-tabs sui-side-tabs">

								<div data-tabs>
									<div class="" data-frequency="daily"><?php esc_html_e( 'Daily', 'snapshot' ); ?></div>
									<div class="" data-frequency="weekly"><?php esc_html_e( 'Weekly', 'snapshot' ); ?></div>
									<div class="" data-frequency="monthly"><?php esc_html_e( 'Monthly', 'snapshot' ); ?></div>
									<div class="active" data-frequency=""><?php esc_html_e( 'None', 'snapshot' ); ?></div>
								</div>

								<div data-panes>

									<div class="sui-tab-boxed">
										<label for="snapshot-daily-time" class="sui-label"><?php esc_html_e( 'Time of the day', 'snapshot' ); ?></label>
										<select id="snapshot-daily-time" name="daily_time">
											<?php foreach ( Helper\Datetime::get_hour_list() as $value => $text ) { ?>
											<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $text ); ?></option>
											<?php } ?>
										</select>
									</div>

									<div class="sui-tab-boxed">
										<div class="sui-row">
											<div class="sui-col-sm-6">
												<label for="snapshot-weekly-dow" class="sui-label"><?php esc_html_e( 'Day of the week', 'snapshot' ); ?></label>
												<select id="snapshot-weekly-dow" name="frequency_weekday">
													<option value="1"><?php esc_html_e( 'Sunday', 'snapshot' ); ?></option>
													<option value="2"><?php esc_html_e( 'Monday', 'snapshot' ); ?></option>
													<option value="3"><?php esc_html_e( 'Tuesday', 'snapshot' ); ?></option>
													<option value="4"><?php esc_html_e( 'Wednesday', 'snapshot' ); ?></option>
													<option value="5"><?php esc_html_e( 'Thursday', 'snapshot' ); ?></option>
													<option value="6"><?php esc_html_e( 'Friday', 'snapshot' ); ?></option>
													<option value="7"><?php esc_html_e( 'Saturday', 'snapshot' ); ?></option>
												</select>
											</div>
											<div class="sui-col-sm-6">
												<label for="snapshot-weekly-time" class="sui-label"><?php esc_html_e( 'Time of the day', 'snapshot' ); ?></label>
												<select id="snapshot-weekly-time" name="weekly_time">
													<?php foreach ( Helper\Datetime::get_hour_list() as $value => $text ) { ?>
													<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $text ); ?></option>
													<?php } ?>
												</select>
											</div>
										</div>
									</div>

									<div class="sui-tab-boxed">
										<div class="sui-row">
											<div class="sui-col-sm-6">
												<label for="snapshot-monthly-day" class="sui-label"><?php esc_html_e( 'Day of the month', 'snapshot' ); ?></label>
												<select id="snapshot-monthly-day" name="frequency_monthday">
													<?php foreach ( range( 1, 28 ) as $day ) { ?>
													<option value="<?php echo esc_attr( $day ); ?>"><?php echo esc_html( $day ); ?></option>
													<?php } ?>
												</select>
											</div>
											<div class="sui-col-sm-6">
												<label for="snapshot-monthly-time" class="sui-label"><?php esc_html_e( 'Time of the day', 'snapshot' ); ?></label>
												<select id="snapshot-monthly-time" name="monthly_time">
													<?php foreach ( Helper\Datetime::get_hour_list() as $value => $text ) { ?>
													<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $text ); ?></option>
													<?php } ?>
												</select>
											</div>
										</div>
									</div>

									<div class="active"></div>

								</div>

								<div id="snapshot-notice-tpd-schedule" class="sui-notice sui-notice-info" >

									<div class="sui-notice-content">

										<div class="sui-notice-message">

											<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>

											<?php /* translators: %s - Link for Destination page */ ?>
											<p><?php echo wp_kses_post( sprintf( __( 'Please note, the schedule set here is applied to all the destinations you have connected in the <a href="%s">Destinations page</a>.', 'snapshot' ), network_admin_url() . 'admin.php?page=snapshot-destinations' ) ); ?></p>

										</div>

									</div>

								</div>

							</div>
						</div>
					</div>

					<div class="sui-block-content-center">
						<button type="submit" class="sui-button sui-button-blue" aria-live="polite">
							<span class="sui-button-text-default">
								<i class="sui-icon-save" aria-hidden="true"></i><?php echo esc_attr( $button ); ?>
							</span>
							<span class="sui-button-text-onload">
								<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
								<?php echo esc_attr( $button_saving ); ?>
							</span>
						</button>
					</div>

				</form>

			</div>
		</div>
	</div>
</div>