jQuery( document ).ready( function( $ ) {

	$( '#todo-cats tbody tr:visible:even' ).addClass( 'alternate' );

	/* Delete Categories */
	$( '#todo-cats' ).on( 'click', '.delete-todo-category', function () {
		var confirmed = confirm( ctdlcat.CONFIRMATION_MSG );
		if ( confirmed == false ) return false;
		var _item = this;
		var todotr = $( _item ).closest( 'tr' );

		$.ajax( {
    	    type:'post',
      	    url: ajaxurl,
	    	data: {
				action: 'cleverness_todo_cat_delete',
				cleverness_todo_cat_id: $( todotr ).attr( 'id' ),
				_ajax_nonce: ctdlcat.NONCE
			},
        	success: function( data ) {
				if ( data == 1 ) {
					$( _item ).parent().html( '<p>'+ctdlcat.SUCCESS_MSG+'</p>' ) // replace edit and delete buttons with message
					$( todotr ).css(' background', '#FFFFE0' ).delay( 2000 ).fadeOut( 400, function () { // change the table row background, fade, and remove row, re-stripe
						$( '#todo-cats tbody tr' ).removeClass( 'alternate' );
						$( '#todo-cats tbody tr:visible:even' ).addClass( 'alternate' );
					} );
				} else if ( data == 0 ) {
					$( '#message' ).html( '<p>'+ctdlcat.ERROR_MSG+'</p>' ).show().addClass( 'error below-h2' );
					$( todotr ).css( 'background', '#FFEBE8' );
				} else if ( data == -1 ) {
					$( '#message' ).html( '<p>'+ctdlcat.PERMISSION_MSG+'</p>' ).show().addClass( 'error below-h2' );
				}
      		},
      	    error: function( r ) {
				$( '#message' ).html( '<p>'+ctdlcat.ERROR_MSG+'</p>' ).show().addClass( 'error below-h2' );
				$( todotr ).css( 'background', '#FFEBE8' );
			}
    	} );
	} );
	/* end Delete Categories */

	/* Get Category */
	$( '#todo-cats' ).on( 'click', '.edit-todo-category', function () {
		var _item = this;
		var todotr = $( _item ).closest( 'tr' );

		$.ajax( {
    	    type:'post',
        	url: ajaxurl,
			dataType: 'json',
	    	data: {
				action: 'cleverness_todo_cat_get',
				cleverness_todo_cat_id: $( todotr ).attr( 'id' )
				},
        	success: function( data ){
      		    var nameform = '<form action=""><input type="text" name="cleverness_todo_cat_name" class="regular-text" value="'+data.cleverness_todo_cat_name+'" />';
				var visibilityform = '<select name="cleverness_todo_cat_visibility"><option value="0"';
				if ( data.cleverness_todo_cat_visibility == 0 ) { visibilityform += ' selected="selected"' }
				visibilityform += '>' + ctdlcat.PUBLIC + '</option><option value="1"';
				if ( data.cleverness_todo_cat_visibility == 1 ) { visibilityform += ' selected="selected"' }
				visibilityform += '>' + ctdlcat.PRIVATE + '</option></select>';
				$( todotr ).find( ':nth-child(2)' ).empty().html( nameform );
				$( todotr ).find( ':nth-child(3)' ).empty().html( visibilityform );
				$( _item ).parent().empty().html( '<input type="button" class="submit-edit-category button-primary" value="'+ctdlcat.EDIT_CAT+'" /></form>' );
      		    },
        	error: function( r ) {
				$( '#message' ).html( '<p>'+ctdlcat.ERROR_MSG+'</p>' ).show().addClass( 'error below-h2' );
				$( todotr ).css( 'background', '#FFEBE8' );
			}
    	  } );
	} );
	/* end Get Category */

	/* Edit Categories */
	$( '#todo-cats' ).on( 'click', '.submit-edit-category', function () {
		var _item = this;
		var todotr = $( _item ).closest( 'tr' );
		var catname = $( todotr ).find( 'input[name = "cleverness_todo_cat_name"]' ).val();
		var visibility = $( todotr ).find( 'select' ).val();

		$.ajax( {
    	    type:'post',
        	url: ajaxurl,
	    	data: {
				action: 'cleverness_todo_cat_update',
				cleverness_todo_cat_id: $( todotr ).attr( 'id' ),
				cleverness_todo_cat_name: catname,
				cleverness_todo_cat_visibility: visibility,
				_ajax_nonce: ctdlcat.NONCE
			},
        	success: function( data ) {
				if (data == 0) {
					$('#message').html('<p>' + ctdlcat.ERROR_MSG + '</p>').show().addClass('error below-h2');
					$(todotr).css('background', '#FFEBE8');
				} else if (data == -1) {
					$('#message').html('<p>' + ctdlcat.PERMISSION_MSG + '</p>').show().addClass('error below-h2');
				} else {
					var color = todotr.css( 'background-color' );
					var visibilitytxt = ctdlcat.PUBLIC;
					if ( visibility == 1 ) { visibilitytxt = ctdlcat.PRIVATE }
					if ( data != $( todotr).attr( 'id' ) ) {
						$('#message').html('<p>' + ctdlcat.SPLIT_MSG + '</p>').show().addClass('error below-h2');
					}
					$( _item).parent().empty().html( '<input class="edit-todo-category button-secondary" type="button" value="Edit" /> <input class="delete-todo-category button-secondary delete-tag" type="button" value="Delete" />' );
					$( todotr ).find( ':nth-child(2)' ).empty().html( catname );
					$( todotr ).find( ':nth-child(3)' ).empty().html(visibilitytxt );
					$( todotr ).css( 'background-color', '#FFFFE0' );
					$( todotr ).animate( {'background-color' : color}, 3000 );
				}
      		},
      	error: function( r ) {
			$( '#message' ).html( '<p>'+ctdlcat.ERROR_MSG+'</p>' ).show().addClass( 'error below-h2' );
			$( todotr ).css( 'background', '#FFEBE8' );
			}
    	} );
	} );
	/* end Edit Categories */

} );