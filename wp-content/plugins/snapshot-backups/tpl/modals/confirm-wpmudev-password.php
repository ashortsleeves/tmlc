<?php // phpcs:ignore
/**
 * Modal for confirmation WPMU DEV password.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Helper;
use WPMUDEV\Snapshot4\Helper\Api;

$assets = new Helper\Assets();

$wpmudev_user_email = Api::get_dashboard_profile_username();
$hub_link           = 'https://wpmudev.com/hub2/site/' . Api::get_site_id() . '/backups/settings';

?>
<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="snapshot-confirm-wpmudev-password-modal"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="snapshot-confirm-wpmudev-password-modal-title"
		aria-describedby="snapshot-confirm-wpmudev-password-modal-description"
	>
		<div class="sui-box">

			<form id="snapshot-confirm-wpmudev-password-modal-form">

				<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60 sui-spacing-sides--30">
					<figure class="sui-box-logo" aria-hidden="true">
						<img
							src="<?php echo esc_attr( $assets->get_asset( 'img/modal-header-wpmudev.png' ) ); ?>"
							srcset="<?php echo esc_attr( $assets->get_asset( 'img/modal-header-wpmudev.png' ) ); ?> 1x, <?php echo esc_attr( $assets->get_asset( 'img/modal-header-wpmudev@2x.png' ) ); ?> 2x"
						/>
					</figure>
					<button type="button" class="sui-button-icon sui-button-float--right" data-modal-close="">
						<span class="sui-icon-close sui-md" aria-hidden="true"></span>
						<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this modal', 'snapshot' ); ?></span>
					</button>
					<h3 id="snapshot-confirm-wpmudev-password-modal-title" class="sui-box-title sui-lg"><?php esc_html_e( 'Confirm WPMU DEV Password', 'snapshot' ); ?></h3>
					<?php /* translators: %s - WPMU DEV user email */ ?>
					<p id="snapshot-confirm-wpmudev-password-modal-description"><?php echo wp_kses_post( sprintf( __( 'As a security measure, you need to confirm your WPMU DEV password before continuing. Please enter the password for your connected account (<strong>%s</strong>) below.', 'snapshot' ), $wpmudev_user_email ) ); ?></p>
				</div>

				<div class="sui-box-body sui-content-center">
					<div class="sui-form-field">
						<label for="snapshot-wpmudev-password" id="snapshot-wpmudev-password-label" class="sui-label">
							<?php echo esc_html( __( 'WPMU DEV Password', 'snapshot' ) ); ?>
						</label>

						<div class="sui-with-button sui-with-button-icon">
							<input
								placeholder="<?php esc_attr_e( 'Enter your WPMU DEV password', 'snapshot' ); ?>"
								id="snapshot-wpmudev-password"
								class="sui-form-control"
								type="password"
								name="wpmudev_password"
								aria-labelledby="snapshot-wpmudev-password-label"
							/>
							<button type="button" class="sui-button-icon">
								<span aria-hidden="true" class="sui-icon-eye"></span>
								<span class="sui-password-text sui-screen-reader-text"><?php esc_html_e( 'Show Password', 'snapshot' ); ?></span>
								<span class="sui-password-text sui-screen-reader-text sui-hidden"><?php esc_html_e( 'Hide Password', 'snapshot' ); ?></span>
							</button>
						</div>

						<span id="error-snapshot-wpmudev-password" class="sui-error-message" style="display: none; text-align: right;" role="alert"><?php esc_html_e( 'The password you entered is incorrect. Please try again.', 'snapshot' ); ?></span>
					</div>

					<button type="submit" class="sui-button sui-button-icon-right submit-button">
						<span class="sui-loading-text"><?php esc_html_e( 'Continue', 'snapshot' ); ?><span class="sui-icon-chevron-right" aria-hidden="true"></span></span>
						<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
					</button>
					<?php /* translators: %s - Link to the Hub */ ?>
					<p class="sui-description" style="margin-bottom: 0;margin-top: 32px;"><?php echo wp_kses_post( sprintf( __( 'You can enable/disable this password protection step from <a href="%s" target="_blank">The Hub</a>.', 'snapshot' ), $hub_link ) ); ?></p>
				</div>

				<div class="sui-box-footer sui-flatten">
				</div>

			</form>

		</div>
	</div>
</div>