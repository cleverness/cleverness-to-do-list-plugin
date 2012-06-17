<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

if ( current_user_can('delete_plugins') ) {
	global $wpdb;

	// delete options
	delete_option( 'cleverness_todo_settings' );
	delete_option( 'atd_db_version' );
	delete_option( 'CTDL_db_version' );
	delete_option( 'CTDL_general' );
	delete_option( 'CTDL_advanced' );
	delete_option( 'CTDL_permissions' );
	delete_option( 'CTDL_categories' );
	delete_option( 'CTDL_dashboard_settings' );
	delete_option( 'todocategories_children' );

	// delete old tables
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

	// delete to-do items
	$args = array(
		'post_type'      => 'todo',
		'posts_per_page' => -1,
	);

	$todo_items = new WP_Query( $args );

	while ( $todo_items->have_posts() ) : $todo_items->the_post();
		$id = get_the_ID();
		wp_delete_post( absint( $id ), true );
	endwhile;

	// delete taxonomy
	if ( !taxonomy_exists( 'todocategories' ) ) {
		$labels = array(
			'name'          => _x( 'Categories', 'taxonomy general name' ),
			'singular_name' => _x( 'Category', 'taxonomy singular name' ),
		);

		register_taxonomy( 'todocategories', array( 'todo' ), array(
			'hierarchical' => true,
			'labels'       => $labels,
			'show_ui'      => false,
			'query_var'    => false,
			'rewrite'      => false,
		) );
	}

	$terms = get_terms( 'todocategories', '&hide_empty=0' );
	$count = count( $terms );
	if ( $count > 0 ) {
		foreach ( $terms as $term ) {
			wp_delete_term( $term->term_id, 'todocategories' );
		}
	}

}