jQuery( document ).ready( function( $ ) {

	$( '.cleverness-to-do-list' ).on( 'click', '.todo-checkbox', function () {
		var status = 0;
		var id = $( this ).attr( 'id' ).substr( 5 );
		var todoid = '#todo-' + id;
		var refresh = $( this ).parent().parent().parent().hasClass( 'refresh-checklist' );
		if ( $( this ).prop( 'checked' ) ) status = 1;

		var data = {
			action: 'ctdl_dashboard_complete',
			ctdl_todo_id: id,
			ctdl_todo_status: status,
			_ajax_nonce: ctdl.NONCE
		};

		var todo_data = {
			action: 'ctdl_dashboard_display_todos',
			_ajax_nonce: ctdl.NONCE
		};

		jQuery.post( ctdl.AJAX_URL, data, function ( response ) {

			// if refresh = true, reload all todos
			if ( true === refresh ) {
				jQuery.post( ctdl.AJAX_URL, todo_data, function (response) {
					$('.cleverness-to-do-list').html( response );
				});
			} else {
				// remove the to-do
				$(todoid).fadeOut(function () {
					$(this).remove();
				});
				$(todoid + ' .todo-checkbox').prop("checked", false);
			}

		} );
	} );

} );