/**
 * Page: Settings.
 */
;(function($) {

	function toggle_navbar_api_key() {
		$('.snapshot-page-main .snapshot-tab-api-key').show();
		$('.snapshot-page-main .snapshot-tab-data-and-settings').hide();

		$('.snapshot-page-main .snapshot-vertical-api-key').addClass('current');
		$('.snapshot-page-main .snapshot-vertical-data-and-settings').removeClass('current');

		return false;
	}

	function toggle_navbar_data_and_settings() {
		$('.snapshot-page-main .snapshot-tab-api-key').hide();
		$('.snapshot-page-main .snapshot-tab-data-and-settings').show();

		$('.snapshot-page-main .snapshot-vertical-api-key').removeClass('current');
		$('.snapshot-page-main .snapshot-vertical-data-and-settings').addClass('current');

		return false;
	}

	function copy_api_key() {
		$('#snapshot-api-key').select();
		document.execCommand('copy');
		document.getSelection().removeAllRanges();
		jQuery(window).trigger('snapshot:show_top_notice', ['success', snapshot_messages.api_key_copied, 3000, false]);
	}

	function copy_site_id() {
		$('#snapshot-site-id').select();
		document.execCommand('copy');
		document.getSelection().removeAllRanges();
		jQuery(window).trigger('snapshot:show_top_notice', ['success', snapshot_messages.site_id_copied, 3000, false]);
	}

	function reset_settings_confirm() {
		SUI.openModal('modal-settings-reset-settings', this);
		return false;
	}

	function reset_settings() {
		var data = {};

		data._wpnonce = $( '#_wpnonce-reset_snapshot_settings' ).val();

		var url = ajaxurl + '?action=reset_snapshot_settings';

		$.ajax({
			type: 'POST',
			url: url,
			data: data,
			cache: false,
			dataType: 'json',
			beforeSend: function () {
				$('#modal-settings-reset-settings .sui-button').prop('disabled', true);
			},
			complete: function () {
				$('#modal-settings-reset-settings .sui-button').prop('disabled', false);
				$(window).trigger('snapshot:close_modal');
			},
			success: function (data) {
				if (data.success) {
					jQuery(window).trigger('snapshot:show_top_notice', ['success', snapshot_messages.reset_settings_success, 3000, false]);
					// Select "Keep" in Data & Settings => Uninstall
					$('#snapshot-settings-save-tab-2 input[name=remove_on_uninstall][value=0]').click();
				} else {
					jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.reset_settings_error]);
				}
			},
			error: function () {
				jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.reset_settings_error]);
			}
		});

		return false;
	}

	function save_settings() {
		var form = $(this);
		var data = {};
		form.serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});
		data._wpnonce = $( '#_wpnonce-save_snapshot_settings' ).val();

		var url = ajaxurl + '?action=save_snapshot_settings';

		$.ajax({
			type: 'POST',
			url: url,
			data: data,
			cache: false,
			dataType: 'json',
			beforeSend: function () {
				form.find('[type="submit"].sui-button').prop('disabled', true);
			},
			complete: function () {
				form.find('[type="submit"].sui-button').prop('disabled', false);
			},
			success: function (data) {
				if (data.success) {
					jQuery(window).trigger('snapshot:show_top_notice', ['success', snapshot_messages.settings_save_success, 3000, false]);
				} else {
					jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.settings_save_error]);
				}
			},
			error: function () {
				jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.settings_save_error]);
			}
		});

		return false;
	}

	$(function () {
		if ($('.snapshot-page-settings').length) {
			$('.snapshot-page-main .snapshot-vertical-api-key').on('click', toggle_navbar_api_key);
			$('.snapshot-page-main .snapshot-vertical-data-and-settings').on('click', toggle_navbar_data_and_settings);

			$('.snapshot-page-main .sui-mobile-nav #undefined-option-api-key').on('click', toggle_navbar_api_key);
			$('.snapshot-page-main .sui-mobile-nav #undefined-option-data-and-settings').on('click', toggle_navbar_data_and_settings);

			$('#snapshot-settings-copy-api-key').on('click', copy_api_key);
			$('#snapshot-settings-copy-site-id').on('click', copy_site_id);

			$('#snapshot-settings-reset-settings-confirm').on('click', reset_settings_confirm);
			$('#snapshot-settings-reset-settings').on('click', reset_settings);

			$('#snapshot-settings-save-tab-1').on('submit', save_settings);
			$('#snapshot-settings-save-tab-2').on('submit', save_settings);
		}
	});

})(jQuery);
