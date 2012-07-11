<?php
/**
 * Cleverness To-Do List Plugin Dashboard Widget
 *
 * Creates the dashboard widget
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.2
 */

/**
 * Dashboard widget class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Dashboard_Widget extends ClevernessToDoList {
	public $dashboard_settings = '';

	public function __construct() {
		add_action( 'wp_dashboard_setup', array( &$this, 'dashboard_setup' ) );
		add_action( 'admin_init', array( &$this, 'dashboard_init' ) );
	}

	/**
	 * Creates the dashboard widget
	 */
	public function dashboard_widget() {

		$cat_id = $this->dashboard_settings['dashboard_cat'];
		$limit = $this->dashboard_settings['dashboard_number'];

		$this->loop_through_todos( $cat_id, $limit );

		echo $this->list;

		$cleverness_todo_permission = CTDL_Lib::check_permission( 'todo', 'add' );
		if ( $cleverness_todo_permission === true ) {
			echo '<br /><p style="float: right">'. '<a href="admin.php?page=cleverness-to-do-list#addtodo">'.apply_filters( 'ctdl_add_text', esc_attr__( 'Add To-Do Item', 'cleverness-to-do-list' ) ).'  &raquo;</a></p>';
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
				$this->list .= '<tr><td>'.apply_filters( 'ctdl_no_items', esc_html__( 'No items to do.', 'cleverness-to-do-list' ) ).'</td></tr>';
			}

		} else {

			$todo_items = CTDL_Lib::get_todos( $user, $limit, 0, $cat_id );

			if ( $todo_items->have_posts() ) {
				$this->show_todo_list_items( $todo_items );
			} else {
				$this->list .= '<tr><td>'.apply_filters( 'ctdl_no_items', esc_html__( 'No items to do.', 'cleverness-to-do-list' ) ).'</td></tr>';
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

		while ( $todo_items->have_posts() ) : $todo_items->the_post();

			$id = get_the_ID();
			$posts_to_exclude[] = $id;
			list( $priority, $assign_meta, $deadline_meta, $completed_meta, $progress_meta ) = CTDL_Lib::get_todo_meta( $id );

			$priority_class = CTDL_Lib::set_priority_class( $priority );

			if ( CTDL_Loader::$settings['categories'] == '1' && $cat_id != 1 ) {
				$cats = get_the_terms( $id, 'todocategories' );
				if ( $cats != NULL ) {
					foreach( $cats as $category ) {
						if ( $catid != $category->term_id ) $this->list .= '<h4>'.esc_html( $category->name ).'</h4>';
						$catid = $category->term_id;
					}
				}
			}

			$this->list .= '<div id="todo-'.$id.'"'.$priority_class.'>';
			$this->show_checkbox( $id, $completed );
			$this->list .= '<div class="todoitem">';
			$this->show_todo_text( get_the_content() );
			if ( ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 0 && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) )
					||  ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 1 ) && CTDL_Loader::$settings['assign'] == 0 ) {
				$assign_user = '';
				if ( $assign_meta != '-1' && $assign_meta != '0' ) {
					$assign_user = get_userdata( $assign_meta );
					$this->list .= ' <small>['.apply_filters( 'ctdl_assigned', esc_html__( 'Assigned To', 'cleverness-to-do-list' ) ).' ';
					$this->show_assigned( $assign_meta );
					$this->list .= ']</small>';
				}
			}
			if ( $this->dashboard_settings['show_dashboard_deadline'] == 1 && $deadline_meta != '' ) {
				$this->list .=  ' <small>['.apply_filters( 'ctdl_deadline', esc_html__( 'Deadline', 'cleverness-to-do-list' ) );
				$this->show_deadline( $deadline_meta );
				$this->list .= ']</small>';
			}
			if ( CTDL_Loader::$settings['show_progress'] == 1 && $progress_meta != '' ) {
				$this->list .= ' <small>[';
				$this->show_progress( $progress_meta );
				$this->list .= ']</small>';
			}
			if ( CTDL_Loader::$settings['list_view'] == 1 && $this->dashboard_settings['dashboard_author'] == 0 ) {
				if ( get_the_author() != '0') {
					$this->list .= ' <small>- '.apply_filters( 'ctdl_added_by', esc_html__( 'Added By', 'cleverness-to-do-list' ) ).' ';
					$this->show_addedby( get_the_author() );
					$this->list .= '</small>';
				}
			}

			$this->list .= do_action( 'ctdl_list_items' );

			if ( $this->dashboard_settings['show_edit_link'] == 1 && ( current_user_can( CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == 0 ) )
				$this->list .= ' <small>(<a href="admin.php?page=cleverness-to-do-list&amp;action=edit-todo&amp;id='.esc_attr( $id ).'">'.__( 'Edit' ).'</a>)</small>';

			$this->list .= '</div></div>';
		endwhile;

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
	 * Dashboard Widget Options
	 */
	public function dashboard_options() {
		if ( isset( $_POST['cleverness_todo_dashboard_settings'] ) ) {
			$cleverness_todo_dashboard_settings = $_POST['cleverness_todo_dashboard_settings'];
			update_option( 'CTDL_dashboard_settings', $cleverness_todo_dashboard_settings );
		}
   	    settings_fields( 'cleverness-todo-dashboard-settings-group' );
 	    $options = get_option( 'CTDL_dashboard_settings' );
		$cat_id = $options['dashboard_cat'];
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

	 	    <p><label for="cleverness_todo_dashboard_settings[dashboard_cat]"><?php echo apply_filters( 'ctdl_category', esc_html__( 'Category', 'cleverness-to-do-list' ) ); ?></label>
			     <?php wp_dropdown_categories( 'taxonomy=todocategories&echo=1&orderby=name&hide_empty=0&show_option_all='.__( 'All', 'cleverness-to-do-list' ).'&id=cleverness_todo_dashboard_settings[dashboard_cat]&name=cleverness_todo_dashboard_settings[dashboard_cat]&selected='.$cat_id ); ?>
			</p>

			<p class="description"><?php _e( 'This setting is only used when <em>List View</em> is set to <em>Group</em>.', 'cleverness-to-do-list' ); ?></p>
   		    <p><label for="cleverness_todo_dashboard_settings[dashboard_author]"><?php _e( 'Show <em>Added By</em> on Dashboard Widget', 'cleverness-to-do-list' ); ?></label>
				<select id="cleverness_todo_dashboard_settings[dashboard_author]" name="cleverness_todo_dashboard_settings[dashboard_author]">
					<option value="0"<?php if ( $options['dashboard_author'] == 0 ) echo ' selected="selected"'; ?>><?php esc_html_e( 'Yes', 'cleverness-to-do-list') ; ?>&nbsp;</option>
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
			$this->dashboard_settings = get_option( 'CTDL_dashboard_settings' );
			wp_add_dashboard_widget( 'cleverness_todo', apply_filters( 'ctdl_todo_list', esc_html__( 'To-Do List', 'cleverness-to-do-list' ) ).' <a href="admin.php?page=cleverness-to-do-list">&raquo;</a>', array( $this, 'dashboard_widget' ), array( $this, 'dashboard_options' ) );
			}
		}

	/**
	 * Add scripts and styles to dashboard widget
	 */
	public function dashboard_init() {
		wp_register_script( 'cleverness_todo_dashboard_complete_js', CTDL_PLUGIN_URL.'/js/cleverness-to-do-list-dashboard-widget.js', '', 1.0, true );
		add_action( 'admin_print_scripts-index.php',  array( $this, 'dashboard_add_js' ) );
		add_action( 'wp_ajax_cleverness_todo_dashboard_complete', array( 'CTDL_Lib', 'complete_todo_callback' ) );
		add_action( 'admin_print_styles-index.php', array ( 'CTDL_Loader', 'add_admin_css' ) );
	}

	/**
	 * Add Javascript and localize variables
	 */
	public function dashboard_add_js() {
		wp_enqueue_script( 'cleverness_todo_dashboard_complete_js' );
		wp_localize_script( 'cleverness_todo_dashboard_complete_js', 'ctdl', CTDL_Loader::get_js_vars() );
    }

}
