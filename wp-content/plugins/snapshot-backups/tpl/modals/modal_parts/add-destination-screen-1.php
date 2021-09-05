<?php // phpcs:ignore
/**
 * First screen of Add Destination modal.
 *
 * @package snapshot
 */

?>
<div class="sui-modal-slide sui-active sui-loaded" id="snapshot-add-destination-dialog-slide-1" data-modal-size="md">
	<div class="sui-box">

		<div class="sui-box-header sui-flatten sui-content-center">

			<figure class="sui-box-banner" role="banner" aria-hidden="true">
				<img
					src="<?php echo esc_attr( $assets->get_asset( 'img/modal-banner-add-destination.png' ) ); ?>"
					srcset="<?php echo esc_attr( $assets->get_asset( 'img/modal-banner-add-destination.png' ) ); ?> 1x, <?php echo esc_attr( $assets->get_asset( 'img/modal-banner-add-destination@2x.png' ) ); ?> 2x"
				/>
			</figure>
			<button class="sui-button-icon sui-button-float--right" data-modal-close>
				<span class="sui-icon-close sui-md" aria-hidden="true"></span>
			</button>

			<h3 class="sui-box-title sui-lg"><?php echo esc_html( 'Add destination' ); ?></h3>
			<span class="sui-description" style=" padding-left: 15px; padding-right: 15px; "><?php echo esc_html( 'Select the destination where you want to export a full copy of each Snapshot backup.' ); ?></span>

		</div>

		<div class="sui-box-selectors sui-box-selectors-col-2 snapshot-destination-selectors">
			<ul>
				<li><label for="s3-destination" class="sui-box-selector">
					<input type="radio" name="snapshot-selected-destination-type" id="s3-destination" value="s3" />
					<span>
						<img src="<?php echo esc_attr( $assets->get_asset( 'img/destination-logo-aws-small.svg' ) ); ?>" style="margin-right: 10px;" />
						<?php echo esc_html( 'Amazon S3' ); ?>
					</span>
				</label></li>

				<li><label for="gd-destination" class="sui-box-selector">
					<input type="radio" name="snapshot-selected-destination-type" id="gd-destination" value="gd" />
					<span>
						<img src="<?php echo esc_attr( $assets->get_asset( 'img/destination-logo-gd-small.svg' ) ); ?>" style="margin-right: 10px;" />
						<?php echo esc_html( 'Google Drive' ); ?>
					</span>
				</label></li>

				<li><label for="dropbox-destination" class="sui-box-selector sui-disabled">
					<input type="radio" name="snapshot-selected-destination-type" id="dropbox-destination" value="dropbox" disabled />
					<span style=" margin: 1px; border-radius: 4px; ">
						<img src="<?php echo esc_attr( $assets->get_asset( 'img/destination-logo-dropbox-small.svg' ) ); ?>" style="margin-right: 10px;" />
						<?php echo esc_html( 'Dropbox' ); ?>
						<span class="sui-tag sui-tag-pro"><?php echo esc_html( 'Coming soon' ); ?></span>
					</span>
				</label></li>

				<li><label for="ftp-destination" class="sui-box-selector sui-disabled">
					<input type="radio" name="snapshot-selected-destination-type" id="ftp-destination" value="ftp" disabled />
					<span style=" margin: 1px; border-radius: 4px; ">
						<img src="<?php echo esc_attr( $assets->get_asset( 'img/destination-logo-ftp-small.svg' ) ); ?>" style="margin-right: 10px;" />
						<?php echo esc_html( 'FTP' ); ?>
						<span class="sui-tag sui-tag-pro"><?php echo esc_html( 'Coming soon' ); ?></span>
					</span>
				</label></li>
			</ul>
		</div>

		<div class="sui-box-footer sui-content-right sui-flatten">
			<button class="sui-button sui-button-icon-right snapshot-next-destination-screen" disabled data-modal-slide="snapshot-add-destination-dialog-slide-2-s3" data-modalslide="snapshot-add-destination-dialog-slide-2-s3" data-modal-slide-intro="next">
				<?php esc_html_e( 'Next' ); ?>
				<span class="sui-icon-arrow-right" aria-hidden="true"></span>
			</button>
		</div>

	</div>
</div>