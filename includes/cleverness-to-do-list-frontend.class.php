<?php
/**
 * Cleverness To-Do List Plugin Frontend Classes
 *
 * Allows administration and viewing of to-do items on front-end
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.2.2
 */

/**
 * Frontend class for to-do list administration
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Frontend_Admin extends ClevernessToDoList {
	public $atts;

	public function __construct() {
		add_shortcode( 'todoadmin', array( $this, 'display_admin' ) );
		parent::__construct();
		add_action( 'wp_enqueue_scripts', array( 'CTDL_Loader', 'frontend_admin_register_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( 'CTDL_Loader', 'frontend_css' ) );
	}

	/**
	 * Displays the to-do list administration
	 * @param $atts shortcode attributes
	 * @return string To-Do List
	 */
	public function display_admin( $atts ) {
		$this->atts = $atts;

		$atts = shortcode_atts( array(
			'title' => '',
			'completed' => 0
		), $this->atts, 'todoadmin' );
		$this->list = '';

		CTDL_Loader::frontend_admin_enqueue_scripts();

		$this->list = '<div id="ctdl-frontend-admin">';

		if ( $atts['title'] != '' ) {
			$this->list .= '<h3 class="todo-title">'.esc_html( $atts['title'] ).$this->show_heading().'</h3>';
		}

		if ( is_user_logged_in() && is_user_member_of_blog() ) {
			list( $this->url, $action ) = CTDL_Lib::set_variables();

			// get the existing to-do data and show the edit form if editing a to-do item
			if ( $action == 'edit-todo' ) {
				$this->edit_todo_item( $this->url );
			} else {
				$this->list .= '<div class="ctdl-tables">';
				$this->display();
				if ( 1 == $atts['completed'] ) {
					$this->display( 1 );
				}
				$this->list .= '</div>';
				$this->list .= $this->create_new_todo_form();
			}

		} else {
			$this->list .= esc_html__( 'You must be logged in to view', 'cleverness-to-do-list' );
			}

		$this->list .= '</div>';

		return $this->list;
	}

	/**
	 * Display a to-do list
	 * @param int $completed
	 * @return string
	 */
	public function display( $completed = 0 ) {
		$atts = shortcode_atts( array(
			'category' => 0,
		), $this->atts, 'todoadmin' );

		$class = ( $completed == 0 ? 'ctdl-uncompleted' : 'ctdl-completed' );
		$id = ( $completed == 0 ? 'todo-list' : 'todo-list-completed' );

		$this->list .= '<table id="'.$id.'" class="todo-table widefat '.$class.'">';

		$this->loop_through_todos( $completed, $atts['category'] );

		$this->list .= '</table>';

		wp_reset_postdata();

		return $this->list;
	}

	/**
	 * Generate the To-Do List
	 * @param $todo_items
	 * @param int $completed
	 * @param $visible
	 * @return array $posts_to_exclude
	 */
	public function show_todo_list_items( $todo_items, $completed = 0, $visible = 0 ) {
		$atts = shortcode_atts( array(
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'categories' => 0,
			'addedby'    => 0,
			'date'       => 0,
			'editlink'   => 1,
			'completed_date' => 0,
			'planner'   => 0
		), $this->atts, 'todoadmin' );

		while ( $todo_items->have_posts() ) : $todo_items->the_post();
			$id = get_the_ID();
			$posts_to_exclude[] = $id;

			if ( $visible == 0 ) {
				list( $the_priority, $assign_meta, $deadline_meta, $completed_meta, $progress_meta, $planner_meta ) = CTDL_Lib::get_todo_meta( $id );

				$priority_class = CTDL_Lib::set_priority_class( $the_priority );

				$this->list .= '<tr id="todo-'.esc_attr( $id ).'"' . $priority_class . '>';
				$this->show_checkbox( $id, $completed );
				$this->show_todo_text( get_the_content() );

				if ( $atts['priority'] == 1 ) $this->show_priority( $the_priority );
				if ( $atts['progress'] == 1 ) $this->show_progress( $progress_meta, 'table', $completed );
				if ( $atts['categories'] == 1 ) $this->show_category( get_the_terms( $id, 'todocategories' ) );
				if ( CTDL_PP && $atts['planner'] == 1 ) $this->show_planner( $planner_meta );
				if ( $atts['assigned'] == 1 ) $this->show_assigned( $assign_meta );
				if ( $atts['addedby'] == 1 ) $this->show_addedby( get_the_author() );
				if ( $atts['deadline'] == 1 ) $this->show_deadline( $deadline_meta );
				if ( $atts['date'] == 1 ) $this->show_date_added( get_the_date( 'Ymd' ), get_the_date( CTDL_Loader::$settings['date_format'] ) );
				if ( $atts['completed_date'] == 1 && $completed == 1 ) $this->show_completed( $completed_meta );
				$this->list .= do_action( 'ctdl_list_items' );
				if ( $atts['editlink'] == 1 ) $this->show_edit_link( $id );
				$this->list .= '</tr>';
			}
		endwhile;

		wp_reset_postdata();

		return $posts_to_exclude;
	}

	/**
	 * Loop through to-do items
	 *
	 * @param int $completed
	 * @param int $cat_id
	 * @param int $limit
	 */
	protected function loop_through_todos( $completed = 0, $cat_id = 0, $limit = 5000 ) {
		global $current_user, $userdata;
		$user = CTDL_Lib::get_user_id( $current_user, $userdata );

		// if categories are enabled and sort order is set to cat id and we're not getting todos for a specific category
		if ( CTDL_Loader::$settings['categories'] == 1 && CTDL_Loader::$settings['sort_order'] == 'cat_id' && $cat_id == 0 ) {

			$categories       = CTDL_Categories::get_categories();
			$items            = 0;
			$visible          = 0;
			$headings         = 0;
			$posts_to_exclude = array();
			$visibility = get_option( 'CTDL_categories' );

			foreach ( $categories as $category ) {
				$visible = $visibility["category_$category->term_id"];

				$todo_items = CTDL_Lib::get_todos( $user, 5000, $completed, $category->term_id );

				if ( $todo_items->have_posts() ) {
					if ( $headings == 0 ) {
						$headings = $this->show_table_headings( $completed );
					}
					array_splice( $posts_to_exclude, count( $posts_to_exclude ), 0, $this->show_todo_list_items( $todo_items, $completed, $visible ) );
					$items = 1;
				}
			}

			$todo_items = CTDL_Lib::get_todos( $user, 5000, $completed, 0, $posts_to_exclude );
			if ( $todo_items->have_posts() ) {
				if ( $headings == 0 ) {
					$this->show_table_headings( $completed );
				}
				$this->show_todo_list_items( $todo_items, $completed );
				$items = 1;
			}

			if ( $items == 0 ) {
				if ( $completed == 0 ) {
					$this->list .= '<tr><td>' . apply_filters( 'ctdl_no_items', esc_html__( 'No items to do.', 'cleverness-to-do-list' ) ) . '</td></tr>';
				} else {
					$this->list .= '<tr><td>' . apply_filters( 'ctdl_no_completed_items', esc_html__( 'No completed items.', 'cleverness-to-do-list' ) ) . '</td></tr>';
				}
			}

		} else {

			$todo_items = CTDL_Lib::get_todos( $user, 5000, $completed, $cat_id );

			if ( $todo_items->have_posts() ) {
				$this->show_table_headings( $completed );
				$this->show_todo_list_items( $todo_items, $completed );
			} else {
				if ( $completed == 0 ) {
					$this->list .= '<tr><td>' . apply_filters( 'ctdl_no_items', esc_html__( 'No items to do.', 'cleverness-to-do-list' ) ) . '</td></tr>';
				} else {
					$this->list .= '<tr><td>' . apply_filters( 'ctdl_no_completed_items', esc_html__( 'No completed items.', 'cleverness-to-do-list' ) ) . '</td></tr>';
				}
			}

		}
	}

	/**
	 * Creates the HTML for the To-Do List Table Headings
	 *
	 * @param $completed
	 *
	 * @return int|void
	 */
	public function show_table_headings( $completed = 0 ) {
		$atts = shortcode_atts( array(
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'categories' => 0,
			'addedby'    => 0,
			'date'       => 0,
			'editlink'   => 1,
			'completed_date' => 0,
			'planner'   => 0
		), $this->atts, 'todoadmin' );

		$this->list .= '<thead><tr>';
		$this->list .= '<th id="checkbox-col" class="{sorter: false} no-sort"></th>';
		$this->list .= '<th id="item-col">'.apply_filters( 'ctdl_heading_item', esc_html__( 'Item', 'cleverness-to-do-list' ) ).'</th>';
		if ( $atts['priority'] == 1 ) $this->list .= '<th id="priority-col">'.apply_filters( 'ctdl_heading_priority', esc_html__( 'Priority', 'cleverness-to-do-list' ) ).'</th>';
		if ( $atts['progress'] == 1 && CTDL_Loader::$settings['show_progress'] == 1 ) $this->list .= '<th id="progress-col">'.apply_filters( 'ctdl_heading_progress', esc_html__( 'Progress', 'cleverness-to-do-list' ) ).'</th>';
		if ( $atts['categories'] == 1 && CTDL_Loader::$settings['categories'] == 1 ) $this->list .= '<th id="category-col">'.apply_filters( 'ctdl_heading_category', esc_html__( 'Category', 'cleverness-to-do-list' ) ).'</th>';
		if ( CTDL_PP && $atts['planner'] == 1 ) $this->list .= '<th id="planner-col">'.apply_filters( 'ctdl_heading_planner', esc_html__( 'Post Planner', 'cleverness-to-do-list' ) ).'</th>';
		if ( $atts['assigned'] == 1 && ( CTDL_Loader::$settings['assign'] == 0 && ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 0
				&& ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) || ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 1 )
				&& CTDL_Loader::$settings['assign'] == 0 ) ) $this->list .= '<th id="assigned-col">'.apply_filters( 'ctdl_heading_assigned', esc_html__( 'Assigned To', 'cleverness-to-do-list' ) ).'</th>';
		if ( $atts['addedby'] == 1 && CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['todo_author'] == 0 )
			$this->list .= '<th id="added-col">'.apply_filters( 'ctdl_heading_added_by', esc_html__( 'Added By', 'cleverness-to-do-list' ) ).'</th>';
		if ( $atts['deadline'] == 1  && CTDL_Loader::$settings['show_deadline'] == 1 ) $this->list .= '<th id="deadline-col">'.apply_filters( 'ctdl_heading_deadline', esc_html__( 'Deadline', 'cleverness-to-do-list' ) ).'</th>';
		if ( $atts['date'] == 1 && CTDL_Loader::$settings['show_date_added'] == 1 ) $this->list .= '<th id="date-col">'.apply_filters( 'ctdl_heading_date_added', esc_html__( 'Date Added', 'cleverness-to-do-list' ) ).'</th>';
		if ( $completed == 1 && $atts['completed_date'] == 1 ) $this->list .= '<th id="completed-col">'.apply_filters( 'ctdl_heading_completed', esc_html__( 'Completed', 'cleverness-to-do-list' ) ).'</th>';
		$this->list .= do_action( 'ctdl_table_headings' );
		if ( $atts['editlink'] == 1 && current_user_can( CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == 0 )
			$this->list .= '<th id="action-col" class="{sorter: false} no-sort">'.apply_filters( 'ctdl_heading_action', esc_html__( 'Action', 'cleverness-to-do-list' ) ).'</th>';
		$this->list .= '</tr></thead>';

		return 1;
	}

	/**
	 * Creates the HTML for the form used to edit a to-do item
	 * @param $todo_item
	 * @return string Form HTML
	 */
	public function create_edit_todo_form( $todo_item ) {
		$atts = shortcode_atts( array(
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'categories' => 0,
			'addedby'    => 0,
			'date'       => 0,
			'editlink'   => 1,
			'category'   => 0,
			'planner'    => 0
		), $this->atts, 'todoadmin' );

		$id = $todo_item->ID;
		list( $priority_meta, $assign_meta, $deadline_meta, $completed_meta, $progress_meta, $planner_meta ) = CTDL_Lib::get_todo_meta( $id );
		$url = strtok( $this->url, "?" );
		$this->form = '';

		$this->form .= '<form name="edittodo" id="edittodo" action="'.$url.'" method="post"><table class="todo-form form-table">';
		if ( $atts['priority'] == 1 ) $this->create_priority_field( $priority_meta );
		if ( $atts['deadline'] == 1 ) $this->create_deadline_field( $deadline_meta );
		if ( $atts['categories'] == 1 || $atts['category'] != 0 ) $this->create_category_field( get_the_terms( $id, 'todocategories' ) );
		if ( CTDL_PP && $atts['planner'] == 1 ) $this->create_planner_field( $planner_meta );
		if ( $atts['assigned'] == 1 ) $this->create_assign_field( $assign_meta );
		if ( $atts['progress'] == 1 ) $this->create_progress_field( $progress_meta );
		$this->form .= do_action( 'ctdl_edit_form_action' );
		$this->form = apply_filters( 'ctdl_edit_form', $this->form );
		$this->create_todo_text_field( $todo_item->post_content );
		$this->form .= '</table>'.wp_nonce_field( 'todoupdate', 'todoupdate', true, false ).'<input type="hidden" name="action" value="updatetodo" />
        	    <p class="submit"><input type="submit" name="submit" class="button-primary" value="'.apply_filters( 'ctdl_edit_text', esc_attr__( 'Edit To-Do Item', 'cleverness-to-do-list' ) ).'" /></p>
				<input type="hidden" name="id" value="'. absint( $id ).'" />';
		$this->form .= '</form>';

		return $this->form;
	}

	/**
	 * Creates the HTML form to add a new to-do item
	 * @return string Form HTML
	 */
	protected function create_new_todo_form() {
		if ( current_user_can( CTDL_Loader::$settings['add_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {

			$atts = shortcode_atts( array(
				'priority'   => 0,
				'assigned'   => 0,
				'deadline'   => 0,
				'progress'   => 0,
				'categories' => 0,
				'addedby'    => 0,
				'date'       => 0,
				'editlink'   => 1,
				'category'   => 0,
				'planner'    => 0
			), $this->atts, 'todoadmin' );

			$this->form = '<h3>'.apply_filters( 'ctdl_add_heading', esc_html__( 'Add New To-Do Item', 'cleverness-to-do-list' ) ).'</h3>';

			$this->form .= '<form name="addtodo" id="addtodo">
	  		    <table class="todo-form form-table">';
			if ( $atts['priority'] == 1 ) $this->create_priority_field();
			if ( $atts['deadline'] == 1 ) $this->create_deadline_field();
			if ( $atts['categories'] == 1 && $atts['category'] == 0 ) {
				$this->create_category_field();
			} elseif ( $atts['category'] != 0 ) {
				$category = array( get_term_by( 'id', $atts['category'], 'todocategories' ) );
				$this->create_category_field( $category );
			}
			if ( CTDL_PP && $atts['planner'] ) $this->create_planner_field();
			if ( $atts['assigned'] == 1 ) $this->create_assign_field();
			if ( $atts['progress'] == 1 ) $this->create_progress_field();
			$this->form .= do_action( 'ctdl_add_form_action' );
			$this->form = apply_filters( 'ctdl_add_form', $this->form );
			$this->create_todo_text_field();
			$this->form .= '</table>' . wp_nonce_field( 'todoadd', 'todoadd', true, false ) . '
        	    <input id="add-todo" type="submit" name="submit" class="button-primary" value="'.apply_filters( 'ctdl_add_text', esc_attr__( 'Add To-Do Item', 'cleverness-to-do-list' ) ).'" />
        	    <div id="ctdl-message"></div>';
			$this->form .= '</form>';

			return $this->form;
		} else {
			return '';
		}
	}

}

/**
 * Frontend class for to-do list checklist
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Frontend_Checklist extends ClevernessToDoList {
	protected $atts;
	protected $cat_id;

	public function __construct() {
		add_shortcode( 'todochecklist', array( $this, 'display_checklist' ) );
		parent::__construct();
		add_action( 'wp_enqueue_scripts', array( 'CTDL_Loader', 'frontend_checklist_register_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( 'CTDL_Loader', 'frontend_css' ) );
		}

	/**
	 * Display the to-do checklist
	 * @param $atts shortcode attributes
	 * @return string To-Do List
	 */
	public function display_checklist( $atts ) {
		$this->atts = $atts;

		CTDL_Loader::frontend_checklist_enqueue_scripts();

		if ( is_user_logged_in() ) {
			$this->display();
		} else {
			$this->list .= esc_html__('You must be logged in to view', 'cleverness-to-do-list');
			}

		return $this->list;
		}

	/**
	 * Display the to-do list with checkboxes
	 */
	public function display( $complete = 0 ) {
		$atts = shortcode_atts( array(
			'title'      => '',
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'category'   => 0,
			'addedby'    => 0,
			'date'       => 0,
			'todoid'     => '',
			'editlink'   => 0,
			'completed'  => 0
		), $this->atts, 'todochecklist' );
		global $current_user;
		get_currentuserinfo();
		$this->add_script = true;
		$layout = 'list';
		$this->list = '';

		list( $this->url, $this->action ) = CTDL_Lib::set_variables();

		$class = 'todo-checklist';
		$class = ( $atts['completed'] == 1 ? $class.' completed-checklist' : $class.' uncompleted-checklist' );
		$this->list .= '<div class="'.$class.'">';

		/** @var $title string */
		if ( $atts['title'] != '') {
			$this->list .= '<h2>'.esc_html( $atts['title'] ).'</h2>';
			}

		// get to-do items
		if ( $atts['todoid'] != '' ) {

			$post = CTDL_Lib::get_todo( $atts['todoid'] );
			if ( $post ) {
				$id = $post->ID;
				list( $the_priority, $assign_meta, $deadline_meta, $completed_meta, $progress_meta ) = CTDL_Lib::get_todo_meta( $id );

				if ( CTDL_Loader::$settings['list_view'] == 2 ) {
					$completed = ( $atts['todoid'] != '' && get_post_meta( $id, '_user_'.$current_user->ID.'_status', true ) == 1 ? 1 : 0 );
				} else {
					$completed = ( $atts['todoid'] != '' && get_post_meta( $id, '_status', true ) == 1 ? 1 : 0 );
				}

				$this->show_checkbox( $id, $completed, $layout, ' single' );
				$this->show_todo_text( $post->post_content, 'list' );
				if ( $atts['priority'] == 1 ) $this->show_priority( $the_priority );
				if ( $atts['progress'] == 1 ) $this->show_progress( $progress_meta, 'list', $completed );
				if ( $atts['assigned'] == 1 ) $this->show_assigned( $assign_meta );
				if ( $atts['addedby'] == 1 ) $this->show_addedby( get_the_author() );
				if ( $atts['deadline'] == 1 ) $this->show_deadline( $deadline_meta );
				if ( $atts['date'] == 1 ) $this->show_date_added( get_the_date(), get_the_date( CTDL_Loader::$settings['date_format'] ) );
				$this->list .= do_action( 'ctdl_list_items' );
				if ( $atts['editlink'] == 1 ) $this->show_edit_link( $id );
			} else {
				/* if there are no to-do items, display this message */
				$this->list .= '<p>'.apply_filters( 'ctdl_no_items', esc_html__( 'No items to do.', 'cleverness-to-do-list' ) ).'</p>';
			}

		} else {

			$this->loop_through_todos( $atts['completed'], $atts['category'] );

		}

		$this->list .= '</div>';

		wp_reset_postdata();
	}

	/**
	 * Generate the To-Do List
	 * @param $todo_items
	 * @param int $completed
	 * @param int $visible
	 * @return array $posts_to_exclude
	 */
	protected function show_todo_list_items( $todo_items, $completed = 0, $visible = 0 ) {
		$atts = shortcode_atts( array(
			'title'      => '',
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'category'   => 0,
			'addedby'    => 0,
			'date'       => 0,
			'todoid'     => '',
			'editlink'   => 0,
			'completed'  => 0
		), $this->atts, 'todochecklist' );

		while ( $todo_items->have_posts() ) : $todo_items->the_post();
			$id = get_the_ID();
			$posts_to_exclude[] = $id;

			if ( $visible == 0 ) {
				list( $the_priority, $assign_meta, $deadline_meta, $completed_meta, $progress_meta ) = CTDL_Lib::get_todo_meta( $id );

				$priority_class = CTDL_Lib::set_priority_class( $the_priority );

				$this->show_category_headings ( get_the_terms( $id, 'todocategories' ), $this->cat_id );

				$this->list .= '<div id="todo-'.esc_attr( $id ).'"'.$priority_class.'>';
				$this->show_checkbox( $id, $completed, 'list' );
				$this->list .= '<div class="todoitem">';
				$this->show_todo_text( get_the_content(), 'list' );
				if ( $atts['priority'] == 1 ) $this->show_priority( $the_priority );
				if ( $atts['progress'] == 1 ) $this->show_progress( $progress_meta, 'list', $completed );
				if ( $atts['assigned'] == 1 ) $this->show_assigned( $assign_meta );
				if ( $atts['addedby'] == 1 ) $this->show_addedby( get_the_author() );
				if ( $atts['deadline'] == 1 ) $this->show_deadline( $deadline_meta );
				if ( $atts['date'] == 1 ) $this->show_date_added( get_the_date( 'Ymd' ), get_the_date( CTDL_Loader::$settings['date_format'] ) );
				$this->list .= do_action( 'ctdl_list_items' );
				if ( $atts['editlink'] == 1 ) $this->show_edit_link( $id );
				$this->list .= '</div></div>';
			}
		endwhile;

		wp_reset_postdata();

		return $posts_to_exclude;

	}

	/**
	 * Show category heading only if it's the first item from that category
	 * @param $categories
	 */
	protected function show_category_headings( $categories ) {
		if ( CTDL_Loader::$settings['categories'] == 1 && $categories != false ) {
			foreach ( $categories as $category ) {
				$cat = CTDL_Categories::get_category_name( $category->term_id );
				if ( $this->cat_id != $category->term_id  && $cat != '' ) {
					$this->list .= '<h3 class="todo-category-heading">'.esc_html( $cat ).'</h3>';
					$this->cat_id = $category->term_id;
				}
			}
		}
	}

	public function show_edit_link( $id ) {
		$edit = '';
		$url  = admin_url( 'admin.php?page=cleverness-to-do-list&amp;action=edit-todo&amp;id='.$id );
		if ( current_user_can( CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {
			$edit = '<a href="'.$url.'" class="edit-todo">'.apply_filters( 'ctdl_edit', esc_attr__( 'Edit', 'cleverness-to-do-list' ) ).'</a>';
		}
		if ( current_user_can( CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {
			$this->list .= ' <small class="edit-todo">['.$edit.']</small>';
		}
	}

	/**
	 * Show who the to-do item was assigned to, if defined
	 * @param $assign
	 * @param string $type
	 */
	public function show_assigned( $assign, $type = 'list' ) {
		if ( ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 0 && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) ||
		( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 1 ) && CTDL_Loader::$settings['assign'] == 0 ) {
			$assigned = '';
			if ( $assign != '-1' && $assign != '' && $assign != '0' ) {
				if ( is_array( $assign ) ) {
					$assign_users = '';
					foreach ( $assign as $value ) {
						if ( $value != '-1' && $value != '' && $value != 0 ) {
							$user = get_userdata( $value );
							$assign_users .= $user->display_name . ', ';
						}
					}
					$assigned .= substr( $assign_users, 0, -2 );
				} else {
					$assign_user = get_userdata( $assign );
					$assigned = $assign_user->display_name;
				}
				if ( $assigned != '' ) {
					$this->list .= ' <small class="todo-assigned">[' . apply_filters( 'ctdl_assigned', esc_html__( 'Assigned To', 'cleverness-to-do-list' ) ) . ': ' . esc_attr( $assigned ) . ']</small>';
				}
			}
		}
   	}

	/**
	 * Show who added the to-do item
	 * @param $author
	 * @param string $type
	 */
	public function show_addedby( $author, $type = 'list' ) {
		if ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['todo_author'] == 0 ) {
			if ( $author != '0' ) {
				$this->list .= ' <small class="todo-addedby">- '.apply_filters( 'ctdl_added_by', esc_html__( 'Added By', 'cleverness-to-do-list' ) ).': '.esc_attr( $author ).'</small>';
			}
		}
	}

	/**
	 * Show the deadline for the to-do item
	 * @param string $deadline
	 * @param string $type
	 * @return void
	 */
	public function show_deadline( $deadline, $type = 'list' ) {
		if ( CTDL_Loader::$settings['show_deadline'] == 1 && $deadline != '' )
			$this->list .= ' <small class="todo-deadline">['.apply_filters( 'ctdl_deadline', esc_html__( 'Deadline', 'cleverness-to-do-list' ) ).': '.
			date( CTDL_Loader::$settings['date_format'], $deadline ).']</small>';
	}

	/**
	 * Show the Date the To-Do Item was Added
	 * @param $date
	 * @param $formatted_date
	 * @param string $type
	 * @return void
	 * @since 3.1
	 */
	public function show_date_added( $date, $formatted_date, $type = 'list' ) {
		$date = ( isset( $date ) ? $date : '' );
		$this->list .= ' <small class="todo-date">['.apply_filters( 'ctdl_date_added', esc_html__( 'Date Added', 'cleverness-to-do-list' ) ).': '.
			( $date != '' ? sprintf( '%s', esc_attr( $formatted_date ) ) : '' ).']</small>';
	}

	/**
	 * Show the progress of the to-do item
	 * @param int $progress
	 * @param string $type
	 * @param int $completed
	 * @return void
	 */
	public function show_progress( $progress, $type = 'list', $completed = 0 ) {
		$progress = ( $completed == 1 ? '100' : $progress );
		if ( CTDL_Loader::$settings['show_progress'] == '1' && $progress != '' ) {
			$this->list .= ' <small class="todo-progress">'.apply_filters( 'ctdl_frontend_checklist_progress', '['.esc_attr( $progress ).'%]' ).'</small>';
		}
	}

}

/**
 * Frontend class for to-do list viewing
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Frontend_List extends ClevernessToDoList {
	protected $atts;

	public function __construct() {
		add_shortcode( 'todolist', array( $this, 'display_list' ) );
		parent::__construct();
	}

	/**
	 * Display the To-Do List
	 * @param $atts shortcode attributes
	 * @return string To-Do list
	 */
	public function display_list( $atts ) {
		$this->atts = $atts;

		$this->display();

		return $this->list;
	}

	/**
	 * Display the To-Do List
	 */
	public function display( $complete = 0 ) {
		$atts = shortcode_atts( array(
			'title'           => '',
			'type'            => 'list',
			'priorities'      => 1,
			'assigned'        => 1,
			'deadline'        => 1,
			'progress'        => 1,
			'addedby'         => 1,
			'date'            => 0,
			'completed'       => '',
			'completed_title' => '',
			'list_type'       => 'ol',
			'category'        => '0'
		), $this->atts, 'todolist' );

		$this->list = '';

		$category = ( $atts['category'] == 'all' ? $category = '0' : $atts['category'] );
		list( $this->url, $this->action ) = CTDL_Lib::set_variables();

		if ( $atts['completed'] != 'only' ) {

			if ( $atts['type'] == 'table' ) {

				$this->list .= '<table id="todo-list" class="todo-table todolist">';

				if ( $atts['title'] != '' ) {
					$this->list .= '<caption>' . $atts['title'] . '</caption>';
				}
				$this->show_table_headings();
				$this->loop_through_todos( 0, $category );
				$this->list .= '</table>';

			} elseif ( $atts['type'] == 'list' ) {

				if ( $atts['title'] != '' ) {
					$this->list .= '<h3 class="todo-title">' . esc_html( $atts['title'] ) . '</h3>';
				}
				if ( CTDL_Loader::$settings['categories'] == 0 || CTDL_Loader::$settings['sort_order'] != 'cat_id' ) {
					$this->list .= '<' . $atts['list_type'] . ' class="todolist">';
				}
				$this->loop_through_todos( 0, $category );
				$this->list .= '</' . $atts['list_type'] . '>';

			}

		}

		if ( $atts['completed'] == 'show' || $atts['completed'] == 1 || $atts['completed'] == 'only' ) {

			wp_reset_postdata();
			$this->cat_id = '';

			if ( $atts['type'] == 'table' ) {

				$this->list .= '<table id="todo-list-completed" class="todo-table todolist">';
				if ( $atts['completed_title'] != '' ) $this->list .= '<caption>'.$atts['completed_title'].'</caption>';
				$this->show_table_headings( 1 );
				$this->loop_through_todos( 1, $category );
				$this->list .= '</table>';

			} elseif ( $atts['type'] == 'list' ) {

				/** @var $completed_title string */
				if ( $atts['completed_title'] != '') {
					$this->list .= '<h3 class="todo-title">'.esc_html( $atts['completed_title'] ).'</h3>';
				}
				$this->list .= '<div class="refresh">';
				if ( CTDL_Loader::$settings['categories'] == 0 || CTDL_Loader::$settings['sort_order'] != 'cat_id' ) $this->list .= '<'.$atts['list_type'].' class="todolist todolist-completed">';
				$this->loop_through_todos( 1, $category );
				$this->list .= '</'.$atts['list_type'].'></div>';

			}

		}

		wp_reset_postdata();
	}

	/**
	 * Generate the To-Do List
	 * @param $todo_items
	 * @param int $completed
	 * @param int $visible
	 * @return array $posts_to_exclude
	 */
	protected function show_todo_list_items( $todo_items, $completed = 0, $visible = 0 ) {
		$atts = shortcode_atts( array(
			'title'             => '',
			'type'              => 'list',
			'priorities'        => 1,
			'assigned'          => 1,
			'deadline'          => 1,
			'progress'          => 1,
			'addedby'           => 1,
			'date'              => 0,
			'list_type'         => 'ol',
			'category'          => '0',
			'completed_date'    => 0
		), $this->atts, 'todolist' );

		while ( $todo_items->have_posts() ) : $todo_items->the_post();
			$id = get_the_ID();
			$posts_to_exclude[] = $id;

			if ( $visible == 0 ) {
				list( $the_priority, $assign_meta, $deadline_meta, $completed_meta, $progress_meta ) = CTDL_Lib::get_todo_meta( $id );

				$priority_class = CTDL_Lib::set_priority_class( $the_priority );

				if ( $atts['type'] == 'list' && CTDL_Loader::$settings['categories'] == 1 && CTDL_Loader::$settings['sort_order'] == 'cat_id' ) {
					$this->show_category_headings( get_the_terms( $id, 'todocategories' ), $atts['list_type'], $completed );
				}

				if ( $atts['type'] == 'table' ) {
					$this->list .= '<tr id="todo-'.esc_attr( $id ).'"'.$priority_class.'>';
				} else {
					$this->list .= '<li'.$priority_class.'>';
				}

				$this->show_todo_text( get_the_content(), $atts['type'] );
				if ( ( $atts['priorities'] == 'show' || $atts['priorities'] == 1 ) && $atts['type'] == 'table' ) $this->show_priority( $the_priority );
				if ( ( $atts['progress'] == 'show' || $atts['progress'] == 1 ) && CTDL_Loader::$settings['show_progress'] == 1 ) $this->show_progress( $progress_meta, $atts['type'], $completed );
				if ( $atts['category'] == 0  && $atts['type'] == 'table' && CTDL_Loader::$settings['categories'] == 1 ) $this->show_category( get_the_terms( $id, 'todocategories' ) );
				if ( ( $atts['assigned'] == 'show' || $atts['assigned'] == 1 ) && ( ( ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 0 && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) ||
						( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 1 ) ) && CTDL_Loader::$settings['assign'] == 0 ) ) $this->show_assigned( $assign_meta, $atts['type'] );
				if ( ( $atts['addedby'] == 'show' || $atts['addedby'] == 1 ) && CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['todo_author'] == 0 ) $this->show_addedby( get_the_author(), $atts['type'] );
				if ( ( $atts['deadline'] == 'show' || $atts['deadline'] == 1 ) && CTDL_Loader::$settings['show_deadline'] == 1 ) $this->show_deadline( $deadline_meta, $atts['type'] );
				if ( $atts['date'] == 1 && CTDL_Loader::$settings['show_date_added'] == 1) $this->show_date_added( get_the_date( 'Ymd' ), get_the_date( CTDL_Loader::$settings['date_format'] ), $atts['type'] );
				if ( $atts['completed_date'] == 1 && CTDL_Loader::$settings['show_completed_date'] && $completed == 1 ) $this->show_completed( $completed_meta, $atts['type'] );
				$this->list .= do_action( 'ctdl_list_items' );

				if ( $atts['type'] == 'table' ) {
					$this->list .= '</tr>';
				} else {
					$this->list .= '</li>';
				}
			}
		endwhile;

		wp_reset_postdata();

		return $posts_to_exclude;
	}

	/**
	 * Creates the HTML for the To-Do List Table Headings
	 * @param $completed
	 */
	protected function show_table_headings( $completed = 0 ) {
		$atts = shortcode_atts( array(
			'priorities' => 1,
			'assigned' => 1,
			'deadline' => 1,
			'progress' => 1,
			'addedby'  => 1,
			'date'     => 0,
			'category' => 0,
			'completed_date' => 0
		), $this->atts, 'todolist' );

		$this->list .= '<thead><tr>';
		$this->list .= '<th class="item-col">'.apply_filters( 'ctdl_heading_item', esc_html__( 'Item', 'cleverness-to-do-list' ) ).'</th>';
		if ( $atts['priorities'] == 'show' || $atts['priorities'] == 1 ) $this->list .= '<th class="priority-col">'.apply_filters( 'ctdl_heading_priority', esc_html__( 'Priority', 'cleverness-to-do-list' ) ).'</th>';
		if ( ( $atts['progress'] == 'show' || $atts['progress'] == 1 ) && CTDL_Loader::$settings['show_progress'] == 1 ) $this->list .= '<th class="progress-col">'.apply_filters( 'ctdl_heading_progress', esc_html__( 'Progress', 'cleverness-to-do-list' ) ).'</th>';
		if ( CTDL_Loader::$settings['categories'] == 1 && $atts['category'] == '0' ) $this->list .= '<th class="category-col">'.apply_filters( 'ctdl_heading_category', esc_html__( 'Category', 'cleverness-to-do-list' ) ).'</th>';
		if ( ( $atts['assigned'] == 'show' || $atts['assigned'] == 1 ) && ( CTDL_Loader::$settings['assign'] == 0  && ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 0
				&& ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) || ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 1 )
				&& CTDL_Loader::$settings['assign'] == 0 ) ) $this->list .= '<th class="assigned-col">'.apply_filters( 'ctdl_heading_assigned', esc_html__( 'Assigned To', 'cleverness-to-do-list' ) ).'</th>';
		if ( ( $atts['addedby'] == 'show' || $atts['addedby'] == 1 ) && CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['todo_author'] == 0 )
			$this->list .= '<th class="added-col">'.apply_filters( 'ctdl_heading_added_by', esc_html__( 'Added By', 'cleverness-to-do-list' ) ).'</th>';
		if ( ( $atts['deadline'] == 'show' || $atts['deadline'] == 1 ) && CTDL_Loader::$settings['show_deadline'] == 1 ) $this->list .= '<th class="deadline-col">'.apply_filters( 'ctdl_heading_deadline', esc_html__( 'Deadline', 'cleverness-to-do-list' ) ).'</th>';
		if ( $atts['date'] == 1 && CTDL_Loader::$settings['show_date_added'] == 1 ) $this->list .= '<th class="date-col">'.apply_filters( 'ctdl_heading_date_added', esc_html__( 'Date Added', 'cleverness-to-do-list' ) ).'</th>';
		if ( $completed == 1 && $atts['completed_date'] == 1 ) $this->list .= '<th class="completed-col">'.apply_filters( 'ctdl_heading_completed', esc_html__( 'Completed', 'cleverness-to-do-list' ) ).'</th>';
		$this->list .= do_action( 'ctdl_table_headings' );
		$this->list .= '</tr></thead>';
	}

	/**
	 * Show category heading only if it's the first item from that category
	 * @param $categories
	 * @param $list_type
	 * @param int $completed
	 */
	protected function show_category_headings( $categories, $list_type, $completed = 0 ) {
		$class = ( $completed == 0 ? 'todolist' : 'todolist todolist-completed' );
		static $i = 0;
		if ( CTDL_Loader::$settings['categories'] == 1 && $categories != false ) {
			foreach ( $categories as $category ) {
				$cat = CTDL_Categories::get_category_name( $category->term_id );
				if ( $this->cat_id != $category->term_id  && $cat != '' ) {
					if ( $this->cat_id != '' ) $this->list .= '</'.$list_type.'>';
					$this->list .= '<h4 class="todo-category-heading">'.esc_html( $cat ).'</h4><'.$list_type.' class="'.$class.'">';
					$this->cat_id = $category->term_id;
				}
			}
		} elseif ( $categories == false && $i == 0 ) {
			if ( $this->cat_id != '' ) $this->list .= '</'.$list_type.'>';
			$this->list .= '<'.$list_type.' class="'.$class.'">';
			$i++;
		} elseif ( $categories == false && $completed == 1 && $i == 1 ) {
			if ( $this->cat_id != '' ) $this->list .= '</'.$list_type.'>';
			$this->list .= '<'.$list_type.' class="'.$class.'">';
			$i++;
		}
	}

	/**
	 * Show who the to-do item was assigned to, if defined
	 * @param $assign
	 * @param $layout
	 */
	public function show_assigned( $assign, $layout = 'list' ) {
		if ( ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 0 && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) ||
				( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 1 ) && CTDL_Loader::$settings['assign'] == 0 ) {
			$assigned = '';
			if ( $assign != '-1' && $assign != '' && $assign != '0' ) {
				if ( is_array( $assign ) ) {
					$assign_users = '';
					foreach ( $assign as $value ) {
						if ( $value != '-1' && $value != '' && $value != 0 ) {
							$user = get_userdata( $value );
							$assign_users .= $user->display_name.', ';
						}
					}
					$assigned .= substr( $assign_users, 0, -2 );
				} else {
					$assign_user = get_userdata( $assign );
					$assigned = $assign_user->display_name;
				}
				if ( $layout == 'table' ) {
					$this->list .= '<td class="todo-assigned">'.esc_attr( $assigned ).'</td>';
				} else {
					if ( $assigned != '' ) {
						$this->list .= ' - ' . apply_filters( 'ctdl_assigned', esc_html__( 'Assigned To', 'cleverness-to-do-list' ) ) . ': ' . esc_attr( $assigned );
					}
				}
			} else {
				if ( $layout == 'table' ) $this->list .= '<td class="todo-assigned"></td>';
			}
		}
	}

	/**
	 * Show who added the to-do item
	 * @param $author
	 * @param $layout
	 */
	public function show_addedby( $author, $layout = 'list' ) {
		if ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['todo_author'] == 0 ) {
			if ( $author != '0' ) {
				if ( $layout == 'table' ) {
					$this->list .= '<td class="todo-addedby">'.esc_attr( $author ).'</td>';
				} else {
					$this->list .= ' - '.apply_filters( 'ctdl_added_by', esc_html__( 'Added By', 'cleverness-to-do-list' ) ).': '.esc_attr( $author );
				}
			} else {
				if ( $layout == 'table' ) $this->list .= '<td class="todo-addedby"></td>';
			}
		}
	}

	/**
	 * Show the deadline for the to-do item
	 * @param $deadline
	 * @param $layout
	 */
	public function show_deadline( $deadline, $layout = 'list' ) {
		if ( CTDL_Loader::$settings['show_deadline'] == 1 && $deadline != '' ) {
			if ( $layout == 'table' ) {
				$this->list .= ( $deadline != '' ? sprintf( '<td class="todo-deadline">%s</td>', date( CTDL_Loader::$settings['date_format'], $deadline ) ) : '<td class="todo-deadline"></td>' );
			} else {
				$this->list .= ' - '.apply_filters( 'ctdl_deadline', esc_html__( 'Deadline', 'cleverness-to-do-list' ) ).': '.date( CTDL_Loader::$settings['date_format'], $deadline );
			}
		} elseif ( $layout == 'table' ) {
				$this->list .= '<td class="todo-deadline"></td>';
			}
	}

	/**
	 * Show the progress of the to-do item
	 * @param $progress
	 * @param $layout
	 * @param $completed
	 */
	public function show_progress( $progress, $layout = 'list', $completed = 0 ) {
		$progress = ( $completed == 1 ? '100' : $progress );
		if ( CTDL_Loader::$settings['show_progress'] == 1 && $progress != '' ) {
			if ( $layout == 'table' ) {
				$this->list .= ( $progress != '' ? sprintf( '<td class="todo-progress">%d%%</td>', esc_attr( $progress ) ) : '<td class="todo-progress"></td>' );
			} else {
				$this->list .= apply_filters( 'ctdl_frontend_progress', ' - '.esc_attr( $progress ).'%' );
			}
		} elseif ( $layout == 'table' ) {
			$this->list .= '<td class="todo-progress"></td>';
		}
	}

	/**
	 * Show the Date the To-Do Item was Added
	 * @param $the_date
	 * @param $formatted_date
	 * @param string $layout
	 * @internal param $date
	 * @since 3.1
	 */
	public function show_date_added( $the_date, $formatted_date, $layout = 'list' ) {
		if ( CTDL_Loader::$settings['show_date_added'] == 1 ) {
			$the_date = ( isset( $the_date ) ? esc_attr( $the_date ) : '' );
			if ( $layout == 'table' ) {
				$this->list .= ( $the_date != '' ? sprintf( '<td class="todo-date">%s</td>', esc_attr( $formatted_date ) ) : '<td class="todo-date"></td>' );
			} else {
				$this->list .= ' - '.apply_filters( 'ctdl_date_added', esc_html__( 'Date Added', 'cleverness-to-do-list' ) ).': '.( $the_date != '' ?
					sprintf( '%s', esc_attr( $formatted_date ) ) : '' );
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
				$this->list .= ' - '.apply_filters( 'ctdl_completed', esc_html__( 'Completed', 'cleverness-to-do-list' ) ).': '.esc_attr( $date );
			}
		}
	}

}