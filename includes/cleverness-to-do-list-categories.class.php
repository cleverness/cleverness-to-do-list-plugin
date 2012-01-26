<?php
/**
 * Categories class
 * @package cleverness-to-do-list
 * @author C.M. Kendrick
 * @version 3.0
 */

class CTDL_Categories {

	/* Get to-do category name */
	public static function get_category_name( $id ) {
		global $wpdb;

		$cat = "SELECT name FROM ".CTDL_CATS_TABLE." WHERE id = '%d' LIMIT 1";
		$result = $wpdb->get_row( $wpdb->prepare( $cat, $id ) );
		return $result;
	}

	/* Get a to-do list category */
	public static function get_category() {
		global $wpdb;

		$select = "SELECT id, name, visibility FROM ".CTDL_CATS_TABLE." WHERE id = '%d' LIMIT 1";
		$result = $wpdb->get_row( $wpdb->prepare( $select, $_POST['cleverness_todo_cat_id'] ) );
		return $result;
	}

	/* Get to-do list categories */
	public static function get_categories() {
		global $wpdb;

		if ( CTDL_Loader::$settings['categories'] == '1' ) {
			$sql = "SELECT id, name, visibility FROM ".CTDL_CATS_TABLE.' ORDER BY name';
			$results = $wpdb->get_results( $wpdb->prepare( $sql ) );
			return $results;
		} else {
			$message = __( 'Categories are not enabled.', 'cleverness-to-do-list' );
		}

		return $message;
	}

	/* Insert new to-do category into the database */
	public static function insert_category() {
		global $wpdb;

		$results = $wpdb->insert( CTDL_CATS_TABLE, array( 'name' => $_POST['cleverness_todo_cat_name'], 'visibility' => $_POST['cleverness_todo_cat_visibility'] ) );
		$success = ( $results === FALSE ? 0 : 1 );
		return $success;
	}

	/* Update to-do list category */
	public static function update_category() {
		global $wpdb;

		$results = $wpdb->update( CTDL_CATS_TABLE, array( 'name' => $_POST['cleverness_todo_cat_name'],
		                                                  'visibility' => $_POST['cleverness_todo_cat_visibility'] ),
														   array( 'id' => absint( $_POST['cleverness_todo_cat_id'] ) ) );
		$success = ( $results === FALSE ? 0 : 1 );
		return $success;
	}

	/* Delete to-do list category */
	public static function delete_category() {
		global $wpdb;

		$delete = 'DELETE FROM ' . CTDL_CATS_TABLE . ' WHERE id = "%d"';
		$results = $wpdb->query( $wpdb->prepare( $delete, $_POST['cleverness_todo_cat_id'] ) );
		$success = ( $results === FALSE ? 0 : 1 );
		return $success;
	}



}
?>