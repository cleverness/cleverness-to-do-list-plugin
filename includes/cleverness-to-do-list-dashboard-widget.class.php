<?php
/**
 * Cleverness To-Do List Plugin Dashboard Widget
 *
 * Creates the dashboard widget
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.3
 */

/**
 * Dashboard widget class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Dashboard_Widget extends ClevernessToDoList {
	public $dashboard_settings = '';

	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_setup' ) );
		add_action( 'admin_init', array( &$this, 'dashboard_init' ) );
	}

	/**
	 * Creates the dashboard widget
	 */
	public function dashboard_widget() {
		$this->dashboard_settings['dashboard_cat'] = ( isset( $this->dashboard_settings['dashboard_cat'] ) ? $this->dashboard_settings['dashboard_cat'] : 0 );
		$cat_ids = ( is_array( $this->dashboard_settings['dashboard_cat'] ) ? $this->dashboard_settings['dashboard_cat'] : array( $this->dashboard_settings['dashboard_cat'] ) );
		$limit = ( isset( $this->dashboard_settings['dashboard_number'] ) ? $this->dashboard_settings['dashboard_number'] : -1 );
		$this->list = '';

		foreach ( $cat_ids as $cat_id ) {
			$this->loop_through_todos( $cat_id, $limit );
		}

		if ( $this->list != '' ) {
			echo $this->list;
		} else {
			echo '<p>' . apply_filters( 'ctdl_no_items', esc_html__( 'No items to do.', 'cleverness-to-do-list' ) ) . '</p>';
		}

		$cleverness_todo_permission = CTDL_Lib::check_permission( 'todo', 'add' );
		if ( $cleverness_todo_permission === true ) {
			echo '<p style="clear: both; text-align: right">' . '<a href="admin.php?page=cleverness-to-do-list#addtodo">' . apply_filters( 'ctdl_add_text', esc_attr__( 'Add To-Do Item', 'cleverness-to-do-list' ) ) . '  &raquo;</a></p>';
		}
	}

	/**
	 * Loops through to-do items
	 * Has no completed items and passes a limit value and a category id
	 * @param int $cat_id
	 * @param $limit
	 */
	protected function loop_through_todos( $cat_id = 0, $limit = -1 ) {
		global $userdata, $current_user;
		get_currentuserinfo();

		if ( CTDL_Loader::$settings['list_view'] == '2' ) {
			$user = $current_user->ID;
		} else {
			$user = $userdata->ID;
		}

		if ( CTDL_Loader::$settings['categories'] == 1 && CTDL_Loader::$settings['sort_order'] == 'cat_id' && $cat_id == 0 ) {

			$categories = CTDL_Categories::get_categories();
			$items = 0;
			$posts_to_exclude = array();

			foreach ( $categories as $category ) {
				$todo_items = CTDL_Lib::get_todos( $user, $limit, 0, $category->term_id );

				if ( $todo_items->have_posts() ) {
					array_splice( $posts_to_exclude, count( $posts_to_exclude ), 0, $this->show_todo_list_items( $todo_items, 0, $cat_id ) );
					$items = 1;
				}
			}

			$todo_items = CTDL_Lib::get_todos( $user, 0, 0, 0, $posts_to_exclude );

			if ( $todo_items->have_posts() ) {
				$this->show_todo_list_items( $todo_items, 0 );
				$items = 1;
			}

			if ( $items == 0 ) {
				$this->list .= apply_filters( 'ctdl_no_items', esc_html__( 'No items to do.', 'cleverness-to-do-list' ) );
			}
		} else {

			$todo_items = CTDL_Lib::get_todos( $user, $limit, 0, $cat_id );

			if ( $todo_items->have_posts() ) {
				$this->show_todo_list_items( $todo_items );
			} else {
				$this->list .= '';
			}
		}
	}

	/**
	 * Shows the to-do list items
	 * Has dashboard specific settings
	 * @param $todo_items
	 * @param int $completed
	 * @param int $cat_id
	 * @return array $posts_to_exclude
	 */
	protected function show_todo_list_items( $todo_items, $completed = 0, $cat_id = 0 ) {
		$catid = '';
		global $CTDL_templates;

		while ( $todo_items->have_posts() ) : $todo_items->the_post();

			$id = get_the_ID();
			$posts_to_exclude[] = $id;
			list( $priority, $assign_meta, $deadline_meta, $completed_meta, $progress_meta ) = CTDL_Lib::get_todo_meta( $id );

			$priority_class = CTDL_Lib::set_priority_class( $priority );

			$CTDL_templates->get_template_part( 'dashboard', 'widget' );

			if ( CTDL_Loader::$settings['categories'] == '1' && $cat_id != 1 ) {
				$cats = get_the_terms( $id, 'todocategories' );
				if ( $cats != NULL ) {
					foreach ( $cats as $category ) {
						if ( $catid != $category->term_id ) $this->list .= '<h4>' . esc_html( $category->name ) . '</h4>';
						$catid = $category->term_id;
					}
				}
			}

			$this->list .= '<div id="todo-' . $id . '"' . $priority_class . '>';
			$this->show_checkbox( $id, $completed );
			$this->list .= '<div class="todoitem">';
			$this->show_todo_text( get_the_content() );
			if ( ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 0 && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) )
					|| ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 1 ) && CTDL_Loader::$settings['assign'] == 0 && $assign_meta != 0 && $assign_meta != ''
					&& $assign_meta != -1 && !in_array( -1, $assign_meta )
			) {
				$this->show_assigned( $assign_meta );
			}
			if ( CTDL_Loader::$settings['show_deadline'] == 1 && isset( $this->dashboard_settings['show_dashboard_deadline'] ) && $this->dashboard_settings['show_dashboard_deadline'] == 1 && $deadline_meta != '' ) {
				$this->list .= ' <small>[' . apply_filters( 'ctdl_deadline', esc_html__( 'Deadline', 'cleverness-to-do-list' ) ) . ' ';
				$this->show_deadline( $deadline_meta );
				$this->list .= ']</small>';
			}
			if ( CTDL_Loader::$settings['show_progress'] == 1 && $progress_meta != '' ) {
				$this->list .= ' <small>[';
				$this->show_progress( $progress_meta, 'list', $completed );
				$this->list .= ']</small>';
			}
			if ( CTDL_Loader::$settings['list_view'] == 1 && isset( $this->dashboard_settings['dashboard_author'] ) && $this->dashboard_settings['dashboard_author'] == 0 ) {
				if ( get_the_author() != '0' ) {
					$this->list .= ' <small>- ' . apply_filters( 'ctdl_added_by', esc_html__( 'Added By', 'cleverness-to-do-list' ) ) . ' ';
					$this->show_addedby( get_the_author() );
					$this->list .= '</small>';
				}
			}

			$this->list .= do_action( 'ctdl_list_items' );

			if ( $this->dashboard_settings['show_edit_link'] == 1 && ( current_user_can( CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == 0 ) )
				$this->list .= ' <small>(<a href="admin.php?page=cleverness-to-do-list&amp;action=edit-todo&amp;id=' . esc_attr( $id ) . '">' . __( 'Edit' ) . '</a>)</small>';

			$this->list .= '</div></div>';
		endwhile;

		wp_reset_postdata();

		return $posts_to_exclude;
	}

	/**
	 * Create the HTML to show a To-Do List Checkbox
	 * @param int $id
	 * @param boolean $completed
	 * @param string $layout
	 * @param string $single
	 * @since 3.2
	 */
	protected function show_checkbox( $id, $completed = NULL, $layout = 'table', $single = '' ) {
		$permission = CTDL_Lib::check_permission( 'todo', 'complete' );
		if ( $permission === true ) {
			$this->list .= sprintf( '<input type="checkbox" id="ctdl-%d" class="todo-checkbox uncompleted floatleft' . $single . '"/>', esc_attr( $id ) );
			$cleverness_todo_complete_nonce = wp_create_nonce( 'todocomplete' );
			$this->list .= '<input type="hidden" name="cleverness_todo_complete_nonce" value="' . esc_attr( $cleverness_todo_complete_nonce ) . '" />';
		}
	}

	/**
	 * Show the User that a To-Do Item is Assigned To
	 * @param int $assign
	 * @param string $layout
	 * @since 3.4
	 */
	public function show_assigned( $assign, $layout = 'list' ) {
		if ( ( ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 0 && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) ||
						( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 1 ) ) && CTDL_Loader::$settings['assign'] == 0
		) {
			$this->list .= ' <small>[' . apply_filters( 'ctdl_assigned', esc_html__( 'Assigned to', 'cleverness-to-do-list' ) ) . ' ';
			if ( is_array( $assign ) ) {
				$assign_users = '';
				foreach ( $assign as $value ) {
					if ( $value != '-1' && $value != '' && $value != 0 ) {
						$user = get_userdata( $value );
						$assign_users .= $user->display_name . ', ';
					}
				}
				$this->list .= substr( $assign_users, 0, -2 );
			} else {
				if ( $assign != '-1' && $assign != '' && $assign != 0 ) {
					$assign_user = get_userdata( $assign );
					$this->list .= esc_html( $assign_user->display_name );
				}
			}
			$this->list .= ']</small>';
		}
	}

	/**
	 * Dashboard Widget Options
	 */
	public function dashboard_options() {
		if ( isset( $_POST['cleverness_todo_dashboard_settings'] ) ) {
			$cleverness_todo_dashboard_settings = $_POST['cleverness_todo_dashboard_settings'];
			update_option( 'CTDL_dashboard_settings', $cleverness_todo_dashboard_settings );
		}
		settings_fields( 'cleverness-todo-dashboard-settings-group' );
		$options = get_option( 'CTDL_dashboard_settings' );
		$cat_id = ( isset( $options['dashboard_cat'] ) ? $options['dashboard_cat'] : 0 );
		$cat_ids = ( is_array( $cat_id ) ? $cat_id : array( $cat_id ) );
		?>
		<fieldset>
			<p><label for="cleverness_todo_dashboard_settings[dashboard_number]"><?php esc_html_e( 'Number of List Items to Show', 'cleverness-to-do-list' ); ?></label>
				<select id="cleverness_todo_dashboard_settings[dashboard_number]" name="cleverness_todo_dashboard_settings[dashboard_number]">
					<option value="1"<?php if ( $options['dashboard_number'] == '1' ) echo ' selected="selected"'; ?>><?php _e( '1', 'cleverness-to-do-list' ); ?></option>
					<option value="5"<?php if ( $options['dashboard_number'] == '5' ) echo ' selected="selected"'; ?>><?php _e( '5', 'cleverness-to-do-list' ); ?></option>
					<option value="10"<?php if ( $options['dashboard_number'] == '10' ) echo ' selected="selected"'; ?>><?php _e( '10', 'cleverness-to-do-list' ); ?></option>
					<option value="15"<?php if ( $options['dashboard_number'] == '15' ) echo ' selected="selected"'; ?>><?php _e( '15', 'cleverness-to-do-list' ); ?></option>
					<option value="-1"<?php if ( $options['dashboard_number'] == '-1' ) echo ' selected="selected"'; ?>><?php _e( 'All', 'cleverness-to-do-list' ); ?>&nbsp;</option>
				</select>
			</p>

			<p><label for="cleverness_todo_dashboard_settings[show_dashboard_deadline]"><?php esc_html_e( 'Show Deadline', 'cleverness-to-do-list' ); ?></label>
				<select id="cleverness_todo_dashboard_settings[show_dashboard_deadline]" name="cleverness_todo_dashboard_settings[show_dashboard_deadline]">
					<option value="0"<?php if ( $options['show_dashboard_deadline'] == 0 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'No', 'cleverness-to-do-list' ); ?></option>
					<option value="1"<?php if ( $options['show_dashboard_deadline'] == 1 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
				</select>
			</p>

			<p><label
						for="cleverness_todo_dashboard_settings[show_edit_link]"><?php esc_html_e( 'Show Edit Link', 'cleverness-to-do-list' ); ?></label>
				<select id="cleverness_todo_dashboard_settings[show_edit_link]"
						name="cleverness_todo_dashboard_settings[show_edit_link]">
					<option value="0"<?php if ( $options['show_edit_link'] == 0 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'No', 'cleverness-to-do-list' ); ?></option>
					<option value="1"<?php if ( $options['show_edit_link'] == 1 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'Yes', 'cleverness-to-do-list' ); ?>
						&nbsp;</option>
				</select>
			</p>

			<p><label for="cleverness_todo_dashboard_settings[show_completed]"><?php esc_html_e( 'Show Completed Items', 'cleverness-to-do-list' ); ?></label>
				<select id="cleverness_todo_dashboard_settings[show_completed]" name="cleverness_todo_dashboard_settings[show_completed]">
					<option value="0"<?php if ( $options['show_completed'] == 0 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'No', 'cleverness-to-do-list' ); ?></option>
					<option value="1"<?php if ( $options['show_completed'] == 1 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
				</select>
			</p>


			<?php if ( CTDL_Loader::$settings['categories'] == 1 ) : ?>
				<p><label for="cleverness_todo_dashboard_settings[dashboard_cat][]" class="cleverness-to-do-list-categories-label"><?php echo apply_filters( 'ctdl_category',
							esc_html__( 'Category', 'cleverness-to-do-list' ) ); ?></label>
				<ul class="cleverness-to-do-list-categories">
					<?php $args = array(
						'descendants_and_self' => 0,
						'selected_cats'        => $cat_ids,
						'popular_cats'         => false,
						'walker'               => new ClevernessToDoListCategoryWalker(),
						'taxonomy'             => 'todocategories',
						'checked_ontop'        => true
					); ?>
					<?php wp_terms_checklist( 0, $args ); ?>
				</ul>
				</p>
			<?php endif; ?>

			<p class="description" style="clear: both;"><?php _e( 'This setting is only used when <em>List View</em> is set to <em>Group</em>.', 'cleverness-to-do-list' ); ?></p>

			<p><label for="cleverness_todo_dashboard_settings[dashboard_author]"><?php _e( 'Show <em>Added By</em> on Dashboard Widget', 'cleverness-to-do-list' ); ?></label>
				<select id="cleverness_todo_dashboard_settings[dashboard_author]" name="cleverness_todo_dashboard_settings[dashboard_author]">
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
		$cleverness_todo_permission = CTDL_Lib::check_permission( 'todo', 'view' );
		if ( $cleverness_todo_permission === true ) {
			$this->dashboard_settings = CTDL_Loader::$dashboard_settings;
			wp_add_dashboard_widget( 'cleverness_todo', apply_filters( 'ctdl_todo_list', esc_html__( 'To-Do List', 'cleverness-to-do-list' ) ) . ' <a href="admin.php?page=cleverness-to-do-list">&raquo;</a>', array( $this, 'dashboard_widget' ), array( $this, 'dashboard_options' ) );
		}
	}

	/**
	 * Add scripts and styles to dashboard widget
	 */
	public function dashboard_init() {
		wp_register_script( 'cleverness_todo_dashboard_complete_js', CTDL_PLUGIN_URL . '/js/cleverness-to-do-list-dashboard-widget.js', '', CTDL_PLUGIN_VERSION, true );
		add_action( 'admin_print_scripts-index.php', array( $this, 'dashboard_add_js' ) );
		add_action( 'wp_ajax_cleverness_todo_dashboard_complete', array( 'CTDL_Lib', 'complete_todo_callback' ) );
		add_action( 'admin_print_styles-index.php', array( 'CTDL_Loader', 'add_admin_css' ) );
	}

	/**
	 * Add Javascript and localize variables
	 */
	public function dashboard_add_js() {
		wp_enqueue_script( 'cleverness_todo_dashboard_complete_js' );
		wp_localize_script( 'cleverness_todo_dashboard_complete_js', 'ctdl', CTDL_Loader::get_js_vars() );
	}
}

class ClevernessToDoListCategoryWalker extends Walker_Category {

	public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {

		$args = wp_parse_args( array(
			'name' => 'cleverness_todo_dashboard_settings[dashboard_cat]'
		), $args );

		extract( $args );

		if ( empty( $taxonomy ) )
			$taxonomy = 'category';

		$output .= "\n<li id='{$taxonomy}-{$category->term_id}'>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="' . $name . '[]" id="in-' . $taxonomy . '-'
				. $category->term_id . '"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters( 'the_category', $category->name ) ) . '</label>';
	}

	function end_el( &$output, $page, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}
}