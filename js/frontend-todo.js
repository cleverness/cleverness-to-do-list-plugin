jQuery(document).ready(function($) {

// add nonce

	$('.todo-checkbox').click(function () {
		var status = 1;
		var id = $(this).attr('id').substr(5);
		var todoid = '#todo-' + id;
		if ($(this).prop('checked') == false ) status = 0;

		var data = {
		action: 'cleverness_todo_complete',
		cleverness_id: id,
		cleverness_status: status
		};

		jQuery.post(cltd.AJAX_URL, data, function(response) {
			$(todoid).fadeOut();
			$('.todo-checkbox').removeAttr("checked");
			// add the row to the correct table
			});
	});


/* Delete To-Dos */
$('.delete-todo').live('click', function (e) {
	e.preventDefault();
	var confirmed = confirm(cltd.CONFIRMATION_MSG);
	if ( confirmed == false ) return;
	var _item = this;
	var todotr = $(_item).closest('tr');

	$.ajax({
    	type:'post',
      	url: cltd.AJAX_URL,
	  	data: {
			action: 'cleverness_todo_delete',
			cleverness_todo_id: $(todotr).attr('id').substr(5),
			_ajax_nonce: cltd.NONCE
			},
      	success: function(data){
			if ( data == 1 ) {
				$(_item).parent().html('<p>'+cltd.SUCCESS_MSG+'</p>') // replace edit and delete buttons with message
				$(todotr).css('background', '#FFFFE0').delay(2000).fadeOut(400, function () { // change the table row background, fade, and remove row, re-stripe
					$('#todo-list tbody tr').removeClass('alternate');
					$('#todo-list tbody tr:visible:even').addClass('alternate');
				});
			} else if ( data == 0 ) {
				$('#message').html('<p>'+cltd.ERROR_MSG+'</p>').show().addClass('error below-h2');
				$(todotr).css('background', '#FFEBE8');
			} else if ( data == -1 ) {
				$('#message').html('<p>'+cltd.PERMISSION_MSG+'</p>').show().addClass('error below-h2');
				}
      		},
      	error: function(r) {
			$('#message').html('<p>'+cltd.ERROR_MSG+'</p>').show().addClass('error below-h2');
			$(todotr).css('background', '#FFEBE8');
			}
    	});
	});
/* end Delete To-Dos */
});