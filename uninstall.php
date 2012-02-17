<?php
if ( !defined( 'ABSPATH') && !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

if ( current_user_can('delete_plugins') ) {
/* @todo delete posts? */
	delete_option( 'cleverness_todo_settings' );
	delete_option( 'atd_db_version' );
	delete_option( 'CTDL_db_version' );
	delete_option( 'CTDL_general' );
	delete_option( 'CTDL_advanced' );
	delete_option( 'CTDL_permissions' );
	delete_option( 'CTDL_categories' );
	delete_option( 'CTDL_dashboard_settings' );

  	global $wpdb;
  	$thetable = $wpdb->prefix."todolist";
  	$wpdb->query( "DROP TABLE IF EXISTS $thetable" );
  	$thecattable = $wpdb->prefix."todolist_cats";
  	$wpdb->query( "DROP TABLE IF EXISTS $thecattable" );
	$thestatustable = $wpdb->prefix."todolist_status";
  	$wpdb->query( "DROP TABLE IF EXISTS $thestatustable" );
}
?>