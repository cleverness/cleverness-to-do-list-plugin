<?php
/*
Plugin Name: Cleverness To-Do List
Version: 3.4.2
Description: Manage to-do list items on a individual or group basis. Includes a dashboard widget, a sidebar widget, and shortcodes.
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
 * @version 3.4
 */
defined( 'ABSPATH' ) or die();
add_action( 'init', 'cleverness_todo_loader' );
include_once 'includes/cleverness-to-do-list-widget.class.php';

/**
 * Define constants and load the plugin
 */
function cleverness_todo_loader() {

	if ( ! defined( 'CTDL_DB_VERSION' ) )     define( 'CTDL_DB_VERSION', '3.4' ); // also update in cleverness_todo_activation at the bottom of this file
	if ( ! defined( 'CTDL_PLUGIN_VERSION' ) ) define( 'CTDL_PLUGIN_VERSION', '3.4.2' );
	if ( ! defined( 'CTDL_FILE' ) )           define( 'CTDL_FILE', __FILE__ );
	if ( ! defined( 'CTDL_BASENAME' ) )       define( 'CTDL_BASENAME', plugin_basename( __FILE__ ) );
	if ( ! defined( 'CTDL_PLUGIN_DIR' ) )     define( 'CTDL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	if ( ! defined( 'CTDL_PLUGIN_URL' ) )     define( 'CTDL_PLUGIN_URL', plugins_url( '', __FILE__ ) );

	$language_path = plugin_basename( dirname( __FILE__ ) .'/languages' );
	load_plugin_textdomain( 'cleverness-to-do-list', '', $language_path );

	include_once 'includes/cleverness-to-do-list-loader.class.php';

	CTDL_Loader::init();

	if ( !defined( 'CTDL_PP' ) ) {
		if ( in_array( 'post-planner/post-planner.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && CTDL_Loader::$settings['post_planner'] == 1 ) {
			define( 'CTDL_PP', true );
		} else {
			define( 'CTDL_PP', false );
		}
	}

	$action = '';
	if ( isset( $_GET['action'] ) )  $action = $_GET['action'];
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
			if ( !wp_verify_nonce( $cleverness_todo_complete_nonce, 'todocomplete' ) ) die( __( 'Security check failed', 'cleverness-to-do-list' ) );
			CTDL_Lib::complete_todo( absint( $_GET['id'] ), 1 );
			break;

		case 'uncompletetodo':
			$cleverness_todo_complete_nonce = $_REQUEST['_wpnonce'];
			if ( !wp_verify_nonce( $cleverness_todo_complete_nonce, 'todocomplete' ) ) die( __( 'Security check failed', 'cleverness-to-do-list' ) );
			CTDL_Lib::complete_todo( absint( $_GET['id'] ), 0 );
			break;

		case 'purgetodo':
			CTDL_Lib::delete_all_completed_todos();
			break;

		case 'deletetables':
			CTDL_Lib::delete_tables();
			break;

		case 'deletealltodos':
			CTDL_Lib::delete_all_todos();
			break;

	}

}

/**
 * Install plugin on plugin activation
 */
function cleverness_todo_activation() {
	global $wp_version;

	$exit_msg = __( 'To-Do List requires WordPress 3.8 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update.</a>', 'cleverness-to-do-list' );
	if ( version_compare( $wp_version, '3.8', '<' ) ) {
		exit( $exit_msg );
	}

	if ( ! defined( 'CTDL_DB_VERSION' ) ) define( 'CTDL_DB_VERSION','3.4' );
	if ( ! defined( 'CTDL_FILE' ) )       define( 'CTDL_FILE', __FILE__ );
	include_once 'includes/cleverness-to-do-list-library.class.php';

	if ( get_option( 'CTDL_db_version' ) ) {
		$installed_ver = get_option( 'CTDL_db_version' );
	} else {
		$installed_ver = 0;
	}

	if ( CTDL_DB_VERSION != $installed_ver ) {
		CTDL_Lib::install_plugin();
	}

}

register_activation_hook( __FILE__, 'cleverness_todo_activation' );