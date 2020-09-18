(
    (
    function( $ ) {
        if ( undefined === restTheme ) {
            return;
        }

        var renderEvents = function( response ) {
            var eventsNode = null;

            if ( response.events.length > 0 ) {
                var eventsNode = $( '<ul>' );
                var eventNodeProps = { style: 'margin-bottom: 1em;' };
                for ( var event of response.events ) {
                    var eventNode = $( '<li>', eventNodeProps );
                    eventNode.text( event.title );
                    var buttonProps = { 'data-event-id': event.id, style: 'padding: 5px; margin-left: 1em;' };
                    var eventNodeButton = $( '<button>', buttonProps ).text( 'Delete this!' ).on( 'click', deleteEvent );
                    eventNodeButton.appendTo( eventNode );
                    eventNode.appendTo( eventsNode );
                }
            } else {
                var eventsNode = $( '' );
                eventsNode.text( 'No upcoming events found!' ); }
                var $container = $( '#rest-events' );
                $container.empty();
                eventsNode.appendTo( $container );
            }
        };

        var showEvents = function() {
            $.ajax( {
                url: restTheme.root + 'events',
                method: 'GET',
                beforeSend: function( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', restTheme.nonce );
                },
                data: { 'page': 1, 'per_page': 3, }
            } ).done( renderEvents );
        };

        var deleteEvent = function() {
            var $this = $( this );
            var eventId = $this.data( 'event-id' );
            if ( ! eventId ) { return; }
            $.ajax( {
                url: restTheme.root + 'events/' + eventId,
                method: 'DELETE',
                beforeSend: function( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', restTheme.nonce );
                },
                data: {}
            } ).done( showEvents );
        }

        $( document ).ready( function() {
            showEvents();
        } );
    }
)( jQuery ) 
