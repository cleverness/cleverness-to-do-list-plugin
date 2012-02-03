<?php
/**
 * Library of functions for the To-Do List
 * @author C.M. Kendrick
 * @version 3.0
 * @package cleverness-to-do-list
 */

class CTDL_Lib {

	/* Get to-do list item */
	public static function get_todo( $id ) {
		$post = get_post( $id );
		return $post;
	}

	public static function test_get_todos( $user, $limit = 0, $status = 0, $cat_id = 0  ) {
		$args = array(
			'post_type' => 'todo',
			'author' => $user,
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'title',
			/*http://codex.wordpress.org/Class_Reference/WP_Query
			 *  'orderby' => 'meta_value', 'meta_key' => 'price'
			 * 'tax_query' => array(
				array(
					'taxonomy' => 'people',
					'field' => 'slug',
					'terms' => 'bob'
				)
			)
			'meta_query' => array(
		array(
			'key' => 'color',
			'value' => 'blue',
			'compare' => 'NOT LIKE'
		)*/
		);
		$results = new WP_Query( $args );
		return $results;
	}

	/* Get to-do list items */
	public static function get_todos( $user, $limit = 0, $status = 0, $cat_id = 0 ) {
		global $wpdb;

		$select = 'SELECT id, author, priority, todotext, assign, progress, deadline, cat_id, completed FROM '.CTDL_TODO_TABLE.' WHERE status = '.absint( $status );

		// individual view
		if ( CTDL_Loader::$settings['list_view'] == '0' ) {
			if ( CTDL_Loader::$settings['assign'] == '0' )
				$select .= $wpdb->prepare( " AND ( author = %d || assign = %d )", $user, $user );
			else
				$select .= $wpdb->prepare( " AND author = %d", $user );
		}

		// group view - show only assigned - show all assigned
		elseif ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '0' && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) )
			$select = $select;
		// group view - show only assigned
		elseif ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '0' )
			$select .= $wpdb->prepare( " AND assign = %d", $user );
		// group view - show all
		elseif ( CTDL_Loader::$settings['list_view'] == '1' )
			$select = $select;
		// master view with edit capablities
		elseif ( CTDL_Loader::$settings['list_view'] == '2' && current_user_can( CTDL_Loader::$settings['edit_capability'] ) )
			$select = $select;
		// master view
		elseif ( CTDL_Loader::$settings['list_view'] == '2' ) {
			if ($status == 0 ) {
				$select .= $wpdb->prepare( " AND ( id = ANY ( SELECT id FROM ".CTDL_STATUS_TABLE." WHERE user = %d AND status = 0) OR id NOT IN( SELECT id FROM ".CTDL_STATUS_TABLE." WHERE user = %d AND status = 1 ) )", $user, $user );
			} elseif ( $status == 1 ) {
				$select = $wpdb->prepare( " SELECT id, author, priority, todotext, assign, progress, deadline, cat_id, completed FROM ".CTDL_TODO_TABLE." LEFT OUTER JOIN ".CTDL_STATUS_TABLE." USING (id) WHERE ( ".CTDL_STATUS_TABLE.".status = 1 AND ".CTDL_STATUS_TABLE.".user = %d )", $user );
			}
		}

		if ( $cat_id != 1 ) {
			// show only one category
			if ( CTDL_Loader::$settings['categories'] == '1' && $cat_id != 'All' ) {
				$select .= $wpdb->prepare( " AND cat_id = %d ", $cat_id );
			}
		}

		// order by sort order - no categories
		if ( CTDL_Loader::$settings['categories'] == '0' )
			$select .= $wpdb->prepare( " ORDER BY priority, %s", CTDL_Loader::$settings['sort_order'] );
		// order by categories then sort order
		else
			$select .= $wpdb->prepare( " ORDER BY cat_id, priority, %s", CTDL_Loader::$settings['sort_order'] );
		if ( $limit != 0 ) $select .= $wpdb->prepare( "  LIMIT %d", $limit );
		$result = $wpdb->get_results( $select, OBJECT_K );

		return $result;
	}

	/* Mark to-do list item as completed or uncompleted */
	public static function complete_todo( $id, $status ) {
		global $wpdb, $current_user;

		$cleverness_todo_complete_nonce = $_REQUEST['_wpnonce'];
		if ( !wp_verify_nonce( $cleverness_todo_complete_nonce, 'todocomplete' ) ) die( 'Security check failed' );

		// if individual view, group view with complete capability, or master view with edit capability
		if ( CTDL_Loader::$settings['list_view'] == '0' ||
				( CTDL_Loader::$settings['list_view'] == '1' && current_user_can( CTDL_Loader::$settings['complete_capability'] ) ) ||
				( CTDL_Loader::$settings['list_view'] == '2' && current_user_can( CTDL_Loader::$settings['edit_capability'] ) )
		) {
			$results = $wpdb->update( CTDL_TODO_TABLE, array( 'status' => $status ), array( 'id' => $id ) );

			if ( $status == 1 ) $status_text = __( 'completed', 'cleverness-to-do-list' );
			else $status_text = __( 'uncompleted', 'cleverness-to-do-list' );
			if ( $results ) $message = __('To-Do item has been marked as ', 'cleverness-to-do-list').$status_text.'.';
			else {
				$message = __('There was a problem changing the status of the item.', 'cleverness-to-do-list');
			}

		// master view - individual
		} elseif ( CTDL_Loader::$settings['list_view'] == '2' ) {
			$user = $current_user->ID;
			$wpdb->get_results( "SELECT * FROM ".CTDL_STATUS_TABLE." WHERE id = $id AND user = $user" );
			$num = $wpdb->num_rows;

			if ( $num == 0 ) {
				$results = $wpdb->insert( CTDL_STATUS_TABLE, array( 'id' => $id, 'status' => $status, 'user' => $user ) );
			} else {
				$results = $wpdb->update( CTDL_STATUS_TABLE, array( 'status' => $status ), array( 'id' => $id, 'user' => $user ) );
			}

			if ( $status == '1' ) $status_text = __( 'completed', 'cleverness-to-do-list' );
			else $status_text = __( 'uncompleted', 'cleverness-to-do-list' );
			if ( $results ) $message = __( 'To-Do item has been marked as ', 'cleverness-to-do-list').$status_text.'.';
			else {
				$message = __( 'There was a problem changing the status of the item.', 'cleverness-to-do-list' );
			}
			// no capability
		} else {
			$message = __( 'You do not have sufficient privileges to do that.', 'cleverness-to-do-list' );
		}
		return $message;
	}

	/* Insert new to-do item into the database */
	public static function insert_todo() {
		global $current_user;

		if ( $_POST['cleverness_todo_description'] == '' ) return;

		$cleverness_todo_permission = CTDL_LIB::check_permission( 'todo', 'add' );

		if ( $cleverness_todo_permission === true ) {

			if ( !wp_verify_nonce( $_REQUEST['todoadd'], 'todoadd' ) ) die( 'Security check failed' );

			if ( CTDL_Loader::$settings['email_assigned'] == '1' && CTDL_Loader::$settings['assign'] == '0' ) {
				CTDL_Lib::email_user( $_POST['cleverness_todo_assign'], $_POST['cleverness_todo_deadline'], $_POST['cleverness_todo_category'] );
			}

			$my_post = array(
				'post_type'        => 'todo',
				'post_title'       => substr( $_POST['cleverness_todo_description'], 0, 100 ),
				'post_content'     => $_POST['cleverness_todo_description'],
				'post_status'      => 'publish',
				'post_author'      => $current_user->ID,
				'comment_status'   => 'closed',
				'ping_status'      => 'closed',
			);

			$post_id = wp_insert_post( $my_post );

			wp_set_post_terms( $post_id, $_POST['cat'], 'todocategories', false);
			add_post_meta( $post_id, '_status', 0, true );
			add_post_meta( $post_id, '_priority', $_POST['cleverness_todo_priority'], true );
			add_post_meta( $post_id, '_assign', $_POST['cleverness_todo_assign'], true );
			add_post_meta( $post_id, '_deadline', $_POST['cleverness_todo_deadline'], true );
			add_post_meta( $post_id, '_progress', $_POST['cleverness_todo_progress'], true );

		}

		return;
	}

	/* Send an email to assigned user - Category code contributed by Daniel
	TODO: test email function
	TODO: make category an option
	*/
	protected static function email_user($assign, $deadline, $category) {

		$priority = esc_attr( $_POST['cleverness_todo_priority'] );
		$todotext = esc_html( $_POST['cleverness_todo_description'] );
		$priority_array = array (0 => CTDL_Loader::$settings['priority_0'] , 1 => CTDL_Loader::$settings['priority_1'], 2 => CTDL_Loader::$settings['priority_2'] );
		get_currentuserinfo();

		if ( current_user_can( CTDL_Loader::$settings['assign_capability']) && $assign != '' && $assign != '-1' && $assign != '0' ) {
			$headers = 'From: '.CTDL_Loader::$settings['email_from'].' <'.get_bloginfo('admin_email').'>' . "\r\n\\";
			//$categoryobj = cleverness_todo_get_cat_name($category);
			//$categoryname = $categoryobj->name;
			//$subject = $cleverness_todo_option['email_subject'].' '.$categoryname; // MAKE CATEGORY NAME OPTION
			$subject = CTDL_Loader::$settings['email_subject'];
			$assign_user = get_userdata( $assign );
			$email = $assign_user->user_email;
			$email_message = CTDL_Loader::$settings['email_text'];
			$email_message .= "\r\n".$todotext."\r\n";
			if ( $deadline != '' )
				$email_message .= __( 'Deadline:', 'cleverness-to-do-list' ).' '.$deadline."\r\n";
			if ( wp_mail( $email, $subject, $email_message, $headers ) )
				$message = __( 'A email has been sent to the assigned user.', 'cleverness-to-do-list' ).'<br /><br />';
			else
				$message = __( 'The email failed to send to the assigned user.', 'cleverness-to-do-list' );
			$message .= '<br />
			To: '.$email.'<br />
			Subject: '.$subject.'<br />
			Message: '.$message.'<br />
			Headers: '.$headers.'<br /><br />';
			return $message;
		} else {
			$message = __( 'No email has been sent.', 'cleverness-to-do-list' ).'<br /><br />';
			return $message;
		}
	}

	/* Update to-do list item */
	public static function edit_todo() {
		$cleverness_todo_permission = CTDL_LIB::check_permission( 'todo', 'edit' );

		if ( $cleverness_todo_permission === true ) {

			if ( !wp_verify_nonce( $_REQUEST['todoupdate'], 'todoupdate' ) ) die( 'Security check failed' );

			$my_post = array(
				'ID'            => $_POST['id'],
				'post_title'    => substr( $_POST['cleverness_todo_description'], 0, 100 ),
				'post_content'  => $_POST['cleverness_todo_description'],
			);

			$post_id = wp_update_post( $my_post );

			wp_set_post_terms( $post_id, $_POST['cat'], 'todocategories', false);
			update_post_meta( $post_id, '_priority', $_POST['cleverness_todo_priority'] );
			update_post_meta( $post_id, '_assign', $_POST['cleverness_todo_assign'] );
			update_post_meta( $post_id, '_deadline', $_POST['cleverness_todo_deadline'] );
			update_post_meta( $post_id, '_progress', $_POST['cleverness_todo_progress'] );

		}

		return;
	}

	/* Delete to-do list item */
	public static function delete_todo( $id ) {
		wp_delete_post( $id, true );
		return 1;
	}


	/* Delete all completed to-do list items
	@todo add js confirm */
	public static function delete_all_todos() {
		global $wpdb, $userdata;

		$cleverness_todo_permission = CTDL_LIB::check_permission( 'todo', 'purge' );

		if ( $cleverness_todo_permission === true ) {
			$cleverness_todo_purge_nonce = $_REQUEST['_wpnonce'];
			if ( !wp_verify_nonce( $cleverness_todo_purge_nonce, 'todopurge' ) ) die( 'Security check failed' );
			if ( CTDL_Loader::$settings['list_view'] == '0' ) {
				$sql = "DELETE FROM ".CTDL_TODO_TABLE." WHERE status = '1' AND ( author = '".$userdata->ID."' || assign = '".$userdata->ID."' )";
			} elseif ( CTDL_Loader::$settings['list_view'] == '1' || CTDL_Loader::$settings['list_view'] == '2' ) {
				$sql = "DELETE FROM ".CTDL_TODO_TABLE." WHERE status = '1'";
			}
			$results = $wpdb->query( $sql );
			if ( $results ) {
				$message = __( 'Completed To-Do items have been deleted.', 'cleverness-to-do-list' );
			} else {
				$message = __( 'There was a problem removing the completed items.', 'cleverness-to-do-list' );
			}
		} else {
			$message = __( 'You do not have sufficient privileges to edit an item.', 'cleverness-to-do-list' );
		}

		return $message;
	}
	/**
	 * Checks the WordPress version to make sure the plugin is compatible
	 * @static
	 */
	public static function check_wp_version() {
		global $wp_version;
		$exit_msg = __( 'To-Do List requires WordPress 3.3 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update.</a>', 'cleverness-to-do-list' );
		if ( version_compare( $wp_version, "3.3", "<" ) ) {
			exit( $exit_msg );
		}
	}

	/**
	 * Set priority, user, url, and action variables
	 * @param $current_user
	 * @param $userdata
	 * @return array
	 */
	public static function set_variables( $current_user, $userdata ) {
		$priorities = array( 0 => CTDL_Loader::$settings['priority_0'],
		                     1 => CTDL_Loader::$settings['priority_1'],
		                     2 => CTDL_Loader::$settings['priority_2'] );
		$user = CTDL_Lib::get_user_id( $current_user, $userdata );
		$url = CTDL_Lib::get_page_url();
		$url = strtok( $url, '?' );
		$action = ( isset( $_GET['action'] ) ? $_GET['action'] : '' );
		return array( $priorities, $user, $url, $action );
	}

	/* Check if User Has Permission */
	public static function check_permission( $type, $action ) {

		switch ( $type ) {
			case 'category':
				// check if categories are enabled and the user has the capability or the list view is individual
				if ( CTDL_Loader::$settings['categories'] == '1' && ( current_user_can( CTDL_Loader::$settings[$action.'_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) ) {
					return true;
				} else {
					return false;
				}
				break;
			case 'todo':
				if ( current_user_can( CTDL_Loader::$settings[$action.'_capability'] ) ) {
					return true;
				} else {
					return false;
				}
				break;
		}
	}

	/**
	 * Gets the ID of a user
	 * @param $current_user
	 * @param $userdata
	 * @return int
	 */
	public static function get_user_id( $current_user, $userdata ) {
		$user = ( CTDL_Loader::$settings['list_view'] == 2 ? $current_user->ID : $userdata->ID );
		return $user;
	}

	/* Get list of users */
	public static function get_users( $role ) {
		$wp_user_search = new WP_User_Query( array( 'role' => $role ) );
		return $wp_user_search->get_results();
	}

	/**
	 * Get the Correct URL of a Page
	 * @return string
	 */
	public static function get_page_url() {
		$pageURL = 'http';
		if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) { $pageURL .= "s"; }
		$pageURL .= "://";
		if ( $_SERVER["SERVER_PORT"] != "80" ) {
			//$pageURL .= $_SERVER["HTTP_HOST"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
			$pageURL .= $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

	/* Add Settings link to plugin */
	public static function add_settings_link( $links, $file ) {
		static $this_plugin;
		if ( !$this_plugin ) $this_plugin = CTDL_BASENAME;

		if ( $file == $this_plugin ) {
			$settings_link = '<a href="admin.php?page=cleverness-to-do-list-settings">'.__( 'Settings', 'cleverness-to-do-list' ).'</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}

	/* Create database table and add default options */
	public static function install_plugin () {
		global $wpdb, $userdata;
		get_currentuserinfo();

		$cleverness_todo_db_version = '1.9';
		if ( !function_exists( 'is_plugin_active_for_network' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		if ( is_plugin_active_for_network( __FILE__ ) ) {
			$prefix = $wpdb->base_prefix;
		} else {
			$prefix = $wpdb->prefix;
		}

		$table_name         = $prefix.'todolist';
		$cat_table_name     = $prefix.'todolist_cats';
		$status_table_name  = $prefix.'todolist_status';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
			$sql = "CREATE TABLE ".$table_name." (
	            id bigint(20) UNIQUE NOT NULL AUTO_INCREMENT,
	            author bigint(20) NOT NULL,
	            status tinyint(1) DEFAULT '0' NOT NULL,
	            priority tinyint(1) NOT NULL,
                todotext text NOT NULL,
		        assign int(10) NOT NULL,
		        progress int(3) NOT NULL,
		        deadline varchar(30) NOT NULL,
		        completed timestamp NOT NULL,
		        cat_id bigint(20) NOT NULL
	            );";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			$sql2 = "CREATE TABLE ".$cat_table_name." (
	            id bigint(20) UNIQUE NOT NULL AUTO_INCREMENT,
	            name varchar(100),
	            visibility tinyint(1) DEFAULT '0' NOT NULL
	            );";
			dbDelta( $sql2 );
			$sql3 = "CREATE TABLE ".$status_table_name." (
		        id bigint(20),
		        user bigint(20) NOT NULL,
	            status tinyint(1) DEFAULT '0' NOT NULL
	            );";
			dbDelta( $sql3 );
			$welcome_text = __( 'Add your first To-Do List item', 'cleverness-to-do-list' );
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
				'date_format'           => 'm-d-Y',
				'priority_0'            => __( 'Important', 'cleverness-to-do-list' ),
				'priority_1'            => __( 'Normal', 'cleverness-to-do-list' ),
				'priority_2'            => __( 'Low', 'cleverness-to-do-list' ),
				'assign'                => '1',
				'show_only_assigned'    => '1',
				'user_roles'            => 'contributor, author, editor, administrator',
				'email_assigned'        => '0',
				'email_from'            => html_entity_decode( get_bloginfo( 'name' ) ),
				'email_subject'         => __( 'A to-do list item has been assigned to you', 'cleverness-to-do-list' ),
				'email_text'            => __( 'The following item has been assigned to you.', 'cleverness-to-do-list' ),
				'show_id'               => '0',
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

			maybe_add_column( $table_name, 'assign', "ALTER TABLE `$table_name` ADD `assign` int(10) NOT NULL;" );
			maybe_add_column( $table_name, 'deadline', "ALTER TABLE `$table_name` ADD `deadline` varchar(30) NOT NULL;" );
			maybe_add_column( $table_name, 'progress', "ALTER TABLE `$table_name` ADD `progress` int(3) NOT NULL;" );
			maybe_add_column( $table_name, 'completed', "ALTER TABLE `$table_name` ADD `completed` timestamp NOT NULL;" );
			maybe_add_column( $table_name, 'cat_id', "ALTER TABLE `$table_name` ADD `cat_id` bigint(20) NOT NULL;" );
			maybe_create_table( $cat_table_name, "CREATE TABLE ".$cat_table_name." (
	            id bigint(20) UNIQUE NOT NULL AUTO_INCREMENT,
	            name varchar(100),
	            sort tinyint(3) DEFAULT '0' NOT NULL,
	            visibility tinyint(1) DEFAULT '0' NOT NULL
	            );");
			maybe_create_table( $status_table_name, "CREATE TABLE ".$status_table_name." (
	            id bigint(20),
	            user bigint(20) NOT NULL,
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
				'date_format'           => $the_options['date_format'],
				'priority_0'            => $the_options['priority_0'],
				'priority_1'            => $the_options['priority_1'],
				'priority_2'            => $the_options['priority_2'],
				'assign'                => $the_options['assign'],
				'show_only_assigned'    => $the_options['show_only_assigned'],
				'user_roles'            => $the_options['user_roles'],
				'email_assigned'        => $the_options['email_assigned'],
				'email_from'            => $the_options['email_from'],
				'email_subject'         => $the_options['email_subject'],
				'email_text'            => $the_options['email_text'],
				'show_id'               => '0',
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

}
?>