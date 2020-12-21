/**
 * Page: Backups.
 */
;(function($) {

	// Offset for continuous update of the running backup log, false - if don't need to update the log
	var update_log_offset = false;

	// Allow auto scrolling in log modal
	var log_allow_auto_scroll = true;

	// Previous scroll position in log modal
	var log_prev_scroll = 0;

	/**
	 * Tabs in Backups page. Array - [box selector, vertical selector, mobile nav selector].
	 *
	 * @type {Object}
	 */
	var navbar_tabs = {
		'backups': ['.snapshot-list-backups', '.snapshot-vertical-backups', '#undefined-option-backups'],
		'logs': ['.snapshot-logs', '.snapshot-vertical-logs', '#undefined-option-logs'],
		'settings': ['.snapshot-backups-settings', '.snapshot-vertical-settings', '#undefined-option-settings'],
		'notifications': ['.snapshot-notifications', '.snapshot-vertical-notifications', '#undefined-option-notifications']
	};

	/**
	 * Toggles the navbar to show specified tab.
	 *
	 * @param {string} tab Tab in Backups page.
	 */
	function toggle_navbar(tab) {
		for (var current_tab in navbar_tabs) {
			var box_selector = navbar_tabs[current_tab][0];
			var vertical_selector = navbar_tabs[current_tab][1];

			if (tab === current_tab) {
				// Toggle to a box.
				$('.snapshot-page-main').find(box_selector).show();
				// Make a sidenav active.
				$('.snapshot-page-main').find(vertical_selector).addClass('current');
			} else {
				$('.snapshot-page-main').find(box_selector).hide();
				$('.snapshot-page-main').find(vertical_selector).removeClass('current');
			}

			if (tab === 'logs') {
				reload_logs(false);
			}
		}
		return false;
	}

    // Retrieve backups info list.
    function snapshot_list_backups() {
        var data = {
            _wpnonce: $( '#_wpnonce-list-backups' ).val()
        };

        $('.snapshot-listed-backups').hide();
        $('.snapshot-no-backups').hide();
        // $('.wps-backup-list-ajax-error').hide();
        
        var deferred = jQuery.Deferred();

        var list_backups_href = ajaxurl + '?action=snapshot-list_backups';

        snapshot_ajax_lhb_xhr = jQuery.ajax({
            type: 'POST',
            url: list_backups_href,
            data: data,
            cache: false,
            dataType: 'json',
			beforeSend: function () {
				$('.sui-box-header.snapshot-has-backups-title').hide();
				$('.snapshot-loading').show();
				$('.snapshot-list-backups .api-error').hide();
				$('.sui-summary-details.snapshot-backups-number .sui-summary-large').text('');
				$('.sui-summary-segment .snapshot-last-backup').text('');
			},
			complete: function () {
				$('.snapshot-loading').hide();
			},
            success: function (reply_data) {
                if (reply_data.success && reply_data.data.backups !== undefined) {
                    backups_number = reply_data.data.backups.length;
					failed_backups_number = reply_data.data.failed_backups || 0;

                    $('.snapshot-listed-backups .sui-table > tbody').empty();

                    // Deal with updating the number of backups.
                    $('.sui-summary-details.snapshot-backups-number .sui-summary-large').html( backups_number - failed_backups_number );

                    if ( reply_data.data.backups && backups_number !== 0) {
                        // Deal with the Last Backup section.
                        lastBackup = reply_data.data.backups[0];
						$('.sui-summary-segment .snapshot-last-backup').html( lastBackup.date );

						// Disable the Backup Now button, if we're still in the same minute with the last backup taken.
						var time_elapsed = ( Date.now() / 1000 - lastBackup.timestamp ) / 60 ;
						$(window).trigger('snapshot:toggle_cooldown', [time_elapsed]);

                        // Deal with the backup listing and loader.
                        $('.snapshot-listed-backups').show();
                        $('.snapshot-no-backups').hide();
                        $('.sui-box-header.snapshot-has-backups-title').show();

                        $.each(reply_data.data.backups, function(i, item) {
                            var row = $(item.row);
                            if (!row.hasClass('snapshot-failed-backup')) {
                                row.addClass('sui-accordion-item');
                            }
                            $('.snapshot-listed-backups .sui-table > tbody:last-child').append(row);
                            $('.snapshot-listed-backups .sui-table > tbody:last-child').append(item.row_content);
						});

						// Workaround to both show the size tooltip above the icon and to show it when hovering the whole span.
						$('.snapshot-tooltip-size').hover(function () {
							$(this).parent().find('.snapshot-icon-tooltip2').show();
							$(this).parent().find('.snapshot-icon-tooltip').hide();
						},function () {
							$(this).parent().find('.snapshot-icon-tooltip').show();
							$(this).parent().find('.snapshot-icon-tooltip2').hide();
						});

						// Workaround to both show the export tooltip above the icon and to show it when hovering the whole span.
						$('.snapshot-export-details-failure').parent().find('.snapshot-export-backup-details').hover(function () {
							$(this).parent().find('.snapshot-export-details-failure2').show();
							$(this).parent().find('.snapshot-export-details-failure').hide();
						},function () {
							$(this).parent().find('.snapshot-export-details-failure').show();
							$(this).parent().find('.snapshot-export-details-failure2').hide();
						});

						// Workaround to hide the destination list tooltip when the i tooltip is hovered.
						$(window).trigger('snapshot:hide_double_tooltip');
                    } else {
                        // Deal with the Last Backup section.
                        $('.sui-summary-segment .snapshot-last-backup').text(snapshot_messages.last_backup_unknown_date);

                        if (false === reply_data.data.backup_running) {
                            // Deal with the backup listing header.
                            $('.sui-box-header.snapshot-has-backups-title').hide();
    
                            // Deal with the backup listing and loader.
                            $('.snapshot-no-backups').show();
                        } else {
                            $('.snapshot-listed-backups').show();
                            $('.sui-box-header.snapshot-has-backups-title').show();
                        }
                    }

                    deferred.resolve(reply_data);
                } else {
                    deferred.reject("error");
                }
                if (!reply_data.success) {
                    show_api_error();
                }
            },
            error: function () {
                deferred.reject("HTTP error");
                show_api_error();
            }
        });

        return deferred.promise();
    }

	function show_api_error() {
		$('.snapshot-list-backups .api-error').show();
		$('.sui-summary-details.snapshot-backups-number .sui-summary-large').text('-');
		$('.sui-summary-segment .snapshot-last-backup').text('-');
		$('.sui-box-header.snapshot-has-backups-title').show();
	}

	function snapshot_get_schedule(open_modal, data) {
		var schedule_modal = function (data) {
			$('#snapshot-backup-schedule').find('>span').text(data.text);
			$('#snapshot-backup-schedule').data('values', data.values);
			$('.snapshot-backups-summary .snapshot-next-backup').text(data.next_backup_time);
			if (open_modal) {
				$(window).trigger('snapshot:schedule', [data.schedule_action, data.values, data.schedule_action]);
			}
		};

		var url = ajaxurl + '?action=snapshot-get_schedule';
		var request_data = {
			_wpnonce: $('#_wpnonce-get-schedule').val()
		};

		if (data) {
			schedule_modal(data);
		} else {
			$.ajax({
				type: 'GET',
				url: url,
				data: request_data,
				cache: false,
				dataType: 'json',
				success: function (response) {
					if (response.success) {
						schedule_modal(response.data);
						$('#snapshot-backup-schedule').data('modalData', response.data);
						$('.snapshot-listed-backups .open-edit-schedule')
							.data('modalData', response.data)
							.find('>span.schedule').text(response.data.text);
					} else {
						jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.get_schedule_error]);
					}
				},
				error: function () {
					jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.get_schedule_error]);
				}
			});
		}
	}

	function snapshot_backup_progress(needs_api_call, already_running_backup_status, already_running_backup) {
        if ($('.snapshot-listed-backups .sui-table > tbody:last-child .current-backup-row').data('updating')) {
            return;
        }

        needs_api_call = needs_api_call || '0';
		already_running_backup_status = already_running_backup_status || '0';
		already_running_backup = already_running_backup || '0';

        var refresh_interval = 5000;

        var url = ajaxurl + '?action=snapshot-update_backup_progress';
		var request_data = {
			_wpnonce: $('#_wpnonce-backup-progress').val(),
			do_api_call: needs_api_call,
			already_running_backup: already_running_backup,
			already_running_backup_status: already_running_backup_status
        };

		if (update_log_offset !== false) {
			// Also get new log entries since the last update
			request_data.expand = 'log';
			request_data.log_offset = update_log_offset;
		}

        $.ajax({
            type: 'GET',
            url: url,
            data: request_data,
            cache: false,
            dataType: 'json',
            success: function (response) {
                var tbody = $('.snapshot-listed-backups .sui-table > tbody:last-child');
                var row = tbody.find('.current-backup-row');
				var row_details = tbody.find('.current-backup-details');
				if ( response.success ) {
					if ( true === response.data.backup_cancelled ) {
						backup_is_cancelled();
					} else if ( true === response.data.backup_failed ) {
						// Running backup has failed.
						var notice = $('<span></span>').html(snapshot_messages.running_backup_fail);
						var a = notice.find('a');
						a.eq(0).on('click', function (e) {
							var link = this;
							$(link).data('backupId', already_running_backup.id);
							e.preventDefault();
							notice.trigger('snapshot:close_notice');
							// reload logs on log tab before switching to it
							reload_logs(true).then(function () {
								view_log.bind(link)();
								toggle_navbar('logs');
								$(window).trigger('snapshot:close_modal');
							});
						});
						a.eq(1).on('click', function (e) {
							e.preventDefault();
							notice.trigger('snapshot:close_notice');
							
							jQuery(window).trigger('snapshot:backup_modal');
						});
				
						jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);

						$('.button-create-backup').prop('disabled', false);

						reload_logs();

						snapshot_list_backups();
					} else if ( 'snapshot_completed' === response.data.backup_running_status ) {
						// Running backup is completed, yay.
						$('#snapshot-modal-cancel-backup #snapshot-cancel-backup').prop('disabled', true);
						update_last_backup_row();
					} else if (false !== response.data.backup_running) {
						// Running backup is yet to be completed, keep doing what you doing.
						var data = $(response.data.backup_running_row).data();
						if (row_details.length && data.percent > 0) {
							row_details.find('.button-view-log').prop('disabled', false);
							row_details.find('.button-cancel-backup').prop('disabled', false);
						}
						if (row.length) {
							// Don't replace the entire block
							// in order to preserve animation phase of loader and prevent its jitter...
							row.find('>tr').attr('id', data.id);
							row.data('backupId', data.backupId);
							row.find('.backup-name').text(data.name);
							row.find('.progress-text').text(data.progressText);

							// Build the Export Destination column
							var export_destination_text;
							if ('None' === response.data.export_text.row) {
								export_destination_text = snapshot_messages.no_destinations;
							} else if ('Loading' === response.data.export_text) {
								export_destination_text = "<i class='sui-icon-loader sui-loading snapshot-destination-loader' aria-hidden='true'></i>" + snapshot_messages.loading_destinations
							} else if (typeof response.data.export_text.html !== 'undefined'){
								var exports = response.data.export_text.html;
								if ( 1 < exports.exports_count ) {
									var export_text = exports.first_export + snapshot_messages.more_destinations.replace('%d', exports.exports_count - 1);
									export_destination_text = "<span class='snapshot-export-backup-header snapshot-" + exports.first_export_type + "-export-backup-header sui-tooltip sui-tooltip-left-mobile sui-tooltip-constrained' style='--tooltip-width: 170px;' data-tooltip='" + exports.exports_tooltip + "'>" + export_text + '</span>';
								} else {
									export_destination_text  = "<span class='snapshot-export-backup-header snapshot-" + exports.first_export_type + "-export-backup-header'>" + exports.first_export + "</span>";
								}
							}
							row.find('.snapshot-backup-export-destinations span').html(export_destination_text);
							row_details.find('.snapshot-backup-export-destinations span').html(export_destination_text);

							row.find('.percent-width').css('width', data.percentWidth);
							$('.current-backup-step-wrap').each(function () {
								var wrap = $(this);
								wrap.attr('class').split(' ').filter(function (cl) {
									return cl.match(/^step\-/);
								}).forEach(function (cl) {
									wrap.removeClass(cl);
								});
								var progressPercent = wrap.find('.progressbar-container .sui-screen-reader-text > p');
								progressPercent.text(progressPercent.text().replace(/\d+%/, data.progressText));
							});
							$('.current-backup-step-wrap').addClass('step-' + data.step);
							$('.current-backup-step-wrap .current-step').text('Step ' + data.step + '/' + data.stepMax);
						} else {
							tbody.prepend(response.data.backup_running_row);
						}

						// If backup still at trigger stage, repeat the progress call with data from the db, if not repeat with data from the DOM.
						if ('manual' !== response.data.backup_running.id) {
							next_running_backup = response.data.backup_running;
						} else {
							next_running_backup = '0';
						}

						// Let's repeat the process, but this time make an api call to get the status.
						// We don't need to do that upon page load, because we use the info taken and stored by the Snapshot_Task_Request_Listing upon page load.
						setTimeout(snapshot_backup_progress.bind(this, '1', response.data.backup_running_status, next_running_backup), refresh_interval);
					}
				} else {
					if ( $('#snapshot-modal-cancel-backup #snapshot-cancel-backup').hasClass('sui-button-onload-text') ) {
						backup_is_cancelled();
					} else {
						jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.update_progress_fail]);
					}
				}

				if (response.data && response.data.log) {
					// Update log with new entries and hide loader if snapshot_completed or backup_failed
					var done = 'snapshot_completed' === response.data.backup_running_status || response.data.backup_failed;
					update_log(response.data.log, false, !done);
					// Set offset for next update or stop updating if done
					update_log_offset = (done || update_log_offset === false) ? false : response.data.log.size;
				} else if (already_running_backup.id && update_log_offset !== false) {
					// Get new log entries since the last update
					update_log_ajax(already_running_backup.id, update_log_offset, false, false);
					// and stop continuous update of the log
					update_log_offset = false;
				} else {
					// Hide loader in the log modal
					update_log(null, false, false);
				}
            },
            error: function () {
                // Hide loader in the log modal
                update_log(null, false, false);
                jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.update_progress_fail]);
            }
        });
	}

	function update_last_backup_row() {
		var tbody = $('.snapshot-listed-backups .sui-table > tbody:last-child');
		var row = tbody.find('.current-backup-row');
		if (!row.length || row.data('updating')) {
			return;
		}
		row.data('updating', true);

		reload_logs();

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'snapshot-list_backups',
				_wpnonce: $( '#_wpnonce-list-backups' ).val(),
				force_refresh: 1
			},
			cache: false,
			dataType: 'json',
			success: function (data) {
				if (data.success && data.data.backups !== undefined) {
					var item = data.data.backups[0];
					var backups_number = data.data.backups.length;
					failed_backups_number = data.data.failed_backups || 0;
					var last_backup = data.data.backups[0];

					// Disable the Backup Now button, if we're still in the same minute with the last backup taken.
					var time_elapsed = ( Date.now() / 1000 - last_backup.timestamp ) / 60 ;
					$(window).trigger('snapshot:toggle_cooldown', [time_elapsed]);

					row.find('.progress-text').text('100%');
					row.find('.percent-width').css('width', '100%');
					var step_max = row.data('stepMax');
					$('.current-backup-step-wrap').each(function () {
						var wrap = $(this);
						wrap.attr('class').split(' ').filter(function (cl) {
							return cl.match(/^step\-/);
						}).forEach(function (cl) {
							wrap.removeClass(cl);
						});
						var progressPercent = wrap.find('.progressbar-container .sui-screen-reader-text > p');
						progressPercent.text(progressPercent.text().replace(/\d+%/, '100%'));
					});
					$('.current-backup-step-wrap').addClass('step-' + step_max);
					$('.current-backup-step-wrap .current-step').text('Step ' + step_max + '/' + step_max);

					$('.snapshot-listed-backups .snapshot-details-row').removeClass('snapshot-last-backup');
					setTimeout(function () {

                        $('.button-create-backup').prop('disabled', false);
                        
						row.remove();
						row = $(item.row);
						var row_content = $(item.row_content);
						if (!row.hasClass('snapshot-failed-backup')) {
						    row.addClass('sui-accordion-item');
						}
						$('.snapshot-listed-backups .sui-table > tbody:last-child').prepend(row_content);
						$('.snapshot-listed-backups .sui-table > tbody:last-child').prepend(row);

						// Workaround to hide the destination list tooltip when the i tooltip is hovered.
						$(window).trigger('snapshot:hide_double_tooltip');

						$('.sui-summary-details.snapshot-backups-number .sui-summary-large').html( backups_number - failed_backups_number );
						$('.sui-summary-segment .snapshot-last-backup').html( last_backup.date );
						// Show the appropriate message of backup completion.
						var export_header = row.find('.snapshot-export-backup-header');
						var backup_complete_msg,
							backup_complete_icon;
						if (!export_header.length) {
							backup_complete_msg = snapshot_messages.create_backup_success;
							backup_complete_icon = 'success';
						} else if (export_header.parent().find('.snapshot-export-failure').length ) {
							backup_complete_msg = snapshot_messages.export_backup_failure;
							backup_complete_icon = 'error';
						} else {
							backup_complete_msg = snapshot_messages.export_backup_success;
							backup_complete_icon = 'success';
						}
						jQuery(window).trigger('snapshot:show_top_notice', [backup_complete_icon, backup_complete_msg]);
						row.trigger('click');
					}, 3000);
				} else {
					row.data('updating', false);
				}
			},
			error: function () {
				row.data('updating', false);
			}
		});
	}

	function handle_create_manual_backup(e) {
		if (e && e.preventDefault) e.preventDefault();

		var form = $('#form-snapshot-create-manual-backup');
		var data = {};
		form.serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});

		var request_data = {
			action: 'snapshot-trigger_backup',
			_wpnonce: $( '#_wpnonce-snapshot_backup_create_manual' ).val(),
			data: {
				backup_name: data.backup_name,
				apply_exclusions: data.apply_exclusions === 'on'
			}
		};

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			beforeSend: function () {
				form.find('.sui-button').addClass('sui-button-onload-text', true);
				$('.button-create-backup').prop('disabled', true);
			},
			complete: function () {
				form.find('.sui-button').removeClass('sui-button-onload-text', true);
			},
			success: function (response) {
                if ( response.success && false !== response.data.backup_running ) {
                    if ($('.snapshot-listed-backups .snapshot-row').length) {
                        jQuery(window).trigger('snapshot:show_top_notice', ['info', snapshot_messages.backup_is_in_progress]);
                    } else {
                        // First backup
                        jQuery(window).trigger('snapshot:show_top_notice', ['info', snapshot_messages.manual_backup_success]);
                    }

                    $('.snapshot-listed-backups').show();
                    $('.snapshot-no-backups').hide();
                    $('.sui-box-header.snapshot-no-backups-title').hide();
                    $('.sui-box-header.snapshot-has-backups-title').show();

                    $('.snapshot-listed-backups .sui-table > tbody:last-child').prepend(response.data.backup_running);
                    snapshot_backup_progress('1', 'just_triggered');
                } else {
                    if (response.data && Array.isArray(response.data.messages) && response.data.messages.length) {
                        response.data.messages.forEach(function (message) {
                            var message_key = 'manual_' + message;
                            var notice = $('<span></span>').html(snapshot_messages[message_key]);
                            notice.find('a').eq(0).on('click', function () {
                                notice.trigger('snapshot:close_notice');
                                toggle_navbar('logs');
                            });
                            notice.find('a').eq(1).on('click', function () {
                                notice.trigger('snapshot:close_notice');
                                jQuery(window).trigger('snapshot:backup_modal');
                            });
                            jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);
                        });
                    } else {
                        jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.manual_backup_error]);
                    }
                }
				$(window).trigger('snapshot:close_modal');
			}
		});
	}

	function delete_backup(e, backup) {
		e = e || false;
		if (e && e.preventDefault) e.preventDefault();

		var delete_buttons = $('.snapshot-last-backup .snapshot-delete-backup .sui-button-icon');
		call_if_can_delete_backup(delete_backup_confirm.bind(this, backup), function () {
			delete_buttons.prop('disabled', true);
		}, function () {
			delete_buttons.prop('disabled', false);
		});
	}

	function delete_backup_confirm(backup) {
		SUI.openModal('snapshot-modal-delete-backup', this);
		$('#snapshot-modal-delete-backup').data('backupId', backup);
		$('#snapshot-delete-backup-button').removeClass('sui-button-onload-text').addClass('sui-button-red');
		$('#snapshot-modal-delete-backup-error-notice').hide();
		return false;
	}

	function delete_backup_confirmed() {
		var backup = $('#snapshot-modal-delete-backup').data('backupId');
		delete_backup_force(backup);
	}

	function delete_backup_force(backup) {
		var delete_buttons = $('.snapshot-last-backup .snapshot-delete-backup .sui-button-icon, #snapshot-settings-delete-backups-confirm');
		var modal_button = $('#snapshot-delete-backup-button');

		delete_buttons.prop('disabled', true);
		modal_button.addClass('sui-button-onload-text').removeClass('sui-button-red');
		$('#snapshot-modal-delete-backup-error-notice').hide();

		var request_data = {
			action: 'snapshot-delete_backup',
			_wpnonce: $('#_wpnonce-delete-backup').val(),
			data: {
				backup_id: backup,
			}
		};

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			success: function (response) {
                if ( response.success ) {
                    jQuery(window).trigger('snapshot:show_top_notice', ['info', snapshot_messages.settings_delete_success, 3000, false]);

                    var Promise = $.when(snapshot_list_backups());
                    Promise.done(function () {
                        snapshot_backup_progress();
                    });

                    var log_row = $('.logs-list .log-row[data-backup-id="' + backup + '"]');
                    log_row.next().remove();
                    log_row.remove();
                    if (!$('.logs-list .log-row').length) {
                        $('.snapshot-logs .logs-empty').show();
                        $('.snapshot-logs .logs-not-empty').hide();
                    }
                    $(window).trigger('snapshot:close_modal');
                } else {
                    $('#snapshot-modal-delete-backup-error-notice').show();
                }
            },
                error: function () {
                $('#snapshot-modal-delete-backup-error-notice').show();
            },
            complete: function () {
                delete_buttons.prop('disabled', false);
                modal_button.removeClass('sui-button-onload-text').addClass('sui-button-red');
            }
        });        
    }
    
    function export_backup(e, backup) {
        if (e && e.preventDefault) e.preventDefault();

        var request_data = {
			action: 'snapshot-export_backup',
			_wpnonce: $('#_wpnonce-export-backup').val(),
			backup_id: backup
        };
        
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			success: function (response) {
                if ( response.success ) {					
                    var notice = $('<span></span>').html(snapshot_messages.backup_export_success.replace('%s', response.data.site));
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

	function cancel_backup_confirm() {
		var backup_row = $(this).closest('table').find('.current-backup-row');
		var backup_id = backup_row.data('backupId');
		$('#snapshot-modal-cancel-backup #snapshot-cancel-backup').data('backupId', backup_id);
		$('#snapshot-modal-cancel-backup #snapshot-cancel-backup').prop('disabled', false);
		SUI.openModal('snapshot-modal-cancel-backup', this);
	}

	function cancel_backup() {
		var backup_id = $(this).data('backupId');
		var data = {};

		data._wpnonce = $( '#_wpnonce-snapshot_cancel_backup' ).val();
		data.backup_id = backup_id;

		var url = ajaxurl + '?action=snapshot-cancel_backup';

		$.ajax({
			type: 'POST',
			url: url,
			data: data,
			cache: false,
			dataType: 'json',
			beforeSend: function () {
				$('#snapshot-modal-cancel-backup .snapshot-cancel-backup-cancel').prop('disabled', true);
				$('#snapshot-modal-cancel-backup #snapshot-cancel-backup').prop('disabled', true);
				$('#snapshot-modal-cancel-backup #snapshot-cancel-backup').addClass('sui-button-onload-text');
			},
			success: function (data) {
				if ( ! data.success) {
					jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.cancel_backup_error]);
				}
			},
			error: function () {
				$(window).trigger('snapshot:close_modal');
				jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.cancel_backup_error]);
			}
		});

		return false;
	}

	function filter_log() {
		var select = $(this);
		var container = select.closest('.sui-box').find('.log-items-container');
		var no_warning = container.find('.no-warning');
		var no_error = container.find('.no-error');
		var log_items = container.find('>.log-item');
		var show_items = log_items;
		var value = select.val();

		no_warning.hide();
		no_error.hide();
		log_items.hide();

		switch (value) {
			case 'all':
				break;
			case 'warning':
				show_items = log_items.filter('.log-level-warning, .log-level-error');
				if (!show_items.length) {
					no_warning.show();
				}
				break;
			case 'error':
				show_items = log_items.filter('.log-level-error');
				if (!show_items.length) {
					no_error.show();
				}
				break;
		}

		show_items.show();
	}

	function view_backup(backup_id) {
		var row = $('.snapshot-listed-backups .snapshot-row[data-backup_id="' + backup_id + '"]');
		if (row.length) {
			toggle_navbar('backups');
			if (!row.hasClass('sui-accordion-item--open')) {
				row.trigger('click');
			}
			$([document.documentElement, document.body]).animate({
				scrollTop: row.offset().top - 35
			}, 'slow');
		} else {
			jQuery(window).trigger('snapshot:show_top_notice', ['warning', snapshot_messages.log_backup_not_found]);
		}
	}

	function view_log(e, force_reload, backup_id) {
		var $this = $(this);
		if (force_reload === undefined) {
			force_reload = false;
		}
		if (!backup_id) {
			backup_id = $this.data('backupId');
		}
		reload_logs(force_reload).done(function () {
			var row = $('.logs-list .log-row[data-backup-id="' + backup_id + '"]');
			if (row.length) {
				toggle_navbar('logs');
				if (!row.hasClass('sui-accordion-item--open')) {
					row.trigger('click');
				}
				$([document.documentElement, document.body]).animate({
					scrollTop: row.offset().top - 35
				}, 'slow');
			} else {
				jQuery(window).trigger('snapshot:show_top_notice', ['warning', snapshot_messages.backup_log_not_found]);
			}
		});
	}

	function on_log_row_click() {
		var row = $(this);
		var content = row.next();
		if (content.data('loaded')) {
			return;
		}
		var backup_id  = row.data('backupId');
		var append_log = row.data('appendLog');

		jQuery.ajax({
			type: 'GET',
			url: ajaxurl,
			data: {
				action: 'snapshot-get_backup_log',
				backup_id: backup_id,
				append_log: append_log,
				_wpnonce: $('#_wpnonce-get-backup-log').val()
			},
			cache: false,
			dataType: 'json',
			beforeSend: function () {
				content.find('.snapshot-loading').show();
				content.find('.snapshot-loaded').hide();
			},
			complete: function () {
				content.find('.snapshot-loading').hide();
				content.find('.snapshot-loaded').show();
			},
			success: function (data) {
				if (data.success) {
					var container = content.find('.log-items-container');
					data.data.log.items.forEach(function (item) {
						var log_item = $('<div class="log-item"></div>');
						log_item.addClass('log-level-' + item.level);
						$('<div class="log-item__icon" aria-hidden="true"></div>').appendTo(log_item);
						$('<div class="log-item__content"></div>').text(item.message).appendTo(log_item);
						log_item.appendTo(container);
					});
					content.data('loaded', true);
				}
			}
		});
	}

	function reload_logs(force) {
		if (force === undefined) {
			force = true;
		}

		var deferred = jQuery.Deferred();

		if (!force) {
			var logs_loaded = $('.snapshot-page-main .logs-list').data('logsLoaded');
			if (logs_loaded) {
				deferred.resolve();
				return deferred.promise();
			}
		}

		jQuery.ajax({
			type: 'GET',
			url: ajaxurl,
			data: {
				action: 'snapshot-get_log_list',
				_wpnonce: $('#_wpnonce-get-backup-log').val()
			},
			cache: false,
			dataType: 'json',
			beforeSend: function () {
				$('.snapshot-logs .logs-empty').hide();
				$('.snapshot-logs .logs-not-empty').hide();
				$('.logs-list .logs-loading').show();
			},
			complete: function () {
				$('.logs-list .logs-loading').hide();
			},
			success: function (data) {
				if (data.success) {
					$('.logs-list').data('logsLoaded', true);
					$('.snapshot-logs .log-rows').html(data.data.content);
					$('.logs-list .log-row').on('click', on_log_row_click);
					$('.logs-list .log-rows select').each(function () {
						SUI.suiSelect(this);
					});
					if (data.data.show_log) {
						$('.snapshot-logs .logs-empty').hide();
						$('.snapshot-logs .logs-not-empty').show();
					} else {
						$('.snapshot-logs .logs-empty').show();
						$('.snapshot-logs .logs-not-empty').hide();
					}
					deferred.resolve();
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

	/**
	 * Open log modal for running backup and start continuous update
	 */
	function open_log_modal(e, backup_id) {
		var modal = $('#snapshot-modal-log');
		if (!modal.length) {
			return;
		}

		modal.find('.sui-box-header .sui-button-icon').on('click', function () {
			update_log_offset = false;
			$(window).trigger('snapshot:close_modal');
		});

		SUI.openModal('snapshot-modal-log', this);

		update_log_ajax(backup_id, 0, true, true);
	}

	/**
	 * Update log modal for running backup with new log entries which are after offset
	 */
	function update_log_ajax(backup_id, offset, clear, show_loader) {
		$.ajax({
			type: 'GET',
			url: ajaxurl,
			data: {
				action: 'snapshot-get_backup_log',
				backup_id: backup_id,
				offset: offset,
				_wpnonce: $('#_wpnonce-get-backup-log').val()
			},
			cache: false,
			dataType: 'json',
			success: function (data) {
				if (data.success) {
					update_log(data.data.log, clear, show_loader);
				} else {
					// Hide loader if error
					update_log(null, false, false);
				}
			},
			error: function () {
				// Hide loader if error
				update_log(null, false, false);
			}
		});
	}

	/**
	 * Update log modal for running backup with new log entries from log.items and scroll down
	 */
	function update_log(log, clear, show_loader) {
		var modal = $('#snapshot-modal-log');
		var log_container = modal.find('.log-container');
		var frame = log_container.closest('.sui-border-frame');

		if (clear) {
			log_container.empty();
			// Offset for next updating
			update_log_offset = log ? log.size : false;

			log_prev_scroll = 0;
			log_allow_auto_scroll = true;
			frame.off('scroll');
			frame.on('scroll', function () {
				var scrollTop = frame.prop('scrollTop');
				var bottom_scroll_offset = frame.prop('scrollHeight') - frame.prop('clientHeight') - scrollTop;
				if (scrollTop < log_prev_scroll) {
					log_allow_auto_scroll = false;
				};
				if (bottom_scroll_offset <= 2) {
					log_allow_auto_scroll = true;
				}
				log_prev_scroll = scrollTop;
			});
		}

		// Remove loader
		log_container.find('>p.log-item:last-child i.sui-icon-loader').remove();

		if (log && log.items) {
			// Update log in modal
			var items = log.items;
			items.reverse().forEach(function (item) {
				var log_item = $('<p class="log-item"></p>');
				log_item.addClass('log-level-' + item.level);
				log_item.text(item.message);
				log_item.appendTo(log_container);
			});
		}

		if (show_loader && update_log_offset !== false) {
			// Show loader in the last log row
			log_container.find('>p.log-item:last-child')
				.append('<i class="sui-icon-loader sui-loading sui-md" aria-hidden="true"></i>');
		}

		// Autoscrolling only if scrollbar is already at the bottom
		if (log_allow_auto_scroll) {
			frame.animate({scrollTop: frame.prop('scrollHeight') - frame.prop('clientHeight')}, 500);
		}
	}

	function view_log_in_modal() {
		var backup_row = $(this).closest('table').find('.current-backup-row');
		var backup_id = backup_row.data('backupId');
		$(window).trigger('snapshot:open_log_modal', [backup_id]);
	}

	function goto_backups_settings() {
		$(window).trigger('snapshot:close_modal');
		toggle_navbar('settings');
		return false;
	}

	function restore_backup(e, backup_id) {
		e = e || false;
		if (e && e.preventDefault) e.preventDefault();

		var form = $('#form-snapshot-restore-backup');
		form.find('input[name=backup_id]').val(backup_id);
		form.find('input[name=restore_rootpath]').val(snapshot_default_restore_path.path);
		SUI.openModal('modal-snapshot-restore-backup', this);
	}

	function show_region_confirmation_modal() {
		call_if_can_delete_backup(show_region_confirmation_modal_force, function () {
			$('.snapshot-region-loading').show();
			$('.snapshot-region-radio').hide();
		}, function () {
			revert_storage_radio();
			$('.snapshot-region-loading').hide();
			$('.snapshot-region-radio').show();
		}).then(function () {
			revert_storage_radio();
		});
		return false;
	}

	function show_region_confirmation_modal_force() {
		SUI.openModal('modal-backups-region-change', this);

		// Remove and re-add listener to the cancel buttons.
		$('#modal-backups-region-change .cancel-region-change').off('click');
		$('#modal-backups-region-change .cancel-region-change').on('click', function() {
			// Lets cancel the change and close the modal.
			revert_storage_radio();
			$(window).trigger('snapshot:close_modal');
		});

		// Deal with the region change.
		$('#snapshot-backups-change-region').off('click');
		$('#snapshot-backups-change-region').on('click', function() {
			var data = {};
	
			data._wpnonce = $( '#_wpnonce-snapshot_change_region' ).val();
			data.no_backups = $( '.snapshot-backups-number .sui-summary-large' ).text() === '0' ? '1' : '0';
			data.new_region = $('input[name="snapshot-backup-region"]:checked').val();
	
			var url = ajaxurl + '?action=snapshot_change_region';

			$.ajax({
				type: 'POST',
				url: url,
				data: data,
				cache: false,
				dataType: 'json',
				beforeSend: function () {
					$('#modal-backups-region-change .sui-button').prop('disabled', true);
				},
				complete: function () {
					$('#modal-backups-region-change .sui-button').prop('disabled', false);
					$(window).trigger('snapshot:close_modal');
				},
				success: function (response) {
					if (response.success) {
						snapshot_list_backups();

						if ( true !== response.data.changed_schedule ) {
							// Show notice prompting to run backup or set schedule.
							var notice = $('<span></span>').html(snapshot_messages.change_region_no_schedule);
							var a = notice.find('a');
							a.eq(0).on('click', function (e) {
								notice.trigger('snapshot:close_notice');
								jQuery(window).trigger('snapshot:backup_modal');
							});
							a.eq(1).on('click', function (e) {
								notice.trigger('snapshot:close_notice');
								$('#snapshot-backup-schedule > a').trigger('click');
							});
					
							jQuery(window).trigger('snapshot:show_top_notice', ['info', notice]);
						} else {
							// Show notice informing that schedule is active already.
							var schedule = $('#snapshot-backup-schedule').data('modalData');
							var notice = $('<span></span>').html(snapshot_messages.change_region_with_schedule.replace('%s', schedule.frequency_human));
							jQuery(window).trigger('snapshot:show_top_notice', ['info', notice]);
						}
					} else {
						revert_storage_radio();

						var notice = $('<span></span>').html(snapshot_messages.change_region_failure);
						jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);
					}
				},
				error: function () {
					revert_storage_radio();

					var notice = $('<span></span>').html(snapshot_messages.change_region_failure);
					jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);
				}
			});

			return false;
		});

		return false;
	}

	function revert_storage_radio() {		
		var selected_value = $('input[name="snapshot-backup-region"]:checked').val();
		if (selected_value === 'US') {
			unselected_value = 'EU';
		} else {
			unselected_value = 'US';
		}
		$("input[name='snapshot-backup-region'][value='"+unselected_value+"']").prop('checked', true);
		$("input[name='snapshot-backup-region'][value='"+selected_value+"']").prop('checked', false);
	}

	function delete_backups_confirm() {
		call_if_can_delete_backup(delete_backups_confirm_force, function () {
			$('#snapshot-settings-delete-backups-confirm').addClass('sui-button-onload');
		}, function () {
			$('#snapshot-settings-delete-backups-confirm').removeClass('sui-button-onload');
		});
		return false;
	}

	function delete_backups_confirm_force() {
		SUI.openModal('modal-settings-delete-backups', this);
		return false;
	}

	function delete_backups() {
		var data = {};

		data._wpnonce = $( '#_wpnonce-snapshot_delete_all_backups' ).val();

		var url = ajaxurl + '?action=snapshot_delete_all_backups';

		$.ajax({
			type: 'POST',
			url: url,
			data: data,
			cache: false,
			dataType: 'json',
			beforeSend: function () {
				$('#modal-settings-delete-backups .sui-button').prop('disabled', true);
			},
			complete: function () {
				$('#modal-settings-delete-backups .sui-button').prop('disabled', false);
				$(window).trigger('snapshot:close_modal');
				$.when(snapshot_list_backups()).done(function () {
					snapshot_backup_progress();
				});
			},
			success: function (data) {
				if (data.success) {
					jQuery(window).trigger('snapshot:show_top_notice', ['success', snapshot_messages.delete_all_backups_success, 3000, false]);
				} else {
					jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.delete_all_backups_error]);
				}
			},
			error: function () {
				jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.delete_all_backups_error]);
			}
		});

		return false;
	}

	function backup_is_cancelled() {		
		$('.button-create-backup').prop('disabled', false);
		jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.cancel_backup_success, 3000, false]);

		var cancelModal = $( '#snapshot-modal-cancel-backup' ).parent();
		cancelModal.find('.sui-button').prop('disabled', false);
		$('#snapshot-modal-cancel-backup #snapshot-cancel-backup').removeClass('sui-button-onload-text');
		if ( cancelModal.hasClass( 'sui-active' ) ) {
			$(window).trigger('snapshot:close_modal');
		}
		snapshot_list_backups();
	}

	function recheck_requirements() {
		var data = {};
		data._wpnonce = $( '#_wpnonce-snapshot_recheck_requirements' ).val();

        $.ajax({
            type: 'POST',
            url: ajaxurl + '?action=snapshot-recheck_requirements',
            data: data,
            beforeSend: function () {
				$('#modal-snapshot-requirements-check-failure .snapshot-recheck-requirements').addClass('sui-button-onload-text', true);
            },
			complete: function () {
				$('#modal-snapshot-requirements-check-failure .snapshot-recheck-requirements').removeClass('sui-button-onload-text', true);
			},
            success: function (response) {
                if (response.success) {
					$('#snapshot-php-version').val(response.data.compat_php_version);
					if (response.data.compat_php_version >= 0) {
						$(window).trigger('snapshot:close_modal');
						SUI.openModal('modal-snapshot-requirements-check-success', 'button-create-backup');
					}
                }
            }
        });
	}

	function populate_snapshot_region() {
		var data = {};
		data._wpnonce = $( '#_wpnonce-populate_snapshot_region' ).val();

        $.ajax({
            type: 'POST',
            url: ajaxurl + '?action=snapshot-check_if_region',
            data: data,
			complete: function () {
				$('.snapshot-region-loading').hide();
				$('.snapshot-region-radio').show();
			},
            success: function (response) {
                if (response.success) {
					if (response.data.region === 'US') {
						$("#backup-region-us").prop("checked", true);
						$("#backup-region-eu").prop("checked", false);
					}
					if (response.data.region === 'EU') {
						$("#backup-region-eu").prop("checked", true);
						$("#backup-region-us").prop("checked", false);
					}
                }
            }
        });
	}

	function requirements_passed() {
		$(window).trigger('snapshot:close_modal');
		jQuery(window).trigger('snapshot:backup_modal');
	}

	function toggle_notification_recipients() {
		var checkbox = $(this);
		var content = checkbox.closest('.sui-form-field').find('.sui-toggle-content');
		checkbox.prop('checked') ? content.show() : content.hide();
	}

	function handle_notifications_settings(e) {
		if (e && e.preventDefault) e.preventDefault();

		var form = $(this);
		var data = {};
		form.serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});
		data.action = 'save_snapshot_settings';
		var recipients = [];
		form.find('#snapshot-notification-recipients .sui-recipients .sui-recipient').each(function () {
			var recipient = $(this);
			recipients.push({
				name: recipient.find('.sui-recipient-name').text(),
				email: recipient.find('.sui-recipient-email').text()
			});
		});
		data.email_settings = {
			on_fail_send: form.find('#snapshot-notifications-send-email').prop('checked'),
			on_fail_recipients: recipients,
		};
		data.email_settings = JSON.stringify(data.email_settings);

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: data,
			beforeSend: function () {
				form.find('.sui-button').addClass('sui-button-onload-text').prop('disabled', true);
			},
			complete: function () {
				form.find('.sui-button').removeClass('sui-button-onload-text').prop('disabled', false);
			},
			success: function (response) {
				if ( response.success ) {
					var notice = form.find('.sui-notice.email-notification-notice');
					notice.find('.sui-notice-message p').text(response.data.notice_text);

					form.find('.sui-recipients .sui-recipient').remove();
					response.data.email_settings.on_fail_recipients.forEach(function (item) {
						add_recipient_html(item.name, item.email);
					});

					if (response.data.notice_type === 'success') {
						notice.addClass('sui-notice-success');
					} else {
						notice.removeClass('sui-notice-success');
						var checkbox = $('#snapshot-notifications-send-email');
						checkbox.prop('checked', response.data.email_settings.on_fail_send);
						var content = checkbox.closest('.sui-form-field').find('.sui-toggle-content');
						response.data.email_settings.on_fail_send ? content.show() : content.hide();
						form.find('.email-notification-notice-empty').hide();
					}
					jQuery(window).trigger('snapshot:show_top_notice', ['success', response.data.top_notice_text, 3000, false]);
				}
			}
		});
	}

	function remove_recipient(e) {
		if (e && e.preventDefault) e.preventDefault();
		var recipient = $(this).closest('.sui-recipient');
		var recipients = recipient.closest('.sui-recipients');
		var notice = recipients.find('.email-notification-notice-empty');
		recipient.remove();
		if (!recipients.find('.sui-recipient').length) {
			notice.show();
		}
	}

	function add_recipient_modal_hide_errors() {
		$('#modal-notification-add-recipient-input-email-error').hide()
			.closest('.sui-form-field').removeClass('sui-form-field-error');
		$('#modal-notification-add-recipient-input-email-duplicate-error').hide();
	}

	function add_recipient(e) {
		if (e && e.preventDefault) e.preventDefault();
		$('#modal-notification-add-recipient input:not([type=hidden])').val('');

		add_recipient_modal_hide_errors();

		SUI.openModal('modal-notification-add-recipient', this, 'modal-notification-add-recipient-input-name');
	}

	function add_recipient_html(name, email) {
		name = name.trim();
		email = email.trim();

		var recipients = $('.snapshot-notifications #snapshot-notification-recipients .sui-recipients');

		var recipient = $('<div class="sui-recipient"></div>');
		$('<div class="sui-recipient-name"></div>').text(name).appendTo(recipient);
		$('<div class="sui-recipient-email"></div>').text(email).appendTo(recipient);
		$('<button type="button" class="sui-button-icon snapshot-remove-recipient"><i class="sui-icon-trash" aria-hidden="true"></i></button>').appendTo(recipient);
		recipient.appendTo(recipients);
		recipients.find('.email-notification-notice-empty').hide();

		return name !== '' ? name : email;
	}

	function handle_add_recipient_form(e) {
		if (e && e.preventDefault) e.preventDefault();

		var error_element = $('#modal-notification-add-recipient-input-email-error');
		var error_element2 = $('#modal-notification-add-recipient-input-email-duplicate-error');

		var form = $(this);
		var data = {};
		form.serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});

		var recipients = $('.snapshot-notifications #snapshot-notification-recipients .sui-recipients');
		error_element2.hide();
		var has_error = false;
		recipients.find('.sui-recipient').each(function () {
			var recipient = $(this);
			if (recipient.find('.sui-recipient-email').text() === data.email.trim()) {
				error_element2.show();
				error_element2.closest('.sui-form-field').addClass('sui-form-field-error');
				has_error = true;
				return false;
			}
		});
		if (has_error) {
			return;
		}

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'snapshot-json_validate_email',
				_wpnonce: data._wpnonce_snapshot_validate_email,
				email: data.email
			},
			beforeSend: function () {
				form.find('.sui-button').addClass('sui-button-onload-text').prop('disabled', true);
			},
			complete: function () {
				form.find('.sui-button').removeClass('sui-button-onload-text').prop('disabled', false);
			},
			success: function (response) {
				if ( response.success && response.data.is_valid ) {
					var added = add_recipient_html(data.name, data.email);
					SUI.closeModal();
					add_recipient_modal_hide_errors();
					if (added !== false) {
						jQuery(window).trigger('snapshot:show_top_notice', [
							'info',
							snapshot_messages.notifications_user_added.replace('%s', added)
						]);
					}
				} else {
					error_element.show();
					error_element.closest('.sui-form-field').addClass('sui-form-field-error');
				}
			}
		});
	}

	function history_replace_state() {
		if (window.history && window.history.replaceState) {
			window.history.replaceState('', document.title, window.location.pathname + window.location.search);
		}
	}

	/**
	 * Check if current user can delete backups
	 */
	function check_can_delete_backup() {
		var deferred = $.Deferred();

		var request_data = {
			action: 'snapshot-check_can_delete_backup',
			_wpnonce: $('#_wpnonce-check_can_delete_backup').val()
		};

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			cache: false,
			success: function (response) {
				if (response.success && response.data.can_delete_backup === true) {
					deferred.resolve();
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

	/**
	 * Show Confirm WPMU DEV password modal
	 * @param {function} callback
	 */
	function confirm_wpmudev_password(callback) {
		var deferred = $.Deferred();
		$('#snapshot-confirm-wpmudev-password-modal').data('on_success', [deferred, callback]);
		$('#error-snapshot-wpmudev-password').hide();
		$('#error-snapshot-wpmudev-password').closest('.sui-form-field').removeClass('sui-form-field-error');
		$('#snapshot-wpmudev-password').val('');
		SUI.openModal('snapshot-confirm-wpmudev-password-modal', this);
		return deferred.promise();
	}

	/**
	 * Check WPMU DEV password
	 * @param {string} wpmudev_password
	 */
	function validate_wpmudev_password(wpmudev_password) {
		var deferred = $.Deferred();

		var request_data = {
			action: 'snapshot-check_wpmudev_password',
			_wpnonce: $('#_wpnonce-check_wpmudev_password').val(),
			wpmudev_password: wpmudev_password
		};

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			cache: false,
			success: function (response) {
				if (response.success && response.data.password_is_valid === true) {
					deferred.resolve();
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

	/**
	 * Confirm WPMU DEV password modal: form submit handler
	 */
	function on_submit_wpmudev_password() {
		var form = $('#snapshot-confirm-wpmudev-password-modal-form');
		var password = form.find('[name=wpmudev_password]').val();

		var on_callback = $('#snapshot-confirm-wpmudev-password-modal').data('on_success');
		var deferred = on_callback[0];
		var callback = on_callback[1];

		var button = form.find('.submit-button');
		button.addClass('sui-button-onload');
		validate_wpmudev_password(password).then(function () {
			SUI.closeModal();
			if (typeof callback === 'function') {
				var result = callback();
				deferred.resolve(result);
			}
			$('#error-snapshot-wpmudev-password').hide();
			$('#error-snapshot-wpmudev-password').closest('.sui-form-field').removeClass('sui-form-field-error');
		}, function () {
			//deferred.reject();
			$('#error-snapshot-wpmudev-password').show();
			$('#error-snapshot-wpmudev-password').closest('.sui-form-field').addClass('sui-form-field-error');
			$('#snapshot-wpmudev-password').focus();
		}).always(function () {
			button.removeClass('sui-button-onload');
		});

		return false;
	}

	/**
	 * Run callback function and ask for WPMU DEV password if needed
	 * @param {function} callback
	 */
	function call_if_can_delete_backup(callback, before, after) {
		var deferred = $.Deferred();

		if (typeof before === 'function') {
			before();
		}

		check_can_delete_backup().then(function () {
			if (typeof after === 'function') {
				after();
			}
			var result = callback();
			deferred.resolve(result);
		}, function () {
			if (typeof after === 'function') {
				after();
			}
			confirm_wpmudev_password(callback).then(function (data) {
				deferred.resolve(data);
			}, function () {
				deferred.reject();
			});
		});

		return deferred.promise();
	}

	$(window).on('load', function (e) {
		var matches;
		if ('#set-schedule' === window.location.hash) {
			snapshot_get_schedule(true, $('#snapshot-backup-schedule').data('modalData'));
			history_replace_state();
		} else if ('#logs' === window.location.hash) {
			// Open backup logs tab from URL.
			toggle_navbar('logs');
			history_replace_state();
		} else if (matches = window.location.hash.match(/^#logs\-(.+)/)) {
			// Open backup log from URL.
			view_log(e, true, matches[1]);
			history_replace_state();
		}
	});

    $(function () {
        if ( $( '.snapshot-page-backups' ).length ) {
			for (var current_tab in navbar_tabs) {
				var vertical_selector = navbar_tabs[current_tab][1];
				var mobile_nav_selector = navbar_tabs[current_tab][2];
				$('.snapshot-page-main').find(vertical_selector)
					.on('click', toggle_navbar.bind(this, current_tab));
				$('.snapshot-page-main .sui-mobile-nav').find(mobile_nav_selector)
					.on('click', toggle_navbar.bind(this, current_tab));
			}

			$('#snapshot-backup-schedule > a').on('click', function () {
				snapshot_get_schedule(true, $(this).parent().data('modalData'));
				return false;
            });

			$(window).on('snapshot:get-schedule', function () {
				snapshot_get_schedule(false);
            });

            $('#form-snapshot-create-manual-backup').on('submit', handle_create_manual_backup);

			$('.snapshot-list-backups').on('click', '.open-edit-schedule', function () {
				var data = $(this).data('modal-data');
				$(window).trigger('snapshot:schedule', [data.schedule_action, data.values, data.schedule_action]);
			});

            $(window).on('snapshot:delete_backup', delete_backup);
            $('#snapshot-delete-backup-button').on('click', delete_backup_confirmed);
            $(window).on('snapshot:export_backup', export_backup);
            $(window).on('snapshot:restore_backup', restore_backup);

            var Promise = $.when(snapshot_list_backups());
            Promise.done(function () {
                snapshot_backup_progress('1');

                var matches;
                if (matches = window.location.hash.match(/^#backups\-(.+)/)) {
                    // Open backup from URL
                    setTimeout(function () {
                        view_backup(matches[1]);
                    }, 100);
                    history_replace_state();
                }
            });

            $('#button-reload-backups').on('click', function () {
                $.when(snapshot_list_backups()).done(function () {
                    snapshot_backup_progress();
                });
            });

            $('.snapshot-list-backups').on('click', '.button-cancel-backup', cancel_backup_confirm);
            $('#snapshot-cancel-backup').on('click', cancel_backup);
            $('.snapshot-list-backups').on('click', '.button-view-log', view_log_in_modal);

            $('.logs-list').on('change', 'select.log-filter', filter_log);
            $('.logs-list').on('click', '.view-backup', function () {
                view_backup($(this).data('backupId'));
            });
            $('.snapshot-list-backups').on('click', '.view-log', function (e) {
				view_log.bind(this, e, true, $(this).data('backup-id') )();
			});
            $('.logs-list .log-row').on('click', on_log_row_click);

            $(window).on('snapshot:open_log_modal', open_log_modal);
            $(window).on('snapshot:view_log', view_log);

            $('#snapshot-button-backups-settings').on('click', goto_backups_settings);

            // For restoration modal log
            $(window).on('snapshot:update_log_ajax', function (event, backup_id, offset, clear, show_loader) {
                update_log_ajax(backup_id, offset, clear, show_loader);
            });
            $(window).on('snapshot:update_log', function (event, log, clear, show_loader) {
                update_log(log, clear, show_loader);
            });
            $(window).on('snapshot:update_log_offset', function (event, value) {
                update_log_offset = value;
            });
            $(window).on('snapshot:get_log_offset', function (event, callback) {
                callback(update_log_offset);
			});

			// Show confirmation modal, when user changes the region in the settings.
			$('#wps-settings input[name=snapshot-backup-region]').on('change', show_region_confirmation_modal);

			$('#snapshot-region-notice').show();

            $('#snapshot-settings-delete-backups-confirm').on('click', delete_backups_confirm);
			$('#snapshot-settings-delete-backups').on('click', delete_backups);

			// Recheck Snapshot requirements (PHP version, etc.) when user clicks the recheck button.
			$('#modal-snapshot-requirements-check-failure .snapshot-recheck-requirements').on('click', recheck_requirements);

			// Continue with the Create Backup modal, since we're covering all requirements.
			$('#modal-snapshot-requirements-check-success .snapshot-checked-requirements').on('click', requirements_passed);

			$('.snapshot-notifications input[aria-controls=snapshot-notification-recipients]').on('change', toggle_notification_recipients);
			$('.snapshot-notifications #wps-notifications').on('submit', handle_notifications_settings);
			$('.snapshot-notifications').on('click', '.snapshot-remove-recipient', remove_recipient);
			$('.snapshot-notifications .snapshot-add-recipient').on('click', add_recipient);
			$('#modal-notification-add-recipient-form').on('submit', handle_add_recipient_form);

			$('#snapshot-default-exclusions').change(function() {
				if(this.checked) {
					$('.snapshot-exclusions-settings-box .sui-accordion-item-body .sui-box-body').removeClass('snapshot-disabled-exclusions');
				} else {
					$('.snapshot-exclusions-settings-box .sui-accordion-item-body .sui-box-body').addClass('snapshot-disabled-exclusions');					
				}

			});

			populate_snapshot_region();

			$('#snapshot-confirm-wpmudev-password-modal-form').on('submit', on_submit_wpmudev_password);
        }
    });
})(jQuery);
