<?php
/**
 * Categories class
 * @package cleverness-to-do-list
 * @author C.M. Kendrick
 * @version 3.0
 */

class CTDL_Categories {

	/* Get to-do category name */
	public static function get_category_name( $cat_id ) {
		global $wpdb;

		$sql = "SELECT name FROM ".CTDL_CATS_TABLE." WHERE id = '%d' LIMIT 1";
		$result = $wpdb->get_row( $wpdb->prepare( $sql, $cat_id ) );
		return $result;
	}

	/* Get a to-do list category */
	public static function get_category() {
		global $wpdb;

		$sql = "SELECT id, name, visibility FROM ".CTDL_CATS_TABLE." WHERE id = '%d' LIMIT 1";
		$result = $wpdb->get_row( $wpdb->prepare( $sql, $_POST['cleverness_todo_cat_id'] ) );
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

		$sql = 'DELETE FROM ' . CTDL_CATS_TABLE . ' WHERE id = "%d"';
		$results = $wpdb->query( $wpdb->prepare( $sql, $_POST['cleverness_todo_cat_id'] ) );
		$success = ( $results === FALSE ? 0 : 1 );
		return $success;
	}

	/* Get Category Ajax */
	public static function get_category_callback() {
		$cleverness_todo_permission = CTDL_Lib::check_permission( 'category', 'add_cat' );

		if ( $cleverness_todo_permission === true ) {
			$cleverness_todo = CTDL_Categories::get_category();
			echo json_encode( array( 'cleverness_todo_cat_name' => $cleverness_todo->name, 'cleverness_todo_cat_visibility' => $cleverness_todo->visibility ) );
		}

		die(); // this is required to return a proper result
	}

	/* Update Category Ajax */
	public static function update_category_callback() {
		check_ajax_referer( 'cleverness-todo-cat' );
		$cleverness_todo_permission = CTDL_Lib::check_permission( 'category', 'add_cat' );

		if ( $cleverness_todo_permission === true ) {
			$cleverness_todo_status = CTDL_Categories::update_category();
		} else {
			$cleverness_todo_status = 2;
		}

		echo $cleverness_todo_status;
		die(); // this is required to return a proper result
	}

	/* Delete Category Ajax */
	public static function delete_category_callback() {
		check_ajax_referer( 'cleverness-todo-cat' );
		$cleverness_todo_permission = CTDL_Lib::check_permission( 'category', 'add_cat' );

		if ( $cleverness_todo_permission === true ) {
			$cleverness_todo_status = CTDL_Categories::delete_category();
		} else {
			$cleverness_todo_status = 2;
		}

		echo $cleverness_todo_status;
		die(); // this is required to return a proper result
	}

	public static function initialize_categories() {
		global $cleverness_todo_cat_page;

		wp_register_script( 'cleverness_todo_category_js', CTDL_PLUGIN_URL.'/js/categories.js', '', 1.0, true );
		add_action( 'admin_print_scripts-' . $cleverness_todo_cat_page, __CLASS__.'::add_category_js' );
		add_action( 'wp_ajax_cleverness_todo_cat_get', __CLASS__.'::get_category_callback' );
		add_action( 'wp_ajax_cleverness_todo_cat_update', __CLASS__.'::update_category_callback' );
		add_action( 'wp_ajax_cleverness_todo_cat_delete', __CLASS__.'::delete_category_callback' );
	}

	public static function add_category_js() {
		wp_enqueue_script( 'cleverness_todo_category_js' );
		wp_enqueue_script( 'jquery-color' );
		wp_localize_script( 'cleverness_todo_category_js', 'cltdcat', CTDL_Categories::get_js_vars() );
	}


	// returns various JavaScript vars needed for the scripts
	public static function get_js_vars() {
		return array(
			'SUCCESS_MSG'       => __( 'Category Deleted.', 'cleverness-to-do-list' ),
			'ERROR_MSG'         => __( 'There was a problem performing that action.', 'cleverness-to-do-list' ),
			'PERMISSION_MSG'    => __( 'You do not have sufficient privileges to do that.', 'cleverness-to-do-list' ),
			'EDIT_CAT'          => __( 'Edit Category', 'cleverness-to-do-list' ),
			'PUBLIC'            => __( 'Public', 'cleverness-to-do-list' ),
			'PRIVATE'           => __( 'Private', 'cleverness-to-do-list' ),
			'CONFIRMATION_MSG'  => __( "You are about to permanently delete the selected item. \n 'Cancel' to stop, 'OK' to delete.", 'cleverness-to-do-list' ),
			'NONCE'             => wp_create_nonce( 'cleverness-todo-cat' )
		);
	}

	public static function create_category_page() {

		$cleverness_todo_message = '';
		$cleverness_todo_action = '';
		if ( isset( $_GET['cleverness_todo_action'] ) ) $cleverness_todo_action = $_GET['cleverness_todo_action'];
		if ( isset( $_POST['cleverness_todo_action'] ) ) $cleverness_todo_action = $_POST['cleverness_todo_action'];

		switch( $cleverness_todo_action ) {

		// call the add to category function
			case 'addtodocat':
				if ( $_POST['cleverness_todo_cat_name'] != '' ) {
					if ( !wp_verify_nonce( $_POST['_todo_add_cat_nonce'], 'todoaddcat') ) die( 'Security check failed' );
					$cleverness_todo_status = CTDL_Categories::insert_category();
					if ( $cleverness_todo_status != 1 ) {
						$cleverness_todo_message = __( 'There was a problem performing that action.', 'cleverness-to-do-list' );
					}
				} else {
					$cleverness_todo_message = __( 'Category name cannot be blank.', 'cleverness-to-do-list' );
				}
				break;

			default:
				break;
		} // end switch

		?>
		<div class="wrap">
		<div class="icon32"><img src="<?php echo CTDL_PLUGIN_URL; ?>/images/cleverness-todo-icon.png" alt="" /></div>
		<h2><?php _e( 'To-Do List Categories', 'cleverness-to-do-list' ); ?></h2>

		<div id="message"><?php if ( $cleverness_todo_message != '' ) echo '<p class="error below-h2">'.$cleverness_todo_message.'</p>'; ?></div>

		<h3><?php _e( 'Add New Category', 'cleverness-to-do-list' ); ?></h3>
		<form name="addtodocat" id="addtodocat" action="" method="post">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="cleverness_todo_cat_name"><?php _e( 'Category Name', 'cleverness-to-do-list' ); ?></label></th>
					<td><input type="text" name="cleverness_todo_cat_name" id="cleverness_todo_cat_name" class="regular-text" value="" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cleverness_todo_cat_visibility"><?php _e( 'Visibility', 'cleverness-to-do-list' ); ?></label></th>
					<td>
						<select name="cleverness_todo_cat_visibility" id="cleverness_todo_cat_visibility">
							<option value="0" selected="selected"><?php _e( 'Public', 'cleverness-to-do-list' ); ?>&nbsp;</option>
							<option value="1"><?php _e( 'Private', 'cleverness-to-do-list' ); ?></option>
						</select>
						<br /><span class="description"><?php _e('Private categories are not visible using the sidebar widgets or shortcode.', 'cleverness-to-do-list'); ?></span>
					</td>
				</tr>
				<tr><td></td>
					<td>
						<?php wp_nonce_field( 'todoaddcat', '_todo_add_cat_nonce' ); ?>
						<input type="hidden" name="cleverness_todo_action" value="addtodocat" />
						<input type="submit" name="button" id="add-todo" class="button-primary" value="<?php _e( 'Add Category', 'cleverness-to-do-list' ); ?>" />
					</td>
				</tr>
			</table>
		</form>

		<h3><?php _e( 'Existing Categories', 'cleverness-to-do-list' ); ?></h3>
		<table id="todo-cats" class="widefat">
			<thead>
			<tr>
				<th id="id-col"><?php _e( 'ID', 'cleverness-to-do-list' ); ?></th>
				<th class="row-title"><?php _e( 'Name', 'cleverness-to-do-list' ); ?></th>
				<th id="vis-col"><?php _e( 'Visibility', 'cleverness-to-do-list' ); ?></th>
				<th id="action-col"><?php _e( 'Action', 'cleverness-to-do-list' ); ?></th>
			</tr>
			</thead>
			<tbody>
				<?php
				$cleverness_todo_results = CTDL_Categories::get_categories();

				if ( $cleverness_todo_results ) {
					foreach ( $cleverness_todo_results as $cleverness_todo_result ) {
						?>
					<tr id="<?php echo absint( $cleverness_todo_result->id ); ?>">
						<td><?php echo absint( $cleverness_todo_result->id ); ?></td>
						<td class="row-title"><?php echo esc_attr( $cleverness_todo_result->name ); ?></td>
						<td><?php if ( $cleverness_todo_result->visibility == '0' ) {
							echo __( 'Public', 'cleverness-to-do-list' );
						} else if ( $cleverness_todo_result->visibility == '1' ) {
							echo __( 'Private', 'cleverness-to-do-list' );
						}?></td>
						<td>
							<input class="edit-todo button-secondary" type="button" value="<?php _e( 'Edit' ); ?>" />
							<input class="delete-todo button-secondary" type="button" value="<?php _e( 'Delete' ); ?>" />
						</td>
					</tr>
						<?php } } ?>
			</tbody>
			<tfoot>
			<tr>
				<th class="row-title"><?php _e( 'ID', 'cleverness-to-do-list' ); ?></th>
				<th><?php _e(' Name', 'cleverness-to-do-list' ); ?></th>
				<th><?php _e( 'Visibility', 'cleverness-to-do-list' ); ?></th>
				<th><?php _e( 'Action', 'cleverness-to-do-list' ); ?></th>
			</tr>
			</tfoot>
		</table>

		</div>
		<?php
	}

}
?>