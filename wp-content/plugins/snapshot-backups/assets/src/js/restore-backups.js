/**
 * Restore backups.
 */
;(function($) {
	var cancelled_restore = false;
		
	window.snapshot_skipped_files = [];
	window.snapshot_skipped_tables = [];		

	function trigger_restore_backup(e) {
		if (e && e.preventDefault) e.preventDefault();

		var form = $('#form-snapshot-restore-backup'),
			data = {},
			nonce = $( '#_wpnonce-snapshot_trigger_backup_restore' ).val();

		form.serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});

		set_restoration_step(data.backup_id, 0);
		enable_restore_buttons(false);
		process_restore_backup(data, form, nonce, 1);
	}

	function process_restore_backup(data, form, nonce, initial_request) {
		var backup_id = data.backup_id;
		var update_log_offset = false;

		if ( cancelled_restore === true ) {
			set_restoration_step(backup_id, false);

			jQuery(window).trigger('snapshot:show_top_notice', ['success', snapshot_messages.restore_cancel_success]);

			var cancel_button = $('.cancel-restore[data-backup-id=' + backup_id + ']');
			cancel_button.removeClass('sui-button-onload-text');

			$('.button-create-backup').prop('disabled', false);

			cancelled_restore = false;

			return;
		}
		// If it's not the first process request, this should be false.
		initial_request = initial_request || 0;

		data['initial'] = initial_request;

		var request_data = {
			action: 'snapshot-process_restore',
			_wpnonce: nonce,
			data: data
		};

		var perform_auth_check = function ( data, form ) {
			var def = $.Deferred();
			window._snapshot4_last_data = data;
			window._snapshot4_last_form = form;
			$( document ).on(
				'heartbeat-tick.wp-auth-check',
				check_auth_context
			);

			if ( ( ( wp || {} ).heartbeat || {} ).connectNow) {
				var check_logged_in_href = ajaxurl + '?action=snapshot-check_logged_in';
				jQuery.ajax({
					type: 'POST',
					url: check_logged_in_href,
					data: {},
					cache: false,
					success: function (response) {
						if ( response.success ) {
							if(response.data.logged_in === 'yes') {
								def.resolve(false);
								return false;
							} else {
								wp.heartbeat.connectNow();
								def.resolve(true);
							}
	
						} else {
							def.resolve(false);
						}
					},
					error: function () {
						def.resolve(false);
					}
				});

			} else {
				def.resolve(false);
			}
			return def;
		},
		clear_auth_context_check_and_continue = function () {
			$( document ).off(
				'heartbeat-tick.wp-auth-check',
				check_auth_context
			);
			var data = window._snapshot4_last_data,
				form = window._snapshot4_last_form,
				url = window.location.pathname + '?page=snapshot-backups';

			// New auth context - exchange nonces before continuing.
			$.get( url, function ( page_data ) {
				var $page = $( page_data );
				var $nonce = $page.find( '#_wpnonce-snapshot_trigger_backup_restore' ),
					nonce = $nonce.val();
				process_restore_backup( data, form, nonce );

				// Replace nonce values
				$page.find('input[id^=_wpnonce-]').each(function () {
					var input = $(this);
					$('#' + input.attr('id')).val(input.val());
				});
			} );
		},
		check_auth_context = function ( event, data ) {
			// Did we just get logged out?
			if ( ! ( 'wp-auth-check' in data ) ) {
				return false;
			}
			if (data['wp-auth-check']) {
				return true;
			}

			var $root = $( '#wp-auth-check-wrap' ),
				$iframe = $root.find( 'iframe' )
			;
			// Handle the login popup.
			if ( $root.length && !$root.is( '.snapshot4-bound' ) && $iframe.length ) {
				$root.addClass( 'snapshot4-bound' );
				$iframe.on( 'load', function() {
					var $body = $( this ).contents().find( 'body' );
					if ( $body.is( '.interim-login-success' ) ) {
						setTimeout( function() {
							clear_auth_context_check_and_continue();
							$root.removeClass( 'snapshot4-bound' );
						} );
					}

					/* DEFENDER 2FA BUG --> */
					if ( ! $body.html() ) {
						setTimeout( function() {
							clear_auth_context_check_and_continue();
							$root.removeClass( 'snapshot4-bound' );
							$root.find( '.wp-auth-check-close' ).trigger('click');
						} );
					}
					/* <-- DEFENDER 2FA BUG */

				});
			}
		};

		var process_restore = function () {
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			beforeSend: function () {
				form.find('.sui-button').prop('disabled', true);
			},
			complete: function () {
				form.find('.sui-button').prop('disabled', false);
				if (initial_request===1) {
					$(window).trigger('snapshot:close_modal');
				}
			}
		})
		.then(function (response) {
			if ( response.success ) {
				$('.button-create-backup').prop('disabled', true);
				if (initial_request===1) {
					jQuery(window).trigger('snapshot:show_top_notice', ['info', snapshot_messages.trigger_restore_info]);
				}
				if (response.data.task==='export') {
					set_restoration_step(backup_id, 0);
					// We have triggered an export, now lets keep track of its status
					data['export_id'] = response.data.api_response.export_id;
					setTimeout(process_restore_backup.bind(this, data, form, nonce), 5000);
				} else if (response.data.task==='exporting') {
					// We are currently quering to see when the export is done.
					if (response.data.api_response.export_status==='export_completed') {
						set_restoration_step(backup_id, 1);
						// OK, it's done, lets begin the download
						data['download_link'] = response.data.api_response.download_link;
						process_restore_backup(data, form, nonce);
					} else {
						set_restoration_step(backup_id, 0);
						data['export_id'] = response.data.api_response.export_id;
						setTimeout(process_restore_backup.bind(this, data, form, nonce), 5000);
					}

				} else if ( response.data.task==='download') {
					// We have initiated backup download, now lets keep track when the download is completed.
					if (response.data.done===true) {
						set_restoration_step(backup_id, 2);
					} else {
						set_restoration_step(backup_id, 1);
					}
					process_restore_backup(data, form, nonce);
				} else if ( response.data.task==='files') {
					if ( response.data.skipped_files.length > 0 ) {
						// We had at least one file that was skipped due to file permissions. Don't worry, we just need to notice the user when the restore ends.
						window.snapshot_skipped_files = window.snapshot_skipped_files.concat(response.data.skipped_files);
					}
					if (response.data.done===true) {
						set_restoration_step(backup_id, 3);
					} else {
						set_restoration_step(backup_id, 2);
					}
					process_restore_backup(data, form, nonce);
				} else if ( response.data.task==='tables') {
					if ( response.data.skipped_tables.length > 0 ) {
						// We had at least one table that was skipped due to wrong db prefix. Don't worry, we just need to notice the user when the restore ends.
						window.snapshot_skipped_tables = window.snapshot_skipped_tables.concat(response.data.skipped_tables);
					}
					if (response.data.done===true) {
						set_restoration_step(backup_id, 4);
					} else {
						set_restoration_step(backup_id, 3);
					}
					process_restore_backup(data, form, nonce);
				} else if ( response.data.task==='finalize') {
					if (response.data.done===true) {
						set_restoration_step(backup_id, 5);

						// Lets see which success notice we're gonna show. If we have skipped some files/tables, we need to communicate that.
						setTimeout( show_restore_result_notices(backup_id, window.snapshot_skipped_files, window.snapshot_skipped_tables, response.data.home), 1000);

						$('.button-create-backup').prop('disabled', false);
						$(window).trigger('snapshot:get-schedule');
					} else {
						// Show error.
					}
				}
			} else {
				set_restoration_step(backup_id, false);
				var notice = $('<span></span>').html(snapshot_messages.trigger_restore_error.replace('%s', response.data.failed_stage));
				notice.find('.snapshot-view-log').data('backupId', backup_id);
				notice.find('.snapshot-ftp-restoration-hub').data('backupId', backup_id);
				jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);

				$('.button-create-backup').prop('disabled', false);
			}

			if (response.data && response.data.log) {
				// Update log with new entries and hide loader if restoration is done or failed
				var done = response.data.task === 'finalize' && !!response.data.done;
				$(window).trigger('snapshot:update_log', [response.data.log, false, !done]);
				// Set offset for next update or stop updating if done
				$(window).trigger('snapshot:update_log_offset', [(done || update_log_offset === false) ? false : response.data.log.size]);
			} else if (backup_id && update_log_offset !== false) {
				// Get new log entries since the last update
				$(window).trigger('snapshot:update_log_ajax', [backup_id, update_log_offset, false, false]);
				// and stop continuous update of the log
				$(window).trigger('snapshot:update_log_offset', [false]);
			} else {
				// Hide loader in the log modal
				$(window).trigger('snapshot:update_log', [null, false, false]);
			}

		})
		.fail(function () {
			perform_auth_check(data, form).done(function (auth_check_result){
				if (auth_check_result) {
					// Let's first try to see if we got logged out.
					// If so, we might be able to handle that.
					return;
				} else {
					set_restoration_step(backup_id, false);

					var notice = $('<span></span>').html(snapshot_messages.trigger_restore_generic_error);
					notice.find('.snapshot-view-log').data('backupId', backup_id);
					notice.find('.snapshot-ftp-restoration-hub').data('backupId', backup_id);
					jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);

					$('.button-create-backup').prop('disabled', false);

					// Hide loader in the log modal
					$(window).trigger('snapshot:update_log', [null, false, false]);
				}
			});
		});
		};

		if ($('#snapshot-modal-log').is(':visible')) {
			// Also get new log entries since the last update
			$(window).trigger('snapshot:get_log_offset', [function (value) {
				update_log_offset = value;
				request_data.expand = 'log';
				request_data.log_offset = update_log_offset;
				process_restore();
			}]);
		} else {
			process_restore();
		}

	}

	function set_restoration_step(backup_id, step) {
		var snapshot_row = $('.snapshot-listed-backups .snapshot-row[data-backup_id="' + backup_id + '"]');
		var snapshot_details_row = $('.snapshot-listed-backups .snapshot-details-row[data-backup_id="' + backup_id + '"]');

		var percent = '0%';
		if (step === false) {
			// normal backup row
			snapshot_row.removeClass('snapshot-current-restoration');
			snapshot_details_row.removeClass('snapshot-current-restoration');

			snapshot_row.find('.progress-text').text('');
			snapshot_row.find('.percent-width').css('width', '0%');
			set_step_class(snapshot_details_row.find('.snapshot-restoration'), step);

			enable_restore_buttons(true);
		} else {
			// restoration
			percent = (step * 20) + '%';
			snapshot_row.find('.progress-text').text(percent);
			snapshot_row.find('.percent-width').css('width', percent);
			set_step_class(snapshot_details_row.find('.snapshot-restoration'), step);

			snapshot_row.addClass('snapshot-current-restoration');
			snapshot_details_row.addClass('snapshot-current-restoration');
		}

		var progressPercent = snapshot_details_row.find('.progressbar-container .sui-screen-reader-text > p');
		progressPercent.text(progressPercent.text().replace(/\d+%/, percent));
	}

	function show_restore_result_notices(backup_id, skipped_files, skipped_tables, home_url) {
		var no_skipped_files = false,
			no_skipped_tables = false;

		var is_wp_config_skipped = false;
		var wp_config_index = skipped_files.indexOf('/wp-config.php');
		if (wp_config_index >= 0) {
			is_wp_config_skipped = true;
			skipped_files.splice(wp_config_index, 1);
		}

		// Checking skipped files first.
		if ( skipped_files.length > 0 ) {
			if ( skipped_files.length === 1 ) {
				// Just one skipped file.
				var notice = $('<span></span>').html(snapshot_messages.trigger_restore_success_one_skipped_file.replace('%s', skipped_files[0]));
				notice.find('.snapshot-view-log').data('backupId', backup_id);

				jQuery(window).trigger('snapshot:show_top_notice', ['success', notice]);
			} else if ( skipped_files.length < 4 ) {
				// 2 or 3 skipped files.
				var notice = $('<span class="snapshot-unwrittable-files-notice"></span>').html(snapshot_messages.trigger_restore_success_few_skipped_files.replace('%s', skipped_files.length ) + '<span class="snapshot-unwrittable-files-list">' + skipped_files.join('</br>') + '</span>');

				notice.find('.snapshot-view-log').data('backupId', backup_id);

				jQuery(window).trigger('snapshot:show_top_notice', ['success', notice]);
			} else {
				// Over 3 skipped files.
				var notice_copy = '<span class="snapshot-many-unwrittable-files-notice">' + snapshot_messages.trigger_restore_success_few_skipped_files.replace('%s', skipped_files.length ) + '</span>',
					notice_filelist = '<span class="snapshot-many-unwrittable-files-list">' + skipped_files.slice(0, 3).join('</br>') + '</span>',
					notice_more_files = '<span class="snapshot-many-unwrittable-files-more"> (+ ' + ( skipped_files.length - 3 ) + ' more files)</span>';

				var notice = $('<span class="snapshot-unwrittable-files-notice"></span>').html(notice_copy + notice_filelist + notice_more_files);

				notice.find('.snapshot-view-log').data('backupId', backup_id);

				jQuery(window).trigger('snapshot:show_top_notice', ['success', notice]);

			}
			window.snapshot_skipped_files = [];
		} else {
			no_skipped_files = true;
		}

		// Checking skipped tables now.
		if ( skipped_tables.length > 0 ) {
			if ( skipped_tables.length === 1 ) {
				// Just one skipped file.
				var notice_tables = $('<span></span>').html(snapshot_messages.trigger_restore_success_one_skipped_table.replace('%s', skipped_tables[0]));
				notice_tables.find('.snapshot-view-log').data('backupId', backup_id);

				jQuery(window).trigger('snapshot:show_top_notice', ['success', notice_tables]);
			} else if ( skipped_tables.length < 4 ) {
				// 2 or 3 skipped tables.
				var notice_tables = $('<span class="snapshot-unwrittable-tables-notice"></span>').html(snapshot_messages.trigger_restore_success_few_skipped_tables.replace('%s', skipped_tables.length ) + '<span class="snapshot-unwrittable-tables-list">' + skipped_tables.join('</br>') + '</span>');

				notice_tables.find('.snapshot-view-log').data('backupId', backup_id);

				jQuery(window).trigger('snapshot:show_top_notice', ['success', notice_tables]);
			} else {
				// Over 3 skipped tables.
				var notice_tables_copy = '<span class="snapshot-many-unwrittable-tables-notice">' + snapshot_messages.trigger_restore_success_few_skipped_tables.replace('%s', skipped_tables.length ) + '</span>',
					notice_tablelist = '<span class="snapshot-many-unwrittable-tables-list">' + skipped_tables.slice(0, 3).join('</br>') + '</span>',
					remaining_tables = skipped_tables.length - 3,
					notice_more_tables = (remaining_tables === 1) ? '<span class="snapshot-many-unwrittable-tables-more"> (+ ' + remaining_tables + ' more table)</span>' : '<span class="snapshot-many-unwrittable-tables-more"> (+ ' + remaining_tables + ' more tables)</span>';

				var notice_tables = $('<span class="snapshot-unwrittable-tables-notice"></span>').html(notice_tables_copy + notice_tablelist + notice_more_tables);

				notice_tables.find('.snapshot-view-log').data('backupId', backup_id);

				jQuery(window).trigger('snapshot:show_top_notice', ['success', notice_tables]);

			}
			window.snapshot_skipped_tables = [];
		} else {
			no_skipped_tables = true;
		}

		if (no_skipped_files === true && no_skipped_tables === true) {
			var notice = $('<span></span>').html(snapshot_messages.trigger_restore_success.replace('%s', home_url));
			jQuery(window).trigger('snapshot:show_top_notice', ['success', notice]);
		}
		if (is_wp_config_skipped) {
			var notice = $('<span></span>').html(snapshot_messages.trigger_restore_success_wp_config_skipped);
			$(window).trigger('snapshot:show_top_notice', ['success', notice]);
		}

		set_restoration_step(backup_id, false);
	}

	function set_step_class(element, step) {
		element.each(function () {
			element = $(this);
			element.attr('class').split(' ').filter(function (cl) {
				return cl.match(/^step\-/);
			}).forEach(function (cl) {
				element.removeClass(cl);
			});
			element.addClass('step-' + step);
		});

	}

	function cancel_restore(e, backup_id) {
		if (e && e.preventDefault) e.preventDefault();
		var data = {
            backup_id: backup_id,
            _wpnonce: $( '#_wpnonce-snapshot_cancel_backup_restore' ).val()
		};

		var cancel_button = $('.cancel-restore[data-backup-id=' + backup_id + ']');
		cancel_button.addClass('sui-button-onload-text');
		
		var cancel_restore_href = ajaxurl + '?action=snapshot-cancel_restore';
		
		snapshot_ajax_lhb_xhr = jQuery.ajax({
            type: 'POST',
            url: cancel_restore_href,
            data: data,
            cache: false,
            dataType: 'json',
            success: function (reply_data) {
                if (reply_data.success) {
                    cancelled_restore = true;
                } else {
					cancel_button.removeClass('sui-button-onload-text');
                    jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.restore_cancel_error]);
                }
            },
            error: function () {
				cancel_button.removeClass('sui-button-onload-text');
				jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.restore_cancel_error]);
            }
        });
	}

	function enable_restore_buttons(value) {
		if (value === undefined) {
			value = true;
		}
		$('.snapshot-list-backups .snapshot-restore-backup').prop('disabled', !value);
	}

	/**
	 * Prevent page closing during restoration
	 */
	function on_before_unload(e) {
		var prevent = $('.snapshot-listed-backups .snapshot-current-restoration').length !== 0;

		if (!prevent) {
			return;
		}

		var e = e || window.event;
		if (e) {
			// Firefox
			e.preventDefault();
			e.returnValue = '';
		}
		return '';
	}
    
    $(function() {
		if ( $( '.snapshot-page-backups' ).length ) {
			$('#form-snapshot-restore-backup').on('submit', trigger_restore_backup);

			$('.snapshot-page-backups').on('click', '.snapshot-view-log', function (e) {
				if (e && e.preventDefault) e.preventDefault();
				$(this).trigger('snapshot:close_notice');
				$(window).trigger('snapshot:view_log', [true, $(this).data('backupId')]);
			});
			$('.snapshot-page-backups').on('click', '.snapshot-ftp-restoration-hub', function (e) {
				if (e && e.preventDefault) e.preventDefault();
				$(this).trigger('snapshot:close_notice');
				window.open(snapshot_urls.hub_backup_tab + '?ftp-restore=' + $(this).data('backupId'), '_blank');
			});
			$('.snapshot-page-backups').on('click', '.view-log-in-modal', function (e) {
				if (e && e.preventDefault) e.preventDefault();
				$(this).trigger('snapshot:close_notice');
				$(window).trigger('snapshot:open_log_modal', [$(this).data('backupId')]);
			});

			$(window).on('snapshot:cancel_restore', cancel_restore);

			$(window).on('beforeunload', on_before_unload);
		}
	});
})(jQuery);
