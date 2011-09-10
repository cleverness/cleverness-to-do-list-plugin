<?php
/* Display Dashboard Widget */
function cleverness_todo_dashboard_widget() {
   	global $wpdb, $userdata, $current_user;
	get_currentuserinfo();

	$cleverness_todo_settings = get_option('cleverness_todo_settings');
	$cleverness_todo_dashboard_settings = get_option('cleverness_todo_dashboard_settings');
	$cat_id = $cleverness_todo_dashboard_settings['dashboard_cat'];

	if ( $cleverness_todo_settings['list_view'] == '2' ) {
		$user = $current_user->ID;
	} else {
		$user = $userdata->ID;
		}
	$limit = $cleverness_todo_dashboard_settings['dashboard_number'];

	// get to-do items
	$results = cleverness_todo_get_todos($user, $limit, 0, 1);

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

			$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'complete' );
			if ( $cleverness_todo_permission === true ) {
				echo '<input type="checkbox" id="ctdl-'.$result->id.'" class="todo-checkbox"/> <span'.$priority_class.'>'.stripslashes($result->todotext).'</span>';
			}

			if ( ($cleverness_todo_settings['list_view'] == '1' && $cleverness_todo_settings['show_only_assigned'] == '0' && (current_user_can($cleverness_todo_settings['view_all_assigned_capability']))) ||  ($cleverness_todo_settings['list_view'] == '1' && $cleverness_todo_settings['show_only_assigned'] == '1') && $cleverness_todo_settings['assign'] == '0') {
				$assign_user = '';
				if ( $result->assign != '-1' && $result->assign != '' && $result->assign != '0') {
					$assign_user = get_userdata($result->assign);
					echo ' <small>['.__('assigned to', 'cleverness-to-do-list').' '.$assign_user->display_name.']</small>';
				}
			}
			if ( $cleverness_todo_dashboard_settings['show_dashboard_deadline'] == '1' && $result->deadline != '' )
				echo ' <small>['.__('Deadline:', 'cleverness-to-do-list').' '.$result->deadline.']</small>';
			if ( $cleverness_todo_settings['show_progress'] == '1' && $result->progress != '' )
				echo ' <small>['.$result->progress.'%]</small>';
			if ( $cleverness_todo_settings['list_view'] == '1' && $cleverness_todo_dashboard_settings['dashboard_author'] == '0' )
				echo ' <small>- '.__('added by', 'cleverness-to-do-list').' '.$user_info->display_name.'</small>';
			if (current_user_can($cleverness_todo_settings['edit_capability']) || $cleverness_todo_settings['list_view'] == '0')
		   		echo ' <small>(<a href="admin.php?page=cleverness-to-do-list&amp;action=edittodo&amp;id='. $result->id . '">'. __('Edit', 'cleverness-to-do-list') . '</a>)</small>';
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

/* Dashboard Widget Options */
function cleverness_todo_dashboard_options() {
	global $wpdb;
	$cleverness_todo_settings = get_option('cleverness_todo_settings');
	$cleverness_todo_dashboard_settings = get_option('cleverness_todo_dashboard_settings');
	if ( isset( $_POST['cleverness_todo_dashboard_settings'] ) ) {
		$cleverness_todo_dashboard_settings = $_POST['cleverness_todo_dashboard_settings'];
		update_option( 'cleverness_todo_dashboard_settings', $cleverness_todo_dashboard_settings );
	}
   	settings_fields( 'cleverness-todo-dashboard-settings-group' );
 	$options = get_option('cleverness_todo_dashboard_settings');
	?>
	<fieldset>
  		<p><label for="cleverness_todo_dashboard_settings[dashboard_number]"><?php _e('Number of List Items to Show', 'cleverness-to-do-list'); ?></label>
			<select id="cleverness_todo_dashboard_settings[dashboard_number]" name="cleverness_todo_dashboard_settings[dashboard_number]">
				<option value="1"<?php if ( $options['dashboard_number'] == '1' ) echo ' selected="selected"'; ?>><?php _e('1', 'cleverness-to-do-list'); ?></option>
				<option value="5"<?php if ( $options['dashboard_number'] == '5' ) echo ' selected="selected"'; ?>><?php _e('5', 'cleverness-to-do-list'); ?></option>
				<option value="10"<?php if ( $options['dashboard_number'] == '10' ) echo ' selected="selected"'; ?>><?php _e('10', 'cleverness-to-do-list'); ?></option>
				<option value="15"<?php if ( $options['dashboard_number'] == '15' ) echo ' selected="selected"'; ?>><?php _e('15', 'cleverness-to-do-list'); ?></option>
				<option value="20"<?php if ( $options['dashboard_number'] == '20' ) echo ' selected="selected"'; ?>><?php _e('20', 'cleverness-to-do-list'); ?>&nbsp;</option>
			</select>
		</p>

		<p><label for="cleverness_todo_dashboard_settings[show_dashboard_deadline]"><?php _e('Show Deadline', 'cleverness-to-do-list'); ?></label>
			<select id="cleverness_todo_dashboard_settings[show_dashboard_deadline]" name="cleverness_todo_dashboard_settings[show_dashboard_deadline]">
				<option value="0"<?php if ( $options['show_dashboard_deadline'] == '0' ) echo ' selected="selected"'; ?>><?php _e('No', 'cleverness-to-do-list'); ?></option>
				<option value="1"<?php if ( $options['show_dashboard_deadline'] == '1' ) echo ' selected="selected"'; ?>><?php _e('Yes', 'cleverness-to-do-list'); ?>&nbsp;</option>
			</select>
		</p>

	 	<p><label for="cleverness_todo_dashboard_settings[dashboard_cat]"><?php _e('Category', 'cleverness-to-do-list'); ?></label>
			<select id="cleverness_todo_dashboard_settings[dashboard_cat]" name="cleverness_todo_dashboard_settings[dashboard_cat]">
		   		<option value="All"<?php if ( 'All' == $options['dashboard_cat'] ) echo ' selected="selected"'; ?>><?php _e('All', 'cleverness-to-do-list'); ?></option>
				<?php
				$results = cleverness_todo_get_cats();
   				if ( $results ) {
   					foreach ( $results as $result ) { ?>
   						<option value="<?php echo $result->id; ?>"<?php if ( $result->id == $options['dashboard_cat'] ) echo ' selected="selected"'; ?>><?php echo $result->name; ?></option>
   					   	<?php
					   	}
   					}
				?>
			</select>
			</p>

		<p class="description"><?php _e('This setting is only used when <em>List View</em> is set to <em>Group</em>.', 'cleverness-to-do-list'); ?></p>
   		<p><label for="cleverness_todo_dashboard_settings[dashboard_author]"><?php _e('Show <em>Added By</em> on Dashboard Widget', 'cleverness-to-do-list'); ?></label>
			<select id="cleverness_todo_dashboard_settings[dashboard_author]" name="cleverness_todo_dashboard_settings[dashboard_author]">
				<option value="0"<?php if ( $options['dashboard_author'] == '0' ) echo ' selected="selected"'; ?>><?php _e('Yes', 'cleverness-to-do-list'); ?>&nbsp;</option>
				<option value="1"<?php if ( $options['dashboard_author'] == '1' ) echo ' selected="selected"'; ?>><?php _e('No', 'cleverness-to-do-list'); ?></option>
			</select>
   		</p>
	</fieldset>
	<?php
}

/* Add Dashboard Widget */
function cleverness_todo_dashboard_setup() {
	$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'view' );
	if ( $cleverness_todo_permission === true ) {
		wp_add_dashboard_widget('cleverness_todo', __( 'To-Do List', 'cleverness-to-do-list' ) . ' <a href="admin.php?page=cleverness-to-do-list">'. __('&raquo;', 'cleverness-to-do-list').'</a>', 'cleverness_todo_dashboard_widget', 'cleverness_todo_dashboard_options');
		}
	}

/* JS and Ajax Setup */
// returns various JavaScript vars needed for the scripts
function cleverness_todo_get_js_vars() {
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
add_action( 'admin_init', 'cleverness_todo_dashboard_init' );

function cleverness_todo_dashboard_init() {
	wp_register_script( 'cleverness_todo_dashboard_complete_js', CTDL_PLUGIN_URL.'/js/complete-todo.js', '', 1.0, true );
	add_action('admin_print_scripts-index.php', 'cleverness_todo_dashboard_add_js');
	add_action('wp_ajax_cleverness_todo_complete', 'cleverness_todo_dashboard_complete_callback');
}

function cleverness_todo_dashboard_add_js() {
	wp_enqueue_script( 'cleverness_todo_dashboard_complete_js' );
	wp_localize_script( 'cleverness_todo_dashboard_complete_js', 'cltd', cleverness_todo_get_js_vars());
    }

function cleverness_todo_dashboard_complete_callback() {
	$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'complete' );

	if ( $cleverness_todo_permission === true ) {
		$cleverness_widget_id = intval($_POST['cleverness_id']);
		$message = cleverness_todo_complete($cleverness_widget_id, '0');
	} else {
		$message = __('You do not have sufficient privileges to do that.', 'cleverness-to-do-list');
	}

	die(); // this is required to return a proper result
}
?>