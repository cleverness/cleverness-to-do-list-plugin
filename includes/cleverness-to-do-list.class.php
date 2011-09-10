<?php
/* Main class */

class ClevernessToDoList {
	protected $settings;

	public function __construct($settings) {
		add_action( 'init', array(&$this, 'cleverness_todo_checklist_init') );
		$this->settings = $settings;
		}

public function display() {
	global $wpdb, $cleverness_todo_option, $userdata, $current_user;
	get_currentuserinfo();

	$priority = array(0 => $cleverness_todo_option['priority_0'] , 1 => $cleverness_todo_option['priority_1'], 2 => $cleverness_todo_option['priority_2']);


	$cleverness_todo_settings = get_option('cleverness_todo_settings');

	$user = $this->get_user($current_user, $userdata);

	// get to-do items
	$results = cleverness_todo_get_todos($user, 0);

	if ($results) {
		$catid = '';
		foreach ($results as $result) {
			$user_info = get_userdata($result->author);
			$priority_class = '';
		   	if ($result->priority == '0') $priority_class = ' class="todo-important"';
			if ($result->priority == '2') $priority_class = ' class="todo-low"';

			if ( $cleverness_todo_settings['categories'] == '1' && $result->cat_id != 0 ) {
				$cat = cleverness_todo_get_cat_name($result->cat_id);
				if ( $catid != $result->cat_id  && $cat->name != '' ) echo '<h4>'.$cat->name.'</h4>';
				$catid = $result->cat_id;
			}
			echo '<p id="todo-'.$result->id.'">';

			$this->show_checkbox($result);
			$this->show_todo_text($result, $priority_class);
			$this->show_assigned($result);
			$this->show_deadline($result);
			$this->show_progress($result);
			$this->show_addedby($result, $user_info);
			$this->show_edit_link($result);

			echo '</p>';
			}
	} else {
		echo '<p>'.__('No items to do.', 'cleverness-to-do-list').'</p>';
		}
	$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'add' );
	if ( $cleverness_todo_permission === true ) {
		echo '<p style="text-align: right">'. '<a href="admin.php?page=cleverness-to-do-list#addtodo">'. __('New To-Do Item &raquo;', 'cleverness-to-do-list').'</a></p>';
	}

}

	protected function get_user($current_user, $userdata) {
		if ( $this->settings['list_view'] == '2' ) {
			$user = $current_user->ID;
		} else {
		   	$user = $userdata->ID;
		   	}
		return $user;
		}

	protected function show_checkbox($result, $priority_class = '') {
		$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'complete' );
		if ( $cleverness_todo_permission === true ) {
			echo '<input type="checkbox" id="ctdl-'.$result->id.'" class="todo-checkbox"/>';
			}
		}

	protected function show_todo_text($result) {
		echo '<td>'.stripslashes($result->todotext).'</td>';
		}

	protected function show_edit_link($result) {
		if ( current_user_can($this->settings['edit_capability']) || $this->settings['list_view'] == '0' ) {
			echo '<input class="edit-todo button-secondary" type="button" value="'. _e( 'Edit' ).'" />';
			}
		}

	protected function show_assigned($result) {
		if ( ($this->settings['list_view'] == '1' && $this->settings['show_only_assigned'] == '0' && (current_user_can($this->settings['view_all_assigned_capability']))) ||
		($this->settings['list_view'] == '1' && $this->settings['show_only_assigned'] == '1') && $this->settings['assign'] == '0') {
			$assign_user = '';
			if ( $result->assign != '-1' && $result->assign != '' && $result->assign != '0') {
				$assign_user = get_userdata($result->assign);
				echo '<td>'.$assign_user->display_name.'</td>';
			} else {
				echo '<td></td>';
				}
			}
   		}

	protected function show_addedby($result, $user_info) {
		if ( $this->settings['list_view'] == '1' && $this->settings['todo_author'] == '0' ) {
			if ( $result->author != '0' ) {
				echo '<td>'.$user_info->display_name.'</td>';
			} else {
				echo '<td></td>';
				}
			}
		}

	protected function show_deadline($result) {
		if ( $this->settings['show_deadline'] == '1' && $result->deadline != '' ) {
			echo '<td>'.$result->deadline.'</td>';
			}
		}

	protected function show_progress($result) {
		if ( $this->settings['show_progress'] == '1' && $result->progress != '' ) {
			echo '<td>'.$result->progress.'</td>';
			}
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
	wp_register_script( 'cleverness_todo_checklist_complete_js', CTDL_PLUGIN_URL.'/js/complete-todo.js', '', 1.0, true );
	add_action('wp_enqueue_scripts', array(&$this, 'cleverness_todo_checklist_add_js') );
	add_action('wp_ajax_cleverness_todo_complete', array(&$this, 'cleverness_todo_checklist_complete_callback') );
}

public function cleverness_todo_checklist_add_js() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'cleverness_todo_checklist_complete_js' );
	wp_localize_script( 'cleverness_todo_checklist_complete_js', 'cltd', $this->cleverness_todo_checklist_get_js_vars() );
    }

public function cleverness_todo_checklist_complete_callback() {
	$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'complete' );

	if ( $cleverness_todo_permission === true ) {
		$cleverness_widget_id = intval($_POST['cleverness_id']);
		$message = cleverness_todo_complete($cleverness_widget_id, 1);
	} else {
		$message = __('You do not have sufficient privileges to do that.', 'cleverness-to-do-list');
	}

	die(); // this is required to return a proper result
}
}


?>