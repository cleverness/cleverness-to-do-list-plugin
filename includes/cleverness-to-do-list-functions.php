<?php
/* Check if User Has Permission */
function cleverness_todo_user_can($type, $action) {
	global $cleverness_todo_option, $current_user;
    get_currentuserinfo();

	switch ($type) {
		case 'category':
			// check if categories are enabled and the user has the capability or the list view is individual
   			if ( $cleverness_todo_option['categories'] == '1' && ( current_user_can($cleverness_todo_option[$action.'_capability']) || $cleverness_todo_option['list_view'] == '0' ) ) {
   				return true;
   			} else {
   				return false;
			}
			break;
		case 'todo':
			if ( current_user_can($cleverness_todo_option[$action.'_capability'])  ) {

			//if ( $cleverness_todo_option['list_view'] == '0' ) {
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

	$cleverness_todo_settings = get_option('cleverness_todo_settings');
	$cleverness_todo_dashboard_settings = get_option('cleverness_todo_dashboard_settings');

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
		$select = $wpdb->prepare(" AND assign = %d", $user);
	// group view - show all
	elseif ( $cleverness_todo_settings['list_view'] == '1' )
		$select = $select;
	// master view with edit capablities
	elseif ( $cleverness_todo_settings['list_view'] == '2' && current_user_can($cleverness_todo_settings['edit_capability']) )
			$select = $select;
	// master view
	elseif ( $cleverness_todo_settings['list_view'] == '2' ) {
		if ($status == 0 ) {
		   	$select = $wpdb->prepare(" AND ( id = ANY ( SELECT id FROM ".CTDL_STATUS_TABLE." WHERE user = %d AND status = 0) OR id NOT IN( SELECT id FROM ".CTDL_STATUS_TABLE." WHERE user = %d AND status = 1 ) )", $user, $user);
		} elseif ( $status == 1 ) {
		   	$select = $wpdb->prepare(" LEFT OUTER JOIN ".CTDL_STATUS_TABLE." USING (id) WHERE ( ".CTDL_STATUS_TABLE.".status = 1 AND ".CTDL_STATUS_TABLE.".user = %d )", $user);
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
function cleverness_todo_email_user($todotext, $priority, $assign, $deadline, $category) {
	global $wpdb, $userdata, $cleverness_todo_option;
	$priority_array = array(0 => $cleverness_todo_option['priority_0'] , 1 => $cleverness_todo_option['priority_1'], 2 => $cleverness_todo_option['priority_2']);
	require_once (ABSPATH . WPINC . '/pluggable.php');
   	get_currentuserinfo();

   	if ( current_user_can($cleverness_todo_option['assign_capability']) && $assign != '' && $assign != '-1' && $assign != '0') {
		$headers = 'From: '.$cleverness_todo_option['email_from'].' <'.get_bloginfo('admin_email').'>' . "\r\n\\";
        $categoryobj=cleverness_todo_get_cat_name($category);
        $categoryname=$categoryobj->name;
		$subject = $cleverness_todo_option['email_subject'].' '.$categoryname; // MAKE CATEGORY NAME OPTION
		$assign_user = get_userdata($assign);
		$email = $assign_user->user_email;
		$email_message = $cleverness_todo_option['email_text'];
		$email_message .= "\r\n\\".$todotext."\r\n\\";
		if ( $deadline != '' )
			$email_message .= __('Deadline:', 'cleverness-to-do-list').' '.$deadline."\r\n\\";
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
   	global $wpdb, $userdata, $cleverness_todo_option;
   	get_currentuserinfo();

   	if ( $cleverness_todo_option['list_view'] == '0' || current_user_can($cleverness_todo_option['edit_capability']) ) {
   		$results = $wpdb->update( CTDL_TODO_TABLE, array( 'priority' => $_POST['cleverness_todo_priority'],
			'todotext' => $_POST['cleverness_todo_description'], 'assign' => $assign, 'deadline' => $deadline,
			'progress' => $progress, 'cat_id' => $category ), array( 'id' => $_POST['id'] ) );
		if ( $results ) {
			$message = __('To-Do item has been updated.', 'cleverness-to-do-list');
		} else {
			$message = __('There was a problem editing the item.', 'cleverness-to-do-list');
			}
	} else {
		$message = __('You do not have sufficient privileges to do that.', 'cleverness-to-do-list');
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

/* Mark to-do list item as completed or uncompleted */
function cleverness_todo_complete($id, $status) {
	global $wpdb, $userdata, $cleverness_todo_option, $current_user;
	require_once (ABSPATH . WPINC . '/pluggable.php');
   	get_currentuserinfo();

	 // if individual view, group view with complete capability, or master view with edit capability
   	 if ( $cleverness_todo_option['list_view'] == '0' ||
	 ( $cleverness_todo_option['list_view'] == '1' && current_user_can($cleverness_todo_option['complete_capability']) ) ||
	 ( $cleverness_todo_option['list_view'] == '2' && current_user_can($cleverness_todo_option['edit_capability']) )
	 ) {
		$results = $wpdb->update( CTDL_TODO_TABLE, array( 'status' => $status ), array( 'id' => $id ) );
		//$success = ( $results === FALSE ? 0 : 1 );
	   //	return $success;
	 	if ( $status == '1' ) $status_text = __('completed', 'cleverness-to-do-list');
		else $status_text = __('uncompleted', 'cleverness-to-do-list');
		if ( $results ) $message = __('To-Do item has been marked as ', 'cleverness-to-do-list').$status_text.'.';
		else {
			$message = __('There was a problem changing the status of the item.', 'cleverness-to-do-list');
			}
	 // master view - individual
	 } elseif ( $cleverness_todo_option['list_view'] == '2' ) {
	 	$user = $current_user->ID;
		$wpdb->get_results("SELECT * FROM ".CTDL_TODO_TABLE." WHERE id = $id AND user = $user");
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

/* Get to-do list item */
function cleverness_todo_get_todo($id) {
   	global $wpdb;

   	$select = "SELECT id, author, priority, todotext, assign, progress, deadline, cat_id, completed FROM ".CTDL_TODO_TABLE." WHERE id = '%d' LIMIT 1";
   	$result = $wpdb->get_row( $wpdb->prepare($select, $id) );
   	return $result;
	}

/* Delete all completed to-do list items */
function cleverness_todo_purge() {
   	global $wpdb, $userdata, $cleverness_todo_option;
	require_once (ABSPATH . WPINC . '/pluggable.php');
   	get_currentuserinfo();

   	if ( $cleverness_todo_option['list_view'] == '0' || current_user_can($cleverness_todo_option['purge_capability']) ) {
   		if ( $cleverness_todo_option['list_view'] == '0' ) {
   			$purge = "DELETE FROM ".CTDL_TODO_TABLE." WHERE status = '1' AND ( author = '".$userdata->ID."' || assign = '".$userdata->ID."' )";
	   	} elseif ( $cleverness_todo_option['list_view'] == '1' || $cleverness_todo_option['list_view'] == '2' ) {
			$purge = "DELETE FROM ".CTDL_TODO_TABLE." WHERE status = '1'";
			}
   		$results = $wpdb->query( $purge );
		if ( $results ) $message = __('Completed To-Do items have been deleted.', 'cleverness-to-do-list');
		else {
			$message = __('There was a problem removing the completed items.', 'cleverness-to-do-list');
			}
	} else {
		$message = __('You do not have sufficient privileges to do that.', 'cleverness-to-do-list');
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

   	if ($wpdb->get_var("SHOW TABLES LIKE '".CTDL_TODO_TABLE."'") != CTDL_TODO_TABLE) {
   		$sql = "CREATE TABLE ".CTDL_TODO_TABLE." (
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
		$sql2 = "CREATE TABLE ".CTDL_CATS_TABLE." (
	      id bigint(20) UNIQUE NOT NULL AUTO_INCREMENT,
	      name varchar(100),
	      visibility tinyint(1) DEFAULT '0' NOT NULL
	    );";
   		dbDelta($sql2);
		$sql3 = "CREATE TABLE ".CTDL_STATUS_TABLE." (
	      id bigint(20),
	      user bigint(20),
	      status tinyint(1) DEFAULT '0' NOT NULL
	    );";
   		dbDelta($sql3);
   		$welcome_text = __('Add your first To-Do List item', 'cleverness-to-do-list');
   		$results = $wpdb->insert( CTDL_TODO_TABLE, array( 'author' => $userdata->ID, 'status' => 0, 'priority' => 1, 'todotext' => $welcome_text ) );

		$new_options = array(
		'list_view' => '0',
		'todo_author' => '0',
		'assign' => '1',
		'show_only_assigned' => '1',
		'view_capability' => 'publish_posts',
		'add_capability' => 'publish_posts',
		'edit_capability' => 'publish_posts',
		'delete_capability' => 'manage_options',
		'purge_capability' => 'manage_options',
		'complete_capability' => 'publish_posts',
		'assign_capability' => 'manage_options',
		'view_all_assigned_capability' => 'manage_options',
		'priority_0' => __('Important', 'cleverness-to-do-list'),
		'priority_1' => __('Normal', 'cleverness-to-do-list'),
		'priority_2' => __('Low', 'cleverness-to-do-list'),
		'show_deadline' => '0',
		'show_progress' => '0',
		'email_assigned' => '0',
		'show_completed_date' => '0',
		'date_format' => 'm-d-Y',
		'user_roles' => 'contributor, author, editor, administrator',
		'categories' => '0',
		'sort_order' => 'id',
		'add_cat_capability' => 'manage_options',
		'email_text' => __('The following item has been assigned to you.', 'cleverness-to-do-list'),
		'email_subject' => __('A to-do list item has been assigned to you', 'cleverness-to-do-list'),
		'email_from' => html_entity_decode(get_bloginfo('name'))
   		);

		$dashboard_options = array(
			'dashboard_number' => '10',
			'show_dashboard_deadline' => '0',
			'dashboard_cat' => 'All',
			'dashboard_author' => '0'
		);

   		add_option( 'cleverness_todo_settings', $new_options );
		add_option( 'cleverness_todo_dashboard_settings', $dashboard_options );
		add_option( 'cleverness_todo_db_version', $cleverness_todo_db_version );
		}

	$installed_ver = get_option( 'cleverness_todo_db_version' );

	if( $installed_ver != $cleverness_todo_db_version ) {

		if ( !function_exists('maybe_create_table') ) {
			require_once(ABSPATH . 'wp-admin/install-helper.php');
		}

		maybe_add_column(CTDL_TODO_TABLE, 'assign', "ALTER TABLE `$table_name` ADD `assign` int(10);");
		maybe_add_column(CTDL_TODO_TABLE, 'deadline', "ALTER TABLE `$table_name` ADD `deadline` varchar(30);");
		maybe_add_column(CTDL_TODO_TABLE, 'progress', "ALTER TABLE `$table_name` ADD `progress` int(3);");
		maybe_add_column(CTDL_TODO_TABLE, 'completed', "ALTER TABLE `$table_name` ADD `completed` timestamp;");
		maybe_add_column(CTDL_TODO_TABLE, 'cat_id', "ALTER TABLE `$table_name` ADD `cat_id` bigint(20);");
		maybe_create_table(CTDL_CATS_TABLE, "CREATE TABLE ".CTDL_CATS_TABLE." (
	      id bigint(20) UNIQUE NOT NULL AUTO_INCREMENT,
	      name varchar(100),
	      sort tinyint(3) DEFAULT '0' NOT NULL,
	      visibility tinyint(1) DEFAULT '0' NOT NULL
	    );");
		maybe_create_table(CTDL_STATUS_TABLE, "CREATE TABLE ".CTDL_STATUS_TABLE." (
	      id bigint(20),
	      user bigint(20),
	      status tinyint(1) DEFAULT '0' NOT NULL
	    );");

		$theoptions = get_option('cleverness_todo_settings');
		if ( $theoptions['categories'] == '' ) $theoptions['categories'] = '0';
		if ( $theoptions['sort_order'] == '' ) $theoptions['sort_order'] = 'id';
		if ( $theoptions['add_cat_capability'] == '' ) $theoptions['add_cat_capability'] = 'manage_options';
		if ( $theoptions['email_text'] == '' ) $theoptions['email_text'] = __('The following item has been assigned to you.', 'cleverness-to-do-list');
		if ( $theoptions['email_subject'] == '' ) $theoptions['email_subject'] = __('A to-do list item has been assigned to you', 'cleverness-to-do-list');
		if ( $theoptions['email_from'] == '' ) $theoptions['email_from'] = html_entity_decode(get_bloginfo('name'));
		update_option( 'cleverness_todo_settings', $theoptions);

		$dash_options = get_option('cleverness_todo_dashboard_settings');
		if ( $dash_options['dashboard_number'] == '' ) {
			$dash_options['dashboard_number'] = '10';
		} else {
			$dash_options['dashboard_number'] = $theoptions['dashboard_number'];
			}
		if ( $dash_options['show_dashboard_deadline'] == '' ) {
			$dash_options['show_dashboard_deadline'] = '0';
		} else {
			$dash_options['show_dashboard_deadline'] = $theoptions['show_dashboard_deadline'];
			}
		if ( $dash_options['dashboard_cat'] == '' ) {
			$dash_options['dashboard_cat'] = 'All';
		} else {
			$dash_options['dashboard_cat'] = $theoptions['dashboard_cat'];
			}
		if ( $dash_options['dashboard_author'] == '' ) {
			$dash_options['dashboard_author'] = '0';
		} else {
			$dash_options['dashboard_author'] = $theoptions['dashboard_author'];
			}
		update_option( 'cleverness_todo_dashboard_settings', $dash_options );

    	update_option( 'cleverness_todo_db_version', $cleverness_todo_db_version );
		delete_option( 'atd_db_version' );
		}
	}


?>