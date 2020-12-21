/**
 * All WP Pages.
 */
;(function($) {

    var $snapshot_confirm_v3_uninstall_modal = $("#confirm-v3-uninstall");
    $snapshot_confirm_v3_uninstall_modal.dialog({
        dialogClass   : "wp-dialog snapshot3-uninstall-confirmation-modal",
        modal         : true,
        autoOpen      : false,
        closeOnEscape : true,
        resizable     : false,
        draggable     : false,
        width         : 500,
        height        : 248,
        buttons       : [            
                            {
                                text  : 'Cancel', 
                                click : function() {
                                        $(this).dialog('close')
                                    }, 
                                class : 'snapshot-button snapshot-button-cancel button button-secondary'
                            },
                            {
                                text  : 'Uninstall Now', 
                                click : function() {},  
                                class : 'snapshot-button snapshot-button-uninstall snapshot-uninstall-v3-admin button button-secondary'
                            }
                        ]
    });

	$('.snapshot-uninstall-v3-admin-confirm').on('click', function (e) {
        e.preventDefault();
        $snapshot_confirm_v3_uninstall_modal.dialog('open');
    });

	$('.snapshot-uninstall-v3-admin').on('click', function (e) {
		if (e && e.preventDefault) e.preventDefault();

		var data = {
			_wpnonce : $( '#_wpnonce-uninstall_snapshot_v3' ).val(),
		};

        var form = $(this).closest('form');
        $.ajax({
            type: 'POST',
            url: SnapshotAjaxGlobal.snapshot_ajaxurl + '?action=snapshot-uninstall_snapshot_v3',
            data: data,
            beforeSend: function () {
                $('.snapshot-uninstall-v3-admin').prop('disabled', true);
                $('.snapshot-uninstall-v3-admin').addClass('button-disabled');
            },
            complete: function () {
                $('.snapshot-uninstall-v3-admin').prop('disabled', false);
                $('.snapshot-uninstall-v3-admin').removeClass('button-disabled');

                var is_confirmation_modal_open = $snapshot_confirm_v3_uninstall_modal.dialog( "isOpen" );
                if ( true === is_confirmation_modal_open ) {
                    $snapshot_confirm_v3_uninstall_modal.dialog( "close" );
                }
            },
            success: function (response) {
                if (response.success) {
					$('.notice.snapshot-uninstall-prompt').hide();
                    $('.notice.snapshot-uninstall-v3-success').show();

                    $('.wp-has-submenu.toplevel_page_snapshot_pro_dashboard').hide();                    

				}
            }
        });
	});
    
    $('.snapshot-uninstall-prompt').on('click', 'button.notice-dismiss', function () {
        $.ajax({
            type: 'POST',
            url: SnapshotAjaxGlobal.snapshot_ajaxurl,
            data: {
                action: 'snapshot-uninstall_v3_notice_dismiss',
                _wpnonce: $('#_wpnonce-snapshot_dismiss_uninstall_notice').val()
            }
        });
        $(this).closest('.notice').hide();
        return false;
    });
})(jQuery);
