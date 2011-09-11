<?php
/* Main class */

class ClevernessToDoList {
	protected $settings;
	protected $cat_id = '';
	protected $list = '';

	public function __construct($settings) {
		add_action( 'init', array(&$this, 'cleverness_todo_checklist_init') );
		$this->settings = $settings;
		}

	public function display($title = '') {
		global $wpdb, $cleverness_todo_option, $userdata, $current_user;
		get_currentuserinfo();

		$priority = array(0 => $this->settings['priority_0'] , 1 => $this->settings['priority_1'], 2 => $this->settings['priority_2']);
		$user = $this->get_user($current_user, $userdata);

		if (current_user_can($this->settings['add_capability']) || $this->settings['list_view'] == '0') {
			$this->list .= '<a href="#addtd">'.__('Add New Item', 'cleverness-to-do-list').'</a>';
		 	}

		$this->list .= '<table id="todo-list" class="todo-table">';

		$this->show_table_headings();

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
				$this->show_assigned($result);
				$this->show_deadline($result);
				$this->show_progress($result);
				$this->show_category($result);
				$this->show_addedby($result, $user_info);
				$this->show_edit_link();
				$this->list .= '</tr>';
				}

		} else {
			/* if there are no to-do items, display this message */
			$this->list .= '<tr><td>'.__('No items to do.', 'cleverness-to-do-list').'</td></tr>';
			}

		$this->list .= '</table>';

	}

	protected function get_user($current_user, $userdata) {
		$user = ( $this->settings['list_view'] == 2 ? $current_user->ID : $userdata->ID );
		return $user;
		}

	protected function show_table_headings() {
		$this->list .= '<thead><tr>
	   		<th>'.__('Item', 'cleverness-to-do-list').'</th>
	  		<th>'.__('Priority', 'cleverness-to-do-list').'</th>';
		if ( $this->settings['assign'] == 0 ) $this->list .= '<th>'.__('Assigned To', 'cleverness-to-do-list').'</th>';
		if ( $this->settings['show_deadline'] == 1 ) $this->list .= '<th>'.__('Deadline', 'cleverness-to-do-list').'</th>';
		if ( $this->settings['show_progress'] == 1 ) $this->list .= '<th>'.__('Progress', 'cleverness-to-do-list').'</th>';
		if ( $this->settings['categories'] == 1 ) $this->list .= '<th>'.__('Category', 'cleverness-to-do-list').'</th>';
		if ( $this->settings['list_view'] == 1  && $this->settings['todo_author'] == 0 ) $this->list .= '<th>'.__('Added By', 'cleverness-to-do-list').'</th>';
		if ( current_user_can($this->settings['edit_capability']) || $this->settings['list_view'] == 0 ) $this->list .= '<th>'.__('Action', 'cleverness-to-do-list').'</th>';
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

	protected function show_edit_link() {
		$edit = '';
		if (current_user_can($this->settings['edit_capability']) || $this->settings['list_view'] == '0')
			//$edit = '<input class="edit-todo button-secondary" type="button" value="'. __( 'Edit' ).'" />';
			$edit = '<a href="" class="edit-todo">'.__( 'Edit' ).'</a>';
		if (current_user_can($this->settings['delete_capability']) || $this->settings['list_view'] == '0')
			//$edit .= ' <input class="delete-todo button-secondary" type="button" value="'. __( 'Delete' ).'" />';
			$edit .= ' | <a href="" class="delete-todo">'.__( 'Delete' ).'</a>';
	  	if (current_user_can($this->settings['edit_capability'])|| $this->settings['list_view'] == '0')
			$this->list .= '<td>'.$edit.'</td>';
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
			$this->list .= ( $result->progress != '' ? sprintf('<td>%d</td>', $result->progress) : '<td></td>' );
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
	wp_register_script( 'cleverness_todo_checklist_complete_js', CTDL_PLUGIN_URL.'/js/frontend-todo.js', '', 1.0, true );
	add_action('wp_enqueue_scripts', array(&$this, 'cleverness_todo_checklist_add_js') );
	add_action('wp_ajax_cleverness_todo_complete', array(&$this, 'cleverness_todo_checklist_complete_callback') );
}

public function cleverness_todo_checklist_add_js() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-color' );
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