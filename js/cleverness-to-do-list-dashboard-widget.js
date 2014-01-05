jQuery( document ).ready( function( $ ) {

	$( '.todo-checkbox' ).click( function () {
		var status = 0;
		var id = $( this ).attr( 'id' ).substr( 5 );
		var todoid = '#todo-' + id;
		var refresh = $( this ).parent().parent().hasClass( 'refresh' );
		if ( $( this ).prop( 'checked' ) ) status = 1;

		var data = {
			action: 'ctdl_dashboard_complete',
			ctdl_todo_id: id,
			ctdl_todo_status: status,
			_ajax_nonce: ctdl.NONCE
		};

		var todo_data = {
			action: 'ctdl_display_todos',
			ctdl_status: status,
			_ajax_nonce: ctdl.NONCE
		};

		jQuery.post( ctdl.AJAX_URL, data, function ( response ) {
			/*$( todoid ).fadeOut( function () {
				$( this ).remove();
			} );*/
			//$( '.todo-checkbox' ).prop( "checked", false );
			jQuery.post( ctdl.AJAX_URL, todo_data, function ( response ) {
				if ( status == 1 ) {
					console.log( response );
					$( '.completed-checklist' ).html( 'test' );
				} else if ( status == 0 ) {
					$( '.uncompleted-checklist' ).html( response );
				}
			} );
		} );
	} );

} );