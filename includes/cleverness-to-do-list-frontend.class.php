<?php
/**
 * Cleverness To-Do List Plugin Frontend Classes
 *
 * Allows administration and viewing of to-do items on front-end
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.0
 */

/**
 * Frontend class for to-do list administration
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Frontend_Admin extends ClevernessToDoList {
	protected $atts;
	public $add_script;

	public function __construct() {
		add_shortcode( 'todoadmin', array( &$this, 'display_admin' ) ) ;
		parent::__construct();
		add_action( 'wp_footer', 'CTDL_Loader::frontend_checklist_init' );
		add_action( 'wp_footer', 'CTDL_Loader::frontend_checklist_add_js' );
		}

	/**
	 * Displays the to-do list administration
	 * @param $atts shortcode attributes
	 * @return string To-Do List
	 */
	public function display_admin( $atts ) {
		extract( shortcode_atts( array(
			'title' => '',
		), $atts ) );
		$this->atts = $atts;
		$this->add_script = true;

		/** @var $title string */
		if ( $title != '' ) {
			$this->list .= '<h3>'.esc_attr( $title ).'</h3>';
			}

		if ( is_user_logged_in() ) {
			$this->display();
		} else {
			$this->list .= __( 'You must be logged in to view', 'cleverness-to-do-list' );
			}

		return $this->list;
	}

	/**
	 * Generate the To-Do List
	 * @param $todo_items
	 * @param $priorities
	 * @param $url
	 * @param int $completed
	 * @param $visible
	 * @return array $posts_to_exclude
	 */
	public function show_todo_list_items( $todo_items, $priorities, $url, $completed = 0, $visible = 0 ) {
		extract( shortcode_atts( array(
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'categories' => 0,
			'addedby'    => 0,
			'date'       => 0,
			'editlink'   => 1
		), $this->atts ) );

		while ( $todo_items->have_posts() ) : $todo_items->the_post();
			$id = get_the_ID();
			$posts_to_exclude[] = $id;

			if ( $visible == 0 ) {
				list( $the_priority, $assign_meta, $deadline_meta, $completed_meta, $progress_meta ) = CTDL_Lib::get_todo_meta( $id );

				$priority_class = CTDL_Lib::set_priority_class( $the_priority );

				$this->list .= '<tr id="todo-'.esc_attr( $id ).'"' . $priority_class . '>';
				$this->show_checkbox( $id, $completed );
				$this->show_todo_text( get_the_content() );

				/** @var $priority int */
				if ( $priority == 1 ) $this->show_priority( $the_priority, $priorities );
				/** @var $progress int */
				if ( $progress == 1 ) $this->show_progress( $progress_meta );
				/** @var $categories int */
				if ( $categories == 1 ) $this->show_category( get_the_terms( $id, 'todocategories' ) );
				/** @var $assigned int */
				if ( $assigned == 1 ) $this->show_assigned( $assign_meta );
				/** @var $addedby int */
				if ( $addedby == 1 ) $this->show_addedby( get_the_author() );
				/** @var $deadline int */
				if ( $deadline == 1 ) $this->show_deadline( $deadline_meta );
				/** @var $date int */
				if ( $date == 1 ) $this->show_date_added( get_the_date() );
				/** @var $editlink int */
				if ( $editlink == 1 ) $this->show_edit_link( $id, $url );
				$this->list .= '</tr>';
			}
		endwhile;

		return $posts_to_exclude;
	}

	/**
	 * Creates the HTML for the To-Do List Table Headings
	 * @param $completed
	 */
	public function show_table_headings( $completed = 0 ) {
		extract( shortcode_atts( array(
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'categories' => 0,
			'addedby'    => 0,
			'date'       => 0,
			'editlink'   => 1
		), $this->atts ) );

		$this->list .= '<thead><tr>';
		if ( !is_admin() ) $this->list .= '<th></th>';
		$this->list .= '<th>' . __( 'Item', 'cleverness-to-do-list' ) . '</th>';
		/** @var $priority int */
		if ( $priority == 1 ) $this->list .= '<th>' . __( 'Priority', 'cleverness-to-do-list' ) . '</th>';
		/** @var $progress int */
		if ( $progress == 1 && CTDL_Loader::$settings['show_progress'] == 1 ) $this->list .= '<th>' . __( 'Progress', 'cleverness-to-do-list' ) . '</th>';
		/** @var $categories int */
		if ( $categories == 1 && CTDL_Loader::$settings['categories'] == 1 ) $this->list .= '<th>' . __( 'Category', 'cleverness-to-do-list' ) . '</th>';
		/** @var $assigned int */
		if ( $assigned == 1 && ( CTDL_Loader::$settings['assign'] == 0 && ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 0
				&& ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) || ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 1 )
				&& CTDL_Loader::$settings['assign'] == 0 ) ) $this->list .= '<th>' . __( 'Assigned To', 'cleverness-to-do-list' ) . '</th>';
		/** @var $addedby int */
		if ( $addedby == 1 && CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['todo_author'] == 0 ) $this->list .= '<th>' . __( 'Added By', 'cleverness-to-do-list' ) . '</th>';
		/** @var $deadline int */
		if ( $deadline == 1  && CTDL_Loader::$settings['show_deadline'] == 1 ) $this->list .= '<th>' . __( 'Deadline', 'cleverness-to-do-list' ) . '</th>';
		/** @var $date int */
		if ( $date == 1 && CTDL_Loader::$settings['show_date_added'] == 1 ) $this->list .= '<th>'.__( 'Date Added', 'cleverness-to-do-list' ).'</th>';
		if ( $completed == 1 && CTDL_Loader::$settings['show_completed_date'] == 1 ) $this->list .= '<th>' . __( 'Completed', 'cleverness-to-do-list' ) . '</th>';
		/** @var $editlink int */
		if ( $editlink == 1 && current_user_can( CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == 0 ) $this->list .= '<th>' . __( 'Action', 'cleverness-to-do-list' ) . '</th>';
		$this->list .= '</tr></thead>';
	}

	/**
	 * Creates the HTML for the form used to edit a to-do item
	 * @param $todo_item
	 * @param string $url The URL the form should be submitted to
	 * @return string Form HTML
	 */
	public function create_edit_todo_form( $todo_item, $url ) {
		extract( shortcode_atts( array(
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'categories' => 0,
			'addedby'    => 0,
			'date'       => 0,
			'editlink'   => 1
		), $this->atts ) );

		$id = $todo_item->ID;
		$url = strtok( $url, "?" );
		$this->form = '';

		$this->form .= '<form name="edittodo" id="edittodo" action="'.$url.'" method="post"><table class="todo-form form-table">';
		/** @var $priority int */
		if ( $priority == 1 ) $this->create_priority_field( get_post_meta( $id, '_priority', true ) );
		/** @var $assigned int */
		if ( $assigned == 1 ) $this->create_assign_field( get_post_meta( $id, '_assign', true ) );
		/** @var $deadline int */
		if ( $deadline == 1 ) $this->create_deadline_field( get_post_meta( $id, '_deadline', true ) );
		/** @var $progress int */
		if ( $progress == 1 ) $this->create_progress_field( get_post_meta( $id, '_progress', true ) );
		/** @var $categories int */
		if ( $categories == 1 ) $this->create_category_field( get_the_terms( $id, 'todocategories' ) );
		$this->create_todo_text_field( $todo_item->post_content );
		$this->form .= '</table>'.wp_nonce_field( 'todoupdate', 'todoupdate', true, false ).'<input type="hidden" name="action" value="updatetodo" />
        	    <p class="submit"><input type="submit" name="submit" class="button-primary" value="'.__( 'Edit To-Do Item', 'cleverness-to-do-list' ).'" /></p>
				<input type="hidden" name="id" value="'. absint( $id ).'" />';
		$this->form .= '</form>';

		return $this->form;
	}

	/**
	 * Creates the HTML form to add a new to-do item
	 * @param string $url
	 * @return string Form HTML
	 */
	protected function create_new_todo_form( $url ) {
		if ( current_user_can( CTDL_Loader::$settings['add_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {

			extract( shortcode_atts( array(
				'priority'   => 0,
				'assigned'   => 0,
				'deadline'   => 0,
				'progress'   => 0,
				'categories' => 0,
				'addedby'    => 0,
				'date'       => 0,
				'editlink'   => 1
			), $this->atts ) );

			$this->form = '<h3>'.__( 'Add New To-Do Item', 'cleverness-to-do-list' ).'</h3>';

			$this->form .= '<form name="addtodo" id="addtodo" action="'.$url.'" method="post">
	  		    <table class="todo-form form-table">';
			/** @var $priority int */
			if ( $priority == 1 ) $this->create_priority_field();
			/** @var $assigned int */
			if ( $assigned == 1 ) $this->create_assign_field();
			/** @var $deadline int */
			if ( $deadline == 1 ) $this->create_deadline_field();
			/** @var $progress int */
			if ( $progress == 1 ) $this->create_progress_field();
			/** @var $categories int */
			if ( $categories == 1 ) $this->create_category_field();
			$this->create_todo_text_field();
			$this->form .= '</table>'.wp_nonce_field( 'todoadd', 'todoadd', true, false ).'<input type="hidden" name="action" value="addtodo" />
        	    <p class="submit"><input type="submit" name="submit" class="button-primary" value="'.__( 'Add To-Do Item', 'cleverness-to-do-list' ).'" /></p>';
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
	public $add_script;

	public function __construct() {
		add_shortcode( 'todochecklist', array( &$this, 'display_checklist' ) );
		parent::__construct();
		add_action( 'wp_footer', 'CTDL_Loader::frontend_checklist_add_js' );
		}

	/**
	 * Display the to-do checklist
	 * @param $atts shortcode attributes
	 * @return string To-Do List
	 */
	public function display_checklist( $atts ) {
		$this->atts = $atts;

		if ( is_user_logged_in() ) {
			$this->display();
		} else {
			$this->list .= __('You must be logged in to view', 'cleverness-to-do-list');
			}

		return $this->list;
		}

	/**
	 * Display the to-do list with checkboxes
	 */
	public function display() {
		extract( shortcode_atts( array(
			'title'      => '',
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'category'   => 0,
			'addedby'    => 0,
			'date'       => 0,
			'todoid'     => ''
		), $this->atts ) );
		global $current_user;
		get_currentuserinfo();
		$this->add_script = true;
		$layout = 'list';

		list( $priorities, $user, $url, $action ) = CTDL_Lib::set_variables();

		/** @var $title string */
		if ( $title != '') {
			$this->list .= '<h3>'.esc_attr( $title ).'</h3>';
			}

		// get to-do items
		/** @var $todoid int */
		if ( $todoid != '' ) {

			$post = CTDL_Lib::get_todo( $todoid );
			if ( $post ) {
				$id = $post->ID;
				$the_priority = get_post_meta( $id, '_priority', true );
				$priority_class = '';
				if ( $the_priority == '0' ) $priority_class = ' class="todo-important"';
				if ( $the_priority == '2' ) $priority_class = ' class="todo-low"';
				$this->list .= '<p id="todo-'.esc_attr( $id ).'" class="todo-list">';
				if ( CTDL_Loader::$settings['list_view'] == 2 ) {
					$completed = ( $todoid != '' && get_post_meta( $id, '_user_'.$current_user->ID.'_status', true ) == 1 ? 1 : 0 );
				} else {
					$completed = ( $todoid != '' && get_post_meta( $id, '_status', true ) == 1 ? 1 : 0 );
				}
				$this->show_checkbox( $id, $completed, $layout, ' single' );
				$this->show_todo_text( $post->post_content, $priority_class  );
				/** @var $priority int */
				if ( $priority == 1 ) $this->show_priority( $the_priority, $priorities );
				/** @var $progress int */
				if ( $progress == 1 ) $this->show_progress( get_post_meta( $id, '_progress', true ) );
				/** @var $assigned int */
				if ( $assigned == 1 ) $this->show_assigned( get_post_meta( $id, '_assign', true ) );
				/** @var $addedby int */
				if ( $addedby == 1 ) $this->show_addedby( get_the_author() );
				/** @var $deadline int */
				if ( $deadline == 1 ) $this->show_deadline( get_post_meta( $id, '_deadline', true ) );
				/** @var $date int */
				if ( $date == 1 ) $this->show_date_added( get_the_date() );
				$this->list .= '</p>';
			} else {
				/* if there are no to-do items, display this message */
				$this->list .= '<p>'.__( 'No items to do.', 'cleverness-to-do-list' ).'</p>';
			}

		} else {

			/** @var $category mixed */
			$this->loop_through_todos( $user, $priorities, $url, 0, $category );

		}

		wp_reset_postdata();
	}

	/**
	 * Generate the To-Do List
	 * @param $todo_items
	 * @param $priorities
	 * @param $url
	 * @param int $completed
	 * @param int $visible
	 * @return array $posts_to_exclude
	 */
	protected function show_todo_list_items( $todo_items, $priorities, $url, $completed = 0, $visible = 0 ) {
		extract( shortcode_atts( array(
			'title'      => '',
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'category'   => 0,
			'addedby'    => 0,
			'date'       => 0,
			'todoid'     => ''
		), $this->atts ) );

		while ( $todo_items->have_posts() ) : $todo_items->the_post();
			$id = get_the_ID();
			$posts_to_exclude[] = $id;

			if ( $visible == 0 ) {
				list( $the_priority, $assign_meta, $deadline_meta, $completed_meta, $progress_meta ) = CTDL_Lib::get_todo_meta( $id );

				$priority_class = CTDL_Lib::set_priority_class( $the_priority );

				$this->show_category_headings ( get_the_terms( $id, 'todocategories' ), $this->cat_id );

				$this->list .= '<p id="todo-'.esc_attr( $id ).'" class="todo-list'.$priority_class.'">';
				$this->show_checkbox( $id, '', 'list' );
				$this->list .= ' ';
				$this->show_todo_text( get_the_content(), 'list' );
				/** @var $priority int */
				if ( $priority == 1 ) $this->show_priority( $the_priority, $priorities );
				/** @var $progress int */
				if ( $progress == 1 ) $this->show_progress( $progress_meta );
				/** @var $assigned int */
				if ( $assigned == 1 ) $this->show_assigned( $assign_meta );
				/** @var $addedby int */
				if ( $addedby == 1 ) $this->show_addedby( get_the_author() );
				/** @var $deadline int */
				if ( $deadline == 1 ) $this->show_deadline( $deadline_meta );
				/** @var $date int */
				if ( $date == 1 ) $this->show_date_added( get_the_date() );
				$this->list .= '</p>';
			}
		endwhile;

		return $posts_to_exclude;

	}

	/**
	 * Show category heading only if it's the first item from that category
	 * @param $categories
	 */
	protected function show_category_headings( $categories ) {
		if ( CTDL_Loader::$settings['categories'] == '1' && $categories != false ) {
			foreach ( $categories as $category ) {
				$cat = CTDL_Categories::get_category_name( $category->term_id );
				if ( $this->cat_id != $category->term_id  && $cat != '' ) {
					$this->list .= '<h4>'.esc_attr( $cat ).'</h4>';
					$this->cat_id = $category->term_id;
				}
			}
		}
	}

	/**
	 * Show who the to-do item was assigned to, if defined
	 * @param $assign
	 */
	public function show_assigned( $assign ) {
		if ( ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '0' && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) ||
		( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '1' ) && CTDL_Loader::$settings['assign'] == '0' ) {
			$assign_user = '';
			if ( $assign != '-1' && $assign != '' && $assign != '0' ) {
				$assign_user = get_userdata( $assign );
				$this->list .= ' <small>['.__( 'assigned to', 'cleverness-to-do-list' ).' '.esc_attr( $assign_user->display_name ).']</small>';
			}
		}
   	}

	/**
	 * Show who added the to-do item
	 * @param $author
	 */
	public function show_addedby( $author ) {
		if ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['todo_author'] == '0' ) {
			if ( $author != '0' ) {
				$this->list .= ' <small>- '.__( 'added by', 'cleverness-to-do-list' ).' '.esc_attr( $author ).'</small>';
			}
		}
	}

	/**
	 * Show the deadline for the to-do item
	 * @param $deadline
	 */
	public function show_deadline( $deadline ) {
		if ( CTDL_Loader::$settings['show_deadline'] == '1' && $deadline != '' )
			$this->list .= ' <small>['.__( 'Deadline:', 'cleverness-to-do-list' ).' '.esc_attr( $deadline ).']</small>';
	}

	/**
	 * Show the Date the To-Do Item was Added
	 * @param $date
	 * @since 3.1
	 */
	public function show_date_added( $date ) {
		if ( CTDL_Loader::$settings['show_date_added'] == 1 ) {
			$date = ( isset( $date ) ? date( CTDL_Loader::$settings['date_format'], strtotime( $date ) ) : '' );
			$this->list .= ' <small>['.__( 'Date Added:', 'cleverness-to-do-list' ).' '.( $date != '' ? sprintf( '%s', esc_attr( $date ) ) : '' ).']</small>';
		}
	}

	/**
	 * Show the progress of the to-do item
	 * @param $progress
	 */
	public function show_progress( $progress ) {
		if ( CTDL_Loader::$settings['show_progress'] == '1' && $progress != '' ) {
			$this->list .= ' <small>['.esc_attr( $progress ).'%]</small>';
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
		add_shortcode( 'todolist', array( &$this, 'display_list' ) );
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
	public function display() {
		extract( shortcode_atts( array(
			'title'      => '',
			'type' => 'list',
			'priorities' => 'show',
			'assigned' => 'show',
			'deadline' => 'show',
			'progress' => 'show',
			'addedby'  => 'show',
			'date'     => 0,
			'completed' => '',
			'completed_title' => '',
			'list_type' => 'ol',
			'category' => '0'
		), $this->atts ) );

		/** @var $category mixed */
		if ( $category == 'all' ) $category = '0';
		list( $priority, $user, $url, $action ) = CTDL_Lib::set_variables();

		/** @var $type string */
		if ( $type == 'table' ) {

			$this->list .= '<table id="todo-list" class="todo-table">';
			/** @var $title string */
			if ( $title != '' ) $this->list .= '<caption>'.$title.'</caption>';
			$this->show_table_headings();
			$this->loop_through_todos( $user, $priority, $url, 0, $category );
			$this->list .= '</table>';

		} elseif ( $type == 'list' ) {

			/** @var $title string */
			if ( $title != '') {
				$this->list .= '<h3>'.esc_attr( $title ).'</h3>';
			}
			/** @var $list_type string */
			$this->list .= '<'.$list_type.'>';
			$this->loop_through_todos( $user, $priority, $url, 0, $category );
			$this->list .= '</'.$list_type.'>';

		}

		/** @var $completed string */
		if ( $completed == 'show' ) {

			wp_reset_postdata();

			if ( $type == 'table' ) {

				$this->list .= '<table id="todo-list" class="todo-table">';
				/** @var $completed_title string */
				if ( $completed_title != '' ) $this->list .= '<caption>'.$completed_title.'</caption>';
				$this->show_table_headings( 1 );
				$this->loop_through_todos( $user, $priority, $url, 1, $category );
				$this->list .= '</table>';

			} elseif ( $type == 'list' ) {

				/** @var $completed_title string */
				if ( $completed_title != '') {
					$this->list .= '<h3>'.esc_attr( $completed_title ).'</h3>';
				}
				$this->list .= '<'.$list_type.'>';
				$this->loop_through_todos( $user, $priority, $url, 1, $category );
				$this->list .= '</'.$list_type.'>';

			}

		}

		wp_reset_postdata();
	}

	/**
	 * Generate the To-Do List
	 * @param $todo_items
	 * @param $priority
	 * @param $url
	 * @param int $completed
	 * @param int $visible
	 * @return array $posts_to_exclude
	 */
	protected function show_todo_list_items( $todo_items, $priority, $url, $completed = 0, $visible = 0 ) {
		extract( shortcode_atts( array(
			'title'             => '',
			'type'              => 'list',
			'priorities'        => 'show',
			'assigned'          => 'show',
			'deadline'          => 'show',
			'progress'          => 'show',
			'addedby'           => 'show',
			'date'              => 0,
			'completed'         => '',
			'completed_title'   => '',
			'list_type'         => 'ol',
			'category'          => '0'
		), $this->atts ) );

		while ( $todo_items->have_posts() ) : $todo_items->the_post();
			$id = get_the_ID();
			$posts_to_exclude[] = $id;

			if ( $visible == 0 ) {
				list( $the_priority, $assign_meta, $deadline_meta, $completed_meta, $progress_meta ) = CTDL_Lib::get_todo_meta( $id );

				$priority_class = CTDL_Lib::set_priority_class( $the_priority );

				/** @var $type string */
				if ( $type == 'list' ) {
					/** @var $list_type string */
					$this->show_category_headings ( get_the_terms( $id, 'todocategories' ), $list_type );
				}

				if ( $type == 'table' ) {
					$this->list .= '<tr id="todo-'.esc_attr( $id ).'"'.$priority_class.'>';
				} else {
					$this->list .= '<li'.$priority_class.'>';
				}

				$this->show_todo_text( get_the_content(), $type );
				/** @var $priorities string */
				if ( $priorities == 'show' && $type == 'table' ) $this->show_priority( $the_priority, $priority );
				/** @var $progress string */
				if ( $progress == 'show' ) $this->show_progress( $progress_meta, $type );
				/** @var $category string */
				if ( $category == 0  && $type == 'table' ) $this->show_category( get_the_terms( $id, 'todocategories' ) );
				/** @var $assigned string */
				if ( $assigned == 'show' ) $this->show_assigned( $assign_meta, $type );
				/** @var $addedby string */
				if ( $addedby == 'show' ) $this->show_addedby( get_the_author(), $type );
				/** @var $deadline string */
				if ( $deadline == 'show' ) $this->show_deadline( $deadline_meta, $type );
				/** @var $date int */
				if ( $date == 1 ) $this->show_date_added( get_the_date(), $type );
				if ( $completed == 1 && $type == 'list' ) $this->list .= ' - ';
				if ( $completed == 1 ) $this->show_completed( $completed_meta, $type );

				if ( $type == 'table' ) {
					$this->list .= '</tr>';
				} else {
					$this->list .= '</li>';
				}
			}
		endwhile;

		return $posts_to_exclude;
	}

	/**
	 * Creates the HTML for the To-Do List Table Headings
	 * @param $completed
	 */
	protected function show_table_headings( $completed = 0 ) {
		extract( shortcode_atts( array(
			'priorities' => 'show',
			'assigned' => 'show',
			'deadline' => 'show',
			'progress' => 'show',
			'addedby'  => 'show',
			'date'     => 0,
			'category' => '0'
		), $this->atts ) );

		$this->list .= '<thead><tr>';
		$this->list .= '<th>'.__( 'Item', 'cleverness-to-do-list' ).'</th>';
		/** @var $priorities string */
		if ( $priorities == 'show' ) $this->list .= '<th>'.__( 'Priority', 'cleverness-to-do-list' ).'</th>';
		/** @var $progress string */
		if ( $progress == 'show' ) $this->list .= '<th>'.__( 'Progress', 'cleverness-to-do-list' ).'</th>';
		/** @var $category mixed */
		if ( CTDL_Loader::$settings['categories'] == 1 && $category == '0' ) $this->list .= '<th>'.__( 'Category', 'cleverness-to-do-list' ).'</th>';
		/** @var $assigned string */
		if ( $assigned == 'show' && ( CTDL_Loader::$settings['assign'] == 0  && (CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 0
				&& ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) || ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 1 )
				&& CTDL_Loader::$settings['assign'] == 0 ) ) $this->list .= '<th>'.__( 'Assigned To', 'cleverness-to-do-list' ).'</th>';
		/** @var $addedby string */
		if ( $addedby == 'show' ) $this->list .= '<th>'.__( 'Added By', 'cleverness-to-do-list' ).'</th>';
		/** @var $deadline string */
		if ( $deadline == 'show') $this->list .= '<th>'.__( 'Deadline', 'cleverness-to-do-list' ).'</th>';
		/** @var $date int */
		if ( $date == 1 && CTDL_Loader::$settings['show_date_added'] == 1 ) $this->list .= '<th>'.__( 'Date Added', 'cleverness-to-do-list' ).'</th>';
		if ( $completed == 1 ) $this->list .= '<th>'.__( 'Completed', 'cleverness-to-do-list' ).'</th>';
		$this->list .= '</tr></thead>';
	}

	/**
	 * Show category heading only if it's the first item from that category
	 * @param $categories
	 * @param $list_type
	 */
	protected function show_category_headings( $categories, $list_type ) {
		if ( CTDL_Loader::$settings['categories'] == '1' && $categories != false ) {
			foreach ( $categories as $category ) {
				$cat = CTDL_Categories::get_category_name( $category->term_id );
				if ( $this->cat_id != $category->term_id  && $cat != '' ) {
					$this->list .= '</'.$list_type.'><h4>'.esc_attr( $cat ).'</h4><'.$list_type.'>';
					$this->cat_id = $category->term_id;
				}
			}
		}
	}

	/**
	 * Show who the to-do item was assigned to, if defined
	 * @param $assign
	 * @param $layout
	 */
	public function show_assigned( $assign, $layout ) {
		if ( ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '0' && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) ||
				( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '1' ) && CTDL_Loader::$settings['assign'] == '0' ) {
			$assign_user = '';
			if ( $assign != '-1' && $assign != '' && $assign != '0' ) {
				$assign_user = get_userdata( $assign );
				if ( $layout == 'table' ) {
					$this->list .= '<td>'.esc_attr( $assign_user->display_name ).'</td>';
				} else {
					$this->list .= ' - '.__( 'assigned to', 'cleverness-to-do-list' ).' '.esc_attr( $assign_user->display_name );
				}
			} else {
				if ( $layout == 'table' ) $this->list .= '<td></td>';
			}
		}
	}

	/**
	 * Show who added the to-do item
	 * @param $author
	 * @param $layout
	 */
	public function show_addedby( $author, $layout ) {
		if ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['todo_author'] == '0' ) {
			if ( $author != '0' ) {
				if ( $layout == 'table' ) {
					$this->list .= '<td>'.esc_attr( $author ).'</td>';
				} else {
					$this->list .= ' - '.__( 'added by', 'cleverness-to-do-list' ).' '.esc_attr( $author );
				}
			}
		}
	}

	/**
	 * Show the deadline for the to-do item
	 * @param $deadline
	 * @param $layout
	 */
	public function show_deadline( $deadline, $layout ) {
		if ( CTDL_Loader::$settings['show_deadline'] == '1' && $deadline != '' ) {
			if ( $layout == 'table' ) {
				$this->list .= ( $deadline != '' ? sprintf( '<td>%s</td>', esc_attr( $deadline ) ) : '<td></td>' );
			} else {
				$this->list .= ' - '.__( 'Deadline:', 'cleverness-to-do-list' ).' '.esc_attr( $deadline );
			}
		} elseif ( $layout == 'table' ) {
				$this->list .= '<td></td>';
			}
	}

	/**
	 * Show the progress of the to-do item
	 * @param $progress
	 * @param $layout
	 */
	public function show_progress( $progress, $layout ) {
		if ( CTDL_Loader::$settings['show_progress'] == '1' && $progress != '' ) {
			if ( $layout == 'table' ) {
				$this->list .= ( $progress != '' ? sprintf( '<td>%d%%</td>', esc_attr( $progress ) ) : '<td></td>' );
			} else {
				$this->list .= ' - '.esc_attr( $progress ).'%';
			}
		} elseif ( $layout == 'table' ) {
			$this->list .= '<td></td>';
		}
	}

	/**
	 * Show the Date the To-Do Item was Added
	 * @param $the_date
	 * @param string $layout
	 * @internal param $date
	 * @since 3.1
	 */
	public function show_date_added( $the_date, $layout ) {
		if ( CTDL_Loader::$settings['show_date_added'] == 1 && $date = 1 ) {
			$the_date = ( isset( $the_date ) ? date( CTDL_Loader::$settings['date_format'], strtotime( $the_date ) ) : '' );
			if ( $layout == 'table' ) {
				$this->list .= ( $date != '' ? sprintf( '<td>%s</td>', esc_attr( $the_date ) ) : '<td></td>' );
			} else {
				$this->list .= ' - '.__( 'Date Added:', 'cleverness-to-do-list' ).' '.( $date != '' ? sprintf( '%s', esc_attr( $the_date ) ) : '' );
			}
		}
	}

}

?>