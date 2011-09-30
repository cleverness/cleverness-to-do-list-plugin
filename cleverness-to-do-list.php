<?php
/*
Plugin Name: Cleverness To-Do List
Version: 2.2.8
Description: Manage to-do list items on a individual or group basis with categories. Includes a dashboard widget and a sidebar widget.
Author: C.M. Kendrick
Author URI: http://cleverness.org
Plugin URI: http://cleverness.org/plugins/to-do-list/
*/

/*
Based on the ToDo plugin by Abstract Dimensions with a patch by WordPress by Example.
*/

add_action('init', 'cleverness_todo_loader');

function cleverness_todo_loader() {

	global $wpdb;
	define( 'CTDL_BASENAME', plugin_basename(__FILE__) );
	define( 'CTDL_PLUGIN_DIR', plugin_dir_path( __FILE__) );
	define( 'CTDL_PLUGIN_URL', plugins_url('', __FILE__) );
	if ( !function_exists( 'is_plugin_active_for_network' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	if ( is_plugin_active_for_network( CTDL_BASENAME ) ) {
		$prefix = $wpdb->base_prefix;
	} else {
		$prefix = $wpdb->prefix;
	}
	define( 'CTDL_TODO_TABLE', $prefix.'todolist' );
	define( 'CTDL_CATS_TABLE', $prefix.'todolist_cats' );
	define( 'CTDL_STATUS_TABLE', $prefix.'todolist_status' );

	include_once 'includes/cleverness-to-do-loader.class.php';
	include_once 'includes/cleverness-to-do-list.class.php';

	ClevernessToDoLoader::init();

	$action = '';
	if ( isset($_GET['action']) ) $action = $_GET['action'];
	if ( isset($_POST['action']) ) $action = $_POST['action'];

	$cleverness_todo_option = get_option('cleverness_todo_settings');

	switch($action) {

	case 'setuptodo':
		cleverness_todo_install();
		break;

	case 'addtodo':
		$message = '';
		if ( $_POST['cleverness_todo_description'] != '' ) {
			$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'add' );

			if ( $cleverness_todo_permission === true ) {
				$assign = (  isset($_POST['cleverness_todo_assign']) ?  $_POST['cleverness_todo_assign'] : 0 );
				$deadline = (  isset($_POST['cleverness_todo_deadline']) ?  $_POST['cleverness_todo_deadline'] : '' );
				$progress = (  isset($_POST['cleverness_todo_progress']) ?  $_POST['cleverness_todo_progress'] : 0 );
				$category = (  isset($_POST['cleverness_todo_category']) ?  $_POST['cleverness_todo_category'] : '' );

				if (!wp_verify_nonce($_REQUEST['todoadd'], 'todoadd') ) die('Security check failed');
				if ( $cleverness_todo_option['email_assigned'] == '1' && $cleverness_todo_option['assign'] == '0' ) {
					$message = cleverness_todo_email_user($todotext, $priority, $assign, $deadline, $category);
					}
				$message .= cleverness_todo_insert($assign, $deadline, $progress, $category);
			} else {
		   		$message = __('You do not have sufficient privileges to add an item.', 'cleverness-to-do-list');
			}

		} else {
			$message = __('To-Do cannot be blank.', 'cleverness-to-do-list');
		}
		break;

	case 'updatetodo':
		$message = '';
		$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'edit' );

		if ( $cleverness_todo_permission === true ) {
			$assign = ( isset($_POST['cleverness_todo_assign']) ?  $_POST['cleverness_todo_assign'] : 0 );
			$deadline = ( isset($_POST['cleverness_todo_deadline']) ?  $_POST['cleverness_todo_deadline'] : '' );
			$progress = ( isset($_POST['cleverness_todo_progress']) ?  $_POST['cleverness_todo_progress'] : 0 );
			$category = ( isset($_POST['cleverness_todo_category']) ?  $_POST['cleverness_todo_category'] : '' );
			if (!wp_verify_nonce($_REQUEST['todoupdate'], 'todoupdate') ) die('Security check failed');
			$message = cleverness_todo_update($assign, $deadline, $progress, $category);
		} else {
			$message = __('You do not have sufficient privileges to edit an item.', 'cleverness-to-do-list');
			}
		break;

	case 'completetodo':
		$id = absint($_GET['id']);
		$cleverness_todo_complete_nonce = $_REQUEST['_wpnonce'];
		if ( !wp_verify_nonce($cleverness_todo_complete_nonce, 'todocomplete') ) die('Security check failed');
		$message = cleverness_todo_complete($id, '1');
		break;

	case 'uncompletetodo':
		$id = absint($_GET['id']);
		$cleverness_todo_complete_nonce = $_REQUEST['_wpnonce'];
		if ( !wp_verify_nonce($cleverness_todo_complete_nonce, 'todocomplete') ) die('Security check failed');
		$message = cleverness_todo_complete($id, '0');
		break;

	case 'purgetodo':
		$message = '';
		$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'purge' );

		if ( $cleverness_todo_permission === true ) {
			$cleverness_todo_purge_nonce = $_REQUEST['_wpnonce'];
			if ( !wp_verify_nonce($cleverness_todo_purge_nonce, 'todopurge') ) die('Security check failed');
			$message = cleverness_todo_purge();
		} else {
			$message = __('You do not have sufficient privileges to edit an item.', 'cleverness-to-do-list');
			}
		break;

} // end switch

}

/* Create admin page */
function cleverness_todo_subpanel() {
   	global $wpdb, $userdata, $cleverness_todo_option, $message, $current_user, $ClevernessToDoList;
   	get_currentuserinfo();

   	$priority = array(0 => $cleverness_todo_option['priority_0'] , 1 => $cleverness_todo_option['priority_1'], 2 => $cleverness_todo_option['priority_2']);
	?>

	<?php if ( isset($message) ) : ?>
		<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
	<?php endif; ?>

	<?php
	//$ClevernessToDoList = new ClevernessToDoList;
	$ClevernessToDoList->display($cleverness_todo_option);
	echo $ClevernessToDoList->list;


   		if ( $cleverness_todo_option['list_view'] == '2' ) {
			$user = $current_user->ID;
		} else {
			$user = $userdata->ID;
		}

?>

	<div class="wrap">
		<h3><?php _e('Completed Items', 'cleverness-to-do-list'); ?>
		<?php if (current_user_can($cleverness_todo_option['purge_capability']) || $cleverness_todo_option['list_view'] == '0') : ?>
		<?php $cleverness_todo_purge_nonce = wp_create_nonce('todopurge'); ?>
			(<a href="admin.php?page=cleverness-to-do-list&amp;action=purgetodo&_wpnonce=<?php echo $cleverness_todo_purge_nonce; ?>"><?php _e('Delete All', 'cleverness-to-do-list'); ?></a>)
		<?php endif; ?>
		</h3>
		<table id="todo-list-completed" class="widefat">
		<thead>
		<tr>
	   		<th><?php _e('Item', 'cleverness-to-do-list'); ?></th>
	   		<th><?php _e('Priority', 'cleverness-to-do-list'); ?></th>
			<?php if ( $cleverness_todo_option['assign'] == '0' ) : ?><th><?php _e('Assigned To', 'cleverness-to-do-list'); ?></th><?php endif; ?>
			<?php if ( $cleverness_todo_option['show_deadline'] == '1' ) : ?><th><?php _e('Deadline', 'cleverness-to-do-list'); ?></th><?php endif; ?>
			<?php if ( $cleverness_todo_option['show_completed_date'] == '1' ) : ?><th><?php _e('Completed', 'cleverness-to-do-list'); ?></th><?php endif; ?>
			<?php if ( $cleverness_todo_option['categories'] == '1' ) : ?><th><?php _e('Category', 'cleverness-to-do-list'); ?></th><?php endif; ?>
	   		<?php if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' ) : ?><th><?php _e('Added By', 'cleverness-to-do-list'); ?></th><?php endif; ?>
       		<?php if (current_user_can($cleverness_todo_option['delete_capability']) || $cleverness_todo_option['list_view'] == '0') : ?><th><?php _e('Action', 'cleverness-to-do-list'); ?></th><?php endif; ?>
    	</tr>
		</thead>
		<?php
		// individual view
		$results = cleverness_todo_get_todos($user, 0, 1);

   		if ($results) {
   			$class = '';
	   		foreach ($results as $result) {
		   		$class = ('alternate' == $class) ? '' : 'alternate';
		   		$prstr = $priority[ $result->priority ];
		   		$user_info = get_userdata($result->author);
				$edit = '';
				if (current_user_can($cleverness_todo_option['delete_capability']) || $cleverness_todo_option['list_view'] == '0')
		   			$edit = '<input class="delete-todo button-secondary" type="button" value="'. __( 'Delete' ).'" />';
		   		echo '<tr id="todo-'.$result->id.'" class="'.$class.'">
			   	<td><input type="checkbox" id="cltd-'.$result->id.'" class="todo-checkbox completed" checked="checked" />&nbsp;'.stripslashes($result->todotext).'</td>
			   	<td>'.$prstr.'</td>';
				if ( $cleverness_todo_option['assign'] == '0' ) {
					$assign_user = '';
					if ( $result->assign != '-1' && $result->assign != '0' ) {
						$assign_user = get_userdata($result->assign);
						echo '<td>'.$assign_user->display_name.'</td>';
					} else {
						echo '<td></td>';
						}
					}
				if ( $cleverness_todo_option['show_deadline'] == '1' )
					echo '<td>'.$result->deadline.'</td>';
				if ( $cleverness_todo_option['show_completed_date'] == '1' ) {
					$date = '';
					if ( $result->completed != '0000-00-00 00:00:00' )
						$date = date($cleverness_todo_option['date_format'], strtotime($result->completed));
					echo '<td>'.$date.'</td>';
					}
				if ( $cleverness_todo_option['categories'] == '1' ) {
					$cat = cleverness_todo_get_cat_name($result->cat_id);
					if ( $cat != '' ) {
						echo '<td>'.$cat->name.'</td>';
						} else {
							echo '<td></td>';
							}
					}
		   		if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' ) {
				if ($result->author != '0') {
		   			echo '<td>'.$user_info->display_name.'</td>';
					} else {
						echo '<td></td>';
						}
					}
		  		if (current_user_can($cleverness_todo_option['delete_capability']) || $cleverness_todo_option['list_view'] == '0')
					 echo '<td>'.$edit.'</td>
			 	</tr>';
	  	 		}
   		} else {
	  		echo '<tr><td ';
			$colspan = 2;
	   		if ( $cleverness_todo_option['assign'] == '0' ) $colspan += 1;
			if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' ) $colspan += 1;
			if ( $cleverness_todo_option['show_deadline'] == '1' ) $colspan += 1;
			if ( $cleverness_todo_option['show_completed_date'] == '1' ) $colspan += 1;
			if ( $cleverness_todo_option['categories'] == '1' ) $colspan += 1;
			if ( current_user_can($cleverness_todo_option['delete_capability']) || $cleverness_todo_option['list_view'] == '0' ) $colspan += 1;
			echo 'colspan="'.$colspan.'"';
	  	 	echo '>'.__('There are no completed items', 'cleverness-to-do-list').'</td></tr>';
   		}
		?>
   		</table>
	</div>


	<?php

}

function cleverness_todo_get_users($role) {
      $wp_user_search = new WP_User_Query( array( 'role' => $role ) );
      return $wp_user_search->get_results();
}

add_action('wp_ajax_cleverness_todo_delete', 'cleverness_todo_delete_todo_callback');

/* Delete To-Do Ajax */
function cleverness_todo_delete_todo_callback() {
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

/* Add plugin info to admin footer */
function cleverness_todo_admin_footer() {
	$plugin_data = get_plugin_data(__FILE__);
	printf(__("%s plugin | Version %s | by %s<br />", 'cleverness-to-do-list'), $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
	}

/* Add Settings link to plugin */
function cleverness_add_settings_link($links, $file) {
	static $this_plugin;
	if (!$this_plugin) $this_plugin = CTDL_BASENAME;

	if ($file == $this_plugin){
		$settings_link = '<a href="admin.php?page=cleverness-to-do-list-options">'.__('Settings', 'cleverness-to-do-list').'</a>';
	 	array_unshift($links, $settings_link);
		}
	return $links;
}
?>