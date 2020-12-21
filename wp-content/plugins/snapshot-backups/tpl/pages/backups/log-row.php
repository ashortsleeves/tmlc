<?php // phpcs:ignore
/**
 * Log row.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Model;

$icon              = Model\Request\Listing::get_backup_icon( $backup_type );
$icon_tooltip_text = Model\Request\Listing::get_backup_icon_tooltip_text( $backup_type );
$destination_text  = Model\Request\Listing::get_backup_destination_text( $backup_type );

?>
<tr class="sui-accordion-item log-row" data-backup-id="<?php echo esc_attr( $backup_id ); ?>" data-append-log="<?php echo esc_attr( $append_log ); ?>">
	<td class="sui-table-item-title">
		<div class="sui-tooltip sui-tooltip-top-left snapshot-icon-tooltip" data-tooltip="<?php echo esc_attr( $icon_tooltip_text ); ?>"></div>
		<i class="<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></i>
		<?php echo esc_html( $name ); ?>
	</td>
	<td class="sui-table-item-title gray">
		<i class="sui-icon-wpmudev-logo" aria-hidden="true"></i>
		<?php echo esc_html( $destination_text ); ?>
		<span class="sui-accordion-open-indicator" aria-label="<?php esc_html_e( 'Expand', 'snapshot' ); ?>"><i class="sui-icon-chevron-down" aria-hidden="true"></i></span>
	</td>
</tr>

<tr class="sui-accordion-item-content">
	<td colspan="2">

		<div class="sui-box snapshot-loading">
			<div class="sui-box-body log-loader">
				<div class="sui-message">
					<div class="sui-message-content">
						<p><i class="sui-icon-loader sui-loading" aria-hidden="true"></i> <?php esc_html_e( 'Loading log...', 'snapshot' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<div class="sui-box snapshot-loaded" style="display: none;">
			<div class="sui-box-header">
				<div class="sui-form-field" style="width: 140px;">
					<select class="sui-select-sm log-filter">
						<option value="all" selected><?php esc_html_e( 'All', 'snapshot' ); ?></option>
						<option value="warning"><?php esc_html_e( 'Warning', 'snapshot' ); ?></option>
						<option value="error"><?php esc_html_e( 'Error', 'snapshot' ); ?></option>
					</select>
				</div>
			</div>
			<div class="sui-box-body log-items-container">
				<div class="sui-notice sui-notice-info no-warning">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
							<p><?php esc_html_e( 'No warning found in the log.', 'snapshot' ); ?></p>
						</div>
					</div>
				</div>
				<div class="sui-notice sui-notice-info no-error">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
							<p><?php esc_html_e( 'No error found in the log.', 'snapshot' ); ?></p>
						</div>
					</div>
				</div>
				<?php foreach ( $log as $item ) { ?>
				<div class="log-item <?php echo esc_attr( 'log-level-' . $item['level'] ); ?>">
					<div class="log-item__icon" aria-hidden="true"></div>
					<div class="log-item__content">
						<?php echo esc_html( $item['message'] ); ?>
					</div>
				</div>
				<?php } ?>
			</div>
			<div class="sui-box-footer">
				<button class="sui-button sui-button-ghost view-backup" data-backup-id="<?php echo esc_attr( $backup_id ); ?>">
					<i class="sui-icon-reply flip-h" aria-hidden="true"></i>
					<?php esc_html_e( 'View backup', 'snapshot' ); ?>
				</button>
				<a download class="sui-button sui-button-blue" href="<?php echo esc_attr( $log_url ); ?>">
					<i class="sui-icon-download" aria-hidden="true"></i>
					<?php esc_html_e( 'Download', 'snapshot' ); ?>
				</a>
			</div>
		</div>

	</td>
</tr>