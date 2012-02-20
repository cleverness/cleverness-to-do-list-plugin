<?php
if ( !defined( 'ABSPATH') && !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

if ( current_user_can('delete_plugins') ) {
	global $wpdb;

	/* @todo delete posts? */
	delete_option( 'cleverness_todo_settings' );
	delete_option( 'atd_db_version' );
	delete_option( 'CTDL_db_version' );
	delete_option( 'CTDL_general' );
	delete_option( 'CTDL_advanced' );
	delete_option( 'CTDL_permissions' );
	delete_option( 'CTDL_categories' );
	delete_option( 'CTDL_dashboard_settings' );

	if ( !function_exists( 'is_plugin_active_for_network' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	if ( is_plugin_active_for_network( __FILE__ ) ) {
		$prefix = $wpdb->base_prefix;
	} else {
		$prefix = $wpdb->prefix;
	}

  	$thetable = $prefix."todolist";
  	$wpdb->query( "DROP TABLE IF EXISTS $thetable" );
  	$thecattable = $prefix."todolist_cats";
  	$wpdb->query( "DROP TABLE IF EXISTS $thecattable" );
	$thestatustable = $prefix."todolist_status";
  	$wpdb->query( "DROP TABLE IF EXISTS $thestatustable" );
}
?>