jQuery( document ).ready( function( $ ) {

	$( '#todo-list' ).tablesorter();

	$( '#todo-list-completed th' ).click( function () {
		$( this ).parents( '#todo-list-completed' ).children( 'tbody' ).toggle();
		$( '#todo-list-completed #checkbox-col .icon' ).toggleClass( 'minus' ).toggleClass( 'plus' );
	} );

	$( "#cleverness_todo_assign" ).select2( {
		placeholder: ctdl.SELECT_USER
	} );

	$( "#cleverness-todo-progress-slider" ).slider( {
		range:"min",
		value:$( "#cleverness_todo_progress" ).val(),
		min  :0,
		max  :100,
		step :5,
		slide:function ( event, ui ) {
			$( "#cleverness_todo_progress" ).val( ui.value );
		}
	} );
	$( "#cleverness_todo_progress" ).val( $( "#cleverness-todo-progress-slider" ).slider( "value" ) );

	var $cleverness_todo_dateformat = $( "#cleverness_todo_format" ).val();
	$( "#cleverness_todo_deadline" ).datepicker( { dateFormat: $cleverness_todo_dateformat }  );

	$( "#cleverness-resizable" ).resizable();

	$( '.todo-table tbody tr:visible:even' ).addClass( 'alternate' );

	/* Complete To-Dos */
	$( '.todo-checkbox' ).click( function () {
		var action = '';
		var id = $( this ).attr( 'id' ).substr( 5 );
		var cleverness_todo_complete_nonce = $( "input[name=cleverness_todo_complete_nonce]" ).val();
		if ( $( this ).hasClass( 'todo-uncompleted' ) ) {
			action = 'completetodo';
		} else if ( $( this ).hasClass( 'todo-completed' ) ) {
			action = 'uncompletetodo';
		}
		document.location.href = 'admin.php?page=cleverness-to-do-list&action='+action+'&id='+id+'&_wpnonce='+cleverness_todo_complete_nonce;
	} );
	/* end Complete To-Dos */

	/* Edit To-Dos */
	$( '.todo-table' ).on( 'click', '.edit-todo', function () {
		var todotr = $( this ).closest( 'tr' );
		var id = $( todotr ).attr( 'id' ).substr( 5 );
		document.location.href = 'admin.php?page=cleverness-to-do-list&action=edit-todo&id='+id;
	} );
	/* end Edit To-Dos */

	/* Delete Tables */
	$( '#delete-tables' ).click( function () {
		var confirmed = confirm( ctdl.CONFIRMATION_DEL_TABLES_MSG );
		if ( confirmed == false ) return false;
	} );

	/* Delete All Todos */
	$( '#delete-all-todos' ).click( function () {
		var confirmed = confirm( ctdl.CONFIRMATION_DELETE_ALL_MSG );
		if ( confirmed == false ) return false;
	} );

	/* Delete To-Dos */
	$( '.todo-table' ).on( 'click', '.delete-todo', function () {
		var confirmed = confirm( ctdl.CONFIRMATION_MSG );
		if ( confirmed == false ) return false;
		var _item = this;
		var todotr = $( _item ).closest( 'tr' );

		$.ajax({
         	type:'post',
        	url: ajaxurl,
	    	data: {
				action: 'cleverness_delete_todo',
				cleverness_todo_id: $( todotr ).attr( 'id' ).substr( 5 ),
				_ajax_nonce: ctdl.NONCE
			},
        	success: function( data ){
				if ( data == 1 ) {
					$( _item ).parent().html( '<p>'+ctdl.SUCCESS_MSG+'</p>' ); // replace edit and delete buttons with message
					$( todotr ).css( 'background', '#FFFFE0' ).delay( 2000 ).fadeOut( 400, function () { // change the table row background, fade, and remove row, re-stripe
						$( '#todo-list tbody tr').removeClass( 'alternate' );
						$( '#todo-list tbody tr:visible:even' ).addClass( 'alternate' );
					});
				} else if ( data == 0 ) {
					$( '#message' ).html( '<p>'+ctdl.ERROR_MSG+'</p>' ).show().addClass( 'error below-h2' );
					$( todotr ).css( 'background', '#FFEBE8' );
				} else if ( data == -1 ) {
					$( '#message' ).html( '<p>'+ctdl.PERMISSION_MSG+'</p>' ).show().addClass( 'error below-h2' );
				}
      		},
      	    error: function( r ) {
				$( '#message' ).html( '<p>'+ctdl.ERROR_MSG+'</p>' ).show().addClass( 'error below-h2' );
				$( todotr ).css( 'background', '#FFEBE8' );
			}
    	} );
	} );
	/* end Delete To-Dos */

} );