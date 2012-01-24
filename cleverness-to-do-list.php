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

	include_once 'includes/cleverness-to-do-list-loader.class.php';
	include_once 'includes/cleverness-to-do-list.class.php';
	include_once 'includes/cleverness-to-do-list-library.class.php';

	CTDL_Loader::init();

	$action = '';
	if ( isset( $_GET['action'] ) ) $action = $_GET['action'];
	if ( isset( $_POST['action'] ) ) $action = $_POST['action'];

	switch( $action ) {

		case 'addtodo':
			$message = CTDL_Lib::insert_todo();
		break;

		case 'updatetodo':
			$message = CTDL_Lib::edit_todo();
		break;

		case 'completetodo':
			$message = CTDL_LIb::complete_todo( absint( $_GET['id'] ), 1 );
			break;

		case 'uncompletetodo':
			$message = CTDL_LIb::complete_todo( absint( $_GET['id'] ), 0 );
			break;

		case 'purgetodo':
			$message = CTDL_Lib::delete_all_todos();
		break;

	} // end switch

	/* Delete To-Do Ajax
	@todo not working
	*/
	function cleverness_delete_todo_callback() {
		check_ajax_referer( 'cleverness-todo' );
		$cleverness_todo_permission = CTDL_LIB::check_permission( 'todo', 'delete' );

		if ( $cleverness_todo_permission === true ) {
			$cleverness_todo_status = CTDL_LIB::delete_todo();
		} else {
			$cleverness_todo_status = 2;
		}

		echo $cleverness_todo_status;
		die(); // this is required to return a proper result
	}

	/* Add plugin info to admin footer */
	function cleverness_todo_admin_footer() {
		$plugin_data = get_plugin_data( __FILE__ );
		printf( __( "%s plugin | Version %s | by %s | <a href='https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=cindy@cleverness.org' target='_blank'>Donate</a><br />", 'cleverness-to-do-list' ), $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author'] );
	}

	function cleverness_todo_checklist_complete_callback() {
		check_ajax_referer( 'cleverness-todo' );
		$cleverness_todo_permission = CTDL_Lib::check_permission( 'todo', 'complete' );

		if ( $cleverness_todo_permission === true ) {
			$cleverness_id = intval( $_POST['cleverness_id'] );
			$cleverness_status = intval( $_POST['cleverness_status'] );

			$message = CTDL_Lib::complete_todo( $cleverness_id, $cleverness_status );
		} else {
			$message = __( 'You do not have sufficient privileges to do that.', 'cleverness-to-do-list' );
		}
		echo $message;

		die(); // this is required to return a proper result
	}

}

register_activation_hook( __FILE__, 'cleverness_todo_activation' );
function cleverness_todo_activation() {
	include_once 'includes/cleverness-to-do-list-library.class.php';
	CTDL_Lib::install_plugin();
}

?>