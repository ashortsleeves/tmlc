/**
 * All WP Pages.
 */
;(function($) {

    function snapshot4_migrate_region() {
        console.log('called');
		var data = {
			_wpnonce : $( '#_wpnonce-snapshot_migrate_region' ).val(),
		};
        $.ajax({
            type: 'POST',
            url: SnapshotAjaxMigration.snapshot_migrationajaxurl + '?action=snapshot-migrate_region',
            data: data
        });
    }

    $(function () {
        snapshot4_migrate_region();
    });
})(jQuery);
