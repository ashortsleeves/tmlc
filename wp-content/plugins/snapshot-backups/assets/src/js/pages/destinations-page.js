/**
 * Page: Destinations.
 */
;(function($) {
	var snapshot_s3_final_form = $('#snapshot-save-s3'),
		snapshot_s3_middle_form = $('#snapshot-add-s3-info'),
		snapshot_gd_final_form = $('#snapshot-save-gd'),
		snapshot_gd_middle_form = $('#snapshot-add-gd-info');

	var destination_backup_count = {};
	var edit_destination_id = null;
	var delete_destination_id = null;
	var snapshot_stored_schedule = {};

	function load_schedule() {
		var url = ajaxurl + '?action=snapshot-get_schedule';
		var request_data = {
			_wpnonce: $('#_wpnonce-get-schedule').val()
		};

		$.ajax({
			type: 'GET',
			url: url,
			data: request_data,
			cache: false,
			dataType: 'json',
			beforeSend: function () {
				snapshot_stored_schedule = {};
				$('.snapshot-loading-schedule').show();
			},
			success: function (response) {
				if (response.success) {
					snapshot_stored_schedule = response.data;
					update_destination_rows_schedule();
				} else {
					on_load_schedule_error();
				}
			},
			complete: function () {
				$('.snapshot-loading-schedule').hide();
			},
			error: function () {
				on_load_schedule_error();
			}
		});
	}

	function on_load_schedule_error() {
		$('.destination-schedule-text').text('-');
	}

	function update_destination_rows_schedule() {
		$('.destination-schedule-text').text(snapshot_stored_schedule.text);
		$('.snapshot-loading-schedule').hide();
	}

	function load_backup_count() {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'snapshot-list_backups',
				_wpnonce: $( '#_wpnonce-list-backups' ).val(),
			},
			cache: false,
            dataType: 'json',
			beforeSend: function () {
				$('.snapshot-loading').show();
			},
			complete: function () {
				$('.snapshot-loading').hide();
			},
			success: function (data) {
				if (!data.success) {
					return;
				}
				$('.snapshot-page-destinations .wpmudev-backup-count').text(data.data.backup_count);
				destination_backup_count = data.data.destination_backup_count || {};
				update_destination_backup_counts();

				var last_destination = $('.sui-summary-segment .snapshot-last-destination');
				var backups = data.data.backups;
				if (backups.length) {
					var row = $(backups[0].row);
					var destination_span = $('<span></span>');
					destination_span.text(row.data('destination_text'));
					var destination_tooltip = row.data('destination_tooltip');
					if (destination_tooltip) {
						destination_span.addClass('sui-tooltip sui-tooltip-left-mobile sui-tooltip-constrained');
						destination_span.css('--tooltip-width', '170px');
						destination_span.attr('data-tooltip', destination_tooltip);
					}
					last_destination.empty().append(destination_span);
				} else {
					last_destination.text(snapshot_messages.no_destinations);
				}
			}
		});
	}

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

	/**
	 * Handles AJAX for testing S3 connection.
	 */
	function destination_test_s3_connection() {
		// Hide any older error notices about data validation.
		hide_errors_test_connection();

		// Prepare the data to be sent for testing the connection.
		var data = {};
		var abort_test_connection = false;
		$(this).serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});
		data._wpnonce = $( '#_wpnonce-snapshot_s3_connection' ).val();

		// Check if we're testing AWS or S3 compatible, so we can show the appropriate errors.
		var destination_type;
		if (check_if_on_aws()) {
			destination_type = 's3';
		} else {
			destination_type = 's3-compatible';
			// Check if provider has been selected, if not show error notice.
			if( ! $('#s3-compatible-connection-provider').find(':selected').val() ) {
				$('#error-s3-compatible-connection-provider').show();
				$('#error-s3-compatible-connection-provider').html(snapshot_messages.required_provider);
				$('#error-s3-compatible-connection-provider').closest('.sui-form-field').find('.select2-selection.select2-selection--single').addClass('snapshot-error-select');
				abort_test_connection = true;
			}
		}

		if(!data['tpd_secretkey'].trim()) {
			$('#error-'+destination_type+'-connection-secret-access-key').show();
			$('#error-'+destination_type+'-connection-secret-access-key').html(snapshot_messages.required_s3_cred.replace('%s', $('.'+destination_type+'-connection-secret-access-key-label').text() ));
			$('#error-'+destination_type+'-connection-secret-access-key').closest('.sui-form-field').addClass('sui-form-field-error');
			$('#'+destination_type+'-connection-secret-access-key').trigger('focus');

			abort_test_connection = true;
		}

		if(!data['tpd_accesskey'].trim()) {
			$('#error-'+destination_type+'-connection-access-key-id').show();
			$('#error-'+destination_type+'-connection-access-key-id').html(snapshot_messages.required_s3_cred.replace('%s', $('.'+destination_type+'-connection-access-key-id-label').text() ));
			$('#error-'+destination_type+'-connection-access-key-id').closest('.sui-form-field').addClass('sui-form-field-error');
			$('#'+destination_type+'-connection-access-key-id').trigger('focus');

			abort_test_connection = true;
		}

		if(!data['tpd_region'].trim()) {
			$('#error-'+destination_type+'-connection-region').show();
			$('#error-'+destination_type+'-connection-region').html(snapshot_messages.required_s3_cred.replace('%s', $('.'+destination_type+'-connection-region-label').text() ));

			if (destination_type==='s3') {
				$('#error-'+destination_type+'-connection-region').closest('.sui-form-field').find('.select2-selection.select2-selection--single').addClass('snapshot-error-select');
			} else {
				$('#error-'+destination_type+'-connection-region').closest('.sui-form-field').addClass('sui-form-field-error');
			}

			abort_test_connection = true;
		}

		if (abort_test_connection) {
			return false;
		}

		// Actually test the connection.
        $.ajax({
            type: 'POST',
            url: ajaxurl + '?action=snapshot-s3_connection',
            data: data,
            beforeSend: function () {
				$('#snapshot-submit-s3-connection-test').removeClass('sui-button-icon-right', true);
				$('#snapshot-submit-s3-connection-test').addClass('sui-button-onload-text', true);

				$('#snapshot-wrong-s3-creds').hide();
				$('#snapshot-wrong-s3-compatible-creds').hide();
				$('#snapshot-region-no-buckets').hide();
				$('#snapshot-region-no-buckets-s3-compatible').hide();
            },
			complete: function () {
				$('#snapshot-submit-s3-connection-test').addClass('sui-button-icon-right', true);
				$('#snapshot-submit-s3-connection-test').removeClass('sui-button-onload-text', true);
			},
            success: function (response) {
                if (response.success) {
					var selectedProvider = $('#s3-compatible-connection-provider').find(':selected').val();
					if (selectedProvider) {
						selectedProviderLink = snapshot_s3_providers[selectedProvider].link,
						selectedProviderName = snapshot_s3_providers[selectedProvider].providerName;
					}

					if (response.data.api_response.length > 0) {
						var buckets_dropdown = $("#s3-details-bucket");
						buckets_dropdown.empty();
						buckets_dropdown.append($("<option />"));
						$.each(response.data.api_response, function(val, text) {
							buckets_dropdown.append($("<option />").val(text).text(text));
						});

						buckets_dropdown.SUIselect2( {
							placeholder: snapshot_messages.choose_bucket,
							dropdownCssClass: 'sui-select-dropdown',
							dropdownParent: snapshot_s3_middle_form
						} );

						// Since the connection is good, pass the data to the final step.
						snapshot_s3_final_form.find("[name='tpd_accesskey']").val(data['tpd_accesskey']);
						snapshot_s3_final_form.find("[name='tpd_secretkey']").val(data['tpd_secretkey']);
						snapshot_s3_final_form.find("[name='tpd_region']").val(data['tpd_region']);
						snapshot_s3_final_form.find("[name='tpd_type']").val(data['tpd_type']);

						//Also pass the data to the intermediate step.
						snapshot_s3_middle_form.find("[name='tpd_accesskey']").val(data['tpd_accesskey']);
						snapshot_s3_middle_form.find("[name='tpd_secretkey']").val(data['tpd_secretkey']);
						snapshot_s3_middle_form.find("[name='tpd_region']").val(data['tpd_region']);
						snapshot_s3_middle_form.find("[name='tpd_type']").val(data['tpd_type']);

						// Also, hide any previous results in the intermediate step.
						$('#snapshot-wrong-s3-details').hide();
						$('#snapshot-duplicate-s3-details, #snapshot-duplicate-s3-bucket-details').hide();
						$('#snapshot-correct-s3-details').hide();

						$('#s3-save-name').val(check_if_on_aws() ? 'S3/Amazon' : 'S3/'+selectedProviderName);

						SUI.slideModal( 'snapshot-add-destination-dialog-slide-3-s3' );

					} else {
						if (check_if_on_aws()) {
							$('#snapshot-region-no-buckets .snapshot-selected-region-no-buckets').html($('#s3-connection-region').find(':selected').text());
							$('#snapshot-region-no-buckets').show();
						} else {
							$('#snapshot-region-no-buckets-s3-compatible .snapshot-selected-region-no-buckets').html($('#s3-compatible-connection-region').val());
							$('#snapshot-region-no-buckets-s3-compatible .snapshot-s3-compatible-login-link').attr('href', selectedProviderLink);
							$('#snapshot-region-no-buckets-s3-compatible').show();
						}
					}
                } else {
                    $('#snapshot-wrong-'+destination_type+'-creds').show();
                }
            },
            error: function () {
                $('#snapshot-wrong-'+destination_type+'-creds').show();
            }
        });
        return false;
	}


	/**
	 * Handles AJAX for testing Google Drive connection.
	 */
	function token_generation_gd_connection() {
		// Prepare the data to be sent for testing the connection.
		var data = {};
		$(this).serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});
		data._wpnonce = $( '#_wpnonce-snapshot_gd_connection' ).val();

		// Actually test the connection.
        $.ajax({
            type: 'POST',
            url: ajaxurl + '?action=snapshot-gd_connection',
            data: data,
            beforeSend: function () {
				$('#snapshot-submit-gd-generate-tokens').removeClass('sui-button-icon-right', true);
				$('#snapshot-submit-gd-generate-tokens').addClass('sui-button-onload-text', true);
				$('#snapshot-wrong-gd-creds').hide();
            },
			complete: function () {
				$('#snapshot-submit-gd-generate-tokens').addClass('sui-button-icon-right', true);
				$('#snapshot-submit-gd-generate-tokens').removeClass('sui-button-onload-text', true);
			},
            success: function (response) {
                if (response.success) {
					var api_response = response.data.api_response;
					// Since the connection is good, pass the data to the final step.
					snapshot_gd_final_form.find("[name='tpd_acctoken_gdrive']").val(api_response.access_token);
					snapshot_gd_final_form.find("[name='tpd_retoken_gdrive']").val(api_response.refresh_token);
					snapshot_gd_final_form.find("[name='tpd_email_gdrive']").val(api_response.email);

					//Also pass the data to the intermediate step.
					snapshot_gd_middle_form.find("[name='tpd_acctoken_gdrive']").val(api_response.access_token);
					snapshot_gd_middle_form.find("[name='tpd_retoken_gdrive']").val(api_response.refresh_token);
					snapshot_gd_middle_form.find("[name='tpd_email_gdrive']").val(api_response.email);
					$('.snapshot-configured-gd-account-email').html(api_response.email);

					// Also, hide any previous results in the intermediate step.
					$('#snapshot-wrong-gd-details').hide();
					$('#snapshot-duplicate-gd-details').hide();
					$('#snapshot-correct-gd-details').hide();

					// Also revert current screen to initial form, in case user gets back to it, so they can start over the auth flow.
					clean_gdrive_flow();

					$('#gd-save-name').val('Google Drive');

					SUI.slideModal( 'snapshot-add-destination-dialog-slide-3-gd' );
                } else {
					$('#snapshot-wrong-gd-creds').show();
					$('#snapshot-correct-gd-creds').hide();
					$('#snapshot-gd-authorization').show();
					$('#snapshot-submit-gd-generate-tokens').hide();
                }
            },
            error: function () {
                $('#snapshot-wrong-gd-creds').show();
				$('#snapshot-correct-gd-creds').hide();
				$('#snapshot-gd-authorization').show();
				$('#snapshot-submit-gd-generate-tokens').hide();
            }
        });
        return false;
	}

	/**
	 * Handles AJAX for using S3 connection details.
	 *
	 * @param {Object} e Event object
	 */
	function destination_intermediate_s3_step(e) {
		if (e && e.preventDefault) e.preventDefault();
		// Hide any older error notices about data validation.
		hide_errors_pass_details('s3');

		// Prepare the data.
		var data = {};
		var abort_details_connection = false;
		$(this).serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});
		data._wpnonce = $( '#_wpnonce-snapshot_s3_connection' ).val();

		if(!data['tpd_limit'].trim() || data['tpd_limit'] < 1) {
			$('#error-s3-details-limit').show();
			$('#error-s3-details-limit').html(snapshot_messages.require_limit);
			$('#error-s3-details-limit').closest('.sui-form-field').addClass('sui-form-field-error');
			$('#s3-details-limit').trigger('focus');

			abort_details_connection = true;
		}

		if(!data['tpd_bucket'].trim()) {
			$('#error-s3-details-bucket').show();
			$('#error-s3-details-bucket').html(snapshot_messages.require_bucket);
			$('#error-s3-details-bucket').closest('.sui-form-field').find('.select2-selection.select2-selection--single').addClass('snapshot-error-select');

			abort_details_connection = true;
		}

		if(data['tpd_directory'].trim() && data['tpd_directory'].charAt(0) !== "/") {
			$('#error-s3-details-directory').show();
			$('#error-s3-details-directory').html(snapshot_messages.require_valid_path);
			$('#error-s3-details-directory').closest('.sui-form-field').addClass('sui-form-field-error');
			$('#s3-details-directory').trigger('focus');

			abort_details_connection = true;
		}

		if (abort_details_connection) {
			return false;
		}

		// Concatenate bucket and directory path, to produce the final directory path to be used.
		var path = (data['tpd_directory'].trim()) ? data['tpd_bucket'] + data['tpd_directory'] : data['tpd_bucket'];

		if (data['tpd_action'] === 'move_to_next_screen') {
			// Actually pass the data to the next step.
			snapshot_s3_final_form.find("[name='tpd_path']").val(path);
			snapshot_s3_final_form.find("[name='tpd_limit']").val(data['tpd_limit']);

			// Hide any results from testing the connection.
			$('#snapshot-wrong-s3-details').hide();
			$('#snapshot-duplicate-s3-details, #snapshot-duplicate-s3-bucket-details').hide();
			$('#snapshot-correct-s3-details').hide();

			SUI.slideModal( 'snapshot-add-destination-dialog-slide-4-s3' );
		} else {
			// Don't forget to add the final path too.
			data['tpd_path'] = path;
			// We'll add a name too, as it's required for the endpoint. Since it isn't actually stored, it can be anything.
			data['tpd_name'] = 'test_name';

			// Just test the connection.
			$.ajax({
				type: 'POST',
				url: ajaxurl + '?action=snapshot-s3_connection',
				data: data,
				beforeSend: function () {
					$('#snapshot-test-s3-connection-path').addClass('sui-button-onload-text', true);

					$('#snapshot-wrong-s3-details').hide();
					$('#snapshot-duplicate-s3-details, #snapshot-duplicate-s3-bucket-details').hide();
					$('#snapshot-correct-s3-details').hide();
				},
				complete: function () {
					$('#snapshot-test-s3-connection-path').removeClass('sui-button-onload-text', true);
				},
				success: function (response) {
					if (response.success && response.data.api_response.length > 0) {
						$('#snapshot-correct-s3-details').show();
					} else if (is_duplicate_destination_error(response)) {
						if (data.tpd_path.split('/').length === 1) {
							$('#snapshot-duplicate-s3-bucket-details').show();
						} else {
							$('#snapshot-duplicate-s3-details').show();
						}
					} else {
						$('#snapshot-wrong-s3-details').show();
					}
				},
				error: function () {
					$('#snapshot-wrong-s3-details').show();
				}
			});
		}

		return false;
	}

	/**
	 * Handles AJAX for using Google Drive connection details.
	 *
	 * @param {Object} e Event object
	 */
	function destination_intermediate_gd_step(e) {
		if (e && e.preventDefault) e.preventDefault();
		// Hide any older error notices about data validation.
		hide_errors_pass_details('gd');

		// Prepare the data.
		var data = {};
		var abort_details_connection = false;
		$(this).serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});
		data._wpnonce = $( '#_wpnonce-snapshot_gd_connection' ).val();

		if(!data['tpd_limit'].trim() || data['tpd_limit'] < 1) {
			$('#error-gd-details-limit').show();
			$('#error-gd-details-limit').html(snapshot_messages.require_limit);
			$('#error-gd-details-limit').closest('.sui-form-field').addClass('sui-form-field-error');
			$('#gd-details-limit').focus();

			abort_details_connection = true;
		}

		if(!data['tpd_path'].trim()) {
			$('#error-gd-details-directory').show();
			$('#error-gd-details-directory').html(snapshot_messages.require_directory_id);
			$('#error-gd-details-directory').closest('.sui-form-field').addClass('sui-form-field-error');
			$('#gd-details-directory').focus();

			abort_details_connection = true;
		}

		if (abort_details_connection) {
			return false;
		}

		if (data['tpd_action'] === 'move_to_next_screen') {
			// Actually pass the data to the next step.
			snapshot_gd_final_form.find("[name='tpd_path']").val(data['tpd_path']);
			snapshot_gd_final_form.find("[name='tpd_limit']").val(data['tpd_limit']);
			snapshot_gd_final_form.find("[name='tpd_path']").val(data['tpd_path']);

			// Hide any results from testing the connection.
			$('#snapshot-wrong-gd-details').hide();
			$('#snapshot-duplicate-gd-details').hide();
			$('#snapshot-correct-gd-details').hide();

			SUI.slideModal( 'snapshot-add-destination-dialog-slide-4-gd' );
		} else {
			// We'll add a name too, as it's required for the endpoint. Since it isn't actually stored, it can be anything.
			data['tpd_name'] = 'test_name';

			// Just test the connection.
			$.ajax({
				type: 'POST',
				url: ajaxurl + '?action=snapshot-gd_connection',
				data: data,
				beforeSend: function () {
					$('#snapshot-test-gd-connection-path').addClass('sui-button-onload-text', true);

					$('#snapshot-wrong-gd-details').hide();
					$('#snapshot-duplicate-gd-details').hide();
					$('#snapshot-correct-gd-details').hide();
				},
				complete: function () {
					$('#snapshot-test-gd-connection-path').removeClass('sui-button-onload-text', true);
				},
				success: function (response) {
					if (response.success) {
						$('#snapshot-correct-gd-details').show();
					} else if (is_duplicate_destination_error(response)) {
						$('#snapshot-duplicate-gd-details').show();
					} else {
						$('#snapshot-wrong-gd-details').show();
					}
				},
				error: function () {
					$('#snapshot-wrong-gd-details').show();
				}
			});
		}

		return false;
	}

	/**
	 * Handles AJAX for saving connection.
	 *
	 * @param {Object} e Event object
	 * @param {Object} form Form used
	 * @param {String} type Storage type
	 */
	function destination_save( e, form, type ) {
		if (e && e.preventDefault) e.preventDefault();

		// Hide any older error notices about data validation.
		hide_errors_save();

		// Prepare the data to be sent for saving the connection.
		var data = {};
		$(form).serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});
		data._wpnonce = $( '#_wpnonce-snapshot_' + type + '_connection' ).val();

		if(!data['tpd_name'].trim()) {
			$('#error-' + type + '-save-name').show();
			$('#error-' + type + '-save-name').html(snapshot_messages.require_name);
			$('#error-' + type + '-save-name').closest('.sui-form-field').addClass('sui-form-field-error');
			$('#' + type + '-save-name').trigger('focus');

			return false;
		}

		if (data.tpd_path !== undefined) {
			data.tpd_path = data.tpd_path.trim().replace(/\/+$/, '');	// unslash
		}

		// Actually save the connection.
        $.ajax({
            type: 'POST',
            url: ajaxurl + '?action=snapshot-' + type + '_connection',
            data: data,
            beforeSend: function () {
				$('#snapshot-submit-save-' + type).addClass('sui-button-onload-text', true);
				$('#snapshot-' + type + '-save-failure').hide();
				$('[id^=snapshot-duplicate-][id$=-save-failure]').hide();    // #snapshot-duplicate-*-save-failure
            },
			complete: function () {
				$('#snapshot-submit-save-' + type).removeClass('sui-button-onload-text', true);
			},
            success: function (response) {
                if (response.success && response.data.api_response.tpd_name) {
					if (snapshot_stored_schedule.schedule_is_active) {
						var notice = $('<span></span>').html(snapshot_messages.destination_saved_schedule.replace('%1$s', response.data.api_response.tpd_name).replace('%2$s', snapshot_stored_schedule.text).replace('%3$s', snapshot_urls.backups + '#set-schedule'));
					} else {
						var notice = $('<span></span>').html(snapshot_messages.destination_saved_no_schedule.replace('%1$s', response.data.api_response.tpd_name).replace('%2$s', snapshot_urls.backups + '#set-schedule').replace('%3$s', snapshot_urls.backups + '#create-backup'));
					}

					jQuery(window).trigger('snapshot:show_top_notice', ['success', notice]);
					$(window).trigger('snapshot:close_modal');
					load_destinations();
                } else if (is_duplicate_destination_error(response)) {
                    if (type === 's3' && data.tpd_path.split('/').length === 1) {
                        $('#snapshot-duplicate-s3-bucket-save-failure').show();
                    } else {
                        $('#snapshot-duplicate-' + type + '-save-failure').show();
                    }
                } else {
                    $('#snapshot-' + type + '-save-failure').show();
                }
            },
            error: function () {
				$('#snapshot-' + type + '-save-failure').show();
            }
        });
        return false;
	}

	function hide_errors_test_connection() {
		$('[id^="error-s3-connection-"]').hide();
		$('[id^="error-s3-connection-"]').html("");
		$('[id^="error-s3-compatible-connection-"]').hide();
		$('[id^="error-s3-compatible-connection-"]').html("");

		$('#error-s3-connection-secret-access-key').closest('.sui-form-field').removeClass('sui-form-field-error');
		$('#error-s3-connection-access-key-id').closest('.sui-form-field').removeClass('sui-form-field-error');
		$('#error-s3-connection-region').closest('.sui-form-field').find('.select2-selection.select2-selection--single').removeClass('snapshot-error-select');
		$('[id^="error-s3-compatible-connection-"]').closest('.sui-form-field').removeClass('sui-form-field-error');
	}

	function hide_errors_pass_details(type) {
		$('[id^="error-'+ type +'-details-"]').hide();
		$('[id^="error-'+ type +'-details-"]').html("");

		$('#error-'+ type +'-details-limit').closest('.sui-form-field').removeClass('sui-form-field-error');
		$('#error-'+ type +'-details-directory').closest('.sui-form-field').removeClass('sui-form-field-error');
		$('#error-'+ type +'-details-bucket').closest('.sui-form-field').find('.select2-selection.select2-selection--single').removeClass('snapshot-error-select');
	}

	function hide_errors_save() {
		$('#error-s3-save-name').hide();
		$('#error-s3-save-name').html("");
		$('#error-s3-save-name').closest('.sui-form-field').removeClass('sui-form-field-error');
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

	function load_destinations() {
		var loaders = $('.snapshot-destinations .snapshot-loader, .snapshot-destinations-summary .sui-summary-details .sui-icon-loader');
		var summary_large = $('.snapshot-destinations-summary .sui-summary-large');
		var summary_subs = $('.snapshot-destinations-summary .sui-summary-sub>span');
		var api_error = $('.snapshot-destinations .api-error');
		var tbody = $('.snapshot-destinations .sui-table>tbody');
		var tpd_rows = tbody.find('>tr:not(:first)');

		tpd_rows.remove();
		loaders.show();
		summary_large.css('visibility', 'hidden');
		api_error.hide();

		get_destinations().then(function (data) {
			var destinations = data.destinations.length + 1;
			summary_large.text(destinations).css('visibility', 'visible');
			summary_subs.hide().filter(destinations === 1 ? '.singular' : '.plural').show();
			data.destinations.forEach(function (item) {
				tbody.append(item.html_row);
			});
			update_destination_backup_counts();
			update_destination_rows_schedule();

			if (edit_destination_id) {
				row_dropdown_click('edit', edit_destination_id);
				edit_destination_id = null;
			}
			if (delete_destination_id) {
				row_dropdown_click('delete', delete_destination_id);
				delete_destination_id = null;
			}
		}, function () {
			api_error.show();
			summary_large.text(1).css('visibility', 'visible');
			summary_subs.hide().filter('.singular').show();
		}).always(function () {
			loaders.hide();
		});
	}

	function set_destination_row_active(tpd_id, is_active) {
		var row = $('.snapshot-destinations, .snapshot-dashboard-destinations').find('.destination-row[data-tpd_id=' + tpd_id + ']');
		if (!row.length) {
			return;
		}
		var checkbox = row.find('.toggle-active');

		if (is_active) {
			row.removeClass('deactivated-destination');
			checkbox.prop('checked', true);
		} else {
			row.addClass('deactivated-destination');
			checkbox.prop('checked', false);
		}
	}

	function toggle_destination_active(callback) {
		var checkbox = $(this);
		var row = checkbox.closest('.destination-row');
		var tpd_id = row.data('tpd_id');
		var tpd_name = row.data('tpd_name');
		var is_active = checkbox.prop('checked');

		if (typeof(callback) !== 'function') {
			callback = function () {};
		}

		var data = {
			tpd_id: tpd_id,
			aws_storage: is_active ? 1 : 0,
			_wpnonce: $('#_wpnonce-snapshot-update-destination').val()
		};

		$.ajax({
			type: 'POST',
			url: ajaxurl + '?action=snapshot-activate_destination',
			data: data,
			beforeSend: function () {
				set_destination_row_active(tpd_id, is_active);
				checkbox.prop('disabled', true);
			},
			complete: function () {
				checkbox.prop('disabled', false);
			},
			success: function (response) {
				if (response.success) {
					is_active = response.data.api_response.aws_storage === '1';
					set_destination_row_active(tpd_id, is_active);
					if (is_active) {
						var notice = $('<span></span>').html(snapshot_messages.destination_notice_activated.replace('%s', $('<span></span>').text(tpd_name).html()));
						jQuery(window).trigger('snapshot:show_top_notice', ['success', notice]);
					} else {
						var notice = $('<span></span>').html(snapshot_messages.destination_notice_deactivated.replace('%s', $('<span></span>').text(tpd_name).html()));
						jQuery(window).trigger('snapshot:show_top_notice', ['success', notice]);
					}
					callback(is_active ? 1 : -1);
				} else {
					set_destination_row_active(tpd_id, !is_active);
					callback(0);
				}
			},
			error: function () {
				set_destination_row_active(tpd_id, !is_active);
				callback(0);
			}
        });


		if (checkbox.prop('checked')) {
			row.removeClass('deactivated-destination');
		} else {
			row.addClass('deactivated-destination');
		}
	}

	function row_dropdown_click(action, id) {
		var li = $(this);
		if (id) {
			var li = $('.snapshot-destinations .destination-row[data-tpd_id=' + id + ']');
		}
		var row = li.closest('.destination-row');
		var tpd_id = row.data('tpd_id');
		var type = row.data('tpd_type');
		if ( type !== 'gdrive') {
			type = 's3';
		}

		if (action === 'edit' || li.hasClass('destination-edit')) {
			var data = row.data(),
				modal = $('#modal-destination-'+ type + '-edit');

			var form = $('#modal-destination-'+ type + '-edit #snapshot-edit-'+ type + '-connection');
			for (var name in data) {
				var el = form.find('[name=' + name + ']'),
					value = data[name];
				if (el.length) {
					if (type !== 'gdrive' && name === 'tpd_path') {
						var path_parts = value.split('/');
						var bucket = path_parts.shift();
						var path = path_parts.join('/');
						el.val(path === '' ? '' : ('/' + path));

						el = form.find('[name=tpd_bucket]');
						el.prop('disabled', true);
						el.empty().append($('<option selected></option>').val(bucket).text(bucket));
						el.val(bucket);
						el.SUIselect2({
							placeholder: snapshot_messages.choose_bucket,
							dropdownCssClass: 'sui-select-dropdown',
							dropdownParent: $('#snapshot-edit-s3-connection')
						});
					} else if (type !== 'gdrive' && name === 'tpd_type') {
						modal.find('.sui-box-title').html(snapshot_messages.configure_provider.replace('%s', snapshot_s3_providers[value].providerName));
						['access-key-id','secret-access-key'].forEach(function(element) {
							var selectedProviderInfo = snapshot_s3_providers[value].fields[element];
							$('#edit-s3-connection-' + element).attr('placeholder', snapshot_messages.provider_placeholder.replace('%s',selectedProviderInfo));
							$('#label-edit-s3-connection-' + element).html(selectedProviderInfo + ' *');
						});
						el.val(value);
					} else if (name === 'tpd_email_gdrive') {
						$('#modal-destination-gdrive-edit .snapshot-configured-gd-account-email').html(value);
					} else {
						el.val(value);
					}
				}
			}

			hide_errors_edit_destination(type);
			SUI.openModal('modal-destination-'+ type + '-edit', this);

			if ( type !== 'gdrive') {
				form.find('select').trigger('change');
				$('#edit-s3-connection-region').SUIselect2({
					placeholder: snapshot_messages.choose_region,
					dropdownCssClass: 'sui-select-dropdown',
					dropdownParent: $('#snapshot-edit-s3-connection')
				});
				load_buckets(form, bucket)
			}
		} else if (action === 'delete' || li.hasClass('destination-delete')) {
			delete_destination_confirm(tpd_id);
		}
	}

	function load_buckets(form, selected_bucket) {
		var data = {
			tpd_action: 'load_buckets',
			tpd_accesskey: form.find('[name=tpd_accesskey]').val(),
			tpd_secretkey: form.find('[name=tpd_secretkey]').val(),
			tpd_region: form.find('[name=tpd_region]').val(),
			tpd_type: form.find('[name=tpd_type]').val()
		};
		data._wpnonce = $( '#_wpnonce-snapshot_s3_connection' ).val();

		$.ajax({
			type: 'POST',
			url: ajaxurl + '?action=snapshot-s3_connection',
			data: data,
			success: function (response) {
				if (response.success && response.data.api_response.length > 0) {

					var el = form.find('[name=tpd_bucket]');
					var val = el.val();
					el.empty();
					response.data.api_response.forEach(function (bucket) {
						if (bucket===selected_bucket) {
							el.append($('<option selected></option>').val(bucket).text(bucket));
						} else {
							el.append($('<option></option>').val(bucket).text(bucket));
						}
					});
					el.prop('disabled', false);
					el.SUIselect2({
						placeholder: snapshot_messages.choose_bucket,
						dropdownCssClass: 'sui-select-dropdown',
						dropdownParent: $('#snapshot-edit-s3-connection')
					});
					el.val(val).trigger('change').trigger('change');
				}
			}
		});
	}

	function is_duplicate_destination_error(response) {
		if (!response.success && response.data && response.data.api_response) {
			if (response.data.api_response.Message === 'Same destination already exists') {
				return true;
			}
		}
		return false;
	}

	function on_update_destination_save() {

		var form = $(this).parents('[id ^=modal-destination-][id $=-edit]').find('[id ^=snapshot-edit-][id $=-connection]');
		var type = form.find('[name=tpd_type]').val();
		if (type !== 'gdrive') {
			type = 's3';
		}
		hide_errors_edit_destination(type);

		var data = {tpd_bucket: $('#edit-s3-connection-bucket').find(':selected').val()};
		var abort = false;
		form.serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});
		data._wpnonce = $('#_wpnonce-snapshot-update-destination').val();

		if(!data['tpd_name'].trim()) {
			$('#error-edit-' + type + '-connection-name').show();
			$('#error-edit-' + type + '-connection-name').html(snapshot_messages.require_name);
			$('#error-edit-' + type + '-connection-name').closest('.sui-form-field').addClass('sui-form-field-error');
			$('#edit-' + type + '-connection-name').trigger('focus');
			abort = true;
		}

		if(type !== 'gdrive' && !data['tpd_secretkey'].trim()) {
			$('#error-edit-s3-connection-secret-access-key').show();
			$('#error-edit-s3-connection-secret-access-key').html(snapshot_messages.require_secret_key);
			$('#error-edit-s3-connection-secret-access-key').closest('.sui-form-field').addClass('sui-form-field-error');
			$('#edit-s3-connection-secret-access-key').trigger('focus');
			abort = true;
		}

		if(type !== 'gdrive' && !data['tpd_accesskey'].trim()) {
			$('#error-edit-s3-connection-access-key-id').show();
			$('#error-edit-s3-connection-access-key-id').html(snapshot_messages.require_access_key);
			$('#error-edit-s3-connection-access-key-id').closest('.sui-form-field').addClass('sui-form-field-error');
			$('#edit-s3-connection-access-key-id').trigger('focus');
			abort = true;
		}

		if(type !== 'gdrive' && !data['tpd_region'].trim()) {
			$('#error-edit-s3-connection-region').show();
			$('#error-edit-s3-connection-region').html(snapshot_messages.require_region);
			$('#error-edit-s3-connection-region').closest('.sui-form-field').find('.select2-selection.select2-selection--single').addClass('snapshot-error-select');
			abort = true;
		}

		if(type !== 'gdrive' && !data['tpd_bucket'].trim()) {
			$('#error-edit-s3-connection-bucket').show();
			$('#error-edit-s3-connection-bucket').html(snapshot_messages.require_bucket);
			$('#error-edit-s3-connection-bucket').closest('.sui-form-field').find('.select2-selection.select2-selection--single').addClass('snapshot-error-select');
			abort = true;
		}

		if (type === 'gdrive') {
			if(!data['tpd_path'].trim() ) {
				$('#error-edit-gdrive-connection-path').show();
				$('#error-edit-gdrive-connection-path').html(snapshot_messages.require_directory_id);
				$('#error-edit-gdrive-connection-path').closest('.sui-form-field').addClass('sui-form-field-error');
				$('#edit-gdrive-connection-path').trigger('focus');
				abort = true;
			}
		} else {
			if(data['tpd_path'].trim() && data['tpd_path'].charAt(0) !== '/') {
				$('#error-edit-s3-connection-path').show();
				$('#error-edit-s3-connection-path').html(snapshot_messages.require_valid_path);
				$('#error-edit-s3-connection-path').closest('.sui-form-field').addClass('sui-form-field-error');
				$('#edit-s3-connection-path').trigger('focus');
				abort = true;
			}
		}

		if(!data['tpd_limit'].trim() || data['tpd_limit'] < 1) {
			$('#error-edit-' + type + '-connection-limit').show();
			$('#error-edit-' + type + '-connection-limit').html(snapshot_messages.require_limit);
			$('#error-edit-' + type + '-connection-limit').closest('.sui-form-field').addClass('sui-form-field-error');
			$('#edit-' + type + '-connection-limit').trigger('focus');
			abort = true;
		}

		if (abort) {
			return false;
		}
		if (type !== 'gdrive') {
			data.tpd_path = data.tpd_bucket + data.tpd_path.replace(/\/+$/, '');
		}
		$.ajax({
			type: 'POST',
			url: ajaxurl + '?action=snapshot-update_destination',
			data: data,
			beforeSend: function () {
				$('.snapshot-edit-destination-button').addClass('sui-button-onload-text');
				form.find('input, select, button').prop('disabled', true);
			},
			complete: function () {
				form.find('input, select, button').prop('disabled', false);
				$('.snapshot-edit-destination-button').removeClass('sui-button-onload-text');
				$('#modal-destination-' + type + '-edit').closest('.sui-modal').animate({scrollTop: 0}, 1000);
			},
			success: function (response) {
				if (response.success) {
					$('#notice-edit-' + type + '-destination-success').show();
					load_destinations();
				} else if (is_duplicate_destination_error(response)) {
					if (type === 's3' && data.tpd_path.split('/').length === 1) {
						$('#notice-edit-s3-bucket-duplicate-destination-error').show();
					} else {
						$('#notice-edit-' + type + '-duplicate-destination-error').show();
					}
				} else {
					$('#notice-edit-' + type + '-destination-error').show();
				}
			},
			error: function () {
				$('#notice-edit-' + type + '-destination-error').show();
			}
        });
	}

	function hide_errors_edit_destination(type) {
		$('[id^="error-edit-' + type + '-connection-"]').hide();
		$('[id^="error-edit-' + type + '-connection-"]').html("");

		$('#error-edit-' + type + '-connection-name').closest('.sui-form-field').removeClass('sui-form-field-error');
		$('#error-edit-' + type + '-connection-secret-access-key').closest('.sui-form-field').removeClass('sui-form-field-error');
		$('#error-edit-' + type + '-connection-access-key-id').closest('.sui-form-field').removeClass('sui-form-field-error');
		$('#error-edit-' + type + '-connection-region').closest('.sui-form-field').find('.select2-selection.select2-selection--single').removeClass('snapshot-error-select');
		$('#error-edit-' + type + '-connection-limit').closest('.sui-form-field').removeClass('sui-form-field-error');
		$('#error-edit-' + type + '-connection-bucket').closest('.sui-form-field').find('.select2-selection.select2-selection--single').removeClass('snapshot-error-select');
		$('#error-edit-' + type + '-connection-path').closest('.sui-form-field').removeClass('sui-form-field-error');

		$('#notice-edit-' + type + '-destination-success').hide();
		$('#notice-edit-' + type + '-destination-error').hide();
		$('[id^=notice-edit-][id$=-duplicate-destination-error]').hide();    // #notice-edit-*-duplicate-destination-error
	}

	function on_update_destination_delete() {
		var form = $(this).parents('[id ^=modal-destination-][id $=-edit]').find('[id ^=snapshot-edit-][id $=-connection]');
		var tpd_id = form.find('[name=tpd_id]').val();
		$(window).trigger('snapshot:close_modal');
		delete_destination_confirm(tpd_id);
	}

	function delete_destination_request(tpd_id) {
		var deferred = $.Deferred();

		var request_data = {
			action: 'snapshot-delete_destination',
			_wpnonce: $('#_wpnonce-snapshot-delete-destination').val(),
			tpd_id: tpd_id
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

	function delete_destination_confirm(tpd_id) {
		var row = $('.snapshot-destinations .destination-row[data-tpd_id=' + tpd_id + ']');
		if (!row.length) {
			return;
		}
		$('#modal-destinations-delete-description').find('strong').text(row.data('tpd_name'));
		$('#modal-destinations-delete-button').data({
			tpd_id: tpd_id,
			tpd_name: row.data('tpd_name')
		});
		SUI.openModal('modal-destinations-delete', this);
		return false;
	}

	function delete_destination() {
		var data = $(this).data();
		var modal = $(this).closest('.sui-modal');
		var modal_buttons = modal.find('.sui-button, sui-button-icon');

		modal_buttons.prop('disabled', true);
		delete_destination_request(data.tpd_id).then(function () {
			$(window).trigger('snapshot:close_modal');
			var notice = $('<span></span>').html(snapshot_messages.destination_delete_successful.replace('%s', $('<span></span>').text(data.tpd_name).html()));
			jQuery(window).trigger('snapshot:show_top_notice', ['success', notice]);
			load_destinations();
		}).always(function () {
			modal_buttons.prop('disabled', false);
		});
	}

	function history_replace_state(bare_url) {
		if (window.history && window.history.replaceState) {
			if (bare_url) {
				window.history.replaceState('', document.title, snapshot_urls.destinations);
			} else {
				window.history.replaceState('', document.title, window.location.pathname + window.location.search);
			}
		}
	}

	function check_if_on_aws() {
		return $('.snapshot-aws-tab.active').length;
	}

	function change_selected_provider() {
		$('#label-s3-compatible-connection-provider .sui-label-link').show();
		$('#error-s3-compatible-connection-provider').hide();
		$('#error-s3-compatible-connection-provider').html('');
		$('#error-s3-compatible-connection-provider').closest('.sui-form-field').find('.select2-selection.select2-selection--single').removeClass('snapshot-error-select');

		$('.s3-compatible-creds').show();
		hide_errors_test_connection();

		var selectedProvider = $('#s3-compatible-connection-provider').find(':selected').val();

		// Create the special UI for Google Cloud.
		if (selectedProvider==='googlecloud') {
			$('.snapshot-s3-compatible-key').addClass('sui-control-with-icon');
			$('.snapshot-s3-compatible-key .sui-icon-profile-male').show();
			$('.snapshot-s3-compatible-key .sui-icon-key').show();
			$('#label-s3-compatible-connection-provider .sui-label-link').show();
		} else if (selectedProvider==='s3_other') {
			$('.snapshot-s3-compatible-key').removeClass('sui-control-with-icon');
			$('.snapshot-s3-compatible-key .sui-icon-profile-male').hide();
			$('.snapshot-s3-compatible-key .sui-icon-key').hide();
			$('#label-s3-compatible-connection-provider .sui-label-link').hide();
		} else {
			$('.snapshot-s3-compatible-key').removeClass('sui-control-with-icon');
			$('.snapshot-s3-compatible-key .sui-icon-profile-male').hide();
			$('.snapshot-s3-compatible-key .sui-icon-key').hide();
			$('#label-s3-compatible-connection-provider .sui-label-link').show();
		}
		['access-key-id','secret-access-key','region'].forEach(function(element) {
			var selectedProviderInfo = snapshot_s3_providers[selectedProvider].fields[element];
			$('input#s3-compatible-connection-' + element).attr('placeholder', snapshot_messages.provider_placeholder.replace('%s',selectedProviderInfo));
			$('.s3-compatible-connection-'+element+'-label').html(selectedProviderInfo);
		});
		$('.snapshot-s3-compatible-tab .sui-accordion').hide();
		$('.snapshot-'+ selectedProvider +'-credentials-howto').show();
	}

	function update_destination_backup_counts() {
		$('.snapshot-destinations .destination-row[data-tpd_id]').each(function () {
			var row = $(this);
			var tpd_id = row.data('tpd_id');
			var value = destination_backup_count[tpd_id] !== undefined ? destination_backup_count[tpd_id] : 0;
			row.find('.backup-count').text(value);
		});
	}

	function prepare_next_modal_screen() {
		$('[id^=snapshot-][id$=-save-failure]').hide();              // #snapshot-*-save-failure
		$('[id^=snapshot-duplicate-][id$=-save-failure]').hide();    // #snapshot-duplicate-*-save-failure
		$('#snapshot-wrong-s3-creds').hide();
		$('#snapshot-wrong-s3-compatible-creds').hide();
		$('#snapshot-region-no-buckets').hide();
		$('#snapshot-region-no-buckets-s3-compatible').hide();

		if ($(this).closest("#snapshot-add-destination-dialog-slide-1").length > 0) {
			// Start the GDrive flow from the top.
			clean_gdrive_flow();
		}
	}

	function clean_gdrive_flow() {
		$('#snapshot-wrong-gd-creds').hide();
		$('#snapshot-correct-gd-creds').hide();
		$('#snapshot-gd-authorization').show();
		$('#snapshot-submit-gd-generate-tokens').hide();
		$('#snapshot-test-gd-connection').find("[name='tpd_auth_code']").val('');

	}

	function get_gdrive_auth_flow(urlParams) {
		SUI.openModal( "snapshot-add-destination-dialog", this );
		SUI.slideModal( 'snapshot-add-destination-dialog-slide-2-gd' );

		if ( urlParams.get('auth_success') && urlParams.has('auth_code') ) {
			$('#snapshot-submit-gd-generate-tokens').show();
			$('#snapshot-test-gd-connection').find("[name='tpd_auth_code']").val(urlParams.get('auth_code'));
			$('#_wpnonce-snapshot_gd_connection' ).val(urlParams.get('snapshot_gdrive_nonce'))
			$('#snapshot-correct-gd-creds').show();
			$('#snapshot-gd-authorization').hide();
		} else {
			$('#snapshot-wrong-gd-creds').show();
		}

		history_replace_state(true);
	}

	function fix_last_destination_tooltip() {
		$('.snapshot-last-destination').on('mouseenter', function () {
			var rect = this.getBoundingClientRect();
			var last_destination = $(this);
			var tooltip = last_destination.find('>.sui-tooltip');
			if (!tooltip.length) {
				return;
			}

			var summary = last_destination.closest('.sui-summary');
			var position = last_destination.position();
			var wrap = last_destination.closest('.sui-wrap');
			var copy = $('#snapshot-last-destination-copy');
			if (!copy.length) {
				var copy = $('<div id="snapshot-last-destination-copy"></div>');
				wrap.append(copy);
			}
			copy.css({
				position:'fixed',
				top: rect.top - 5,
				left: rect.left
			});

			var tooltip_clone = tooltip.clone();
			tooltip_clone.empty().css({
				display: 'inline-block',
				width: Math.min(tooltip.width(), summary.width() - position.left + 15),
				height: tooltip.height()
			});
			tooltip_clone.appendTo(copy);
			tooltip.removeClass('sui-tooltip').addClass('not-sui-tooltip');
		}).on('mouseleave', function () {
			$(this).find('>span.not-sui-tooltip').removeClass('not-sui-tooltip').addClass('sui-tooltip');
		});
	}

	$(window).on('load', function () {
		var matches;
		if ('#add-destination' === window.location.hash) {
			$('#snapshot-add-destination').trigger('click');
			history_replace_state(false);
		} else if (matches = window.location.hash.match(/^#edit\-destination\-(.+)/)) {
			edit_destination_id = matches[1];
			history_replace_state(false);
		} else if (matches = window.location.hash.match(/^#delete\-destination\-(.+)/)) {
			delete_destination_id = matches[1];
			history_replace_state(false);
		}

		var urlParams = new URLSearchParams(window.location.search);
		if ( 'google-auth' === urlParams.get('snapshot_action') ) {
			get_gdrive_auth_flow(urlParams);
		}
	});

	$(function () {
		if ( $( '.snapshot-page-destinations' ).length ) {
			load_backup_count();

			get_current_storage();

			load_destinations();

			load_schedule();

			// Expand AWS instructions when "Follow the instructions" is clicked.
			$('.snapshot-expand-aws-instructions').on('click', function(e){
				$('.snapshot-aws-credentials-howto .sui-accordion-item').addClass('sui-accordion-item--open');
				$("#snapshot-add-destination-modal").animate({ scrollTop: $("#snapshot-add-destination-modal #snapshot-add-destination-dialog").height() }, 1000);
			});

			// Expand S3 instructions when "Guide to find the credentials" is clicked.
			$('#label-s3-compatible-connection-provider .sui-label-link').on('click', function(e){
				if (e && e.preventDefault) e.preventDefault();

				var selectedProvider = $('#s3-compatible-connection-provider').find(':selected').val();
				$('.snapshot-'+ selectedProvider +'-credentials-howto .sui-accordion-item').addClass('sui-accordion-item--open');
				$("#snapshot-add-destination-modal").animate({ scrollTop: $("#snapshot-add-destination-modal #snapshot-add-destination-dialog").height() }, 1000);
			});

			// Open the Add Destination modal and initialize the forms.
			$('#snapshot-add-destination').on('click', function(e){
				SUI.openModal( "snapshot-add-destination-dialog", this );

				hide_errors_test_connection();
				hide_errors_pass_details('s3')
				hide_errors_save();

				// Create the AWS S3 region dropdown from the start.
				$('#s3-connection-region').SUIselect2("destroy");
				$("#s3-connection-region option").prop("selected", false);
				$('#s3-connection-region').SUIselect2( {
					placeholder: snapshot_messages.choose_region,
					dropdownCssClass: 'sui-select-dropdown',
					dropdownParent: $( '#snapshot-test-s3-connection' )
				} );

				// Create the S3 providers dropdown from the start.
				$('#s3-compatible-connection-provider').SUIselect2("destroy");
				$("#s3-compatible-connection-provider option").prop("selected", false);
				$('#s3-compatible-connection-provider').SUIselect2( {
					placeholder: snapshot_messages.choose_provider,
					dropdownCssClass: 'sui-select-dropdown',
					dropdownParent: $( '.s3-compatible-providers' )
				} );

				$('#s3-connection-access-key-id').val('');
				$('#s3-compatible-connection-access-key-id').val('');
				$('#s3-connection-secret-access-key').val('');
				$('#s3-compatible-connection-secret-access-key').val('');
				$('#s3-compatible-connection-region').val('');
				$('#s3-details-directory').val('');
				$('#s3-details-limit').val('30');
				$('.s3-compatible-creds').hide();
				$('.snapshot-s3-compatible-tab [class$="credentials-howto"]').hide();
				$('#label-s3-compatible-connection-provider .sui-label-link').hide();

				clean_gdrive_flow();
			});

			// Submit the form on the 1st S3 modal screen.
			$('#snapshot-submit-s3-connection-test').on('click', function(e){
				if (e && e.preventDefault) e.preventDefault();
				if (check_if_on_aws()) {
					$('form#snapshot-test-s3-connection').trigger('submit');
				} else {
					$('form#snapshot-test-s3-compatible-connection').trigger('submit');
				}
			});

			$('form#snapshot-test-s3-connection, form#snapshot-test-s3-compatible-connection').on('submit', destination_test_s3_connection);

			// Submit the form on the 1st GD modal screen.
			$('#snapshot-submit-gd-generate-tokens').on('click', function(e){
				if (e && e.preventDefault) e.preventDefault();
				$('form#snapshot-test-gd-connection').submit();
			});

			$('form#snapshot-test-gd-connection').on('submit', token_generation_gd_connection);

			// Submit the form on the 2nd S3 modal screen.
			$('#snapshot-submit-s3-connection-details').on('click', function(e){
				if (e && e.preventDefault) e.preventDefault();
				snapshot_s3_middle_form.find("[name='tpd_action']").val('move_to_next_screen');
				snapshot_s3_middle_form.trigger('submit');
			});

			// Perform the non-required 'Test Connection' action on the 2st S3 modal screen.
			$('#snapshot-test-s3-connection-path').on('click', function(e){
				if (e && e.preventDefault) e.preventDefault();
				snapshot_s3_middle_form.find("[name='tpd_action']").val('test_connection_final');
				snapshot_s3_middle_form.trigger('submit');
			});

			snapshot_s3_middle_form.on('submit', destination_intermediate_s3_step);

			// Submit the form on the 2nd GD modal screen.
			$('#snapshot-submit-gd-connection-details').on('click', function(e){
				if (e && e.preventDefault) e.preventDefault();
				snapshot_gd_middle_form.find("[name='tpd_action']").val('move_to_next_screen');
				snapshot_gd_middle_form.submit();
			});

			// Perform the non-required 'Test Connection' action on the 2st GD modal screen.
			$('#snapshot-test-gd-connection-path').on('click', function(e){
				if (e && e.preventDefault) e.preventDefault();
				snapshot_gd_middle_form.find("[name='tpd_action']").val('test_connection_final');
				snapshot_gd_middle_form.submit();
			});

			snapshot_gd_middle_form.on('submit', destination_intermediate_gd_step);

			// Submit the final form on the last S3 modal screen.
			$('#snapshot-submit-save-s3').on('click', function(e){
				if (e && e.preventDefault) e.preventDefault();
				snapshot_s3_final_form.trigger('submit');
			});

			snapshot_s3_final_form.on('submit', function(e){
				destination_save(e, this, 's3');
			});

			// Submit the final form on the last GD modal screen.
			$('#snapshot-submit-save-gd').on('click', function(e){
				if (e && e.preventDefault) e.preventDefault();
				snapshot_gd_final_form.submit();
			});

			snapshot_gd_final_form.on('submit', function(e){
				destination_save(e, this, 'gd');
			});

			// Hide any past error notices when going to a next modal screen.
			$('.snapshot-next-destination-screen').on('click', function(e){
				prepare_next_modal_screen();
			});

			// Hide the Require Region notice when selecting a region
			$('#s3-connection-region').on('select2:select', function (e) {
				$('#error-s3-connection-region').hide();
				$('#error-s3-connection-region').html('');
				$('#error-s3-connection-region').closest('.sui-form-field').find('.select2-selection.select2-selection--single').removeClass('snapshot-error-select');
			});

			  // Hide the Require Bucket notice when selecting a region
			$('#s3-details-bucket').on('select2:select', function (e) {
				$('#error-s3-details-bucket').hide();
				$('#error-s3-details-bucket').html('');
				$('#error-s3-details-bucket').closest('.sui-form-field').find('.select2-selection.select2-selection--single').removeClass('snapshot-error-select');
			});

			// S3 providers dropdown listener.
			$('#s3-compatible-connection-provider').on('select2:select', change_selected_provider);

			$('.snapshot-destinations').on('change', 'input[type=checkbox].toggle-active', toggle_destination_active);
			$('.snapshot-destinations').on('click', '.sui-dropdown>ul>li', function () {row_dropdown_click.bind(this)()});
			$('#modal-destinations-delete-button').on('click', delete_destination);
			$('.snapshot-delete-destination-button').on('click', on_update_destination_delete);
			$('.snapshot-edit-destination-button').on('click', on_update_destination_save);
			$('#modal-destination-s3-edit').find('[name=tpd_accesskey], [name=tpd_secretkey], [name=tpd_region]').on('change', function() {
				var form = $(this).closest('form'),
					bucket = form.find('[name=tpd_bucket]').val();
				load_buckets(form, bucket);
			});
			$('#button-reload-destinations').on('click', load_destinations);

			$('.sui-notice .hide-notice').on('click', function () {
				$(this).closest('.sui-notice').fadeOut(300);
			});

			// Queue respective modal screen depending on which destination type is selected.
			$('input[type=radio][name=snapshot-selected-destination-type]').change(function() {
				$('#snapshot-add-destination-dialog-slide-1 .snapshot-next-destination-screen').attr('data-modal-slide', 'snapshot-add-destination-dialog-slide-2-' + this.value);
				$('#snapshot-add-destination-dialog-slide-1 .snapshot-next-destination-screen').prop("disabled", false);
			});

		}

		// For Destination page
		$(window).on('snapshot:toggle_destination_active', function (e, element, callback) {
			toggle_destination_active.bind(element)(callback);
		});

		// Fix tooltip in block with overflow: hidden
		fix_last_destination_tooltip();
	});
})(jQuery);
