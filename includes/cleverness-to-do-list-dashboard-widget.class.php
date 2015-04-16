<?php
/**
 * Cleverness To-Do List Plugin Dashboard Widget
 *
 * Creates the dashboard widget
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.4
 */

/**
 * Dashboard widget class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Dashboard_Widget extends ClevernessToDoList {

	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_setup' ) );
		add_action( 'admin_init', array( $this, 'dashboard_init' ) );
	}

	/**
	 * Creates the dashboard widget
	 */
	public function dashboard_widget() {
		global $CTDL_templates;
		$completed = ( CTDL_Loader::$dashboard_settings['show_completed'] == 1 ? 1 : 0 );
		$class = 'cleverness-to-do-list';

		if ( 1 == $completed ) {
			$class .= ' refresh-checklist';
		}

		echo '<div class="'.$class.'">';

		$CTDL_templates->get_template_part( 'dashboard', 'widget' );

		echo '<input type="hidden" name="ctdl_complete_nonce" value="' . wp_create_nonce( 'todocomplete' ) . '" />';

		echo '</div>';

	}

	/**
	 * Loops through to-do items
	 * Passes a completed value, limit value and a category id
	 * @param int $status
	 * @param int $cat_id
	 * @param $limit
	 */
	public function loop_through_todos( $status = 0, $cat_id = 0, $limit = 100 ) {
		global $userdata, $current_user;
		get_currentuserinfo();

		$limit = ( isset( CTDL_Loader::$dashboard_settings['dashboard_number'] ) ? CTDL_Loader::$dashboard_settings['dashboard_number'] : $limit );
		$limit = ( $limit == '-1' ? 1000 : $limit );
		$user = ( CTDL_Lib::check_permission( 'todo', 'view' ) ? $current_user->ID : $userdata->ID );

		if ( CTDL_Loader::$settings['categories'] == 1 && CTDL_Loader::$settings['sort_order'] == 'cat_id' && $cat_id == 0 ) {

			$categories = CTDL_Categories::get_categories();
			$items = 0;
			$posts_to_exclude = array();

			foreach ( $categories as $category ) {
				$todo_items = CTDL_Lib::get_todos( $user, $limit, $status, $category->term_id );

				if ( $todo_items->have_posts() ) {
					array_splice( $posts_to_exclude, count( $posts_to_exclude ), 0, $this->show_todo_list_items( $todo_items, $status, $category->term_id ) );
					$items = 1;
				}
			}

			$todo_items = CTDL_Lib::get_todos( $user, 0, $status, 0, $posts_to_exclude );

			if ( $todo_items->have_posts() ) {
				$this->show_todo_list_items( $todo_items, $status );
				$items = 1;
			}

			if ( $items == 0 && $status == 0 ) {
				echo '<p>' . apply_filters( 'ctdl_no_items', esc_html__( 'No items to do.', 'cleverness-to-do-list' ) ) . '</p>';
			}
		} else {

			$todo_items = CTDL_Lib::get_todos( $user, $limit, $status, $cat_id );

			if ( $todo_items->have_posts() ) {
				$this->show_todo_list_items( $todo_items, $status );
			} elseif ( $status == 0 ) {
				echo '<p>' . apply_filters( 'ctdl_no_items', esc_html__( 'No items to do.', 'cleverness-to-do-list' ) ) . '</p>';
			}
		}
	}

	/**
	 * Shows the to-do list items
	 * Has dashboard specific settings
	 * @param $todo_items
	 * @param int $status
	 * @param int $cat_id
	 * @return array $posts_to_exclude
	 */
	public function show_todo_list_items( $todo_items, $status = 0, $cat_id = 0 ) {
		global $CTDL_templates, $CTDL_status, $CTDL_category;
		$CTDL_status = $status;
		$CTDL_category = $cat_id;

		while ( $todo_items->have_posts() ) : $todo_items->the_post();

			$id = get_the_ID();
			$posts_to_exclude[] = $id;

			$CTDL_templates->get_template_part( 'dashboard', 'single' );

		endwhile;

		wp_reset_postdata();

		return $posts_to_exclude;
	}

	/**
	 * Dashboard Widget Options
	 */
	public function dashboard_options() {
		if ( isset( $_POST['ctdl_dashboard_settings'] ) ) {
			$ctdl_dashboard_settings = $_POST['ctdl_dashboard_settings'];
			update_option( 'CTDL_dashboard_settings', $ctdl_dashboard_settings );
		}
		settings_fields( 'ctdl-dashboard-settings-group' );
		$options = get_option( 'CTDL_dashboard_settings' );
		$heading = ( isset( $options['dashboard_heading'] ) ? $options['dashboard_heading'] : __( 'To-Do List', 'cleverness-to-do-list' ) );
		$cat_id = ( isset( $options['dashboard_cat'] ) ? $options['dashboard_cat'] : 0 );
		$cat_ids = ( is_array( $cat_id ) ? $cat_id : array( $cat_id ) );
		?>
		<fieldset>
			<p><label for="ctdl_dashboard_settings[dashboard_heading]"><?php esc_html_e( 'Heading', 'cleverness-to-do-list' ); ?></label>
				<input type="text" class="widefat" id="ctdl_dashboard_settings[dashboard_heading]" name="ctdl_dashboard_settings[dashboard_heading]"
				       value="<?php echo esc_html( $heading ); ?>" />
			</p>

			<p><label for="ctdl_dashboard_settings[dashboard_number]"><?php esc_html_e( 'Number of Items', 'cleverness-to-do-list' ); ?></label>
				<select id="ctdl_dashboard_settings[dashboard_number]" name="ctdl_dashboard_settings[dashboard_number]">
					<option value="1"<?php if ( $options['dashboard_number'] == '1' ) echo ' selected="selected"'; ?>><?php esc_html_e( '1', 'cleverness-to-do-list' ); ?></option>
					<option value="5"<?php if ( $options['dashboard_number'] == '5' ) echo ' selected="selected"'; ?>><?php esc_html_e( '5', 'cleverness-to-do-list' ); ?></option>
					<option value="10"<?php if ( $options['dashboard_number'] == '10' ) echo ' selected="selected"'; ?>><?php esc_html_e( '10', 'cleverness-to-do-list' ); ?></option>
					<option value="15"<?php if ( $options['dashboard_number'] == '15' ) echo ' selected="selected"'; ?>><?php esc_html_e( '15', 'cleverness-to-do-list' ); ?></option>
					<option value="-1"<?php if ( $options['dashboard_number'] == '-1' ) echo ' selected="selected"'; ?>><?php esc_html_e( 'All', 'cleverness-to-do-list' ); ?>&nbsp;</option>
				</select>
			</p>

			<p><label for="ctdl_dashboard_settings[show_dashboard_deadline]"><?php esc_html_e( 'Show Deadline', 'cleverness-to-do-list' ); ?></label>
				<select id="cctdl_dashboard_settings[show_dashboard_deadline]" name="ctdl_dashboard_settings[show_dashboard_deadline]">
					<option value="0"<?php if ( $options['show_dashboard_deadline'] == 0 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'No', 'cleverness-to-do-list' ); ?></option>
					<option value="1"<?php if ( $options['show_dashboard_deadline'] == 1 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
				</select>
			</p>

			<p><label for="ctdl_dashboard_settings[show_edit_link]"><?php esc_html_e( 'Show Edit Link', 'cleverness-to-do-list' ); ?></label>
				<select id="ctdl_dashboard_settings[show_edit_link]" name="ctdl_dashboard_settings[show_edit_link]">
					<option value="0"<?php if ( $options['show_edit_link'] == 0 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'No', 'cleverness-to-do-list' ); ?></option>
					<option value="1"<?php if ( $options['show_edit_link'] == 1 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
				</select>
			</p>

			<p><label for="ctdl_dashboard_settings[show_completed]"><?php esc_html_e( 'Show Completed Items', 'cleverness-to-do-list' ); ?></label>
				<select id="ctdl_dashboard_settings[show_completed]" name="ctdl_dashboard_settings[show_completed]">
					<option value="0"<?php if ( $options['show_completed'] == 0 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'No', 'cleverness-to-do-list' ); ?></option>
					<option value="1"<?php if ( $options['show_completed'] == 1 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
				</select>
			</p>

			<?php if ( CTDL_Loader::$settings['categories'] == 1 ) : ?>
				<p><label for="ctdl_dashboard_settings[dashboard_cat][]" class="ctdl-categories-label"><?php echo apply_filters( 'ctdl_category',
							esc_html__( 'Category', 'cleverness-to-do-list' ) ); ?></label></p>
				<ul class="ctdl-categories">
					<?php $args = array(
						'descendants_and_self' => 0,
						'selected_cats'        => $cat_ids,
						'popular_cats'         => false,
						'walker'               => new CTDL_CategoryWalker(),
						'taxonomy'             => 'todocategories',
						'checked_ontop'        => true
					); ?>
					<?php wp_terms_checklist( 0, $args ); ?>
				</ul>
			<?php endif; ?>

			<p class="description"><?php _e( 'The setting below is only used when <em>List View</em> is set to <em>Group</em>.', 'cleverness-to-do-list' ); ?></p>

			<p><label for="ctdl_dashboard_settings[dashboard_author]"><?php _e( 'Show Added By', 'cleverness-to-do-list' ); ?></label>
				<select id="ctdl_dashboard_settings[dashboard_author]" name="ctdl_dashboard_settings[dashboard_author]">
					<option value="0"<?php if ( $options['dashboard_author'] == 0 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
					<option value="1"<?php if ( $options['dashboard_author'] == 1 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'No', 'cleverness-to-do-list' ); ?></option>
				</select>
			</p>
		</fieldset>
	<?php
	}

	/**
	 * Setup the dashboard widget
	 */
	public function dashboard_setup() {
		global $CTDL_status, $CTDL_category, $CTDL_category_id;
		$CTDL_status = 0;
		$CTDL_category = 0;
		$CTDL_category_id = 0;
		$heading = ( isset( CTDL_Loader::$dashboard_settings['dashboard_heading'] ) && CTDL_Loader::$dashboard_settings['dashboard_heading'] != ''
			? CTDL_Loader::$dashboard_settings['dashboard_heading'] : __( 'To-Do List', 'cleverness-to-do-list' ) );

		if ( CTDL_Lib::check_permission( 'todo', 'view' ) ) {
			wp_add_dashboard_widget( 'ctdl', apply_filters( 'ctdl_todo_list', esc_html( $heading ) ) .
					' <a href="admin.php?page=cleverness-to-do-list">&raquo;</a>', array( $this, 'dashboard_widget' ), array( $this, 'dashboard_options' ) );
		}
	}

	/**
	 * Add scripts and styles to dashboard widget
	 */
	public function dashboard_init() {
		wp_register_script( 'ctdl_dashboard_widget_js', CTDL_PLUGIN_URL . '/js/cleverness-to-do-list-dashboard-widget.js', '', CTDL_PLUGIN_VERSION, true );
		add_action( 'admin_print_scripts-index.php', array( $this, 'dashboard_add_js' ) );
		add_action( 'wp_ajax_ctdl_dashboard_complete', array( 'CTDL_Lib', 'complete_todo_callback' ) );
		add_action( 'wp_ajax_ctdl_dashboard_display_todos', array( 'CTDL_Lib', 'dashboard_display_todos_callback' ) );
		add_action( 'admin_print_styles-index.php', array( 'CTDL_Loader', 'add_admin_css' ) );
	}

	/**
	 * Add Javascript and localize variables
	 */
	public function dashboard_add_js() {
		wp_enqueue_script( 'ctdl_dashboard_widget_js' );
		wp_localize_script( 'ctdl_dashboard_widget_js', 'ctdl', CTDL_Loader::get_js_vars() );
	}
}

class CTDL_CategoryWalker extends Walker_Category {

	public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {

		$args = wp_parse_args( array(
			'name' => 'ctdl_dashboard_settings[dashboard_cat]'
		), $args );

		if ( empty( $taxonomy ) )
			$taxonomy = 'category';

		$output .= "\n<li id='{$taxonomy}-{$category->term_id}'>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="' . $args['name'] . '[]" id="in-' . $taxonomy . '-'
				. $category->term_id . '"' . checked( in_array( $category->term_id, $args['selected_cats'] ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters( 'the_category', $category->name ) ) . '</label>';
	}

	function end_el( &$output, $page, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}
}