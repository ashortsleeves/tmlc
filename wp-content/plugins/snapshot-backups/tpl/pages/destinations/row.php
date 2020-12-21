<?php // phpcs:ignore
/**
 * Row with destination details.
 *
 * @package snapshot
 */

switch ( $tpd_type ) {
	case 'aws':
		$icon_tooltip = __( 'S3/Amazon', 'snapshot' );
		break;
	case 'wasabi':
		$icon_tooltip = __( 'S3/Wasabi', 'snapshot' );
		break;
	case 'digitalocean':
		$icon_tooltip = __( 'S3/DigitalOcean', 'snapshot' );
		break;
	case 'backblaze':
		$icon_tooltip = __( 'S3/Backblaze', 'snapshot' );
		break;
	case 'googlecloud':
		$icon_tooltip = __( 'S3/Google Cloud', 'snapshot' );
		break;
	case 'gdrive':
		$icon_tooltip = __( 'Google Drive', 'snapshot' );
		break;
	case 's3_other':
		$icon_tooltip = __( 'S3/Other', 'snapshot' );
		break;
	default:
		$icon_tooltip = __( 'S3/', 'snapshot' ) . $tpd_type;
		break;
}

?>
<tr class="destination-row destination-type-<?php echo esc_attr( $tpd_type ); ?> <?php echo ! $aws_storage ? 'deactivated-destination' : ''; ?>"
	data-tpd_id="<?php echo esc_attr( $tpd_id ); ?>"
	data-tpd_name="<?php echo esc_attr( $tpd_name ); ?>"
	data-tpd_path="<?php echo esc_attr( $tpd_path ); ?>"
	data-tpd_accesskey="<?php echo esc_attr( $tpd_accesskey ); ?>"
	data-tpd_secretkey="<?php echo esc_attr( $tpd_secretkey ); ?>"
	data-tpd_region="<?php echo esc_attr( $tpd_region ); ?>"
	data-tpd_limit="<?php echo esc_attr( $tpd_limit ); ?>"
	data-tpd_type="<?php echo esc_attr( $tpd_type ); ?>"
	data-tpd_email_gdrive="<?php echo isset( $tpd_email_gdrive ) ? esc_attr( $tpd_email_gdrive ) : ''; ?>"
>
	<td class="sui-table-item-title sui-hidden-xs sui-hidden-sm row-icon row-icon-<?php echo esc_attr( $tpd_type ); ?>">
		<div class="tooltip-container">
			<div class="tooltip-background"></div>
			<div class="tooltip-block <?php echo $tpd_type ? 'sui-tooltip' : ''; ?>" data-tooltip="<?php echo esc_attr( $icon_tooltip ); ?>"></div><span class="tpd-name"><?php echo esc_html( $tpd_name ); ?></span>
		</div>
	</td>

	<td class="sui-hidden-xs sui-hidden-sm snapshot-destination-path"><i class="sui-icon-folder sui-md" aria-hidden="true"></i><span><?php echo esc_html( $tpd_path ); ?></span></td>
	<td class="sui-hidden-xs sui-hidden-sm"><?php echo esc_html( $schedule ); ?></td>
	<td class="sui-hidden-xs sui-hidden-sm backup-count">0</td>

	<td colspan="5" class="sui-table-item-title first-child sui-hidden-md sui-hidden-lg mobile-row">
		<div class="destination-name"><i class="destination-icon destination-icon-amazon-s3 <?php echo $tpd_type ? 'sui-tooltip sui-tooltip-right' : ''; ?>" data-tooltip="<?php echo esc_attr( $icon_tooltip ); ?>"></i><?php echo esc_html( $tpd_name ); ?></div>
		<div class="sui-row destination-cells">
			<div class="sui-col-xs-6">
				<div class="sui-table-item-title"><?php esc_html_e( 'Directory', 'snapshot' ); ?></div>
				<div class="sui-table-item-title destination-path"><i class="sui-icon-folder sui-md" aria-hidden="true"></i><span><?php echo esc_html( $tpd_path ); ?></span></div>
			</div>

			<div class="sui-col-xs-6">
				<div class="sui-table-item-title"><?php esc_html_e( 'Schedule', 'snapshot' ); ?></div>
				<div class="sui-table-item-title"><?php echo esc_html( $schedule ); ?></div>
			</div>

			<div class="sui-col-xs-6">
				<div class="sui-table-item-title"><?php esc_html_e( 'Exported Backups', 'snapshot' ); ?></div>
				<div class="sui-table-item-title backup-count">0</div>
			</div>
		</div>
	</td>

	<td class="destination-actions-cell">
		<div class="destination-actions">
			<div class="sui-form-field">
				<label class="sui-toggle">
					<input type="checkbox" class="toggle-active" <?php echo $aws_storage ? 'checked' : ''; ?>>
					<span class="sui-toggle-slider" aria-hidden="true"></span>
				</label>
			</div>
			<div class="sui-dropdown">
				<button class="sui-button-icon sui-dropdown-anchor">
					<i class="sui-icon-widget-settings-config" aria-hidden="true"></i>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Destination actions', 'snapshot' ); ?></span>
				</button>
				<ul>
					<li class="destination-edit"><button><i class="sui-icon-pencil" aria-hidden="true"></i> <?php esc_html_e( 'Edit destination', 'snapshot' ); ?></button></li>
					<li class="destination-view"><a href="<?php echo esc_attr( $view_url ); ?>" target="_blank" rel="noopener noreferrer"><i class="sui-icon-link" aria-hidden="true"></i> <?php esc_html_e( 'View Directory', 'snapshot' ); ?></a></li>
					<li class="destination-delete"><button class="sui-option-red"><i class="sui-icon-trash" aria-hidden="true"></i> <?php esc_html_e( 'Delete', 'snapshot' ); ?></button></li>
				</ul>
			</div>
		</div>
	</td>
</tr>