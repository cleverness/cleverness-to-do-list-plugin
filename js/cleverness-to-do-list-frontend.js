jQuery( document ).ready( function( $ ) {

	/* For todochecklist shortcode */
	$('.todo-checklist').on( 'click', '.todo-checkbox', function () {
		var status = 0;
		var id = $(this).attr('id').substr(5);
		var todoid = '#todo-' + id;
		var single = $(this).hasClass('single');
		if ($(this).prop('checked')) status = 1;
		var data = {
			action          : 'cleverness_todo_complete',
			ctdl_todo_id    : id,
			ctdl_todo_status: status,
			_ajax_nonce     : ctdl.NONCE
		};
		jQuery.post(ctdl.AJAX_URL, data, function (response) {
			if (single != true) {
				if (status == 1) {
					$(this).prop("checked", true);
				} else {
					$(this).prop("checked", false);
				}
				$(todoid).fadeOut(function () {
					$(this).remove();
				});
			}
		});
	});

} );