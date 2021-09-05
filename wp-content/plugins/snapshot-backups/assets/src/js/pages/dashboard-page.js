/**
 * Page: Settings.
 */
;(function($) {
	// "null" means loading, "false" - error
	var snapshot_backups = null;
	var hosting_backups = snapshot_env.values.has_hosting_backups ? null : false;

	function get_current_storage() {
		var data = {};

		data._wpnonce = $( '#_wpnonce-snapshot_get_storage' ).val();

		var url = ajaxurl + '?action=snapshot-get_storage';

		$.ajax({
			type: 'POST',
			url: url,
			data: data,
			cache: false,
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					$('.snapshot-storage-loading').hide();
					$('.wpmudev-snapshot-storage span').css({"width": response.data.width});
					$('.wpmudev-snapshot-storage').show();
					$('.snapshot-current-stats .used-space').html(response.data.current_stats);
					$('.snapshot-current-stats .used-space').show();
				}
			}
		});

		return false;
	}

	function get_backups(action, nonce_selector) {
		var deferred = $.Deferred();

		var request_data = {
			action: action,
			_wpnonce: $(nonce_selector).val()
		};

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			cache: false,
			success: function (response) {
				if (response.success && response.data.backups) {
					deferred.resolve(response.data);
				} else {
					deferred.reject();
				}
			},
			error: function () {
				deferred.reject();
			}
		});

		return deferred.promise();
	}

	function get_snapshot_backups() {
		return get_backups('snapshot-list_backups', '#_wpnonce-list-backups');
	}

	function get_hosting_backups() {
		return get_backups('snapshot-list_hosting_backups', '#_wpnonce-list-hosting-backups');
	}

	function load_snapshot_backups() {
		snapshot_backups = null;
		update_summary();

		$('.snapshot-dashboard-backups .api-error').hide();
		$('.snapshot-dashboard-backups .snapshot-loading').show();
		$('.snapshot-dashboard-summary .snapshot-loading').show();

		get_snapshot_backups().then(function (data) {
			snapshot_backups = data;

			if (data.backups.length) {
				var last_backup = data.backups[0];

				// Disable the Backup Now button, if we're still in the same minute with the last backup taken.
				var time_elapsed = ( Date.now() / 1000 - last_backup.timestamp ) / 60 ;
				$(window).trigger('snapshot:toggle_cooldown', [time_elapsed]);

				var backups_table = $('.snapshot-listed-backups .sui-table');
				backups_table.find('>tbody:last-child').html('');
				data.backups.slice(0, 5).forEach(function (item) {
					var row = $(item.row);
					row.find('>td').slice(-5).remove();
					row.find('>td:not(.mobile-row)').slice(1).remove();
					row.find('.failed-icon-tooltip').addClass('sui-tooltip-top-left');
					row.find('.sui-accordion-open-indicator').remove();

					var first_td = row.find('>td').eq(0);
					var destination = first_td.clone().empty().addClass('gray');
					var destination_span = $('<span></span>');
					destination_span.text(row.data('destination_text'));
					var destination_tooltip = row.data('destination_tooltip');
					if (destination_tooltip) {
						destination_span.addClass('sui-tooltip sui-tooltip-left-mobile sui-tooltip-constrained');
						//destination_span.css('--tooltip-width', '170px');
						destination_span.attr('data-tooltip', destination_tooltip);
					}
					destination.append(destination_span).insertAfter(first_td);

					var mobile_row = row.find('td.mobile-row');
					mobile_row.find('>.sui-row').remove();
					var col = $('<div class="sui-col"><div class="sui-table-item-title snapshot-mobile-title"></div></div>');
					var destination_mobile_span = destination_span.clone();
					destination_mobile_span.removeClass('sui-tooltip-left-mobile');
					col.append($('<div class="sui-table-item-title gray"></div>').append(destination_mobile_span));
					col.find('.snapshot-mobile-title').text(backups_table.find('>thead>tr>th').eq(1).text());
					mobile_row.append($('<div class="sui-row"></div>').append(col));

					backups_table.find('>tbody:last-child').append(row);
					$('.snapshot-dashboard-backups > .sui-box-body:not(.api-error)').show();
					$('.snapshot-dashboard-backups > .snapshot-listed-backups').show();
					$('.snapshot-dashboard-backups > .sui-box-footer').attr('style', 'display: flex !important');

					// Workaround to hide the destination list tooltip when the i tooltip is hovered.
					$(window).trigger('snapshot:hide_double_tooltip');
				});
			} else {
				$('.snapshot-dashboard-backups > .snapshot-listed-backups-empty').show();
			}
		}, function () {
			snapshot_backups = false;
			$('.snapshot-dashboard-backups .api-error').show();
		}).always(function () {
			$('.snapshot-dashboard-backups .snapshot-loading').hide();
			update_summary();
		});
	}

	function load_hosting_backups() {
		hosting_backups = null;
		update_summary();

		var table = $('.snapshot-dashboard-hosting-backups table.snapshot-listed-hosting-backups');
		var footer = $('.snapshot-dashboard-hosting-backups .sui-box-footer');
		$('.snapshot-dashboard-hosting-backups .api-error').hide();
		$('.snapshot-dashboard-hosting-backups .body-description').hide();
		table.hide();
		footer.hide();
		$('.snapshot-dashboard-hosting-backups .snapshot-loading').show();
		$('.snapshot-dashboard-summary .snapshot-loading').show();

		get_hosting_backups().then(function (data) {
			hosting_backups = data;
			var tbody = table.find('> tbody');
			tbody.empty();
			data.backups.slice(0, 5).forEach(function (item) {
				var src_row = $('<tbody></tbody>').append(item.html_row);
				var row = src_row.find('>.hosting-backup-row');
				row.find('>td').slice(-1).remove();
				tbody.append(row);
			});
			$('.snapshot-dashboard-hosting-backups .body-description').show();
			table.show();
			footer.show();
		}, function () {
			hosting_backups = false;
			$('.snapshot-dashboard-hosting-backups .api-error').show();
		}).always(function () {
			$('.snapshot-dashboard-hosting-backups .snapshot-loading').hide();
			update_summary();
		});
	}

	function update_summary() {
		if (snapshot_backups === null || hosting_backups === null) {
			// any backup list is loading, show loaders
			$('.snapshot-dashboard-summary .snapshot-loading').show();
			$('.sui-summary-details.snapshot-backups-number .sui-summary-large').text('');
			$('.sui-summary-segment .snapshot-last-backup').text('');
			return;
		}
		if (snapshot_backups === false && hosting_backups === false) {
			// both backup lists are failed
			$('.snapshot-dashboard-summary .snapshot-loading').hide();
			$('.sui-summary-details.snapshot-backups-number .sui-summary-large').text('-');
			$('.sui-summary-segment .snapshot-last-backup').text('-');
			return;
		}

		var backups_number = 0;
		var last_backup_ts = 0;
		var last_backup = snapshot_messages.last_backup_unknown_date;
		if (snapshot_backups && snapshot_backups.backups) {
			var failed_backups_number = snapshot_backups.failed_backups || 0;
			backups_number += snapshot_backups.backups.length;
			backups_number -= failed_backups_number;
			var successful_backups = snapshot_backups.backups.filter(function (backup) {
				return !backup.is_failed;
			});
			if (successful_backups.length) {
				last_backup_ts = successful_backups[0].timestamp;
				last_backup = successful_backups[0].date;
			}
		}
		if (hosting_backups && hosting_backups.backups) {
			backups_number += hosting_backups.backups.length;
			if (hosting_backups.backups.length && hosting_backups.backups[0].created_at > last_backup_ts) {
				last_backup_ts = hosting_backups.backups[0].created_at;
				last_backup = hosting_backups.last_backup_time;
			}
		}

		$('.sui-summary-details.snapshot-backups-number .sui-summary-large').text(backups_number);
		$('.sui-summary-segment .snapshot-last-backup').text(last_backup);
		$('.snapshot-dashboard-summary .snapshot-loading').hide();
	}

	function load_all_backups() {
		load_snapshot_backups();
		if (snapshot_env.values.has_hosting_backups) {
			load_hosting_backups();
		}
	}

	function load_dashboard_destinations() {
		var loaders = $('.snapshot-destinations-number-loading, .snapshot-dashboard-destinations .snapshot-loader');
		var destinations_number = $('.snapshot-destinations-number');

		var tbody = $('.snapshot-dashboard-destinations .sui-table>tbody');
		var tpd_rows = tbody.find('>tr:not(:first)');

		tpd_rows.remove();
		loaders.show();

		get_destinations().then(function (data) {

			var destinations = data.destinations.filter(function (destination) {
				return !!parseInt(destination.aws_storage);
			}).length + 1;
			destinations_number.text(destinations).data('value', destinations);

			data.destinations.slice(0, 5).forEach(function (item) {
				var row = $(item.html_row);
				row.find('>td').slice(1, 4).remove();
				row.find('>td .sui-row.destination-cells').remove();
				tbody.append(row);
			});
		}).always(function () {
			loaders.hide();
		});
	}

	function on_toggle_destination_active(increment) {
		var destinations_number = $('.snapshot-destinations-number');
		var new_value = destinations_number.data('value') + increment;
		destinations_number.text(new_value).data('value', new_value);
	}

	function get_destinations() {
		var deferred = $.Deferred();

		var request_data = {
			action: 'snapshot-get_destinations',
			destination_page: 1,
			_wpnonce: $('#_wpnonce-snapshot-get-destinations').val()
		};

		$.ajax({
			type: 'GET',
			url: ajaxurl,
			data: request_data,
			cache: false,
			success: function (response) {
				if (response.success) {
					deferred.resolve(response.data);
				} else {
					deferred.reject();
				}
			},
			error: function () {
				deferred.reject();
			}
		});

		return deferred.promise();
	}

	function row_dropdown_click() {
		var li = $(this);
		var tpd_id = li.closest('.destination-row').data('tpd_id');

		if (li.hasClass('destination-edit')) {
			window.location = '?page=snapshot-destinations#edit-destination-' + tpd_id;
		} else if (li.hasClass('destination-delete')) {
			window.location = '?page=snapshot-destinations#delete-destination-' + tpd_id;
		}
	}

	$(function () {
		if ($('.snapshot-dashboard-backups').length) {
			load_all_backups();
			$('#button-reload-backups').on('click', load_snapshot_backups);
			$('#button-reload-hosting-backups').on('click', load_hosting_backups);

			get_current_storage();
			load_dashboard_destinations();
		}

		if ($('.snapshot-dashboard-destinations').length) {
			$('.snapshot-dashboard-destinations').on('change', 'input[type=checkbox].toggle-active', function () {
				$(window).trigger('snapshot:toggle_destination_active', [this, on_toggle_destination_active]);
			});
			$('.snapshot-dashboard-destinations').on('click', '.sui-dropdown>ul>li', row_dropdown_click);
		}
	});
})(jQuery);
