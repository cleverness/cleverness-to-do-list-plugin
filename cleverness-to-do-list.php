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

//add_action('init', 'cleverness_todo_loader');
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
global $ClevernessToDoList;
add_action('init', 'ClevernessToDoLoader::init' );

function cleverness_todo_loader() {

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
			//$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'add_todo' ); NEED TO FIX
			$cleverness_todo_permission = true;

			if ( $cleverness_todo_permission === true ) {
				$assign = (  isset($_POST['cleverness_todo_assign']) ?  $_POST['cleverness_todo_assign'] : 0 );
				$deadline = (  isset($_POST['cleverness_todo_deadline']) ?  $_POST['cleverness_todo_deadline'] : '' );
				$progress = (  isset($_POST['cleverness_todo_progress']) ?  $_POST['cleverness_todo_progress'] : 0 );
				$category = (  isset($_POST['cleverness_todo_category']) ?  $_POST['cleverness_todo_category'] : '' );

		   		require_once (ABSPATH . WPINC . '/pluggable.php'); // NEED TO REMOVE
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
		$assign = ( isset($_POST['cleverness_todo_assign']) ?  $_POST['cleverness_todo_assign'] : 0 );
		$deadline = ( isset($_POST['cleverness_todo_deadline']) ?  $_POST['cleverness_todo_deadline'] : '' );
		$progress = ( isset($_POST['cleverness_todo_progress']) ?  $_POST['cleverness_todo_progress'] : 0 );
		$category = ( isset($_POST['cleverness_todo_category']) ?  $_POST['cleverness_todo_category'] : '' );
		require_once (ABSPATH . WPINC . '/pluggable.php');
		if (!wp_verify_nonce($_REQUEST['todoupdate'], 'todoupdate') ) die('Security check failed');
		$message = cleverness_todo_update($assign, $deadline, $progress, $category);
		break;

	case 'completetodo':
		$id = absint($_GET['id']);
		$message = cleverness_todo_complete($id, '1');
		break;

	case 'uncompletetodo':
		$id = absint($_GET['id']);
		$message = cleverness_todo_complete($id, '0');
		break;

	case 'purgetodo':
		$message = cleverness_todo_purge();
		break;

} // end switch

}

/* Create admin page */
function cleverness_todo_subpanel() {
   	global $wpdb, $userdata, $cleverness_todo_option, $message, $current_user;
   	get_currentuserinfo();

   	$priority = array(0 => $cleverness_todo_option['priority_0'] , 1 => $cleverness_todo_option['priority_1'], 2 => $cleverness_todo_option['priority_2']);
	?>

	<?php if ( isset($message) ) : ?>
		<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
	<?php endif; ?>

	<?php
	/* Display this section if editing an existing to-do item */
	$action = ( isset($_GET['action']) ? $_GET['action'] : '' );
	if ($action == 'edittodo') {
    	$id = absint($_GET['id']);
    	$todo = cleverness_todo_get_todo($id);
	?>

	<div class="wrap">
 		<div class="icon32"><img src="<?php echo CTDL_PLUGIN_URL; ?>/images/cleverness-todo-icon.png" alt="" /></div> <h2><?php _e('To-Do List', 'cleverness-to-do-list'); ?></h2>
 		<h3><?php _e('Edit To-Do Item', 'cleverness-to-do-list') ?></h3>
 		<form name="edittodo" action="" method="post">
	  		<table class="form-table">
			<tr>
		  		<th scope="row"><label for="cleverness_todo_priority"><?php _e('Priority', 'cleverness-to-do-list') ?></label></th>
		  		<td>
					<select name="cleverness_todo_priority">
					<option value="0" <?php if ($todo->priority == 0) { echo "selected"; } ?>><?php echo $cleverness_todo_option['priority_0']; ?>&nbsp;</option>
					<option value="1" <?php if ($todo->priority == 1) { echo "selected"; } ?>><?php echo $cleverness_todo_option['priority_1']; ?></option>
					<option value="2" <?php if ($todo->priority == 2) { echo "selected"; } ?>><?php echo $cleverness_todo_option['priority_2']; ?></option>
					</select>
					<input type="hidden" name="id" value="<?php echo absint($todo->id); ?>" />
				</td>
			</tr>
			<?php if ($cleverness_todo_option['assign'] == '0' && current_user_can($cleverness_todo_option['assign_capability'])) : ?>
			<tr>
		  		<th scope="row"><label for="cleverness_todo_assign"><?php _e('Assign To', 'cleverness-to-do-list') ?></label></th>
		  		<td>
				<select name='cleverness_todo_assign' id='cleverness_todo_assign' class=''>
					<option value='-1'<?php if ( isset($todo->assign) && $todo->assign == '-1' ) echo ' selected="selected"'; ?>><?php _e('None', 'cleverness-to-do-list') ?></option>
					<?php
					if ( $cleverness_todo_option['user_roles'] == '' ) $roles = array('contributor', 'author', 'editor', 'administrator');
					else $roles = explode(", ", $cleverness_todo_option['user_roles']);
					foreach ( $roles as $role ) {
						$role_users = cleverness_todo_get_users($role);
						foreach($role_users as $role_user){
							$user_info = get_userdata($role_user->ID);
							echo '<option value="'.$role_user->ID.'"';
							if ( isset($todo->assign) && $todo->assign == $role_user->ID ) echo ' selected="selected"';
							echo '>'.$user_info->display_name.'</option>';
						}
					}
					?>
				</select>
				</td>
			</tr>
			<?php endif; ?>
			<?php if ($cleverness_todo_option['assign'] == '0' && !current_user_can($cleverness_todo_option['assign_capability'])) : ?>
				<input type="hidden" name='cleverness_todo_assign' id='cleverness_todo_assign' value="<?php echo $todo->assign; ?>" />
			<?php endif; ?>
			<?php if ($cleverness_todo_option['show_deadline'] == '1') : ?>
			<tr><th scope="row"><label for="cleverness_todo_deadline"><?php _e('Deadline', 'cleverness-to-do-list') ?></label></th>
				<td><input type="text" name="cleverness_todo_deadline" id="cleverness_todo_deadline" value="<?php if ( isset($todo->deadline) ) echo esc_html($todo->deadline, 1); ?>" /></td>
			</tr>
			<?php endif; ?>
			<?php if ($cleverness_todo_option['show_progress'] == '1') : ?>
				<tr><th scope="row"><label for="cleverness_todo_progress"><?php _e('Progress', 'cleverness-to-do-list') ?></label></th>
				<td><select name="cleverness_todo_progress">
				<?php
				$i = 0;
				while ( $i <= 100 ) {
					echo '<option value="'.$i.'">'.$i.'</option>';
					$i += 5;
				} ?>
				</select></td>
			</tr>
			<?php endif; ?>
			<?php if ($cleverness_todo_option['categories'] == '1') : ?>
			<tr>
				<th scope="row"><label for="cleverness_todo_category"><?php _e('Category', 'cleverness-to-do-list') ?></label></th>
				<td><select name="cleverness_todo_category">
					<?php $cats = cleverness_todo_get_cats();
					foreach ( $cats as $cat ) { ?>
					<option value="<?php echo $cat->id; ?>"<?php if ( isset($todo->cat_id) && $todo->cat_id == $cat->id ) echo ' selected="selected"'; ?>><?php echo $cat->name; ?></option>
					<?php } ?>
					</select></td>
			</tr>
			<?php endif; ?>
	   		<tr>
				<th scope="row" valign="top"><label for="cleverness_todo_description"><?php _e('To-Do', 'cleverness-to-do-list') ?></label></th>
				<td><textarea name="cleverness_todo_description" rows="5" cols="50"><?php echo stripslashes(esc_html($todo->todotext, 1)); ?></textarea></td>
			</tr>
			</table>
			<?php echo wp_nonce_field( 'todoupdate', 'todoupdate', true, false ); ?>
			<input type="hidden" name="action" value="updatetodo" />
	  		<p class="submit"><input type="submit" name="submit" class="button-primary" value="<?php _e('Edit To-Do Item', 'cleverness-to-do-list') ?> &raquo;" /></p>
 		</form>
 		<p><a href="admin.php?page=cleverness-to-do-list"><?php _e('&laquo; Return to To-Do List', 'cleverness-to-do-list'); ?></a></p>
	</div>

	<?php
	} else {
	/* Display the to-do list items */
	?>

	<div class="wrap">
   		<div class="icon32"><img src="<?php echo CTDL_PLUGIN_URL; ?>/images/cleverness-todo-icon.png" alt="" /></div>	<h2><?php _e('To-Do List', 'cleverness-to-do-list'); ?></h2>
		<h3><?php _e('To-Do Items', 'cleverness-to-do-list'); ?>
		<?php if (current_user_can($cleverness_todo_option['add_capability']) || $cleverness_todo_option['list_view'] == '0') : ?>
			(<a href="#addtd"><?php _e('Add New Item', 'cleverness-to-do-list'); ?></a>)
		<?php endif; ?>
		</h3>
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
		</thead>
		<?php
   		if ( $cleverness_todo_option['list_view'] == '2' ) {
			$user = $current_user->ID;
		} else {
			$user = $userdata->ID;
		}

		// get to-do items
		$results = cleverness_todo_get_todos($user);

   		if ($results) {
	   		foreach ($results as $result) {
		   		$prstr = $priority[ $result->priority ];
		   		$priority_class = '';
		   		$user_info = get_userdata($result->author);
		   		if ($result->priority == '0') $priority_class = ' todo-important';
				if ($result->priority == '2') $priority_class = ' todo-low';
				$edit = '';
				if (current_user_can($cleverness_todo_option['edit_capability']) || $cleverness_todo_option['list_view'] == '0')
		  			$edit = '<input class="edit-todo button-secondary" type="button" value="'. __( 'Edit' ).'" />';
				if (current_user_can($cleverness_todo_option['delete_capability']) || $cleverness_todo_option['list_view'] == '0')
					$edit .= ' <input class="delete-todo button-secondary" type="button" value="'. __( 'Delete' ).'" />';
		   		echo '<tr id="todo-'.$result->id.'" class="'.$priority_class.'">';
				echo '<td><input type="checkbox" id="cltd-'.$result->id.'" class="todo-checkbox uncompleted" />&nbsp;'.stripslashes($result->todotext).'</td>
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
				if ( $cleverness_todo_option['show_progress'] == '1' ) {
					echo '<td>'.$result->progress;
					if ( $result->progress != '' ) echo '%';
					echo '</td>';
					}
				if ( $cleverness_todo_option['categories'] == '1' ) {
					$cat = cleverness_todo_get_cat_name($result->cat_id);
					echo '<td>';
					if (isset($cat)) echo $cat->name;
					echo '</td>';
					}
		   		if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' ) {
		   			if ($result->author != '0') {
		   			echo '<td>'.$user_info->display_name.'</td>';
					} else {
						echo '<td></td>';
						}
					}
		   		if (current_user_can($cleverness_todo_option['edit_capability'])|| $cleverness_todo_option['list_view'] == '0')
					echo '<td>'.$edit.'</td></tr>';
	   		}
   		} else {
	   		echo '<tr><td ';
	   		$colspan = 2;
	   		if ( $cleverness_todo_option['assign'] == '0' ) $colspan += 1;
			if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' ) $colspan += 1;
			if ( $cleverness_todo_option['show_deadline'] == '1' ) $colspan += 1;
			if ( $cleverness_todo_option['show_progress'] == '1' ) $colspan += 1;
			if ( $cleverness_todo_option['categories'] == '1' ) $colspan += 1;
			if ( current_user_can($cleverness_todo_option['edit_capability']) || $cleverness_todo_option['list_view'] == '0' ) $colspan += 1;
			echo 'colspan="'.$colspan.'"';
	   		echo '>'.__('There is nothing to do...', 'cleverness-to-do-list').'</td></tr>';
   			}
		?>
		</table>
	</div>

	<div class="wrap">
		<h3><?php _e('Completed Items', 'cleverness-to-do-list'); ?>
		<?php if (current_user_can($cleverness_todo_option['purge_capability']) || $cleverness_todo_option['list_view'] == '0') : ?>
			(<a href="admin.php?page=cleverness-to-do-list&amp;action=purgetodo"><?php _e('Delete All', 'cleverness-to-do-list'); ?></a>)
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

	<?php // add new to-do
	if (current_user_can($cleverness_todo_option['add_capability']) || $cleverness_todo_option['list_view'] == '0') : ?>
	<div class="wrap">
   	 	<h3><?php _e('Add New To-Do Item', 'cleverness-to-do-list') ?></h3>
    	<form name="addtodo" id="addtodo" action="" method="post">
	  		<table class="form-table">
			<tr>
		  		<th scope="row"><label for="cleverness_todo_priority"><?php _e('Priority', 'cleverness-to-do-list') ?></label></th>
		  		<td>
        			<select name="cleverness_todo_priority">
       	 				<option value="0"><?php echo $cleverness_todo_option['priority_0']; ?>&nbsp;</option>
        				<option value="1" selected="selected"><?php echo $cleverness_todo_option['priority_1']; ?></option>
       	 		   		<option value="2"><?php echo $cleverness_todo_option['priority_2']; ?></option>
        			</select>
		  		</td>
			</tr>
			<?php if ($cleverness_todo_option['assign'] == '0' && current_user_can($cleverness_todo_option['assign_capability'])) : ?>
			<tr>
		  		<th scope="row"><label for="cleverness_todo_assign"><?php _e('Assign To', 'cleverness-to-do-list') ?></label></th>
		  		<td>
					<select name='cleverness_todo_assign' id='cleverness_todo_assign' class=''>
					<option value='-1'><?php _e('None', 'cleverness-to-do-list') ?></option>
					<?php
					if ( $cleverness_todo_option['user_roles'] == '' ) $roles = array('contributor', 'author', 'editor', 'administrator');
					else $roles = explode(", ", $cleverness_todo_option['user_roles']);
					foreach ( $roles as $role ) {
						$role_users = cleverness_todo_get_users($role);
						foreach($role_users as $role_user){
							$user_info = get_userdata($role_user->ID);
							echo '<option value="'.$role_user->ID.'">'.$user_info->display_name.'</option>';
						}
					}
					?>
					</select>
				</td>
			</tr>
			<?php endif; ?>
			<?php if ($cleverness_todo_option['show_deadline'] == '1') : ?>
			<tr>
				<th scope="row"><label for="cleverness_todo_deadline"><?php _e('Deadline', 'cleverness-to-do-list') ?></label></th>
				<td><input type="text" name="cleverness_todo_deadline" id="cleverness_todo_deadline" value="" /></td>
			</tr>
			<?php endif; ?>
			<?php if ($cleverness_todo_option['show_progress'] == '1') : ?>
			<tr>
				<th scope="row"><label for="cleverness_todo_progress"><?php _e('Progress', 'cleverness-to-do-list') ?></label></th>
				<td><select name="cleverness_todo_progress">
				<?php
				$i = 0;
				while ( $i <= 100 ) {
					echo '<option value="'.$i.'">'.$i.'</option>';
					$i += 5;
				} ?>
				</select></td>
			</tr>
			<?php endif; ?>
			<?php if ($cleverness_todo_option['categories'] == '1') : ?>
			<tr>
				<th scope="row"><label for="cleverness_todo_category"><?php _e('Category', 'cleverness-to-do-list') ?></label></th>
				<td><select name="cleverness_todo_category">
					<?php $cats = cleverness_todo_get_cats();
					foreach ( $cats as $cat ) { ?>
					<option value="<?php echo $cat->id; ?>"><?php echo $cat->name; ?></option>
					<?php } ?>
					</select></td>
			</tr>
			<?php endif; ?>
			<tr>
        		<th scope="row" valign="top"><label for="cleverness_todo_description"><?php _e('To-Do', 'cleverness-to-do-list') ?></label></th>
        		<td><textarea name="cleverness_todo_description" rows="5" cols="50" id="the_editor"></textarea></td>
			</tr>
			</table>
	   		<?php echo wp_nonce_field( 'todoadd', 'todoadd', true, false ); ?>
			<input type="hidden" name="action" value="addtodo" />
        	<p class="submit"><input type="submit" name="submit" class="button-primary" value="<?php _e('Add To-Do Item &raquo;', 'cleverness-to-do-list') ?>" /></p>
		</form>
	</div>
	<?php endif; ?>
	<?php
  	}
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
?>