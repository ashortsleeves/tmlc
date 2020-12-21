/**
 * Bulk checkbox
 */
;(function($) {

//	.bulk-checkbox-wrap
//		input.bulk-checkbox-main data-bulk-group="group1"
//		input.bulk-checkbox      data-bulk-group="group1"
//		input.bulk-checkbox      data-bulk-group="group1"
//		...
//
//	.bulk-show-if-any            data-bulk-group="group1"

	function bulk_checkbox_change() {
		var checkbox = $(this);
		var is_main = checkbox.hasClass('bulk-checkbox-main');
		var group = checkbox.data('bulkGroup');

		if (!group) {
			return;
		}

		var all = $('input.bulk-checkbox:not(.bulk-checkbox-main)[data-bulk-group="' + group + '"]').filter(':visible');
		var main = $('input.bulk-checkbox-main[data-bulk-group="' + group + '"]').filter(':visible');
		var show_if_any = $('.bulk-show-if-any[data-bulk-group="' + group + '"]');
		var all_checked = !!all.length;
		var any_checked = false;
		var selected_items = [];

		all.each(function () {
			all_checked = all_checked && this.checked;
			any_checked = any_checked || this.checked;
		});

		if (is_main) {
			all.prop('checked', this.checked);
			this.checked ? show_if_any.show() : show_if_any.hide();
		} else {
			main.prop('checked', all_checked);
			any_checked ? show_if_any.show() : show_if_any.hide();
		}

		all.each(function () {
			if (this.checked) {
				selected_items.push(this);
			}
		});

		show_if_any.data('selectedItems', selected_items);
		show_if_any.trigger('snapshot:bulk_change', [selected_items]);
	}

	function uncheck_all(event, group) {
		var all = $('input.bulk-checkbox:not(.bulk-checkbox-main)[data-bulk-group="' + group + '"]');
		var main = $('input.bulk-checkbox-main[data-bulk-group="' + group + '"]');
		var show_if_any = $('.bulk-show-if-any[data-bulk-group="' + group + '"]');

		all.prop('checked', false);
		main.prop('checked', false);
		show_if_any.hide();
	}

	$(function() {
		$('.bulk-checkbox-wrap').on('change', 'input.bulk-checkbox', bulk_checkbox_change);
		$(window).on('snapshot:bulk_uncheck_all', uncheck_all);
	});

})(jQuery);
