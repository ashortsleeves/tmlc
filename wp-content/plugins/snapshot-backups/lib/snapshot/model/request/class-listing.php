<?php // phpcs:ignore
/**
 * Snapshot models: Backup listing requests model
 *
 * Holds information for communication with the service about listing existing backups.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Model\Request;

use WPMUDEV\Snapshot4\Model;
use WPMUDEV\Snapshot4\Helper;

/**
 * Listing backups requests model class
 */
class Listing extends Model\Request {

	/**
	 * Listing backups request endpoint
	 *
	 * @var string
	 */
	protected $endpoint = 'snapshots';

	/**
	 * Retrieves a list of all backups.
	 *
	 * @return array|mixed|object
	 */
	public function list_backups() {
		$data   = array();
		$method = 'get';
		$path   = $this->get_api_url();

		$response = $this->request( $path, $data, $method );

		return $response;
	}

	/**
	 * Build backup info for displaying.
	 *
	 * @param array  $backup The backup we're going to display the info for.
	 * @param string $schedule_info Schedule human-readable text.
	 *
	 * @return string The HTML for the backup row.
	 */
	public function get_backup_info( $backup, $schedule_info ) {
		$failed_backups_number = $this->get( 'failed_backups' );

		$backup_info['timestamp'] = strtotime( $backup['created_at'] );

		$backup_info['date'] = Helper\Datetime::format( $backup_info['timestamp'] );
		$backup_info['name'] = isset( $backup['bu_snapshot_name'] ) ? $backup['bu_snapshot_name'] : $backup_info['name'];
		if ( is_null( $backup_info['name'] ) || '' === $backup_info['name'] || 'null' === $backup_info['name'] ) {
			$backup_info['name'] = $backup_info['date'];
		}

		$failed_backup = false;
		if ( 0 === strpos( $backup['snapshot_status'], 'snapshot_failed_' ) ) {
			$failed_backup = true;

			$failed_backups_number++;
			$this->set( 'failed_backups', $failed_backups_number );
		}

		$global_exclusions = array();
		if ( isset( $backup['excluded_files'] ) ) {
			$excluded_files_list = preg_replace( '/(^\[)|(\]$)/u', '', $backup['excluded_files'] );
			$excluded_files      = '' === $excluded_files_list ? array() : explode( ',', $excluded_files_list );
			if ( is_array( $excluded_files ) ) {
				$global_exclusions = $excluded_files;
			}
		}

		$row_class = ( $failed_backup ) ? ' snapshot-failed-backup' : '';
		$row_icon  = ( $failed_backup )
			? 'sui-icon-warning-alert'
			: self::get_backup_icon( $backup['type'] );

		$row_accordion_indicator = ( $failed_backup ) ? '' : '<span class="sui-accordion-open-indicator" aria-label="Expand"><i class="sui-icon-chevron-down" aria-hidden="true"></i></span>';

		$row_failed_buttons = ( $failed_backup ) ?
'
	<button class="sui-button sui-button-ghost view-log" data-backup-id="' . esc_attr( $backup['snapshot_id'] ) . '">
	<i class="sui-icon-eye" aria-hidden="true"></i>' .
	esc_html__( 'View logs', 'snapshot' ) .
	'</button>
	<button class="sui-button-icon sui-button-red sui-tooltip snapshot-delete-backup" data-tooltip="' . esc_html__( 'Delete', 'snapshot' ) . '" onclick="jQuery(window).trigger(\'snapshot:delete_backup\', [\'' . esc_attr( $backup['snapshot_id'] ) . '\'])">
					<i class="sui-icon-trash" aria-hidden="true"></i>
					<span class="sui-screen-reader-text">' . esc_html__( 'Delete', 'snapshot' ) . '</span>
	</button>
'
		: '';

		$frequency_human = '';
		if ( isset( $backup['bu_frequency'] ) ) {
			switch ( $backup['bu_frequency'] ) {
				case 'daily':
					$frequency_human = __( 'Daily', 'snapshot' );
					break;
				case 'weekly':
					$frequency_human = __( 'Weekly', 'snapshot' );
					break;
				case 'monthly':
					$frequency_human = __( 'Monthly', 'snapshot' );
					break;
				default:
					$frequency_human = __( 'None', 'snapshot' );
					break;
			}
		}

		$icon_tooltip_text = self::get_backup_icon_tooltip_text( $backup['type'] );
		if ( $failed_backup ) {
			$icon_tooltip_text = __( 'This backup has failed. Check the logs for further information.', 'snapshot' );
		}

		$destination_text = self::get_backup_destination_text( $backup['type'] );
		$export_status    = isset( $backup['tpd_exp_status'] ) ? $backup['tpd_exp_status'] : array();
		$export_text      = self::get_backup_export_texts( $export_status );

		add_filter(
			'safe_style_css',
			function( $styles ) {
				$styles[] = '--tooltip-width';
				return $styles;
			}
		);

		$backup_info['row'] =
'<tr class="snapshot-row' . esc_attr( $row_class ) . '" data-backup_id="' . esc_attr( $backup['snapshot_id'] ) . '">
	<td class="sui-hidden-xs sui-table-item-title">
		<div class="sui-tooltip sui-tooltip-top-left snapshot-icon-tooltip" data-tooltip="' . esc_attr( $icon_tooltip_text ) . '"></div>
		<i class="' . esc_attr( $row_icon ) . '" aria-hidden="true"></i>
		' . esc_html( $backup_info['name'] ) . '
	</td>
	<td class="sui-hidden-xs sui-table-item-title gray">
		<i class="sui-icon-wpmudev-logo" aria-hidden="true"></i>'
		. esc_html( $destination_text ) . '
	</td>
	<td class="sui-hidden-xs sui-table-item-title gray snapshot-export-column">
		<span style="display:inline-block;">'
		. wp_kses_post( $export_text['row'] ) . '
		</span>
	</td>
	<td class="sui-hidden-xs sui-table-item-title gray snapshot-schedule-column last-child">
		<span class="frequency">' . esc_html( $frequency_human ) . '</span>' .
		$row_failed_buttons .
		$row_accordion_indicator . '
	</td>

	<td class="sui-hidden-sm sui-hidden-md sui-hidden-lg sui-table-item-title mobile-row" colspan="4">
		<div class="sui-table-item-title">
			<i class="' . esc_attr( $row_icon ) . ' sui-md" aria-hidden="true"></i>
			' . esc_html( $backup_info['name'] ) .
			$row_failed_buttons .
			$row_accordion_indicator . '
		</div>
		<div class="sui-row">
			<div class="sui-col-xs-6">
				<div class="sui-table-item-title snapshot-mobile-title">' . esc_html__( 'Storage', 'snapshot' ) . '</div>
				<div class="sui-table-item-title gray">
					<i class="sui-icon-wpmudev-logo" aria-hidden="true"></i>'
					. esc_html( $destination_text ) . '
				</div>
			</div>
			<div class="sui-col-xs-6">
				<div class="sui-table-item-title snapshot-mobile-title">' . esc_html__( 'Export destination', 'snapshot' ) . '</div>
				<div class="sui-table-item-title gray">
				<span style="display:inline-block;">'
					. wp_kses_post( $export_text['row_mobile'] ) . '
				</span>
				</div>
			</div>
			<div class="sui-col-xs-6">
				<div class="sui-table-item-title snapshot-mobile-title">' . esc_html__( 'Frequency', 'snapshot' ) . '</div>
				<div class="sui-table-item-title gray">' . esc_html( $frequency_human ) . '</div>
			</div>
		</div>
	</td>

	<td class="snapshot-restoration sui-hidden-xs sui-table-item-title first-child">
		<i class="' . esc_attr( $row_icon ) . '" aria-hidden="true"></i>
		<span class="backup-name">' . esc_html( $backup_info['name'] ) . '</span>
	</td>
	<td class="snapshot-restoration sui-hidden-xs sui-table-item-title">
		<i class="sui-icon-wpmudev-logo" aria-hidden="true"></i>'
		. esc_html( $destination_text ) . '
	</td>
	<td class="snapshot-restoration sui-hidden-xs sui-table-item-title snapshot-export-column">
		<span style="display:inline-block;">'
		. wp_kses_post( $export_text['row_mobile'] ) . '
		</span>
	</td>
	<td class="snapshot-restoration sui-hidden-xs last-child">
		<div class="sui-progress" style="width: 130px; float: left;">
			<span class="sui-progress-icon" aria-hidden="true"><i class="sui-icon-loader sui-loading"></i></span>
			<span class="sui-progress-text"><span class="progress-text"></span></span>
			<div class="sui-progress-bar" aria-hidden="true"><span class="percent-width" style="width: 0%;"></span></div>
		</div>
		<span class="sui-accordion-open-indicator" aria-label="Expand"><i class="sui-icon-chevron-down" aria-hidden="true"></i></span>
	</td>

	<td class="snapshot-restoration sui-hidden-sm sui-hidden-md sui-hidden-lg sui-table-item-title mobile-row" colspan="4">
		<div class="sui-table-item-title">
			<div class="sui-progress" style="width: 90%; float: left;">
				<div class="sui-table-item-title">
					<i class="' . esc_attr( $row_icon ) . ' sui-md" aria-hidden="true"></i>
				</div>
				<span class="sui-progress-icon" aria-hidden="true"><i class="sui-icon-loader sui-loading"></i></span>
				<span class="sui-progress-text"><span class="progress-text"></span></span>
				<div class="sui-progress-bar" aria-hidden="true"><span class="percent-width" style="width: 0%;"></span></div>
			</div>
			<span class="sui-accordion-open-indicator" aria-label="Expand"><i class="sui-icon-chevron-down" aria-hidden="true"></i></span>
		</div>
	</td>

</tr>';

		$template = new Helper\Template();
		ob_start();
		$template->render(
			'pages/backups/snapshot-details-row',
			array(
				'snapshot_id'       => $backup['snapshot_id'],
				'schedule_info'     => $schedule_info,
				'date'              => $backup_info['date'],
				'global_exclusions' => $global_exclusions,
				'size'              => empty( $backup['snapshot_size'] ) ? '? MB' : ( $backup['snapshot_size'] . ' MB' ),
				'frequency_human'   => $frequency_human,
				'last_snap'         => ! empty( $backup['last_snap'] ),
				'backup_type'       => $backup['type'],
				'destination_text'  => $destination_text,
				'export_details'    => $export_text['details'],
				'add_export_notice' => $export_text['successful_exports'] > 0,
			)
		);
		$backup_info['row_content'] = ob_get_clean();

		$backup_info['is_failed'] = $failed_backup;

		$backup['tpd_exp_done'] = isset( $backup['tpd_exp_done'] )
			// @TODO: fix JSON
			? str_replace( "'", '"', $backup['tpd_exp_done'] )
			: null;

		$tpd_exp_done = json_decode( $backup['tpd_exp_done'], true );
		$done_tpd_ids = array();
		if ( isset( $tpd_exp_done['tpd_s3'] ) ) {
			foreach ( $tpd_exp_done['tpd_s3'] as $tpd_value => $export_status ) {
				if ( 'export_success' === $export_status ) {
					$done_tpd_ids[] = $tpd_value;
				}
			}
		}
		if ( isset( $tpd_exp_done['tpd_gdrive'] ) ) {
			foreach ( $tpd_exp_done['tpd_gdrive'] as $tpd_value => $export_status ) {
				if ( 'export_success' === $export_status ) {
					$done_tpd_ids[] = $tpd_value;
				}
			}
		}
		$backup_info['done_tpd_ids'] = $done_tpd_ids;

		return $backup_info;
	}

	/**
	 * Utility function to sort backups chronologically.
	 *
	 * @param array $backups The backups to be sorted chronologically.
	 *
	 * @return array
	 */
	public function sort_backups( $backups ) {
		usort(
			$backups,
			function( $backup1, $backup2 ) {
				$datetime1 = strtotime( $backup1['created_at'] );
				$datetime2 = strtotime( $backup2['created_at'] );

				return $datetime2 - $datetime1;
			}
		);

		return $backups;
	}

	/**
	 * Returns backup icon class
	 *
	 * @param string $backup_type Type of backup.
	 *
	 * @return string
	 */
	public static function get_backup_icon( $backup_type ) {
		return 'automate' === $backup_type ? 'sui-icon-automate' : 'sui-icon-snapshot';
	}

	/**
	 * Returns backup icon tooltip
	 *
	 * @param string $backup_type Type of backup.
	 *
	 * @return string
	 */
	public static function get_backup_icon_tooltip_text( $backup_type ) {
		$text = __( 'WPMU DEV Scheduled backup', 'snapshot' );
		if ( 'automate' === $backup_type ) {
			$text = __( 'WPMU DEV Automated backup', 'snapshot' );
		} elseif ( 'manual' === $backup_type ) {
			$text = __( 'WPMU DEV Manual backup', 'snapshot' );
		}
		return $text;
	}

	/**
	 * Returns backup destination
	 *
	 * @param string $backup_type Type of backup.
	 *
	 * @return string
	 */
	public static function get_backup_destination_text( $backup_type ) {
		$text = __( 'WPMU DEV', 'snapshot' );
		if ( 'automate' === $backup_type ) {
			$text = __( 'WPMU DEV (Automate)', 'snapshot' );
		}
		return $text;
	}

	/**
	 * Returns HTML for 'Export Destination' column of each backup.
	 *
	 * @param object $exports List of performed exports with their statuses.
	 * @param bool   $running_backup Whether it's a running backup we're talking about or an already completed one.
	 *
	 * @return array
	 */
	public static function get_backup_export_texts( $exports, $running_backup = false ) {
		$exports = ( ! is_array( $exports ) ) ? str_replace( "'", '"', $exports ) : $exports;
		$exports = ( ! is_array( $exports ) ) ? json_decode( $exports, true ) : $exports;

		$export_info   = array();
		$total_exports = array();
		$exports_exist = false;
		$first_type    = '';

		if ( empty( $exports ) ) {
			$export_info['row']        = ( $running_backup ) ? 'None' : __( 'None', 'snapshot' );
			$export_info['row_mobile'] = $export_info['row'];
			$export_info['details']    = '';

			$export_info['successful_exports'] = 0;

			return $export_info;
		}

		if ( isset( $exports['tpd_s3'] ) ) {
			$exports_exist       = true;
			$total_exports['s3'] = $exports['tpd_s3'];
		}

		if ( isset( $exports['tpd_gdrive'] ) ) {
			$exports_exist           = true;
			$total_exports['gdrive'] = $exports['tpd_gdrive'];
		}

		if ( $exports_exist ) {
			$exports_count              = 0;
			$first_export               = '';
			$exports_tooltip            = '';
			$failed_export              = false;
			$warning_icon_header        = '';
			$warning_icon_header_mobile = '';
			$export_details             = '';
			$successful_exports         = 0;

			foreach ( $total_exports as $type => $type_exports ) {
				foreach ( $type_exports as $name => $status ) {
					$first_type       = empty( $first_export ) ? $type : $first_type;
					$first_export     = empty( $first_export ) ? $name : $first_export;
					$exports_tooltip .= $name . ', ';
					$exports_count++;
					$successful_exports += 'export_success' === $status ? 1 : 0;

					$export_details .=
'<div class="sui-col-md-3 sui-col-xs-6">
	<span class="sui-settings-label">' . sprintf( __( 'Export Destination %d', 'snapshot' ), $exports_count ) . '</span>';

					if ( ! $failed_export && 'export_failed' === $status ) {
						// If even one export was failed, show warning icon in the header.
						$failed_export              = true;
						$warning_icon_header        = "<span class='sui-tooltip sui-tooltip-constrained snapshot-export-icon snapshot-export-failure' data-tooltip='" . esc_html__( 'Backup failed to export to the connected destination.', 'snapshot' ) . "'><i class='sui-icon-warning-alert' aria-hidden='true'></i></span>";
						$warning_icon_header_mobile = "<span class='sui-tooltip sui-tooltip-left sui-tooltip-constrained snapshot-export-icon snapshot-export-failure' style='--tooltip-width: 170px;' data-tooltip='" . esc_html__( 'Backup failed to export to the connected destination.', 'snapshot' ) . "'><i class='sui-icon-warning-alert' aria-hidden='true'></i></span>";
					}

					if ( 'export_failed' === $status ) {
						$export_details .= '
	<span class="snapshot-export-backup-details snapshot-' . $type . '-export-backup-details">' . $name . '</span>
	<span class="sui-tooltip sui-tooltip-constrained sui-tooltip-left-mobile snapshot-export-icon snapshot-export-details-failure" data-tooltip="' . esc_html__( 'The backup is stored on WPMU DEV storage, but has failed to export to the connected destination. Make sure you have the destination set up correctly and try to run the backup again.', 'snapshot' ) . '">
		<i class="sui-icon-warning-alert" aria-hidden="true"></i>
	</span>
	<span class="sui-tooltip sui-tooltip-constrained sui-tooltip-left-mobile snapshot-export-icon snapshot-export-details-failure2" data-tooltip="' . esc_html__( 'The backup is stored on WPMU DEV storage, but has failed to export to the connected destination. Make sure you have the destination set up correctly and try to run the backup again.', 'snapshot' ) . '">
		<i class="sui-icon-warning-alert" aria-hidden="true"></i>
	</span>';
					} else {
						$export_details .= '
	<span class="snapshot-export-backup-details snapshot-' . $type . '-export-backup-details sui-tooltip snapshot-export-icon snapshot-export-details-success" data-tooltip="' . esc_html__( 'Exported successfully', 'snapshot' ) . '">' . $name . '
		<i class="sui-icon-check-tick" aria-hidden="true"></i>
	</span>';
					}

					$export_details .= '
</div>';

				}
			}

			$exports_tooltip = rtrim( rtrim( $exports_tooltip ), ',' );

			if ( 1 < $exports_count ) {
				/* translators: %d - Number of configured 3rd party destinations */
				$export_text = $first_export . sprintf( __( ' + %d more', 'snapshot' ), $exports_count - 1 );
				$export_row  = "<span class='snapshot-export-backup-header snapshot-" . $first_type . "-export-backup-header sui-tooltip sui-tooltip-left-mobile sui-tooltip-constrained' style='--tooltip-width: 170px;' data-tooltip='" . $exports_tooltip . "'>" . $export_text;
			} else {
				$export_row = "<span class='snapshot-export-backup-header snapshot-" . $first_type . "-export-backup-header'>" . $first_export;
			}

			$export_info['row']        = $export_row . $warning_icon_header . '</span>';
			$export_info['row_mobile'] = $export_row . $warning_icon_header_mobile . '</span>';
			$export_info['details']    = $export_details;

			$export_info['html']['exports_count']     = $exports_count;
			$export_info['html']['first_export']      = $first_export;
			$export_info['html']['first_export_type'] = $first_type;
			$export_info['html']['exports_tooltip']   = $exports_tooltip;

			$export_info['successful_exports'] = $successful_exports;

			return $export_info;
		}
	}
}