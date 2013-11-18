jQuery( document ).ready( function( $ ) {

	$( '.todo-checkbox' ).on( 'click', function () {
		var status = 1;
		var id = $( this ).attr( 'id' ).substr( 5 );
		var todoid = '#todo-' + id;
		var completed = $( "input[name='cleverness-dashboard-completed']" ).val();
		if ( $( this ).prop( 'checked' ) == false ) status = 0;

		var data = {
			action: 'cleverness_todo_dashboard_complete',
			cleverness_id: id,
			cleverness_status: status,
			_ajax_nonce: ctdl.NONCE
		};

		var todo_data = {
			action: 'cleverness_dashboard_get_todos',
			cleverness_status: status,
			_ajax_nonce: ctdl.NONCE
		};

		jQuery.post( ctdl.AJAX_URL, data, function( response ) {
			if ( status == 1 ) {
				$( this ).prop( 'checked', true );
			} else if ( status == 0 ) {
				$( this ).prop( 'checked', false );
			}
			$( todoid ).fadeOut();
			if ( completed == 1 ) {
				jQuery.post( ctdl.AJAX_URL, todo_data, function ( response ) {
					if ( status == 1 ) {
						$( '#cleverness-completed' ).html( response.data );
					} else if ( status == 0 ) {
						$( '#cleverness-uncompleted' ).html( response.data );
					}
				} );
			}
			} );
	} );

} );