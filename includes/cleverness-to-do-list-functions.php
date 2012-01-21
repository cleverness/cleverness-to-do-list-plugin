<?php
/* Get list of users */
function cleverness_todo_get_users( $role ) {
      $wp_user_search = new WP_User_Query( array( 'role' => $role ) );
      return $wp_user_search->get_results();
}


/* Check if User Has Permission */
function cleverness_todo_user_can( $type, $action ) {
	$cleverness_todo_option = array_merge( get_option( 'cleverness-to-do-list-general' ), get_option( 'cleverness-to-do-list-advanced' ), get_option( 'cleverness-to-do-list-permissions' ) );
    get_currentuserinfo();

	switch ( $type ) {
		case 'category':
			// check if categories are enabled and the user has the capability or the list view is individual
   			if ( $cleverness_todo_option['categories'] == '1' && ( current_user_can( $cleverness_todo_option[$action.'_capability'] ) || $cleverness_todo_option['list_view'] == '0' ) ) {
   				return true;
   			} else {
   				return false;
			}
			break;
		case 'todo':
			if ( current_user_can( $cleverness_todo_option[$action.'_capability'] ) ) {
   				return true;
   			} else {
   				return false;
			}
			break;
			}
}

/* Get to-do list items */
function cleverness_todo_get_todos($user, $limit = 0, $status = 0, $cat_id = 0) {
   	global $wpdb;

	$cleverness_todo_settings = array_merge( get_option( 'cleverness-to-do-list-general' ), get_option( 'cleverness-to-do-list-advanced' ), get_option( 'cleverness-to-do-list-permissions' ) );

	$select = 'SELECT id, author, priority, todotext, assign, progress, deadline, cat_id, completed FROM '.CTDL_TODO_TABLE.' WHERE status = '.absint($status);

	// individual view
	if ( $cleverness_todo_settings['list_view'] == '0' ) {
		if ( $cleverness_todo_settings['assign'] == '0' )
			$select .= $wpdb->prepare(" AND ( author = %d || assign = %d )", $user, $user);
		else
			$select .= $wpdb->prepare(" AND author = %d", $user);
		}

	// group view - show only assigned - show all assigned
	elseif ( $cleverness_todo_settings['list_view'] == '1' && $cleverness_todo_settings['show_only_assigned'] == '0' && (current_user_can($cleverness_todo_settings['view_all_assigned_capability'])) )
		$select = $select;
	// group view - show only assigned
	elseif ( $cleverness_todo_settings['list_view'] == '1' && $cleverness_todo_settings['show_only_assigned'] == '0' )
		$select .= $wpdb->prepare(" AND assign = %d", $user);
	// group view - show all
	elseif ( $cleverness_todo_settings['list_view'] == '1' )
		$select = $select;
	// master view with edit capablities
	elseif ( $cleverness_todo_settings['list_view'] == '2' && current_user_can($cleverness_todo_settings['edit_capability']) )
			$select = $select;
	// master view
	elseif ( $cleverness_todo_settings['list_view'] == '2' ) {
		if ($status == 0 ) {
		   	$select .= $wpdb->prepare(" AND ( id = ANY ( SELECT id FROM ".CTDL_STATUS_TABLE." WHERE user = %d AND status = 0) OR id NOT IN( SELECT id FROM ".CTDL_STATUS_TABLE." WHERE user = %d AND status = 1 ) )", $user, $user);
		} elseif ( $status == 1 ) {
		   	$select = $wpdb->prepare(" SELECT id, author, priority, todotext, assign, progress, deadline, cat_id, completed FROM ".CTDL_TODO_TABLE." LEFT OUTER JOIN ".CTDL_STATUS_TABLE." USING (id) WHERE ( ".CTDL_STATUS_TABLE.".status = 1 AND ".CTDL_STATUS_TABLE.".user = %d )", $user);
			}
		}

	if ( $cat_id != 1 ) {
		// show only one category
		if ( $cleverness_todo_settings['categories'] == '1' && $cat_id != 'All' ) {
			$select .= $wpdb->prepare(" AND cat_id = %d ", $cat_id);
		}
	}

	// order by sort order - no categories
	if ( $cleverness_todo_settings['categories'] == '0' )
		$select .= $wpdb->prepare(" ORDER BY priority, %s", $cleverness_todo_settings['sort_order']);
	// order by categories then sort order
	else
		$select .= $wpdb->prepare(" ORDER BY cat_id, priority, %s", $cleverness_todo_settings['sort_order']);
	if ( $limit != 0 ) $select .= $wpdb->prepare("  LIMIT %d", $limit);
   	$result = $wpdb->get_results( $select, OBJECT_K );

   	return $result;
	}

/* Insert new to-do item into the database */
function cleverness_todo_insert($assign = 0, $deadline, $progress = 0, $category = 0) {
	global $wpdb, $current_user;
	get_currentuserinfo();

   	$results = $wpdb->insert( CTDL_TODO_TABLE, array( 'author' => $current_user->ID, 'status' => 0,
		'priority' => $_POST['cleverness_todo_priority'], 'todotext' => $_POST['cleverness_todo_description'],
		'assign' => $assign, 'deadline' => $deadline, 'progress' => $progress, 'cat_id' => $category ) );
	if ( $results ) {
		$message = __('New To-Do item has been added.', 'cleverness-to-do-list');
	} else {
		$message = __('There was a problem adding the item to the database.', 'cleverness-to-do-list');
	}
	return $message;
	}


/* Send an email to assigned user - Category code contributed by Daniel */
function cleverness_todo_email_user($assign, $deadline, $category) {
	global $wpdb, $userdata, $cleverness_todo_option;
	$priority = esc_attr($_POST['cleverness_todo_priority']);
	$todotext = esc_html($_POST['cleverness_todo_description']);
	$priority_array = array(0 => $cleverness_todo_option['priority_0'] , 1 => $cleverness_todo_option['priority_1'], 2 => $cleverness_todo_option['priority_2']);
   	get_currentuserinfo();

   	if ( current_user_can($cleverness_todo_option['assign_capability']) && $assign != '' && $assign != '-1' && $assign != '0') {
		$headers = 'From: '.$cleverness_todo_option['email_from'].' <'.get_bloginfo('admin_email').'>' . "\r\n\\";
        //$categoryobj = cleverness_todo_get_cat_name($category);
        //$categoryname = $categoryobj->name;
		//$subject = $cleverness_todo_option['email_subject'].' '.$categoryname; // MAKE CATEGORY NAME OPTION
		$subject = $cleverness_todo_option['email_subject'];
		$assign_user = get_userdata($assign);
		$email = $assign_user->user_email;
		$email_message = $cleverness_todo_option['email_text'];
		$email_message .= "\r\n".$todotext."\r\n";
		if ( $deadline != '' )
			$email_message .= __('Deadline:', 'cleverness-to-do-list').' '.$deadline."\r\n";
  		if ( wp_mail($email, $subject, $email_message, $headers) )
			$message = __('A email has been sent to the assigned user.', 'cleverness-to-do-list').'<br /><br />';
		else
			$message = __('The email failed to send to the assigned user.', 'cleverness-to-do-list');
			$message .= '<br />
			To: '.$email.'<br />
			Subject: '.$subject.'<br />
			Message: '.$message.'<br />
			Headers: '.$headers.'<br /><br />';
		return $message;
	} else {
		$message = __('No email has been sent.', 'cleverness-to-do-list').'<br /><br />';
		return $message;
	}
}

/* Update to-do list item */
function cleverness_todo_update($assign = 0, $deadline, $progress = 0, $category = 0) {
   	global $wpdb, $userdata;
   	get_currentuserinfo();

   		$results = $wpdb->update( CTDL_TODO_TABLE, array( 'priority' => $_POST['cleverness_todo_priority'],
			'todotext' => $_POST['cleverness_todo_description'], 'assign' => $assign, 'deadline' => $deadline,
			'progress' => $progress, 'cat_id' => $category ), array( 'id' => $_POST['id'] ) );
		if ( $results ) {
			$message = __('To-Do item has been updated.', 'cleverness-to-do-list');
		} else {
			$message = __('There was a problem editing the item.', 'cleverness-to-do-list');
			}

	return $message;
	}

/* Delete to-do list item */
function cleverness_todo_delete() {
   	global $wpdb;

   	$delete = 'DELETE FROM ' . CTDL_TODO_TABLE . ' WHERE id = "%d"';
   	$results = $wpdb->query( $wpdb->prepare($delete, $_POST['cleverness_todo_id']) );
	$success = ( $results === FALSE ? 0 : 1 );
	return $success;
	}

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

/* Mark to-do list item as completed or uncompleted */
function cleverness_todo_complete($id, $status) {
	global $wpdb, $userdata, $cleverness_todo_option, $current_user;
	$cleverness_todo_option = array_merge( get_option( 'cleverness-to-do-list-general' ), get_option( 'cleverness-to-do-list-advanced' ), get_option( 'cleverness-to-do-list-permissions' ) );
   	get_currentuserinfo();

	 // if individual view, group view with complete capability, or master view with edit capability
   	 if ( $cleverness_todo_option['list_view'] == '0' ||
	 ( $cleverness_todo_option['list_view'] == '1' && current_user_can($cleverness_todo_option['complete_capability']) ) ||
	 ( $cleverness_todo_option['list_view'] == '2' && current_user_can($cleverness_todo_option['edit_capability']) )
	 ) {
		$results = $wpdb->update( CTDL_TODO_TABLE, array( 'status' => $status ), array( 'id' => $id ) );
		//$success = ( $results === FALSE ? 0 : 1 );
	   //	return $success;
	   return $id;
	 	if ( $status == '1' ) $status_text = __('completed', 'cleverness-to-do-list');
		else $status_text = __('uncompleted', 'cleverness-to-do-list');
		if ( $results ) $message = __('To-Do item has been marked as ', 'cleverness-to-do-list').$status_text.'.';
		else {
			$message = __('There was a problem changing the status of the item.', 'cleverness-to-do-list');
			}
	 // master view - individual
	 } elseif ( $cleverness_todo_option['list_view'] == '2' ) {
	 	$user = $current_user->ID;
		$wpdb->get_results("SELECT * FROM ".CTDL_STATUS_TABLE." WHERE id = $id AND user = $user");
		$num = $wpdb->num_rows;

		if ( $num == 0 ) {
			$results = $wpdb->insert( CTDL_STATUS_TABLE, array( 'id' => $id, 'status' => $status, 'user' => $user ) );
	 	} else {
			$results = $wpdb->update( CTDL_STATUS_TABLE, array( 'status' => $status ), array( 'id' => $id, 'user' => $user ) );
			}

		if ( $status == '1' ) $status_text = __('completed', 'cleverness-to-do-list');
		else $status_text = __('uncompleted', 'cleverness-to-do-list');
		if ( $results ) $message = __('To-Do item has been marked as ', 'cleverness-to-do-list').$status_text.'.';
		else {
			$message = __('There was a problem changing the status of the item.', 'cleverness-to-do-list');
			}
	 // no capability
	 } else {
		$message = __('You do not have sufficient privileges to do that.', 'cleverness-to-do-list');
		}
	return $message;
	}

function cleverness_todo_checklist_complete_callback() {
	check_ajax_referer( 'cleverness-todo' );
	$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'complete' );

	if ( $cleverness_todo_permission === true ) {
		$cleverness_id = intval($_POST['cleverness_id']);
		$cleverness_status = intval($_POST['cleverness_status']);

		$message = cleverness_todo_complete($cleverness_id, $cleverness_status);
	} else {
		$message = __('You do not have sufficient privileges to do that.', 'cleverness-to-do-list');
	}
	echo $message;

	die(); // this is required to return a proper result
}

/* Get to-do list item */
function cleverness_todo_get_todo($id) {
   	global $wpdb;

   	$select = "SELECT id, author, priority, todotext, assign, progress, deadline, cat_id, completed, status FROM ".CTDL_TODO_TABLE." WHERE id = '%d' LIMIT 1";
   	$result = $wpdb->get_row( $wpdb->prepare($select, $id) );
   	return $result;
	}

/* Delete all completed to-do list items */
function cleverness_todo_purge() {
   	global $wpdb, $userdata, $cleverness_todo_option;
   	get_currentuserinfo();

   		if ( $cleverness_todo_option['list_view'] == '0' ) {
   			$purge = "DELETE FROM ".CTDL_TODO_TABLE." WHERE status = '1' AND ( author = '".$userdata->ID."' || assign = '".$userdata->ID."' )";
	   	} elseif ( $cleverness_todo_option['list_view'] == '1' || $cleverness_todo_option['list_view'] == '2' ) {
			$purge = "DELETE FROM ".CTDL_TODO_TABLE." WHERE status = '1'";
			}
   		$results = $wpdb->query( $purge );
		if ( $results ) {
			$message = __('Completed To-Do items have been deleted.', 'cleverness-to-do-list');
		} else {
			$message = __('There was a problem removing the completed items.', 'cleverness-to-do-list');
			}

	return $message;
	}

/* Insert new to-do category into the database */
function cleverness_todo_insert_cat() {
	global $wpdb;

   	$results = $wpdb->insert( CTDL_CATS_TABLE, array( 'name' => $_POST['cleverness_todo_cat_name'], 'visibility' => $_POST['cleverness_todo_cat_visibility'] ) );
	$success = ( $results === FALSE ? 0 : 1 );
	return $success;
	}

/* Update to-do list category */
function cleverness_todo_update_cat() {
   	global $wpdb;

   	$results = $wpdb->update( CTDL_CATS_TABLE,
	array( 'name' => $_POST['cleverness_todo_cat_name'], 'visibility' => $_POST['cleverness_todo_cat_visibility'] ),
	array( 'id' => absint($_POST['cleverness_todo_cat_id']) ) );
	$success = ( $results === FALSE ? 0 : 1 );
	return $success;
	}

/* Delete to-do list category */
function cleverness_todo_delete_cat() {
   	global $wpdb;

   	$delete = 'DELETE FROM ' . CTDL_CATS_TABLE . ' WHERE id = "%d"';
   	$results = $wpdb->query( $wpdb->prepare($delete, $_POST['cleverness_todo_cat_id']) );
	$success = ( $results === FALSE ? 0 : 1 );
	return $success;
	}

/* Get a to-do list category */
function cleverness_todo_get_todo_cat() {
   	global $wpdb;

   	$select = "SELECT id, name, visibility FROM ".CTDL_CATS_TABLE." WHERE id = '%d' LIMIT 1";
   	$result = $wpdb->get_row( $wpdb->prepare($select, $_POST['cleverness_todo_cat_id']) );
   	return $result;
	}

/* Get to-do list categories */
function cleverness_todo_get_cats() {
   	global $wpdb, $cleverness_todo_option;
	$cleverness_todo_option = array_merge( get_option( 'cleverness-to-do-list-general' ), get_option( 'cleverness-to-do-list-advanced' ), get_option( 'cleverness-to-do-list-permissions' ) );

	// check if categories are enabled
   	if ( $cleverness_todo_option['categories'] == '1' ) {

   		$sql = "SELECT id, name, visibility FROM ".CTDL_CATS_TABLE.' ORDER BY name';
   		$results = $wpdb->get_results( $wpdb->prepare($sql) );
   		return $results;

	// if categories are not enabled
	} else {
		$message = __('Categories are not enabled.', 'cleverness-to-do-list');
		}

	return $message;
	}

/* Get to-do category name */
function cleverness_todo_get_cat_name($id) {
   	global $wpdb;

   	$cat = "SELECT name FROM ".CTDL_CATS_TABLE." WHERE id = '%d' LIMIT 1";
   	$result = $wpdb->get_row( $wpdb->prepare($cat, $id) );
   	return $result;
	}

/* Create database table and add default options */
function cleverness_todo_install () {
   	global $wpdb, $userdata;
   	get_currentuserinfo();

	$cleverness_todo_db_version = '1.9';
	if ( !function_exists( 'is_plugin_active_for_network' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	if ( is_plugin_active_for_network( __FILE__ ) ) {
		$prefix = $wpdb->base_prefix;
	} else {
		$prefix = $wpdb->prefix;
	}

	$table_name = $prefix.'todolist';
	$cat_table_name = $prefix.'todolist_cats';
	$status_table_name = $prefix.'todolist_status';

   	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
   		$sql = "CREATE TABLE ".$table_name." (
	      id bigint(20) UNIQUE NOT NULL AUTO_INCREMENT,
	      author bigint(20) NOT NULL,
	      status tinyint(1) DEFAULT '0' NOT NULL,
	      priority tinyint(1) NOT NULL,
          todotext text NOT NULL,
		  assign int(10),
		  progress int(3),
		  deadline varchar(30),
		  completed timestamp,
		  cat_id bigint(20)
	    );";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   		dbDelta($sql);
		$sql2 = "CREATE TABLE ".$cat_table_name." (
	      id bigint(20) UNIQUE NOT NULL AUTO_INCREMENT,
	      name varchar(100),
	      visibility tinyint(1) DEFAULT '0' NOT NULL
	    );";
   		dbDelta($sql2);
		$sql3 = "CREATE TABLE ".$status_table_name." (
	      id bigint(20),
	      user bigint(20),
	      status tinyint(1) DEFAULT '0' NOT NULL
	    );";
   		dbDelta($sql3);
   		$welcome_text = __('Add your first To-Do List item', 'cleverness-to-do-list');
   		$results = $wpdb->insert( $table_name, array( 'author' => $userdata->ID, 'status' => 0, 'priority' => 1, 'todotext' => $welcome_text ) );

		$general_options = array(
			'categories'            => '0',
			'list_view'             => '0',
			'todo_author'           => '0',
			'show_completed_date'   => '0',
			'show_deadline'         => '0',
			'show_progress'         => '0',
			'sort_order'            => 'id',
			'admin_bar'             => '1'
   		);

		 $advanced_options = array(
			 'date_format'                  => 'm-d-Y',
			 'priority_0'                   => __( 'Important', 'cleverness-to-do-list' ),
			 'priority_1'                   => __( 'Normal', 'cleverness-to-do-list' ),
			 'priority_2'                   => __( 'Low', 'cleverness-to-do-list' ),
			 'assign'                       => '1',
			 'show_only_assigned'           => '1',
			 'user_roles'                   => 'contributor, author, editor, administrator',
			 'email_assigned'               => '0',
			 'email_from'                   => html_entity_decode( get_bloginfo( 'name' ) ),
			 'email_subject'                => __( 'A to-do list item has been assigned to you', 'cleverness-to-do-list' ),
			 'email_text'                   => __( 'The following item has been assigned to you.', 'cleverness-to-do-list' ),
			 'show_id'                      => '0',
		   );

		$permissions_options = array(
			'view_capability'              => 'publish_posts',
			'add_capability'               => 'publish_posts',
			'edit_capability'              => 'publish_posts',
			'delete_capability'            => 'manage_options',
			'purge_capability'             => 'manage_options',
			'complete_capability'          => 'publish_posts',
			'assign_capability'            => 'manage_options',
			'view_all_assigned_capability' => 'manage_options',
			'add_cat_capability'           => 'manage_options',
		   );

   		add_option( 'cleverness-to-do-list-general', $general_options );
		add_option( 'cleverness-to-do-list-advanced', $advanced_options );
		add_option( 'cleverness-to-do-list-permissions', $permissions_options );
		add_option( 'cleverness_todo_db_version', $cleverness_todo_db_version );
		}

	$installed_ver = get_option( 'cleverness_todo_db_version' );

	if( $installed_ver != $cleverness_todo_db_version ) {

		if ( !function_exists( 'maybe_create_table' ) ) {
			require_once( ABSPATH . 'wp-admin/install-helper.php' );
		}

		maybe_add_column($table_name, 'assign', "ALTER TABLE `$table_name` ADD `assign` int(10);");
		maybe_add_column($table_name, 'deadline', "ALTER TABLE `$table_name` ADD `deadline` varchar(30);");
		maybe_add_column($table_name, 'progress', "ALTER TABLE `$table_name` ADD `progress` int(3);");
		maybe_add_column($table_name, 'completed', "ALTER TABLE `$table_name` ADD `completed` timestamp;");
		maybe_add_column($table_name, 'cat_id', "ALTER TABLE `$table_name` ADD `cat_id` bigint(20);");
		maybe_create_table($cat_table_name, "CREATE TABLE ".$cat_table_name." (
	      id bigint(20) UNIQUE NOT NULL AUTO_INCREMENT,
	      name varchar(100),
	      sort tinyint(3) DEFAULT '0' NOT NULL,
	      visibility tinyint(1) DEFAULT '0' NOT NULL
	    );");
		maybe_create_table($status_table_name, "CREATE TABLE ".$status_table_name." (
	      id bigint(20),
	      user bigint(20),
	      status tinyint(1) DEFAULT '0' NOT NULL
	    );");

		$the_options = get_option( 'cleverness_todo_settings' );
		if ( $the_options['categories'] == '' ) $the_options['categories'] = '0';
		if ( $the_options['sort_order'] == '' ) $the_options['sort_order'] = 'id';
		if ( $the_options['add_cat_capability'] == '' ) $the_options['add_cat_capability'] = 'manage_options';
		if ( $the_options['dashboard_cat'] == '' ) $the_options['dashboard_cat'] = 'All';
		if ( $the_options['email_text'] == '' ) $the_options['email_text'] = __( 'The following item has been assigned to you.', 'cleverness-to-do-list' );
		if ( $the_options['email_subject'] == '' ) $the_options['email_subject'] = __( 'A to-do list item has been assigned to you', 'cleverness-to-do-list' );
		if ( $the_options['email_from'] == '' ) $the_options['email_from'] = html_entity_decode( get_bloginfo( 'name' ) );

		$general_options = array(
			'categories'            => $the_options['categories'],
			'list_view'             => $the_options['list_view'],
			'todo_author'           => $the_options['todo_author'],
			'show_completed_date'   => $the_options['show_completed_date'],
			'show_deadline'         => $the_options['show_deadline'],
			'show_progress'         => $the_options['show_progress'],
			'sort_order'            => $the_options['sort_order'],
			'admin_bar'             => '1'
			);

		$advanced_options = array(
			'date_format'                  => $the_options['date_format'],
			'priority_0'                   => $the_options['priority_0'],
			'priority_1'                   => $the_options['priority_1'],
			'priority_2'                   => $the_options['priority_2'],
			'assign'                       => $the_options['assign'],
			'show_only_assigned'           => $the_options['show_only_assigned'],
			'user_roles'                   => $the_options['user_roles'],
			'email_assigned'               => $the_options['email_assigned'],
			'email_from'                   => $the_options['email_from'],
			'email_subject'                => $the_options['email_subject'],
			'email_text'                   => $the_options['email_text'],
			'show_id'                      => '0',
		);

		$permissions_options = array(
			'view_capability'              => $the_options['view_capability'],
			'add_capability'               => $the_options['add_capability'],
			'edit_capability'              => $the_options['edit_capability'],
			'delete_capability'            => $the_options['delete_capability'],
			'purge_capability'             => $the_options['purge_capability'],
			'complete_capability'          => $the_options['complete_capability'],
			'assign_capability'            => $the_options['assign_capability'],
			'view_all_assigned_capability' => $the_options['view_all_assigned_capability'],
			'add_cat_capability'           => $the_options['add_cat_capability'],
		);

		add_option( 'cleverness-to-do-list-general', $general_options );
		add_option( 'cleverness-to-do-list-advanced', $advanced_options );
		add_option( 'cleverness-to-do-list-permissions', $permissions_options );

    	update_option( 'cleverness_todo_db_version', $cleverness_todo_db_version );
		delete_option( 'atd_db_version' );
		delete_option( 'cleverness_todo_settings' );
		}
	}

?>