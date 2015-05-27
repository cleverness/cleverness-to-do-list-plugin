<?php
/**
 * Cleverness To-Do List Plugin Main Class
 *
 * The main to-do list class
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.2
 */

/**
 * Main class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class ClevernessToDoList {
	protected $cat_id = '';
	protected $form = '';
	public $priorities = '';
	protected $url = '';
	public $list = '';

	public function __construct() {
		add_action( 'init', array( $this, 'cleverness_todo_checklist_init' ) );
		$this->priorities = array(
			0 => CTDL_Loader::$settings['priority_0'],
			1 => CTDL_Loader::$settings['priority_1'],
			2 => CTDL_Loader::$settings['priority_2'] );
	}

	/**
	 * Display a to-do list
	 * @param int $completed
	 * @return void
	 */
	public function display( $completed = 0 ) {
		list( $this->url, $action ) = CTDL_Lib::set_variables();

		if ( is_admin() ) $completed = 1;

		if ( is_admin() ) $this->list .= '<div class="wrap"><div class="icon32"><img src="'.CTDL_PLUGIN_URL.'/images/cleverness-todo-icon.png" alt="" /></div>
			<h2>'.apply_filters( 'ctdl_todo_list', esc_html__('To-Do List', 'cleverness-to-do-list') ).'</h2>';

		// get the existing to-do data and show the edit form if editing a to-do item
		if ( $action == 'edit-todo' ) {
			$this->edit_todo_item( $this->url );
			return;
		}

		// otherwise, display the list of to-do items
		$this->list .= $this->show_heading();

		$this->list .= '<table id="todo-list" class="todo-table widefat">';

		$this->show_table_headings();

		$this->loop_through_todos();

		$this->list .= '</table>';

		/* Show completed items in admin */
		if ( $completed == 1 ) {
			wp_reset_postdata();
			$this->list .= $this->show_completed_heading();

			$this->list .= '<table id="todo-list-completed" class="todo-table widefat">';

			$this->show_table_headings( 1 );

			$this->loop_through_todos( 1 );

			$this->list .= '</table>';
		}

		$this->list .= $this->create_new_todo_form();

		if ( is_admin() ) $this->list .= '</div>';

		wp_reset_postdata();
	}

	/**
	 * Show heading before table
	 * @return string $heading
	 */
	protected function show_heading() {
		$heading = '';
		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) $heading = '<h3>'.esc_html__( 'To-Do Items', 'cleverness-to-do-list' );
		if ( current_user_can( CTDL_Loader::$settings['add_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {
			$heading .= ' (<a href="#addtodo">'.esc_html__( 'Add New Item', 'cleverness-to-do-list' ).'</a>)';
		}
		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )) $heading .= '</h3>';
		return apply_filters( 'ctdl_heading', $heading );
	}

	/**
	 * Show heading before completed table
	 * @return string $completed_heading
	 */
	protected function show_completed_heading() {
		$completed_heading = '<h3>'.__( 'Completed Items', 'cleverness-to-do-list' );
		if ( current_user_can( CTDL_Loader::$settings['purge_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {
			$cleverness_todo_purge_nonce = wp_create_nonce( 'todopurge' );
			$completed_heading .= ' (<a id="delete-all-todos" href="admin.php?page=cleverness-to-do-list&amp;action=purgetodo&_wpnonce='.esc_attr( $cleverness_todo_purge_nonce ).'">'.esc_html__( 'Delete All', 'cleverness-to-do-list' ).'</a>)';
		}
		$completed_heading .= '</h3>';
		return apply_filters( 'ctdl_completed_heading', $completed_heading );
	}

	/**
	 * Loop through to-do items
	 * @param int $completed
	 * @param int $cat_id
	 * @param int $limit
	 */
	protected function loop_through_todos( $completed = 0, $cat_id = 0, $limit = 5000 ) {
		global $current_user, $userdata;
		$user = CTDL_Lib::get_user_id( $current_user, $userdata );

		// if categories are enabled and sort order is set to cat id and we're not getting todos for a specific category
		if ( CTDL_Loader::$settings['categories'] == 1 && CTDL_Loader::$settings['sort_order'] == 'cat_id' && $cat_id == 0 ) {

			$categories = CTDL_Categories::get_categories();
			$items = 0;
			$visible = 0;
			$posts_to_exclude = array();
			if ( !is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) $visibility = get_option( 'CTDL_categories' );

			foreach ( $categories as $category) {
				if ( !is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
					$visible = $visibility["category_$category->term_id"];
				}

				$todo_items = CTDL_Lib::get_todos( $user, 5000, $completed, $category->term_id );

				if ( $todo_items->have_posts() ) {
					array_splice( $posts_to_exclude, count( $posts_to_exclude ), 0, $this->show_todo_list_items( $todo_items, $completed, $visible ) );
					$items = 1;
				}
			}

			$todo_items = CTDL_Lib::get_todos( $user, 5000, $completed, 0, $posts_to_exclude );
			if ( $todo_items->have_posts() ) {
				$this->show_todo_list_items( $todo_items, $completed );
				$items = 1;
			}

			if ( $items == 0 ) {
				if ( $completed == 0 ) {
					$this->list .= '<tr><td colspan="100%">'.apply_filters( 'ctdl_no_items', esc_html__( 'No items to do.', 'cleverness-to-do-list' ) ).'</td></tr>';
				} else {
					$this->list .= '<tr><td colspan="100%">'.apply_filters( 'ctdl_no_completed_items', esc_html__( 'No completed items.', 'cleverness-to-do-list' ) ).'</td></tr>';
				}
			}

		} else {

			$todo_items = CTDL_Lib::get_todos( $user, 5000, $completed, $cat_id );

			if ( $todo_items->have_posts() ) {
				$this->show_todo_list_items( $todo_items, $completed );
			} else {
				if ( $completed == 0 ) {
					$this->list .= '<tr><td colspan="100%">'.apply_filters( 'ctdl_no_items', esc_html__( 'No items to do.', 'cleverness-to-do-list' ) ).'</td></tr>';
				} else {
					$this->list .= '<tr><td colspan="100%">'.apply_filters( 'ctdl_no_completed_items', esc_html__( 'No completed items.', 'cleverness-to-do-list' ) ).'</td></tr>';
				}
			}

		}
	}

	/**
	 * Generate the To-Do List
	 * @param object $todo_items
	 * @param int $completed
	 * @param int $visible
	 * @return array $posts_to_exclude
	 */
	protected function show_todo_list_items( $todo_items, $completed = 0, $visible = 0 ) {

		while ( $todo_items->have_posts() ) : $todo_items->the_post();
			$id = get_the_ID();
			$posts_to_exclude[] = $id;

			if ( $visible == 0 ) {
				list( $priority, $assign_meta, $deadline_meta, $completed_meta, $progress_meta, $planner_meta ) = CTDL_Lib::get_todo_meta( $id );

				$priority_class = CTDL_Lib::set_priority_class( $priority );

				$this->list .= '<tr id="todo-'.esc_attr( $id ).'"'.$priority_class.'>';
				$this->show_id( $id );
				$this->show_checkbox( $id, $completed );
				$this->show_todo_text( get_the_content() );
				$this->show_priority( $priority );
				$this->show_progress( $progress_meta, 'table', $completed );
				$this->show_category( get_the_terms( $id, 'todocategories' ) );
				if ( CTDL_PP ) $this->show_planner( $planner_meta );
				$this->show_assigned( $assign_meta );
				$this->show_addedby( get_the_author() );
				$this->show_deadline( $deadline_meta );
				$this->show_date_added( get_the_date( 'Ymd' ), get_the_date( CTDL_Loader::$settings['date_format'] ) );
				if ( $completed == 1 ) $this->show_completed( $completed_meta );
				$this->list .= do_action( 'ctdl_list_items' );
				$this->show_edit_link( $id );
				$this->list .= '</tr>';
			}
		endwhile;

		wp_reset_postdata();

		return $posts_to_exclude;

	}

	/**
	 * Get the to-do item data and display the edit form
	 */
	protected function edit_todo_item() {
		$id = absint( $_GET['id'] );
		$todo_item = CTDL_Lib::get_todo( $id );
		$this->list .= $this->create_edit_todo_form( $todo_item );
		if ( is_admin() ) $this->url = 'admin.php?page=cleverness-to-do-list';
		$this->list .= '<p><a href="'.$this->url.'">&laquo; '.apply_filters( 'ctdl_return', esc_html__( 'Return to To-Do List', 'cleverness-to-do-list' ) ).'</a></p>';
	}

	/**
	 * Creates the HTML for the form used to edit a to-do item
	 * @param $todo_item
	 * @return string Form HTML
	 */
	protected function create_edit_todo_form( $todo_item ) {
		$id = $todo_item->ID;
		list( $priority, $assign_meta, $deadline_meta, $completed_meta, $progress_meta, $planner_meta ) = CTDL_Lib::get_todo_meta( $id );
		if ( is_admin() ) $url = 'admin.php?page=cleverness-to-do-list'; else $url = strtok( $this->url, "?" );
		$this->form = '';

		if ( is_admin() ) $this->form .= apply_filters( 'ctdl_edit_heading', '<h3>'.esc_html__( 'Edit To-Do Item', 'cleverness-to-do-list' ).'</h3>' );

		$this->form .= '<form name="edittodo" id="edittodo" action="'.$url.'" method="post"><table class="todo-form form-table">';
		$this->create_priority_field( $priority );
		$this->create_deadline_field( $deadline_meta );
		$this->create_category_field( get_the_terms( $id, 'todocategories' ) );
		if ( CTDL_PP ) $this->create_planner_field( $planner_meta );
		$this->create_assign_field( $assign_meta );
		$this->create_progress_field( $progress_meta );
		$this->form .= do_action( 'ctdl_edit_form_action' );
		$this->form = apply_filters( 'ctdl_edit_form', $this->form );
		$this->create_todo_text_field( $todo_item->post_content );
		$this->form .= '</table>'.wp_nonce_field( 'todoupdate', 'todoupdate', true, false ).'<input type="hidden" name="action" value="updatetodo" />
        	    <p class="submit"><input type="submit" name="submit" class="button-primary" value="'.apply_filters( 'ctdl_edit_text', esc_attr__( 'Save Changes', 'cleverness-to-do-list' ) ).'" /></p>
				<input type="hidden" name="id" value="'.absint( $id ).'" />';
		$this->form .= '</form>';

		return $this->form;
	}

	/**
	 * Creates the HTML form to add a new to-do item
	 * @return string Form HTML
	 */
	protected function create_new_todo_form() {
		if ( current_user_can( CTDL_Loader::$settings['add_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {

			if ( is_admin() ) $this->url = 'admin.php?page=cleverness-to-do-list';

   	 	    $this->form = '<h3>'.apply_filters( 'ctdl_add_heading', esc_html__( 'Add New To-Do Item', 'cleverness-to-do-list' ) ).'</h3>';

    	    $this->form .= '<form name="addtodo" id="addtodo" action="'.$this->url.'" method="post">
	  		    <table class="todo-form form-table">';
				$this->create_priority_field();
				$this->create_deadline_field();
				$this->create_category_field();
				if ( CTDL_PP ) $this->create_planner_field();
				$this->create_assign_field();
				$this->create_progress_field();
				$this->form .= do_action( 'ctdl_add_form_action' );
				$this->form = apply_filters( 'ctdl_add_form', $this->form );
				$this->create_todo_text_field();
				$this->form .= '</table>'.wp_nonce_field( 'todoadd', 'todoadd', true, false ).'<input type="hidden" name="action" value="addtodo" />
        	    <p class="submit"><input type="submit" name="submit" class="button-primary" value="'.apply_filters( 'ctdl_add_text', esc_attr__( 'Submit To-Do Item', 'cleverness-to-do-list' ) ).'" /></p>';
			$this->form .= '</form>';

			return $this->form;
		} else {
			return '';
		}
	}

	/**
	 * Creates the HTML for the Post Planner Form Field
	 * @param null $planner_meta
	 * @since 3.2
	 */
	protected function create_planner_field( $planner_meta = NULL ) {
		if ( $planner_meta == NULL && isset( $_GET['planner'] ) ) {
			$planner_meta = absint( $_GET['planner'] );
		}
		$this->form .= '<tr>
		  	<th scope="row"><label for="cleverness_todo_planner">'.apply_filters( 'ctdl_planner', esc_html__( 'Post Planner', 'cleverness-to-do-list' ) ).'</label></th>
		  	<td>
        		<select id="cleverness_todo_planner" name="cleverness_todo_planner"><option value="" '.selected( $planner_meta, NULL, false ).'>'.esc_html__( 'Select', 'cleverness-to-do-list' ).'</option>';
		$planners = CTDL_Lib::get_planners();
		foreach ( $planners as $planner ) {
			$this->form .= '<option value="'.$planner->ID.'" '.selected( $planner_meta, $planner->ID, false ).'>'.$planner->post_title.'</option>';
		}
		$this->form .= '</select>
		  	</td>
			</tr>';
	}

	/**
	 * Creates the HTML for the Priority Form Field
	 * @param int $priority Existing field data
	 */
	protected function create_priority_field( $priority = NULL ) {
		$selected = '';
		$this->form .= '<tr>
		  	<th scope="row"><label for="cleverness_todo_priority">'.apply_filters( 'ctdl_priority', esc_html__( 'Priority', 'cleverness-to-do-list' ) ).'</label></th>
		  	<td>
        		<select id="cleverness_todo_priority" name="cleverness_todo_priority">';
					if ( isset( $priority ) ) $selected = ( $priority == 0 ? ' selected = "selected"' : '' );
					$this->form .= sprintf( '<option value="0"%s>%s</option>', $selected, CTDL_Loader::$settings['priority_0'] );
					if ( isset( $priority ) ) {
						$selected = ( $priority == 1 ? ' selected' : '' );
						} else {
							$selected = ' selected="selected"';
						}
					$this->form .= sprintf( '<option value="1"%s>%s</option>', $selected, CTDL_Loader::$settings['priority_1'] );
					$selected = '';
					if ( isset( $priority ) ) $selected = ( $priority == 2 ? ' selected' : '' );
					$this->form .= sprintf( '<option value="2"%s>%s</option>', $selected, CTDL_Loader::$settings['priority_2'] );
        		$this->form .= '</select>
		  	</td>
			</tr>';
	}

	/**
	 * Creates the HTML for the Assign to Use Field
	 * @param int $assign Existing field data
	 */
	protected function create_assign_field( $assign = NULL ) {
		if ( CTDL_Loader::$settings['list_view'] != 0 && ( CTDL_Loader::$settings['assign'] == 0 && current_user_can( CTDL_Loader::$settings['assign_capability'] ) ) ) {
			$this->form .= '<tr>
		  		<th scope="row"><label for="cleverness_todo_assign">'.apply_filters( 'ctdl_assign', esc_html__( 'Assign To', 'cleverness-to-do-list' ) ).'</label></th>
		  		<td>
		  			<select name="cleverness_todo_assign[]" id="cleverness_todo_assign" multiple="multiple" style="width: 220px;"><option></option>';

			if ( CTDL_Loader::$settings['user_roles'] == '' ) {
				$roles = array( 'contributor', 'author', 'editor', 'administrator' );
			} else {
				$roles = explode( ", ", CTDL_Loader::$settings['user_roles'] );
			}

			foreach ( $roles as $role ) {
				$role_users = CTDL_Lib::get_users( $role );
				foreach ( $role_users as $role_user ) {
					$selected = '';
					if ( is_array( $assign ) ) {
						if ( isset( $assign ) && in_array( $role_user->ID, $assign ) ) $selected = ' selected="selected"';
					} else {
						if ( isset( $assign ) && $assign == $role_user->ID ) $selected = ' selected="selected"';
					}
					$this->form .= sprintf( '<option value="%d"%s>%s</option>', $role_user->ID, $selected, $role_user->display_name );
				}
			}

			$this->form .= '</select>';

			$this->form .= '</td></tr>';
		}
	}

	/**
	 * Creates the HTML for the Deadline Field
	 * @param string $deadline Existing field data
	 */
	protected function create_deadline_field( $deadline = NULL ) {
		if ( CTDL_Loader::$settings['show_deadline'] == 1 ) {
			$value = ( isset( $deadline ) && $deadline != 0 ? date( 'Y-m-d', $deadline ) : '' );
			$this->form .= sprintf( '<tr>
				<th scope="row"><label for="cleverness_todo_deadline">%s</label></th>
				<td><input type="hidden" name="cleverness_todo_format" id="cleverness_todo_format" value="%s" />
				<input type="text" name="cleverness_todo_deadline" id="cleverness_todo_deadline" value="%s" /></td>
			</tr>', apply_filters( 'ctdl_deadline', esc_html__( 'Deadline', 'cleverness-to-do-list' ) ), CTDL_Lib::dateFormatTojQueryUIDatePickerFormat( 'Y-m-d' ), esc_attr( $value ) );
		}
	}

	/**
	 * Creates the HTML for the Progress Field
	 * @param int $progress Existing field data
	 */
	protected function create_progress_field( $progress = NULL ) {
		if ( CTDL_Loader::$settings['show_progress'] == 1 ) {
			$value = ( isset( $progress ) ? $progress : 0 );
			$this->form .= '<tr>
				<th scope="row"><label for="cleverness_todo_progress">'.apply_filters( 'ctdl_progress', esc_html__( 'Progress', 'cleverness-to-do-list' ) ).'</label></th>
				<td><input type="text" name="cleverness_todo_progress" id="cleverness_todo_progress" value="'.esc_attr( $value ).'" />%
				<div id="cleverness-todo-progress-slider"></div></td>
			</tr>';
		}
	}

	/**
	 * Creates the HTML for the Category Field
	 * @param int $cat_id Existing field data
	 */
	protected function create_category_field( $cat_id = NULL ) {
		$cat_id = ( is_array( $cat_id ) ? reset( $cat_id ) : NULL );
		if ( CTDL_Loader::$settings['categories'] == 1 ) {
			$cat_id = ( $cat_id != NULL ? $cat_id->term_id : 0 );
			$this->form .= '<tr><th scope="row"><label for="cat">'.apply_filters( 'ctdl_category', esc_html__( 'Category', 'cleverness-to-do-list' ) ).'</label></th><td>'.
				wp_dropdown_categories( 'taxonomy=todocategories&echo=0&orderby=name&hide_empty=0&show_option_none='.__( 'None', 'cleverness-to-do-list' ).'&selected='.$cat_id ).'</td></tr>';
		}
	}

	/**
	 * Creates the HTML for the To-Do Text Field
	 * @param array $todo_text Existing field data
	 */
	protected function create_todo_text_field( $todo_text = NULL ) {
		$this->form .= '<tr><th scope="row">'.apply_filters( 'ctdl_todo', esc_html__( 'To-Do', 'cleverness-to-do-list' ) ).'</th><td>';
		if ( CTDL_Loader::$settings['wysiwyg'] == 1 ) {
			ob_start();
			wp_editor( $todo_text, 'clevernesstododescription', array(
				'media_buttons' => true,
				'textarea_name' => 'cleverness_todo_description',
				'textarea_rows' => 5,
				'wpautop'       => true,
			) );
			$this->form .= ob_get_contents();
			ob_end_clean();
		} else {
			$text = ( isset( $todo_text ) ? stripslashes( esc_html( $todo_text, 1 ) ) : '' );
			$this->form .= '<textarea id="cleverness_todo_description" name="cleverness_todo_description" rows="5" cols="50">'.$text.'</textarea>';
		}
		$this->form .= '</td></tr>';
	}

	/**
	 * Creates the HTML for the To-Do List Table Headings
	 * @param $completed
	 */
	protected function show_table_headings( $completed = 0 ) {
		$this->list .= '<thead><tr>';
		if ( CTDL_Loader::$settings['show_id'] ) $this->list .= '<th id="id-col">'.apply_filters( 'ctdl_heading_id', esc_html__( 'ID', 'cleverness-to-do-list' ) ).'</th>';
		if ( CTDL_Lib::check_permission( 'todo', 'complete' ) ) $this->list .= '<th id="checkbox-col" class="{sorter: false} no-sort"><span class="icon minus"></span></th>';
		$this->list .= '<th id="item-col">'.apply_filters( 'ctdl_heading_item', esc_html__( 'Item', 'cleverness-to-do-list' ) ).'</th>';
	  	$this->list .= '<th id="priority-col">'.apply_filters( 'ctdl_heading_priority', esc_html__( 'Priority', 'cleverness-to-do-list' ) ).'</th>';
		if ( CTDL_Loader::$settings['show_progress'] == 1 ) $this->list .= '<th id="progress-col">'.apply_filters( 'ctdl_heading_progress', esc_html__( 'Progress', 'cleverness-to-do-list' ) ).'</th>';
		if ( CTDL_Loader::$settings['categories'] == 1 ) $this->list .= '<th id="category-col">'.apply_filters( 'ctdl_heading_category', esc_html__( 'Category', 'cleverness-to-do-list' ) ).'</th>';
		if ( CTDL_PP ) $this->list .= '<th id="planner-col">'.apply_filters( 'ctdl_heading_planner', esc_html__( 'Post Planner', 'cleverness-to-do-list' ) ).'</th>';
			if ( CTDL_Loader::$settings['assign'] == 0 && ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 0
			&& ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) || ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 1 )
			&& CTDL_Loader::$settings['assign'] == 0 ) $this->list .= '<th id="assigned-col">'.apply_filters( 'ctdl_heading_assigned', esc_html__( 'Assigned To', 'cleverness-to-do-list' ) ).'</th>';
		if ( CTDL_Loader::$settings['todo_author'] == 0 && CTDL_Loader::$settings['list_view'] == 1 ) $this->list .= '<th id="added-col">'.apply_filters( 'ctdl_heading_added_by', esc_html__ ('Added By', 'cleverness-to-do-list' ) ).'</th>';
		if ( CTDL_Loader::$settings['show_deadline'] == 1 ) $this->list .= '<th id="deadline-col">'.apply_filters( 'ctdl_heading_deadline', esc_html__( 'Deadline', 'cleverness-to-do-list' ) ).'</th>';
		if ( CTDL_Loader::$settings['show_date_added'] == 1 ) $this->list .= '<th id="date-col">'.apply_filters( 'ctdl_heading_date_added', esc_html__( 'Date Added', 'cleverness-to-do-list' ) ).'</th>';
		if ( $completed == 1 && CTDL_Loader::$settings['show_completed_date'] == 1) $this->list .= '<th id="completed-col">'.apply_filters( 'ctdl_heading_completed', esc_html__('Completed', 'cleverness-to-do-list' ) ).'</th>';
		$this->list .= do_action( 'ctdl_table_headings' );
		if ( current_user_can(CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == 0 )
			$this->list .= '<th id="action-col" class="{sorter: false} no-sort">'.apply_filters( 'ctdl_heading_action', esc_html__( 'Action', 'cleverness-to-do-list' ) ).'</th>';
    	$this->list .= '</tr></thead>';
	}

	/**
	 * Show the ID for a To-Do Item
	 * @param int $id
	 */
	protected function show_id( $id ) {
		if ( CTDL_Loader::$settings['show_id'] == 1 ) {
			$this->list .= ( $id != '' ? sprintf( '<td class="todo-id">%s</td>', esc_attr( $id ) ) : '<td></td>' );
		}
	}

	/**
	 * Create the HTML to show a To-Do List Checkbox
	 * @param int $id
	 * @param int $completed
	 * @param string $layout
	 * @param string $single
	 */
	protected function show_checkbox( $id, $completed = 0, $layout = 'table', $single = '' ) {
		$permission = CTDL_Lib::check_permission( 'todo', 'complete' );
		if ( $permission === true ) {
			if ( is_admin() || $layout == 'table' ) $this->list .= '<td>';
			if ( $completed == 1 ) {
				$this->list .= sprintf( '<input type="checkbox" id="ctdl-%d" class="todo-checkbox todo-completed'.$single.'" checked="checked" />', esc_attr( $id ) );
			} else {
				$this->list .= sprintf( '<input type="checkbox" id="ctdl-%d" class="todo-checkbox todo-uncompleted'.$single.'"/>', esc_attr( $id ) );
			}
			$cleverness_todo_complete_nonce = wp_create_nonce( 'todocomplete' );
			$this->list .= '<input type="hidden" name="cleverness_todo_complete_nonce" value="'.esc_attr( $cleverness_todo_complete_nonce ).'" />';
			if ( is_admin() || $layout == 'table' ) $this->list .= '</td>';
		}
	}

	/**
	 * Show the To-Do Text
	 * @param string $todo_text
	 * @param string $layout
	 */
	public function show_todo_text( $todo_text, $layout = 'table' ) {
		if ( is_admin() || $layout == 'table' ) {
			$this->list .= '<td class="todo-text">';
		}
		if ( CTDL_Loader::$settings['autop'] == 1 ) {
			$todo_text = wpautop( $todo_text );
		}
		if ( CTDL_Loader::$settings['wysiwyg'] == 1 ) {
			$this->list .= $todo_text;
		} else {
			$this->list .= stripslashes( $todo_text );
		}
		if ( is_admin() || $layout == 'table' ) $this->list .= '</td>';
	}

	/**
	 * Show the Edit To-Do Link
	 * @param int $id
	 */
	protected function show_edit_link( $id ) {
		$edit = '';
		$url = $this->url.'?action=edit-todo&amp;id='.absint( $id );
		if ( current_user_can( CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {
			if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
				$edit = '<input class="edit-todo button-secondary" type="button" value="'.apply_filters( 'ctdl_edit', esc_attr__( 'Edit', 'cleverness-to-do-list' ) ).'" />';
			} else {
				$edit = '<a href="'.$url.'" class="edit-todo">'.apply_filters( 'ctdl_edit', esc_attr__( 'Edit', 'cleverness-to-do-list' ) ).'</a>';
				}
			}
		if ( current_user_can( CTDL_Loader::$settings['delete_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {
			if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
				$edit .= ' <input class="delete-todo button-secondary" type="button" value="'.apply_filters( 'ctdl_delete', esc_attr__( 'Delete', 'cleverness-to-do-list' ) ).'" />';
			} else {
				$edit .= ' | <a href="" class="delete-todo">'.apply_filters( 'ctdl_delete', esc_html__( 'Delete', 'cleverness-to-do-list' ) ).'</a>';
				}
			}
	  	if ( current_user_can( CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {
			  $this->list .= '<td class="todo-actions">'.$edit.'</td>';
		 }
	}

	/**
	 * Show the Priority Level of a To-Do Item
	 * @param int $the_priority
	 */
	public function show_priority( $the_priority ) {
		$this->list .= sprintf( '<td class="todo-priority">%s</td>', esc_attr( $this->priorities[$the_priority] ) );
	}

	/**
	 * Show the User that a To-Do Item is Assigned To
	 * @param int $assign
	 * @param string $layout
	 */
	public function show_assigned( $assign, $layout = 'table' ) {
		if ( ( ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 0 && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) ||
		( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 1) ) && CTDL_Loader::$settings['assign'] == 0 ) {

				if ( is_array( $assign ) ) {
					$assign_users = '';
					if ( $layout == 'table' ) $this->list .= '<td class="todo-assigned">';
					foreach ( $assign as $value ) {
						if ( $value != '-1' && $value != '' && $value != 0 ) {
							$user = get_userdata( $value );
							$assign_users .= $user->display_name.', ';
						}
					}
					$this->list .= substr( $assign_users, 0, -2 );
					if ( $layout == 'table' ) $this->list .= '</td>';

				} else {
					if ( $assign != '-1' && $assign != '' && $assign != 0 ) {
						$assign_user = get_userdata( $assign );
						if ( $layout == 'table' ) {
							$this->list .= '<td class="todo-assigned">' . esc_html( $assign_user->display_name ) . '</td>';
						} else {
							$this->list .= esc_attr( $assign_user->display_name );
						}
					}

				}
			}
   	}

	/**
	 * Show the Post Planner
	 * @param $planner
	 * @since 3.2
	 */
	public function show_planner( $planner ) {
		$this->list .= '<td class="todo-planner">';
		if ( $planner != 0 && PostPlanner_Lib::planner_exists( $planner ) ) {
			$url = admin_url( 'post.php?post=' . absint( $planner ) . '&action=edit' );
			$this->list .= '<a href="'.esc_url( $url ).'">'.get_the_title( $planner ).'</a>';
		}
		$this->list .= '</td>';
	}

	/**
	 * Show the Category that a To-Do Item is In
	 * @param array $categories
	 */
	public function show_category( $categories ) {
		if ( CTDL_Loader::$settings['categories'] == 1 ) {
			$this->list .= '<td class="todo-categories">';
			if ( $categories != NULL ) {
				foreach( $categories as $category ) {
					$this->list .= esc_attr( $category->name );
				}
			}
			$this->list .= '</td>';
		}
	}

	/**
	 * Show Who Added a To-Do Item
	 * @param int $author
	 * @param string $layout
	 */
	public function show_addedby( $author, $layout = 'table' ) {
		if ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['todo_author'] == 0 ) {
			if ( $layout == 'table' ) {
				$this->list .= '<td class="todo-addedby">'.esc_attr( $author ).'</td>';
			} else {
				$this->list .= esc_attr( $author );
			}
		}
	}

	/**
	 * Show the Deadline for a To-Do Item
	 * @param string $deadline
	 * @param string $layout
	 */
	public function show_deadline( $deadline, $layout = 'table' ) {
		if ( CTDL_Loader::$settings['show_deadline'] == 1 ) {
			if ( $layout == 'table' ) {
				$formatted_deadline = ( $deadline != '' ? date( CTDL_Loader::$settings['date_format'], $deadline ) : '' );
				$this->list .= apply_filters( 'ctdl_show_deadline', '<td class="todo-deadline">'.$formatted_deadline.'</td>', CTDL_Loader::$settings['date_format'], $deadline );
			} else {
				$this->list .= ( $deadline != '' ? sprintf( '%s', date( CTDL_Loader::$settings['date_format'], $deadline ) ) : '' );
			}
		}
	}

	/**
	 * Show the Date the To-Do Item was Added
	 * @param $date
	 * @param $formatted_date
	 * @param string $layout
	 * @since 3.1
	 */
	public function show_date_added( $date, $formatted_date, $layout = 'table' ) {
		if ( CTDL_Loader::$settings['show_date_added'] == 1 ) {
			if ( $layout == 'table' ) {
				$this->list .= ( $date != '' ? sprintf( '<td class="todo-date"><span style="display:none;">%s</span>%s</td>', esc_attr( $date ),
					esc_attr( $formatted_date ) ) : '<td class="todo-date"></td>' );
			} else {
				$this->list .= ( $date != '' ? sprintf( '%s', esc_attr( $formatted_date ) ) : '' );
			}
		}
	}

	/**
	 * Show the Date that a To-Do Item was Completed
	 * @param string $completed
	 * @param string $layout
	 */
	public function show_completed( $completed, $layout = 'table' ) {
		if ( CTDL_Loader::$settings['show_completed_date'] && $completed != '0000-00-00 00:00:00' ) {
			$date = ( isset( $completed ) ? date( CTDL_Loader::$settings['date_format'], strtotime( $completed ) ) : '' );
			if ( $layout == 'table' ) {
				$this->list .= '<td class="todo-completed">'.esc_attr( $date ).'</td>';
			} else {
				$this->list .= esc_attr( $date );
			}
		}
	}

	/**
	 * Show the Progress of a To-Do Item
	 * @param int $progress
	 * @param string $layout
	 * @param int $completed
	 */
	public function show_progress( $progress, $layout = 'table', $completed = 0 ) {
		if ( CTDL_Loader::$settings['show_progress'] == 1 ) {
			$progress = ( $completed == 1 ? '100' : $progress );
			if ( $layout == 'table' ) {
				$this->list .= ( $progress != '' ? sprintf( '<td class="todo-progress">%d%%</td>', esc_attr( $progress ) ) : '<td class="todo-progress"></td>' );
			} else {
				$this->list .= ( $progress != '' ? sprintf( '%d%%', esc_attr( $progress ) ) : '' );
			}

		}
	}

} // end class