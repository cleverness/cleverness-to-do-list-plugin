<?php
/**
 * Categories class
 * @package cleverness-to-do-list
 * @author C.M. Kendrick
 * @version 3.0
 * @todo convert to todocategories taxonomy
 * @todo add meta value for privacy, sort order
 */

class CTDL_Categories {

	public static function init() {
		global $cat_options;
		$meta_sections = array();
		$meta_sections[] = array(
			'title' => 'To-Do Category Options',			// section title
			'taxonomies' => array('todocategories'),			// list of taxonomies. Default is array('category', 'post_tag'). Optional
			'id' => 'cleverness_todo_category_options',					// ID of each section, will be the option name

			'fields' => array(							// list of meta fields
				array(
					'name' => 'Privacy',					// field name
					'id' => 'privacy',						// field id, i.e. the meta key
					'type' => 'text',						// text box
				),

				array(
					'name' => 'Sort Order',
					'id' => 'sort_order',
					'type' => 'text',						// select box
					),

			)
		);

		foreach ($meta_sections as $meta_section) {
			$cat_options = new RW_Taxonomy_Meta($meta_section);
		}
	}

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
		$categories = get_terms( 'todocategories' );
		return $categories;
	}
	public static function get_categories_old() {
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
		global $cat_options;

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
				$categories = CTDL_Categories::get_categories();

				if ( $categories ) {
					foreach ( $categories as $category ) {
						?>
					<tr id="<?php echo absint( $category->term_id ); ?>">
						<td><?php echo absint( $category->term_id ); ?></td>
						<td class="row-title"><?php echo esc_attr( $category->name ); ?></td>
						<td><?php $cat_options->show( 'privacy', 'todocategories' ); ?></td>
						<td><?php if ( $category->visibility == '0' ) {
							echo __( 'Public', 'cleverness-to-do-list' );
						} else if ( $category->visibility == '1' ) {
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

/**
 * RW Taxonomy Meta Class
 * Add meta values to terms, mimic custom post fields
 *
 * Support input types: text, textarea, checkbox, checkbox list, radio box, select, wysiwyg, file, image, date, time, color
 *
 * @author Rilwis <rilwis@gmail.com>
 * @link http://www.deluxeblogtips.com
 * @example taxonomy-box-usage.php Sample declaration and usage of meta boxes
 * @version 1.0
 * @license GNU General Public License v3.0
 */

class RW_Taxonomy_Meta {
	protected $_meta;
	protected $_taxonomies;
	protected $_fields;

	function __construct($meta) {
		if (!is_admin()) return;

		$this->_meta = $meta;
		$this->_taxonomies = & $this->_meta['taxonomies'];
		$this->_fields = & $this->_meta['fields'];

		$this->add_missed_values();

		add_action('admin_init', array(&$this, 'add'));
		add_action('edit_term', array(&$this, 'save'), 10, 2);
		add_action('delete_term', array(&$this, 'delete'), 10, 2);

	}



	/******************** BEGIN META BOX PAGE **********************/

	// Add meta fields for taxonomies
	function add() {
		foreach (get_taxonomies(array('show_ui' => true)) as $tax_name) {
			if (in_array($tax_name, $this->_taxonomies)) {
				add_action($tax_name . '_edit_form', array(&$this, 'show'), 10, 2);
			}
		}
	}

	// Show meta fields
	function show($tag, $taxonomy) {
		// get meta fields from option table
		$metas = get_option($this->_meta['id']);
		if (empty($metas)) $metas = array();
		if (!is_array($metas)) $metas = (array) $metas;

		// get meta fields for current term
		$metas = isset($metas[$tag->term_id]) ? $metas[$tag->term_id] : array();

		wp_nonce_field(basename(__FILE__), 'rw_taxonomy_meta_nonce');

		echo "<h3>{$this->_meta['title']}</h3>
			<table class='form-table'>";

		foreach ($this->_fields as $field) {
			echo '<tr>';

			$meta = !empty($metas[$field['id']]) ? $metas[$field['id']] : $field['std'];	// get meta value for current field
			$meta = is_array($meta) ? array_map('esc_attr', $meta) : esc_attr($meta);

			call_user_func(array(&$this, 'show_field_' . $field['type']), $field, $meta);

			echo '</tr>';
		}

		echo '</table>';
	}

	/******************** END META BOX PAGE **********************/

	/******************** BEGIN META BOX FIELDS **********************/

	function show_field_begin($field, $meta) {
		echo "<th scope='row' valign='top'><label for='{$field['id']}'>{$field['name']}</label></th><td>";
	}

	function show_field_end($field, $meta) {
		echo "<br />{$field['desc']}</td>";
	}

	function show_field_text($field, $meta) {
		$this->show_field_begin($field, $meta);
		echo "<input type='text' name='{$field['id']}' id='{$field['id']}' value='$meta' size='40'  style='{$field['style']}' />";
		$this->show_field_end($field, $meta);
	}

	function show_field_textarea($field, $meta) {
		$this->show_field_begin($field, $meta);
		echo "<textarea name='{$field['id']}' cols='60' rows='15' style='{$field['style']}'>$meta</textarea>";
		$this->show_field_end($field, $meta);
	}

	function show_field_select($field, $meta) {
		if (!is_array($meta)) $meta = (array) $meta;
		$this->show_field_begin($field, $meta);
		echo "<select style='{$field['style']}' name='{$field['id']}" . ($field['multiple'] ? "[]' multiple='multiple'" : "'") . ">";
		foreach ($field['options'] as $key => $value) {
			echo "<option value='$key'" . selected(in_array($key, $meta), true, false) . ">$value</option>";
		}
		echo "</select>";
		$this->show_field_end($field, $meta);
	}

	function show_field_radio($field, $meta) {
		$this->show_field_begin($field, $meta);
		foreach ($field['options'] as $key => $value) {
			echo "<input type='radio' name='{$field['id']}' value='$key'" . checked($meta, $key, false) . " /> $value ";
		}
		$this->show_field_end($field, $meta);
	}

	function show_field_checkbox($field, $meta) {
		$this->show_field_begin($field, $meta);
		echo "<input type='checkbox' name='{$field['id']}'" . checked(!empty($meta), true, false) . " /> {$field['desc']}</td>";
	}

	function show_field_wysiwyg($field, $meta) {
		$this->show_field_begin($field, $meta);
		echo "<textarea name='{$field['id']}' id='{$field['id']}' class='{$field['id']} theEditor' cols='60' rows='15' style='{$field['style']}'>$meta</textarea>";
		$this->show_field_end($field, $meta);
	}

	function show_field_file($field, $meta) {
		if (!is_array($meta)) $meta = (array) $meta;

		$this->show_field_begin($field, $meta);
		echo "{$field['desc']}<br />";

		if (!empty($meta)) {
			$nonce = wp_create_nonce('rw_ajax_delete_file');
			$rel = "{$this->_meta['id']}!{$_GET['tag_ID']}!{$field['id']}";

			echo '<div style="margin-bottom: 10px"><strong>' . esc_html__('Uploaded files', 'meta_box') . '</strong></div>';
			echo '<ol>';
			foreach ($meta as $att) {
				if (wp_attachment_is_image($att)) continue; // what's image uploader for?
				echo "<li>" . wp_get_attachment_link($att) . " (<a class='rw-delete-file' href='#' rel='$rel!$att!$nonce'>" . __('Delete') . "</a>)</li>";
			}
			echo '</ol>';
		}

		// show form upload
		echo "<div style='clear: both'><strong>" . __('Upload new files') . "</strong></div>
			<div class='new-files'>
				<div class='file-input'><input type='file' name='{$field['id']}[]' /></div>
				<a class='rw-add-file' href='javascript:void(0)'>" . __('Add more file') . "</a>
			</div>
		</td>";
	}

	function show_field_image($field, $meta) {
		if (!is_array($meta)) $meta = (array) $meta;

		$this->show_field_begin($field, $meta);
		echo "{$field['desc']}<br />";

		$nonce_delete = wp_create_nonce('rw_ajax_delete_file');
		$rel = "{$this->_meta['id']}!{$_GET['tag_ID']}!{$field['id']}";

		echo "<ul id='rw-images-{$field['id']}' class='rw-images'>";
		foreach ($meta as $att) {
			$src = wp_get_attachment_image_src($att, 'full');
			$src = $src[0];

			echo "<li id='item_{$att}'>
					<img src='$src' />
					<a title='" . __('Delete this image') . "' class='rw-delete-file' href='#' rel='$rel!$att!$nonce_delete'>" . __('Delete') . "</a>
					<input type='hidden' name='{$field['id']}[]' value='$att' />
				</li>";
		}
		echo '</ul>';

		echo "<a href='#' style='float: left; clear: both; margin-top: 10px' id='rw_upload_{$field['id']}' class='rw_upload button'>" . __('Upload new image') . "</a>";
	}

	function show_field_color($field, $meta) {
		if (empty($meta)) $meta = '#';
		$this->show_field_begin($field, $meta);
		echo "<input type='text' name='{$field['id']}' id='{$field['id']}' value='$meta' size='8' style='{$field['style']}' />
			  <a href='#' id='select-{$field['id']}'>" . __('Select a color') . "</a>
			  <div style='display:none' id='picker-{$field['id']}'></div>";
		$this->show_field_end($field, $meta);
	}

	function show_field_checkbox_list($field, $meta) {
		if (!is_array($meta)) $meta = (array) $meta;
		$this->show_field_begin($field, $meta);
		$html = array();
		foreach ($field['options'] as $key => $value) {
			$html[] = "<input type='checkbox' name='{$field['id']}[]' value='$key'" . checked(in_array($key, $meta), true, false) . " /> $value";
		}
		echo implode('<br />', $html);
		$this->show_field_end($field, $meta);
	}

	function show_field_date($field, $meta) {
		$this->show_field_text($field, $meta);
	}

	function show_field_time($field, $meta) {
		$this->show_field_text($field, $meta);
	}

	/******************** END META BOX FIELDS **********************/

	/******************** BEGIN META BOX SAVE **********************/

	// Save meta fields
	function save($term_id, $tt_id) {
		/*
		if (!check_admin_referer(basename(__FILE__), 'rw_taxonomy_meta_nonce')) {	// check nonce
			return;
		}
		*/

		$metas = get_option($this->_meta['id']);
		if (!is_array($metas)) $metas = (array) $metas;

		$meta = isset($metas[$term_id]) ? $metas[$term_id] : array();

		foreach ($this->_fields as $field) {
			$name = $field['id'];
			$type = $field['type'];

			$old = isset($meta[$name]) ? $meta[$name] : ($field['multiple'] ? array() : '');
			$new = isset($_POST[$name]) ? $_POST[$name] : ($field['multiple'] ? array() : '');

			// validate meta value
			if (class_exists('RW_Taxonomy_Meta_Validate') && method_exists('RW_Taxonomy_Meta_Validate', $field['validate_func'])) {
				$new = call_user_func(array('RW_Taxonomy_Meta_Validate', $field['validate_func']), $new);
			}

			// call defined method to save meta value, if there's no methods, call common one
			$save_func = 'save_field_' . $type;
			if (method_exists($this, $save_func)) {
				call_user_func(array(&$this, 'save_field_' . $type), &$meta, $field, $old, $new);
			} else {
				$this->save_field(&$meta, $field, $old, $new);
			}
		}

		$metas[$term_id] = $meta;
		update_option($this->_meta['id'], $metas);
	}

	// Common functions for saving field
	function save_field(&$meta, $field, $old, $new) {
		$name = $field['id'];

		$new = is_array($new) ? array_map('stripslashes', $new) : stripslashes($new);

		if (empty($new)) {
			unset($meta[$name]);
		} else {
			$meta[$name] = $new;
		}
	}

	function save_field_wysiwyg(&$meta, $field, $old, $new) {
		$new = stripslashes($new);
		$new = wpautop($new);
		$this->save_field(&$meta, $field, $old, $new);
	}

	function save_field_file(&$meta, $field, $old, $new) {
		$name = $field['id'];
		if (empty($_FILES[$name])) return;

		$this->fix_file_array($_FILES[$name]);

		foreach ($_FILES[$name] as $position => $fileitem) {
			$file = wp_handle_upload($fileitem, array('test_form' => false));

			if (empty($file['file'])) continue;
			$filename = $file['file'];

			$attachment = array(
				'post_mime_type' => $file['type'],
				'guid' => $file['url'],
				'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
				'post_content' => ''
			);
			$id = wp_insert_attachment($attachment, $filename);
			if (!is_wp_error($id)) {
				wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $filename));
				$meta[$name][] = $id;
			}
		}
	}

	/******************** END META BOX SAVE **********************/

	function delete($term_id, $tt_id) {
		$metas = get_option($this->_meta['id']);
		if (!is_array($metas)) $metas = (array) $metas;

		unset($metas[$term_id]);

		update_option($this->_meta['id'], $metas);
	}

	/******************** BEGIN HELPER FUNCTIONS **********************/

	// Add missed values for meta box
	function add_missed_values() {
		// default values for meta box
		$this->_meta = array_merge(array(
			'taxonomies' => array('category', 'post_tag')
		), $this->_meta);

		// default values for fields
		foreach ($this->_fields as & $field) {
			$multiple = in_array($field['type'], array('checkbox_list', 'file', 'image')) ? true : false;
			$std = $multiple ? array() : '';
			$format = 'date' == $field['type'] ? 'yy-mm-dd' : ('time' == $field['type'] ? 'hh:mm' : '');
			$style = 'width: 97%';
			if ('select' == $field['type']) $style = 'height: auto';

			$field = array_merge(array(
				'multiple' => $multiple,
				'std' => $std,
				'desc' => '',
				'format' => $format,
				'style' => $style,
				'validate_func' => ''
			), $field);
		}
	}

	// Check if field with $type exists
	function has_field($type) {
		foreach ($this->_fields as $field) {
			if ($type == $field['type']) return true;
		}
		return false;
	}

	/**
	 * Fixes the odd indexing of multiple file uploads from the format:
	 *	 $_FILES['field']['key']['index']
	 * To the more standard and appropriate:
	 *	 $_FILES['field']['index']['key']
	 */
	function fix_file_array(&$files) {
		$output = array();
		foreach ($files as $key => $list) {
			foreach ($list as $index => $value) {
				$output[$index][$key] = $value;
			}
		}
		$files = $output;
	}

	// Get proper jQuery UI version to not conflict with WP admin scripts
	function get_jqueryui_ver() {
		global $wp_version;
		if (version_compare($wp_version, '3.1', '>=')) {
			return '1.8.10';
		}

		return '1.7.3';
	}

	/******************** END HELPER FUNCTIONS **********************/
}

?>