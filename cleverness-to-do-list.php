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
Based on the to-do plugin by Abstract Dimensions with a patch by WordPress by Example.
*/

add_action( 'init', 'cleverness_todo_loader' );

function cleverness_todo_loader() {
	global $wpdb;

	define( 'CTDL_FILE', __FILE__ );
	define( 'CTDL_BASENAME', plugin_basename( __FILE__ ) );
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
	CTDL_Loader::init();

	$action = '';
	if ( isset( $_GET['action'] ) ) $action = $_GET['action'];
	if ( isset( $_POST['action'] ) ) $action = $_POST['action'];

	switch( $action ) {

		case 'addtodo':
			CTDL_Lib::insert_todo();
			break;

		case 'updatetodo':
			CTDL_Lib::edit_todo();
			break;

		case 'completetodo':
			$cleverness_todo_complete_nonce = $_REQUEST['_wpnonce'];
			if ( !wp_verify_nonce( $cleverness_todo_complete_nonce, 'todocomplete' ) ) die( 'Security check failed' );
			CTDL_LIb::complete_todo( absint( $_GET['id'] ), 1 );
			break;

		case 'uncompletetodo':
			$cleverness_todo_complete_nonce = $_REQUEST['_wpnonce'];
			if ( !wp_verify_nonce( $cleverness_todo_complete_nonce, 'todocomplete' ) ) die( 'Security check failed' );
			CTDL_LIb::complete_todo( absint( $_GET['id'] ), 0 );
			break;

		case 'purgetodo':
			CTDL_Lib::delete_all_todos();
			break;

	} // end switch

}

register_activation_hook( __FILE__, 'cleverness_todo_activation' );
function cleverness_todo_activation() {
	include_once 'includes/cleverness-to-do-list-library.class.php';
	CTDL_Lib::install_plugin();
}

// @todo can it be moved?
include_once 'includes/cleverness-to-do-list-widget.class.php';

?>