jQuery(document).ready(function ($) {

	$('#ctdl-message').hide();

	$('.todo-table').tablesorter();

	$("#cleverness_todo_assign").select2({
		placeholder: ctdl.SELECT_USER
	});

	$(function () {
		$("#cleverness-todo-progress-slider").slider({
			range: "min",
			value: $("#cleverness_todo_progress").val(),
			min  : 0,
			max  : 100,
			step : 5,
			slide: function (event, ui) {
				$("#cleverness_todo_progress").val(ui.value);
			}
		});
		$("#cleverness_todo_progress").val($("#cleverness-todo-progress-slider").slider("value"));
	});

	$(function () {
		var $cleverness_todo_dateformat = $("#cleverness_todo_format").val();
		$("#cleverness_todo_deadline").datepicker({dateFormat: $cleverness_todo_dateformat});
	});

	/* For todoadmin shortcode */
	$('.todo-table').on('click', '.todo-checkbox', ( function () {
		var status = 0;
		var id = $(this).attr('id').substr(5);
		var todoid = '#todo-' + id;
		var single = $(this).hasClass('single');
		var completed = $('.todo-table').hasClass('ctdl-completed');
		if ($(this).prop('checked')) status = 1;

		var data = {
			action          : 'cleverness_todo_complete',
			ctdl_todo_id    : id,
			ctdl_todo_status: status,
			_ajax_nonce     : ctdl.NONCE
		};

		var todo_data = {
			action             : 'cleverness_frontend_display_todos',
			ctdl_status        : status,
			ctdl_shortcode_atts: ctdl.TODOADMIN_ATTS,
			_ajax_nonce        : ctdl.NONCE
		};

		jQuery.post(ctdl.AJAX_URL, data, function (response) {
			if (status == 1) {
				$(this).prop("checked", true);
			} else {
				$(this).prop("checked", false);
			}
			$(todoid).fadeOut(function () {
				$(this).remove();
			});
			if (true == completed) {
				var table = '#todo-list';
				jQuery.post(ctdl.AJAX_URL, todo_data, function (response) {
					if (status == 1) {
						table = '#todo-list-completed';
					}
					$(table).html(response);
				});
			}
		});
	} ));

	/* Add To-Dos */
	$('#addtodo').submit(function (e) {
		e.preventDefault();
		var data = $('#addtodo').serializeArray();
		if (jQuery('#wp-clevernesstododescription-wrap').hasClass('tmce-active') && tinyMCE.get('clevernesstododescription')) {
			data.push({
				name : 'cleverness_todo_description',
				value: tinyMCE.get('clevernesstododescription').getContent()
			});
		}
		data.push(
			{name: 'action', value: 'cleverness_add_todo'},
			{name: '_ajax_nonce', value: ctdl.NONCE},
			{name: 'ctdl_shortcode_atts', value: JSON.stringify(ctdl.TODOADMIN_ATTS)}
		);

		$.ajax({
			type   : 'post',
			url    : ctdl.AJAX_URL,
			data   : data,
			success: function (data) {
				if (data != 0) {
					$('#addtodo').each(function () {
						this.reset();
					});
					var message = '<p>' + ctdl.INSERT_MSG + '</p>';
					$('#ctdl-message').html(message).show().addClass('ctdl-message');
					$('#todo-list').html(data);
				} else {
					$('#ctdl-message').html('<p>' + ctdl.ERROR_MSG + '</p>').show().addClass('ctdl-message ctdl-error');
				}
			},
			error  : function (r) {
				$('#ctdl-message').html('<p>' + ctdl.ERROR_MSG + '</p>').show().addClass('ctdl-message ctdl-error');
			}
		});
	});
	/* end Add To-Dos */

	/* Delete To-Dos */
	$('.todo-table').on('click', '.delete-todo', function (e) {
		e.preventDefault();
		var confirmed = confirm(ctdl.CONFIRMATION_MSG);
		if (confirmed == false) return false;
		var _item = this;
		var todotr = $(_item).closest('tr');

		$.ajax({
			type   : 'post',
			url    : ctdl.AJAX_URL,
			data   : {
				action            : 'cleverness_delete_todo',
				cleverness_todo_id: $(todotr).attr('id').substr(5),
				_ajax_nonce       : ctdl.NONCE
			},
			success: function (data) {
				if (data == 1) {
					$(_item).parent().html('<p>' + ctdl.SUCCESS_MSG + '</p>') // replace edit and delete buttons with message
					$(todotr).css('background', '#FFFFE0').delay(2000).fadeOut(400, function () { // change the table row background, fade, and remove row, re-stripe
						$('.todo-table tr').removeClass('alternate');
						$('.todo-table tbody tr:visible:even').addClass('alternate');
					});
				}
			}
		});
	});
	/* end Delete To-Dos */

});