<?php
/**
 * Cleverness To-Do List Plugin Categories
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.0
 * @todo add meta value for sort order and enable sorting
 */

/**
 * Categories class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Categories {

	/**
	 * Get to-do category name
	 * @static
	 * @param int $category_id
	 * @return mixed
	 */
	public static function get_category_name( $category_id ) {
		$category = get_term( $category_id, 'todocategories' );
		return $category->name;
	}

	/**
	 * Get a specific to-do list category
	 * @static
	 * @return mixed|null|WP_Error
	 */
	public static function get_category() {
		$category = get_term( $_POST['cleverness_todo_cat_id'], 'todocategories' );
		return $category;
	}

	/**
	 * Get all to-do list categories
	 * @static
	 * @return array|WP_Error
	 */
	public static function get_categories() {
		$categories = get_terms( 'todocategories', '&hide_empty=0' );
		return $categories;
	}

	/**
	 * Add a new to-do list category to the taxonomy
	 * @static
	 * @return int
	 */
	public static function insert_category() {
		$term = wp_insert_term( $_POST['cleverness_todo_cat_name'], 'todocategories' );
		if ( !is_wp_error( $term ) ) {
			$category_id = $term['term_id'];
			$options = get_option( 'cleverness_todo_categories' );
			$options["category_$category_id"] = $_POST['cleverness_todo_cat_visibility'];
			update_option( "cleverness_todo_categories", $options );
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Update to-do list category
	 * @static
	 * @return int
	 */
	public static function update_category() {
		$category_id = absint( $_POST['cleverness_todo_cat_id'] );
		$term = wp_update_term( $category_id, 'todocategories', array( 'name' => $_POST['cleverness_todo_cat_name'] ) );
		if ( !is_wp_error( $term ) ) {
			$options = get_option( 'cleverness_todo_categories' );
			$options["category_$category_id"] = $_POST['cleverness_todo_cat_visibility'];
			update_option( "cleverness_todo_categories", $options );
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Delete to-do list category
	 * @static
	 * @return int
	 */
	public static function delete_category() {
		wp_delete_term( $_POST['cleverness_todo_cat_id'], 'todocategories' );
		return 1;
	}

	/**
	 * Get a specific to-do list category using ajax
	 * @static
	 */
	public static function get_category_callback() {
		$permission = CTDL_Lib::check_permission( 'category', 'add_cat' );

		if ( $permission === true ) {
			$cleverness_todo = CTDL_Categories::get_category();
			$category_id = $cleverness_todo->term_id;
			$visibility = get_option( 'cleverness_todo_categories' );
			$visibility = ( $visibility["category_$category_id"] != '' ? $visibility["category_$category_id"] : '0' );
			echo json_encode( array( 'cleverness_todo_cat_name' => $cleverness_todo->name, 'cleverness_todo_cat_visibility' => $visibility ) );
		}

		die(); // this is required to return a proper result
	}

	/**
	 * Update a to-do list category using ajax
	 * @static
	 */
	public static function update_category_callback() {
		check_ajax_referer( 'cleverness-todo-cat' );
		$permission = CTDL_Lib::check_permission( 'category', 'add_cat' );
		$status = ( $permission === true ? CTDL_Categories::update_category() : 2 );
		echo $status;
		die(); // this is required to return a proper result
	}

	/**
	 * Delete a to-do list category using ajax
	 * @static
	 */
	public static function delete_category_callback() {
		check_ajax_referer( 'cleverness-todo-cat' );
		$permission = CTDL_Lib::check_permission( 'category', 'add_cat' );
		$status = ( $permission === true ? CTDL_Categories::delete_category() : 2 );
		echo $status;
		die(); // this is required to return a proper result
	}

	/**
	 * Create the category management page
	 * @static
	 */
	public static function create_category_page() {
		$cleverness_todo_message = '';
		$cleverness_todo_action = '';
		if ( isset( $_GET['cleverness_todo_action'] ) ) $cleverness_todo_action = $_GET['cleverness_todo_action'];
		if ( isset( $_POST['cleverness_todo_action'] ) ) $cleverness_todo_action = $_POST['cleverness_todo_action'];

		switch( $cleverness_todo_action ) {

			case 'addtodocat':
				if ( $_POST['cleverness_todo_cat_name'] != '' ) {
					if ( !wp_verify_nonce( $_POST['_todo_add_cat_nonce'], 'todoaddcat') ) die( 'Security check failed' );
					$status = CTDL_Categories::insert_category();
					if ( $status != 1 ) {
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

		<?php if ( $cleverness_todo_message != '' ) echo '<div id="message" class="error below-h2"><p>'.$cleverness_todo_message.'</p></div>'; ?>

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
				$categories = CTDL_Categories::get_categories();

				if ( $categories ) {
					foreach ( $categories as $category ) {
						$category_id = $category->term_id;
						$visibility = get_option( 'cleverness_todo_categories' );
						$visibility = ( $visibility["category_$category_id"] != '' ? $visibility["category_$category_id"] : '0' );
						?>
						<tr id="<?php echo $category_id; ?>">
							<td><?php echo $category_id; ?></td>
							<td class="row-title"><?php echo esc_attr( $category->name ); ?></td>
							<td><?php if ( $visibility == '0' ) {
								echo __( 'Public', 'cleverness-to-do-list' );
							} else if ( $visibility == '1' ) {
								echo __( 'Private', 'cleverness-to-do-list' );
							} ?></td>
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

	/**
	 * Set up the category page actions
	 * @static
	 */
	public static function initialize_categories() {
		global $cleverness_todo_cat_page;

		wp_register_script( 'cleverness_todo_category_js', CTDL_PLUGIN_URL.'/js/categories.js', '', 1.0, true );
		add_action( 'admin_print_scripts-' . $cleverness_todo_cat_page, __CLASS__.'::add_category_js' );
		add_action( 'wp_ajax_cleverness_todo_cat_get', __CLASS__.'::get_category_callback' );
		add_action( 'wp_ajax_cleverness_todo_cat_update', __CLASS__.'::update_category_callback' );
		add_action( 'wp_ajax_cleverness_todo_cat_delete', __CLASS__.'::delete_category_callback' );
	}

	/**
	 * Add Javascript to the category page
	 * @static
	 */
	public static function add_category_js() {
		wp_enqueue_script( 'cleverness_todo_category_js' );
		wp_enqueue_script( 'jquery-color' );
		wp_localize_script( 'cleverness_todo_category_js', 'ctdlcat', CTDL_Categories::get_js_vars() );
	}

	/**
	 * Localize the category Javascript variables
	 * @static
	 * @return array
	 */
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

}

?>