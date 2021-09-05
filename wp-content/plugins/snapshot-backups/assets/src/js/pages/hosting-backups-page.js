/**
 * Page: Hosting Backups.
 */
;(function($) {

	function get_hosting_backups() {
		var deferred = $.Deferred();

		var request_data = {
			action: 'snapshot-list_hosting_backups',
			_wpnonce: $('#_wpnonce-list-hosting-backups').val()
		};

		$.ajax({
			type: 'POST',
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

	function load_hosting_backups() {
		var loaders = $([
			'.snapshot-hosting-backups-backups .snapshot-backup-list-loader',
			'.snapshot-page-hosting-backups .snapshot-loading',
		].join(','));
		var api_error = $('.snapshot-hosting-backups-backups .api-error');
		var api_error_elements = $('.snapshot-page-hosting-backups').find([
			'.snapshot-hosting-backup-count',
			'.snapshot-last-hosting-backup',
			'.snapshot-next-hosting-backup',
			'.snapshot-hosting-backup-schedule',
		].join(','));
		var table = $('.snapshot-hosting-backups-backups .snapshot-hosting-backups-table');

		var tbody = $(table).find('>tbody');
		tbody.empty();
		api_error_elements.empty();

		loaders.show();
		api_error.hide();
		table.hide();

		get_hosting_backups().then(function (data) {
			api_error.hide();

			$('.snapshot-page-hosting-backups .snapshot-hosting-backup-count').text(data.backups.length);
			$('.snapshot-page-hosting-backups .snapshot-last-hosting-backup').text(data.last_backup_time);
			$('.snapshot-page-hosting-backups .snapshot-next-hosting-backup').text(data.next_backup_time);
			$('.snapshot-page-hosting-backups .snapshot-hosting-backup-schedule')
					.append('<span class="sui-icon-lock" style="margin-right: 5px;" aria-hidden="true"></span>')
					.append($('<span></span>').text(data.backup_schedule))
					.attr('data-tooltip', data.backup_schedule_tooltip)
			;

			data.backups.forEach(function (item) {
				tbody.append(item.html_row);
			});
			table.show();
		}, function() {
			api_error.show();
			api_error_elements.text('-');
		}).always(function () {
			loaders.hide();
		});
	}

	function download_hosting_backup(backup_id) {
		var request_data = {
			action: 'snapshot-download_hosting_backup',
			_wpnonce: $('#_wpnonce-download-hosting-backup').val(),
			backup_id: backup_id
		};

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			cache: false,
			success: function (response) {
				if (response.success) {
					var notice = $('<span></span>').html(response.data.notice_html);
					jQuery(window).trigger('snapshot:show_top_notice', ['success', notice]);
				} else {
					var notice = $('<span></span>').html(snapshot_messages.backup_export_error);
					jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);
				}
			},
			error: function () {
				var notice = $('<span></span>').html(snapshot_messages.backup_export_error);
				jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);
			}
		});
	}

	$(function () {
		if ( $( '.snapshot-page-hosting-backups' ).length ) {
			$('.snapshot-hosting-backups-backups .reload-backups').on('click', load_hosting_backups);
			load_hosting_backups();

			$('.snapshot-hosting-backups-table').on('click', '.download-hosting-backup', function () {
				var backup_id = $(this).data('backupId');
				download_hosting_backup(backup_id);
			});
		}
	});
})(jQuery);
