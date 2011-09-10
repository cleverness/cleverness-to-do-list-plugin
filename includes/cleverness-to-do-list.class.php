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

/*
		<table id="todo-list" class="widefat">
		<thead>
		<tr>
	   		<th><?php _e('Item', 'cleverness-to-do-list'); ?></th>
	  		<th><?php _e('Priority', 'cleverness-to-do-list'); ?></th>
			<?php if ( $cleverness_todo_option['assign'] == '0' ) : ?><th><?php _e('Assigned To', 'cleverness-to-do-list'); ?></th><?php endif; ?>
			<?php if ( $cleverness_todo_option['show_deadline'] == '1' ) : ?><th><?php _e('Deadline', 'cleverness-to-do-list'); ?></th><?php endif; ?>
			<?php if ( $cleverness_todo_option['show_progress'] == '1' ) : ?><th><?php _e('Progress', 'cleverness-to-do-list'); ?></th><?php endif; ?>
			<?php if ( $cleverness_todo_option['categories'] == '1' ) : ?><th><?php _e('Category', 'cleverness-to-do-list'); ?></th><?php endif; ?>
	  		<?php if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' ) : ?><th><?php _e('Added By', 'cleverness-to-do-list'); ?></th><?php endif; ?>
       		<?php if (current_user_can($cleverness_todo_option['edit_capability'])|| $cleverness_todo_option['list_view'] == '0') : ?><th><?php _e('Action', 'cleverness-to-do-list'); ?></th><?php endif; ?>
    	</tr>
		</thead>*/


		// get to-do items
		$results = cleverness_todo_get_todos($user, 0, 0);

		if ($results) {

			foreach ($results as $result) {
				$user_info = get_userdata($result->author);
				$priority_class = '';
		   		if ($result->priority == '0') $priority_class = ' class="todo-important"';
				if ($result->priority == '2') $priority_class = ' class="todo-low"';



				$this->show_checkbox($result);
				$this->show_todo_text($result, $priority_class);
				$this->show_assigned($result);
				$this->show_deadline($result);
				$this->show_progress($result);
				$this->show_addedby($result, $user_info);


				}

		} else {
			/* if there are no to-do items, display this message */
			$this->list .= '<p>'.__('No items to do.', 'cleverness-to-do-list').'</p>';
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
			$this->list .= '<input type="checkbox" id="ctdl-'.$result->id.'" class="todo-checkbox"/>';
			}
		}

	protected function show_todo_text($result) {
		$this->list .= '<td>'.stripslashes($result->todotext).'</td>';
		}

	protected function show_edit_link($result) {
		if ( current_user_can($this->settings['edit_capability']) || $this->settings['list_view'] == '0' ) {
			$this->list .= '<input class="edit-todo button-secondary" type="button" value="'. _e( 'Edit' ).'" />';
			}
		}

	protected function show_assigned($result) {
		if ( ($this->settings['list_view'] == '1' && $this->settings['show_only_assigned'] == '0' && (current_user_can($this->settings['view_all_assigned_capability']))) ||
		($this->settings['list_view'] == '1' && $this->settings['show_only_assigned'] == '1') && $this->settings['assign'] == '0') {
			$assign_user = '';
			if ( $result->assign != '-1' && $result->assign != '' && $result->assign != '0') {
				$assign_user = get_userdata($result->assign);
				$this->list .= '<td>'.$assign_user->display_name.'</td>';
			} else {
				$this->list .= '<td></td>';
				}
			}
   		}

	protected function show_addedby($result, $user_info) {
		if ( $this->settings['list_view'] == '1' && $this->settings['todo_author'] == '0' ) {
			if ( $result->author != '0' ) {
				$this->list .= '<td>'.$user_info->display_name.'</td>';
			} else {
				$this->list .= '<td></td>';
				}
			}
		}

	protected function show_deadline($result) {
		if ( $this->settings['show_deadline'] == '1' && $result->deadline != '' ) {
			$this->list .= '<td>'.$result->deadline.'</td>';
			}
		}

	protected function show_progress($result) {
		if ( $this->settings['show_progress'] == '1' && $result->progress != '' ) {
			$this->list .= '<td>'.$result->progress.'</td>';
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