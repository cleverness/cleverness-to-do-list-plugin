<?php
/*
Plugin Name: Cleverness To-Do List
Version: 3.0
Description: Manage to-do list items on a individual or group basis with categories. Includes a dashboard widget and a sidebar widget.
Author: C.M. Kendrick
Author URI: http://cleverness.org
Plugin URI: http://cleverness.org/plugins/to-do-list/
*/

/*
Based on the ToDo plugin by Abstract Dimensions with a patch by WordPress by Example.
*/

add_action( 'init', 'cleverness_todo_loader' );

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

	$cleverness_todo_option = array_merge( get_option( 'cleverness-to-do-list-general' ), get_option( 'cleverness-to-do-list-advanced' ), get_option( 'cleverness-to-do-list-permissions' ) );

	ClevernessToDoLoader::init( $cleverness_todo_option );

	$action = '';
	if ( isset( $_GET['action'] ) ) $action = $_GET['action'];
	if ( isset( $_POST['action'] ) ) $action = $_POST['action'];

	switch( $action ) {

		case 'addtodo':
			$message = '';
			if ( $_POST['cleverness_todo_description'] != '' ) {
				$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'add' );

				if ( $cleverness_todo_permission === true ) {
					$assign = ( isset($_POST['cleverness_todo_assign']) ?  $_POST['cleverness_todo_assign'] : 0 );
					$deadline = ( isset($_POST['cleverness_todo_deadline']) ?  $_POST['cleverness_todo_deadline'] : '' );
					$progress = ( isset($_POST['cleverness_todo_progress']) ?  $_POST['cleverness_todo_progress'] : 0 );
					$category = ( isset($_POST['cleverness_todo_category']) ?  $_POST['cleverness_todo_category'] : '' );

					if ( !wp_verify_nonce( $_REQUEST['todoadd'], 'todoadd' ) ) die( 'Security check failed' );
					if ( $cleverness_todo_option['email_assigned'] == '1' && $cleverness_todo_option['assign'] == '0' ) {
						$message = cleverness_todo_email_user( $assign, $deadline, $category );
						}
					$message .= cleverness_todo_insert( $assign, $deadline, $progress, $category );
				} else {
		   		    $message = __( 'You do not have sufficient privileges to add an item.', 'cleverness-to-do-list' );
				}

			} else {
				$message = __( 'To-Do cannot be blank.', 'cleverness-to-do-list' );
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
				if ( !wp_verify_nonce( $_REQUEST['todoupdate'], 'todoupdate' ) ) die( 'Security check failed' );
				$message = cleverness_todo_update( $assign, $deadline, $progress, $category );
			} else {
				$message = __( 'You do not have sufficient privileges to edit an item.', 'cleverness-to-do-list' );
				}
		break;

		case 'completetodo':
			$id = absint( $_GET['id'] );
			$cleverness_todo_complete_nonce = $_REQUEST['_wpnonce'];
			if ( !wp_verify_nonce( $cleverness_todo_complete_nonce, 'todocomplete' ) ) die( 'Security check failed' );
			$message = cleverness_todo_complete ($id, '1' );
			break;

		case 'uncompletetodo':
			$id = absint( $_GET['id'] );
			$cleverness_todo_complete_nonce = $_REQUEST['_wpnonce'];
			if ( !wp_verify_nonce( $cleverness_todo_complete_nonce, 'todocomplete' ) ) die( 'Security check failed' );
			$message = cleverness_todo_complete( $id, '0' );
			break;

		case 'purgetodo':
			$message = '';
			$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'purge' );

			if ( $cleverness_todo_permission === true ) {
				$cleverness_todo_purge_nonce = $_REQUEST['_wpnonce'];
				if ( !wp_verify_nonce( $cleverness_todo_purge_nonce, 'todopurge' ) ) die( 'Security check failed' );
				$message = cleverness_todo_purge();
			} else {
				$message = __( 'You do not have sufficient privileges to edit an item.', 'cleverness-to-do-list' );
				}
		break;

	} // end switch

	/* Add plugin info to admin footer */
	function cleverness_todo_admin_footer() {
		$plugin_data = get_plugin_data( __FILE__ );
		printf( __( "%s plugin | Version %s | by %s | <a href='https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=cindy@cleverness.org' target='_blank'>Donate</a><br />", 'cleverness-to-do-list' ), $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author'] );
	}

}

register_activation_hook( __FILE__, 'cleverness_todo_activation' );
function cleverness_todo_activation() {
	include_once 'includes/cleverness-to-do-list-functions.php';
	cleverness_todo_install();
}

?>