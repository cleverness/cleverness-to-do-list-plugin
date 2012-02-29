<?php
/*
Plugin Name: Cleverness To-Do List
Version: 3.0.4
Description: Manage to-do list items on a individual or group basis with categories. Includes a dashboard widget and a sidebar widget.
Author: C.M. Kendrick
Author URI: http://cleverness.org
Plugin URI: http://cleverness.org/plugins/to-do-list/
*/

/**
 * Cleverness To-Do List Plugin Main File
 *
 * This plugin was based on the to-do plugin by Abstract Dimensions with a patch by WordPress by Example.
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.0.4
 */

add_action( 'init', 'cleverness_todo_loader' );
register_activation_hook( __FILE__, 'cleverness_todo_activation' );
include_once 'includes/cleverness-to-do-list-widget.class.php';

/**
 * Define constants and load the plugin
 */
function cleverness_todo_loader() {

	define( 'CTDL_DB_VERSION', '3.0.3' ); // also update in install function in library
	define( 'CTDL_PLUGIN_VERSION', '3.0.4' );
	define( 'CTDL_FILE', __FILE__ );
	define( 'CTDL_BASENAME', plugin_basename( __FILE__ ) );
	define( 'CTDL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'CTDL_PLUGIN_URL', plugins_url( '', __FILE__ ) );

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

		case 'deletetables':
			CTDL_Lib::delete_tables();
			break;

	}

}

/**
 * Install plugin on plugin activation
 */
function cleverness_todo_activation() {
	define( 'CTDL_FILE', __FILE__ );
	include_once 'includes/cleverness-to-do-list-library.class.php';
	CTDL_Lib::install_plugin();
}

?>