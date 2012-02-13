jQuery(document).ready(function($) {

	$(function() {
		$( "#cleverness_todo_deadline" ).datepicker();
	});

$('.todo-table tbody tr:visible:even').addClass('alternate');

/* Complete To-Dos */
$('.todo-checkbox').click(function () {
	var action = '';
	var id = $(this).attr('id').substr(5);
	var cleverness_todo_complete_nonce = $("input[name=cleverness_todo_complete_nonce]").val();
	if ($(this).hasClass('uncompleted')) {
		action = 'completetodo';
	} else if ($(this).hasClass('completed')) {
		action = 'uncompletetodo';
	}
	document.location.href = 'admin.php?page=cleverness-to-do-list&action='+action+'&id='+id+'&_wpnonce='+cleverness_todo_complete_nonce;
	});
/* end Complete To-Dos */

/* Edit To-Dos */
$('.edit-todo').live('click', function () {
		var todotr = $(this).closest('tr');
		var id = $(todotr).attr('id').substr(5)
		document.location.href = 'admin.php?page=cleverness-to-do-list&action=edit-todo&id='+id;
		});
/* end Edit To-Dos */

/* Delete All Todos */
$('#delete-all-todos').live('click', function () {
	var confirmed = confirm(ctdl.CONFIRMATION_ALL_MSG);
	if ( confirmed == false ) return;
});

/* Delete To-Dos */
$('.delete-todo').live('click', function () {
	var confirmed = confirm(ctdl.CONFIRMATION_MSG);
	if ( confirmed == false ) return;
	var _item = this;
	var todotr = $(_item).closest('tr');

	$.ajax({
    	type:'post',
      	url: ajaxurl,
	  	data: {
			action: 'cleverness_delete_todo',
			cleverness_todo_id: $(todotr).attr('id').substr(5),
			_ajax_nonce: ctdl.NONCE
			},
      	success: function(data){
			if ( data == 1 ) {
				$(_item).parent().html('<p>'+ctdl.SUCCESS_MSG+'</p>') // replace edit and delete buttons with message
				$(todotr).css('background', '#FFFFE0').delay(2000).fadeOut(400, function () { // change the table row background, fade, and remove row, re-stripe
					$('#todo-list tbody tr').removeClass('alternate');
					$('#todo-list tbody tr:visible:even').addClass('alternate');
				});
			} else if ( data == 0 ) {
				$('#message').html('<p>'+ctdl.ERROR_MSG+'</p>').show().addClass('error below-h2');
				$(todotr).css('background', '#FFEBE8');
			} else if ( data == -1 ) {
				$('#message').html('<p>'+ctdl.PERMISSION_MSG+'</p>').show().addClass('error below-h2');
				}
      		},
      	error: function(r) {
			$('#message').html('<p>'+ctdl.ERROR_MSG+'</p>').show().addClass('error below-h2');
			$(todotr).css('background', '#FFEBE8');
			}
    	});
	});
/* end Delete To-Dos */
});