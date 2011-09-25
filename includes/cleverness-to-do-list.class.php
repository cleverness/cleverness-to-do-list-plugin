<?php
/* Main class */

class ClevernessToDoList {
	protected $settings;
	protected $cat_id = '';
	protected $list = '';
	protected $form = '';

	public function __construct($settings) {
		add_action( 'init', array(&$this, 'cleverness_todo_checklist_init') );
		$this->settings = $settings;
		}

	public function display($title = '', $priority = 1, $assigned = 1, $deadline = 1, $progress = 1, $categories = 1, $addedby = 1, $editlink = 1) {
		global $wpdb, $cleverness_todo_option, $userdata, $current_user;
		get_currentuserinfo();

		$priorities = array(0 => $this->settings['priority_0'] , 1 => $this->settings['priority_1'], 2 => $this->settings['priority_2']);
		$user = $this->get_user($current_user, $userdata);
		$url = $this->get_page_url();

		$action = ( isset($_GET['action']) ? $_GET['action'] : '' );

		if ($action == 'edit-todo') {

    		$id = absint($_GET['id']);
    		$result = cleverness_todo_get_todo($id);
			$this->list .= $this->edit_form($result, $url);

		} else {

		if (current_user_can($this->settings['add_capability']) || $this->settings['list_view'] == '0') {
			$this->list .= '<a href="#addtodo">'.__('Add New Item', 'cleverness-to-do-list').'</a>';
		 	}

		$this->list .= '<table id="todo-list" class="todo-table">';

		$this->show_table_headings($priority, $assigned, $deadline, $progress, $categories, $addedby, $editlink);

		// get to-do items
		$results = cleverness_todo_get_todos($user, 0, 0);

		if ($results) {

			foreach ($results as $result) {
				$user_info = get_userdata($result->author);
				$priority_class = '';
		   		if ($result->priority == '0') $priority_class = ' class="todo-important"';
				if ($result->priority == '2') $priority_class = ' class="todo-low"';

				$this->list .= '<tr id="todo-'.$result->id.'" class="'.$priority_class.'">';
				$this->show_checkbox($result);
				$this->show_todo_text($result, $priority_class);
				if ( $priority == 1 ) $this->show_priority($result, $priorities);
				if ( $assigned == 1 ) $this->show_assigned($result);
				if ( $deadline == 1 ) $this->show_deadline($result);
				if ( $progress == 1 ) $this->show_progress($result);
				if ( $categories == 1 ) $this->show_category($result);
				if ( $addedby == 1 ) $this->show_addedby($result, $user_info);
				if ( $editlink == 1 ) $this->show_edit_link($result, $url);
				$this->list .= '</tr>';
				}

		} else {
			/* if there are no to-do items, display this message */
			$this->list .= '<tr><td>'.__('No items to do.', 'cleverness-to-do-list').'</td></tr>';
			}

		$this->list .= '</table>';

		$this->list .= $this->todo_form();

		}

	}

	protected function get_user($current_user, $userdata) {
		$user = ( $this->settings['list_view'] == 2 ? $current_user->ID : $userdata->ID );
		return $user;
		}

	protected function edit_form($result, $url) {
		$url = strtok($url, "?");
		$this->form = '';
    	$this->form .= '<form name="edittodo" id="edittodo" action="'.$url.'" method="post">
	  		<table class="todo-form">';
		$this->priority_field($result);
		$this->assign_field($result);
		$this->deadline_field($result);
		$this->progress_field($result);
		$this->category_field($result);
		$this->todo_field($result);
		$this->form .= '</table>'.wp_nonce_field( 'todoupdate', 'todoupdate', true, false ).'<input type="hidden" name="action" value="updatetodo" />
        	<p class="submit"><input type="submit" name="submit" class="button-primary" value="'. __('Edit To-Do Item', 'cleverness-to-do-list').'" /></p>
			<input type="hidden" name="id" value="'. absint($result->id).'" />';
		$this->form .= '</form>';
		return $this->form;
	}

	protected function todo_form() {
		if (current_user_can($this->settings['add_capability']) || $this->settings['list_view'] == '0') {

   	 	$this->form = '<h3>'.__('Add New To-Do Item', 'cleverness-to-do-list').'</h3>';

    	$this->form .= '<form name="addtodo" id="addtodo" action="" method="post">
	  		<table class="todo-form">';
			$this->priority_field();
			$this->assign_field();
			$this->deadline_field();
			$this->progress_field();
			$this->category_field();
			$this->todo_field();
			$this->form .= '</table>'.wp_nonce_field( 'todoadd', 'todoadd', true, false ).'<input type="hidden" name="action" value="addtodo" />
        	<p class="submit"><input type="submit" name="submit" class="button-primary" value="'. __('Add To-Do Item', 'cleverness-to-do-list').'" /></p>';
		$this->form .= '</form>';

		return $this->form;
		}
	}

	protected function priority_field($result = NULL) {
		$selected = '';
		$this->form .= '<tr>
		  		<th scope="row"><label for="cleverness_todo_priority">'.__('Priority', 'cleverness-to-do-list').'</label></th>
		  		<td>
        			<select name="cleverness_todo_priority">';
					if ( isset($result) ) $selected = ( $result->priority == 0 ? ' selected = "selected"' : '' );
					$this->form .= sprintf('<option value="0"%s>%s</option>', $selected, $this->settings['priority_0']);
					if ( isset($result) ) {
						$selected = ( $result->priority == 1 ? ' selected' : '' );
						} else {
							$selected = ' selected="selected"';
						}
					$this->form .= sprintf('<option value="1"%s>%s</option>', $selected, $this->settings['priority_1']);
					$selected = '';
					if ( isset($result) ) $selected = ( $result->priority == 2 ? ' selected' : '' );
					$this->form .= sprintf('<option value="2"%s>%s</option>', $selected, $this->settings['priority_2']);
        			$this->form .= '</select>
		  		</td>
			</tr>';
		}

	protected function assign_field($result = NULL) {
		if ($this->settings['assign'] == '0' && current_user_can($this->settings['assign_capability'])) {
			$selected = '';
			$this->form .= '<tr>
		  		<th scope="row"><label for="cleverness_todo_assign">'.__('Assign To', 'cleverness-to-do-list').'</label></th>
		  		<td>
					<select name="cleverness_todo_assign" id="cleverness_todo_assign">';
					if ( isset($result->assign) && $result->assign == '-1' ) $selected = ' selected="selected"';
					$this->form .= sprintf('<option value="-1"%s>%s</option>', $selected, __('None', 'cleverness-to-do-list'));

					if ( $this->settings['user_roles'] == '' ) {
						$roles = array('contributor', 'author', 'editor', 'administrator');
					} else {
						$roles = explode(", ", $this->settings['user_roles']);
						}
					foreach ( $roles as $role ) {
						$role_users = cleverness_todo_get_users($role);
						foreach($role_users as $role_user) {
							$user_info = get_userdata($role_user->ID);
							if ( isset($result->assign) && $result->assign == $role_user->ID ) $selected = ' selected="selected"';
							$this->form .= sprintf('<option value="%d"%s>%s</option>', $role_user->ID, $selected, $user_info->display_name);
						}
					}

					$this->form .= '</select>
				</td>
			</tr>';
			}
		}

	protected function deadline_field($result = NULL) {
		if ($this->settings['show_deadline'] == '1') {
			$value = ( isset($result->deadline) && $result->deadline == 0 ? $result->deadline : '' );
			$this->form .= sprintf('<tr>
				<th scope="row"><label for="cleverness_todo_deadline">%s</label></th>
				<td><input type="text" name="cleverness_todo_deadline" id="cleverness_todo_deadline" value="%s" /></td>
			</tr>', __('Deadline', 'cleverness-to-do-list'), $value);
			}
		}

	protected function progress_field($result = NULL) {
		if ($this->settings['show_progress'] == '1') {
			$this->form .= '<tr>
				<th scope="row"><label for="cleverness_todo_progress">'.__('Progress', 'cleverness-to-do-list').'</label></th>
				<td><select name="cleverness_todo_progress">';
				$i = 0;
				while ( $i <= 100 ) {
					$this->form .= '<option value="'.$i.'"';
					if ( isset($result->progress) && $result->progress == $i ) $this->form .= ' selected="selected"';
					$this->form .= '>'.$i.'</option>';
					$i += 5;
				}
				$this->form .= '</select></td>
			</tr>';
			}
		}

	protected function category_field($result = NULL) {
		if ($this->settings['categories'] == '1') {
			$selected = '';
			$this->form .= '<tr>
				<th scope="row"><label for="cleverness_todo_category">'. __('Category', 'cleverness-to-do-list').'</label></th>
				<td><select name="cleverness_todo_category">';
					$cats = cleverness_todo_get_cats();
					foreach ( $cats as $cat ) {
						if ( isset($result->cat_id) && $result->cat_id == $cat->id ) $selected = ' selected="selected"';
						$this->form .= sprintf('<option value="%d"%s>%s</option>', $cat->id, $selected, $cat->name);
						$selected = '';
					 }
					$this->form .= '</select></td>
			</tr>';
			}
		}

	protected function todo_field($result = NULL) {
		$text = ( isset($result) ? stripslashes(esc_html($result->todotext, 1)) : '' );
		$this->form .= sprintf('<tr>
        	<th scope="row" valign="top"><label for="cleverness_todo_description">%s</label></th>
        	<td><textarea name="cleverness_todo_description" rows="5" cols="50" id="the_editor">%s</textarea></td>
			</tr>', __('To-Do', 'cleverness-to-do-list'), $text);
		}

	protected function show_table_headings($priority, $assigned, $deadline, $progress, $categories, $addedby, $editlink) {
		$this->list .= '<thead><tr><th></th><th>'.__('Item', 'cleverness-to-do-list').'</th>';
	  	if ( $priority == 1 ) $this->list .= '<th>'.__('Priority', 'cleverness-to-do-list').'</th>';
		if ( $assigned == 1 && $this->settings['assign'] == 0 ) $this->list .= '<th>'.__('Assigned To', 'cleverness-to-do-list').'</th>';
		if ( $deadline == 1 && $this->settings['show_deadline'] == 1 ) $this->list .= '<th>'.__('Deadline', 'cleverness-to-do-list').'</th>';
		if ( $progress == 1 && $this->settings['show_progress'] == 1 ) $this->list .= '<th>'.__('Progress', 'cleverness-to-do-list').'</th>';
		if ( $categories == 1 && $this->settings['categories'] == 1 ) $this->list .= '<th>'.__('Category', 'cleverness-to-do-list').'</th>';
		if ( $addedby == 1 && $this->settings['list_view'] == 1  && $this->settings['todo_author'] == 0 ) $this->list .= '<th>'.__('Added By', 'cleverness-to-do-list').'</th>';
		if ( $editlink == 1 ) { if ( current_user_can($this->settings['edit_capability']) || $this->settings['list_view'] == 0 ) $this->list .= '<th>'.__('Action', 'cleverness-to-do-list').'</th>'; }
    	$this->list .= '</tr></thead>';
	 	}

	protected function show_checkbox($result, $priority_class = '') {
		$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'complete' );
		if ( $cleverness_todo_permission === true ) {
			$this->list .= sprintf('<td><input type="checkbox" id="ctdl-%d" class="todo-checkbox"/></td>', $result->id);
			}
		}

	protected function show_todo_text($result) {
		$this->list .= '<td>'.stripslashes($result->todotext).'</td>';
		}

	protected function show_edit_link($result, $url) {
		$edit = '';
		$url = $url.'?action=edit-todo&amp;id='.$result->id;
		if (current_user_can($this->settings['edit_capability']) || $this->settings['list_view'] == '0')
			//$edit = '<input class="edit-todo button-secondary" type="button" value="'. __( 'Edit' ).'" />';
			$edit = '<a href="'.$url.'" class="edit-todo">'.__( 'Edit' ).'</a>';
		if (current_user_can($this->settings['delete_capability']) || $this->settings['list_view'] == '0')
			//$edit .= ' <input class="delete-todo button-secondary" type="button" value="'. __( 'Delete' ).'" />';
			$edit .= ' | <a href="" class="delete-todo">'.__( 'Delete' ).'</a>';
	  	if (current_user_can($this->settings['edit_capability'])|| $this->settings['list_view'] == '0')
			$this->list .= '<td>'.$edit.'</td>';
		}

	protected function show_priority($result, $priority) {
		$this->list .= sprintf('<td>%s</td>', $priority[$result->priority]);
		}

	protected function show_assigned($result) {
		if ( ($this->settings['list_view'] == 1 && $this->settings['show_only_assigned'] == 0 && (current_user_can($this->settings['view_all_assigned_capability']))) ||
		($this->settings['list_view'] == 1 && $this->settings['show_only_assigned'] == 1) && $this->settings['assign'] == 0) {
			$assign_user = '';
			if ( $result->assign != '-1' && $result->assign != '' && $result->assign != 0) {
				$assign_user = get_userdata($result->assign);
				$this->list .= '<td>'.$assign_user->display_name.'</td>';
			} else {
				$this->list .= '<td></td>';
				}
			}
   		}

	protected function show_category($result) {
		if ( $this->settings['categories'] == '1' ) {
			$cat = cleverness_todo_get_cat_name($result->cat_id);
			$this->list .= '<td>';
			if ( isset($cat) ) $this->list .= $cat->name;
			$this->list .= '</td>';
			}
		}

	protected function show_addedby($result, $user_info) {
		if ( $this->settings['list_view'] == 1 && $this->settings['todo_author'] == 0 ) {
			$this->list .= ( $result->author != 0 ? sprintf('<td>%s</td>', $user_info->display_name) : '<td></td>' );
			}
		}

	protected function show_deadline($result) {
		if ( $this->settings['show_deadline'] == 1 ) {
			$this->list .= ( $result->deadline != '' ? sprintf('<td>%s</td>', $result->deadline) : '<td></td>' );
			}
		}

	protected function show_progress($result) {
		if ( $this->settings['show_progress'] == 1 ) {
			$this->list .= ( $result->progress != '' ? sprintf('<td>%d%%</td>', $result->progress) : '<td></td>' );
			}
		}

	protected function get_page_url() {
        $pageURL = 'http';
        if ( isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
            $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            //$pageURL .= $_SERVER["HTTP_HOST"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
			$pageURL .= $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }

/* JS and Ajax Setup */
// returns various JavaScript vars needed for the scripts
public function cleverness_todo_checklist_get_js_vars() {
	return array(
	'SUCCESS_MSG' => __('To-Do Deleted.', 'cleverness-to-do-list'),
	'ERROR_MSG' => __('There was a problem performing that action.', 'cleverness-to-do-list'),
	'PERMISSION_MSG' => __('You do not have sufficient privileges to do that.', 'cleverness-to-do-list'),
	'EDIT_TODO' => __('Edit To-Do', 'cleverness-to-do-list'),
	'PUBLIC' => __('Public', 'cleverness-to-do-list'),
	'PRIVATE' => __('Private', 'cleverness-to-do-list'),
	'CONFIRMATION_MSG' => __("You are about to permanently delete the selected item. \n 'Cancel' to stop, 'OK' to delete.", 'cleverness-to-do-list'),
	'NONCE' => wp_create_nonce('cleverness-todo'),
	'AJAX_URL' => admin_url('admin-ajax.php')
	);
}

public function cleverness_todo_checklist_init() {
	wp_register_script( 'cleverness_todo_checklist_complete_js', CTDL_PLUGIN_URL.'/js/frontend-todo.js', '', 1.0, true );
	add_action('wp_enqueue_scripts', array(&$this, 'cleverness_todo_checklist_add_js') );
	add_action('wp_ajax_cleverness_todo_complete', array(&$this, 'cleverness_todo_checklist_complete_callback') );
}

public function cleverness_todo_checklist_add_js() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-color' );
	wp_enqueue_script( 'cleverness_todo_checklist_complete_js' );
	wp_localize_script( 'cleverness_todo_checklist_complete_js', 'ctdl', $this->cleverness_todo_checklist_get_js_vars() );
    }

public function cleverness_todo_checklist_complete_callback() {
	check_ajax_referer( 'cleverness-todo' );
	$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'complete' );

	if ( $cleverness_todo_permission === true ) {
		$cleverness_id = intval($_POST['cleverness_id']);
		$cleverness_status = intval($_POST['cleverness_status']);

		$message = cleverness_todo_complete($cleverness_id, $cleverness_status);
	} else {
		$message = __('You do not have sufficient privileges to do that.', 'cleverness-to-do-list');
	}

	die(); // this is required to return a proper result
}

/* Delete To-Do Ajax */
public function cleverness_todo_delete_callback() {
	check_ajax_referer( 'cleverness-todo' );
	$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'delete' );

	if ( $cleverness_todo_permission === true ) {
		$cleverness_todo_status = cleverness_todo_delete();
	} else {
		$cleverness_todo_status = 2;
		}

	echo $cleverness_todo_status;
	die(); // this is required to return a proper result
}
/* end Delete To-Do Ajax */

} // end class
?>