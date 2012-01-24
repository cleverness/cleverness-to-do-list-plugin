<?php























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
	$cleverness_todo_option = array_merge( get_option( 'cleverness-to-do-list-general' ), get_option( 'cleverness-to-do-list-advanced' ), get_option( 'cleverness-to-do-list-permissions' ) );

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



?>