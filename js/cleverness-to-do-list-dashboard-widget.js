jQuery( document ).ready( function( $ ) {

	$( '.todo-checkbox' ).click( function () {
		var id = $( this ).attr( 'id' ).substr( 5 );
		var todoid = '#todo-' + id;

		var data = {
		action: 'ctdl_dashboard_complete',
		cleverness_id: id,
		cleverness_status: 1,
		_ajax_nonce: ctdl.NONCE
		};

		jQuery.post( ctdl.AJAX_URL, data, function( response ) {
			$( todoid ).fadeOut();
			} );
	} );

} );