<?php // phpcs:ignore
/**
 * Row with hosting backup details.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Helper\Datetime;

?>
<tr class="sui-accordion-item hosting-backup-row">
	<td class="sui-table-item-title">
		<div class="sui-tooltip sui-tooltip-top-left snapshot-icon-tooltip" data-tooltip="<?php echo esc_attr( $icon_tooltip_text ); ?>"></div>
		<i class="sui-icon-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></i>
		<span><?php echo esc_html( Datetime::format( $created_at ) ); ?></span>
	</td>
	<td class="sui-table-item-title gray">
		<i class="sui-icon-<?php echo esc_attr( $destination_icon ); ?>" aria-hidden="true"></i>
		<?php echo esc_html( $destination_title ); ?>
	</td>
	<td class="sui-table-item-title gray"><?php echo esc_html( $created_ago_human ); ?><span class="sui-accordion-open-indicator" aria-label="Expand"><i class="sui-icon-chevron-down" aria-hidden="true"></i></span></td>
</tr>
<tr class="sui-accordion-item-content">
	<td colspan="3">
		<div class="sui-box">
			<div class="sui-box-body">
				<p style="color: #888888;"><?php esc_html_e( 'You can restore the backup, view details, and add comments via the Hub.', 'snapshot' ); ?></p>
				<a class="sui-button sui-button-gray" target="_blank" href="<?php echo esc_attr( $manage_link ); ?>">
					<i class="sui-icon-open-new-window" aria-hidden="true"></i>
					<?php esc_html_e( 'Manage', 'snapshot' ); ?>
				</a>
			</div>
			<div class="sui-box-footer">
				<div class="sui-actions-right">
					<button class="sui-button sui-button-blue sui-tooltip sui-tooltip-constrained download-hosting-backup" data-backup-id="<?php echo esc_attr( $backup_id ); ?>" data-tooltip="<?php esc_attr_e( 'The backup file will be sent to your email', 'snapshot' ); ?>">
						<i class="sui-icon-download" aria-hidden="true"></i>
						<?php esc_html_e( 'Download', 'snapshot' ); ?>
					</button>
				</div>
			</div>
		</div>
	</td>
</tr>