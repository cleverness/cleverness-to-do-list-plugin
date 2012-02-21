<?php
/**
 * Cleverness To-Do List Plugin Frontend Class
 *
 * Allows administration of items on front-end
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.0
 * @todo don't show category if private
 */

/**
 * Frontend class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Frontend_Admin extends ClevernessToDoList {
	protected $atts;
	public $add_script;

	public function __construct() {
		add_shortcode( 'todoadmin', array ( &$this, 'display_admin' ) ) ;
		parent::__construct();
		add_action( 'wp_footer', 'CTDL_Loader::frontend_checklist_init' );
		add_action( 'wp_footer', 'CTDL_Loader::frontend_checklist_add_js' );
		}

	public function display_admin( $atts ) {
		extract( shortcode_atts( array(
			'title' => '',
		), $atts ) );
		$this->atts = $atts;
		$this->add_script = true;

		if ( $title != '' ) {
			$this->list .= '<h3>'.$title.'</h3>';
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
	 * @param $completed
	 * @return array $posts_to_exclude
	 */
	public function show_todo_list_items( $todo_items, $priorities, $url, $completed = 0 ) {
		extract( shortcode_atts( array(
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'categories' => 0,
			'addedby'    => 0,
			'editlink'   => 1
		), $this->atts ) );

		while ( $todo_items->have_posts() ) : $todo_items->the_post();
			$id = get_the_ID();
			$posts_to_exclude[] = $id;
			$the_priority = get_post_meta( $id, '_priority', true );
			$priority_class = '';
			if ( $the_priority == '0' ) $priority_class = ' class="todo-important"';
			if ( $the_priority == '2' ) $priority_class = ' class="todo-low"';

			$this->list .= '<tr id="todo-'.esc_attr( $id ).'"' . $priority_class . '>';
			$this->show_checkbox( $id, $completed );
			$this->show_todo_text( get_the_content() );
			if ( $priority == 1 ) $this->show_priority( $the_priority, $priorities );
			if ( $assigned == 1 ) $this->show_assigned( get_post_meta( $id, '_assign', true ) );
			if ( $deadline == 1 ) $this->show_deadline( get_post_meta( $id, '_deadline', true ) );
			if ( $progress == 1 ) $this->show_progress( get_post_meta( $id, '_progress', true ) );
			if ( $categories == 1 ) $this->show_category( get_the_terms( $id, 'todocategories' ) );
			if ( $addedby == 1 ) $this->show_addedby( get_the_author() );
			if ( $editlink == 1 ) $this->show_edit_link( $id, $url );
			$this->list .= '</tr>';
		endwhile;

		return $posts_to_exclude;

	}

	/**
	 * Creates the HTML for the To-Do List Table Headings
	 * @param $completed
	 */
	protected function show_table_headings( $completed = 0 ) {
		extract( shortcode_atts( array(
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'categories' => 0,
			'addedby'    => 0,
			'editlink'   => 1
		), $this->atts ) );

		$this->list .= '<thead><tr>';
		if ( !is_admin() ) $this->list .= '<th></th>';
		$this->list .= '<th>' . __( 'Item', 'cleverness-to-do-list' ) . '</th>';
		if ( $priority == 1 ) $this->list .= '<th>' . __( 'Priority', 'cleverness-to-do-list' ) . '</th>';
		if ( $assigned == 1 && ( CTDL_Loader::$settings['assign'] == 0 && ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 0
				&& ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) || ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 1 )
				&& CTDL_Loader::$settings['assign'] == 0 ) ) $this->list .= '<th>' . __( 'Assigned To', 'cleverness-to-do-list' ) . '</th>';
		if ( $deadline == 1  && CTDL_Loader::$settings['show_deadline'] == 1 ) $this->list .= '<th>' . __( 'Deadline', 'cleverness-to-do-list' ) . '</th>';
		if ( $completed == 1 && CTDL_Loader::$settings['show_completed_date'] == 1 ) $this->list .= '<th>' . __( 'Completed', 'cleverness-to-do-list' ) . '</th>';
		if ( $progress == 1 && CTDL_Loader::$settings['show_progress'] == 1 ) $this->list .= '<th>' . __( 'Progress', 'cleverness-to-do-list' ) . '</th>';
		if ( $categories == 1 && CTDL_Loader::$settings['categories'] == 1 ) $this->list .= '<th>' . __( 'Category', 'cleverness-to-do-list' ) . '</th>';
		if ( $addedby == 1 && CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['todo_author'] == 0 ) $this->list .= '<th>' . __( 'Added By', 'cleverness-to-do-list' ) . '</th>';
		if ( $editlink == 1 && current_user_can( CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == 0 ) $this->list .= '<th>' . __( 'Action', 'cleverness-to-do-list' ) . '</th>';
		$this->list .= '</tr></thead>';
	}

}

class CTDL_Frontend_Checklist extends ClevernessToDoList {
	protected $atts;
	protected $cat_id;
	public $add_script;

	public function __construct() {
		add_shortcode( 'todochecklist', array( &$this, 'display_checklist' ) );
		parent::__construct();
		add_action( 'wp_footer', 'CTDL_Loader::frontend_checklist_add_js' );
		}

	public function display_checklist( $atts ) {
		$this->atts = $atts;

		if ( is_user_logged_in() ) {
			$this->display();
		} else {
			$this->list .= __('You must be logged in to view', 'cleverness-to-do-list');
			}

		return $this->list;
		}

	/* display the to-do list with checkboxes */
	public function display() {
		extract( shortcode_atts( array(
			'title'      => '',
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'category'   => 0,
			'addedby'    => 0,
			'todoid'     => ''
		), $this->atts ) );
		global $userdata, $current_user;
		get_currentuserinfo();
		$this->add_script = true;
		$layout = 'list';

		list( $priorities, $user, $url, $action ) = CTDL_Lib::set_variables();

		if ( $title != '') {
			$this->list .= '<h3>'.$title.'</h3>';
			}

		// get to-do items
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
			if ( $priority == 1 ) $this->show_priority( $the_priority, $priorities );
			if ( $assigned == 1 ) $this->show_assigned( get_post_meta( $id, '_assign', true ) );
			if ( $deadline == 1 ) $this->show_deadline( get_post_meta( $id, '_deadline', true ) );
			if ( $progress == 1 ) $this->show_progress( get_post_meta( $id, '_progress', true ) );
			if ( $addedby == 1 ) $this->show_addedby( get_the_author() );
			$this->list .= '</p>';

			} else {
				/* if there are no to-do items, display this message */
				$this->list .= '<p>'.__( 'No items to do.', 'cleverness-to-do-list' ).'</p>';
			}

		} else {

			$this->loop_through_todos( $user, $priorities, $url, 0, $category );

		}

		wp_reset_postdata();

	}

	/**
	 * Generate the To-Do List
	 * @param $todo_items
	 * @param $priorities
	 * @param $url
	 * @param $completed
	 * @return array $posts_to_exclude
	 */
	protected function show_todo_list_items( $todo_items, $priorities, $url, $completed = 0 ) {
		extract( shortcode_atts( array(
			'title'      => '',
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'category'   => 0,
			'addedby'    => 0,
			'todoid'     => ''
		), $this->atts ) );

		while ( $todo_items->have_posts() ) : $todo_items->the_post();
			$id = get_the_ID();
			$posts_to_exclude[] = $id;
			$the_priority = get_post_meta( $id, '_priority', true );
			$priority_class = '';
			if ( $the_priority == '0' ) $priority_class = ' class="todo-important"';
			if ( $the_priority == '2' ) $priority_class = ' class="todo-low"';

			$this->show_category_headings ( get_the_terms( $id, 'todocategories' ), $this->cat_id );

			$this->list .= '<p id="todo-'.esc_attr( $id ).'" class="todo-list'.$priority_class.'">';
			$this->show_checkbox( $id, '', 'list' );
			$this->list .= ' ';
			$this->show_todo_text( get_the_content(), 'list' );
			if ( $priority == 1 ) $this->show_priority( $the_priority, $priorities );
			if ( $assigned == 1 ) $this->show_assigned( get_post_meta( $id, '_assign', true ) );
			if ( $deadline == 1 ) $this->show_deadline( get_post_meta( $id, '_deadline', true ) );
			if ( $progress == 1 ) $this->show_progress( get_post_meta( $id, '_progress', true ) );
			if ( $addedby == 1 ) $this->show_addedby( get_the_author() );
			$this->list .= '</p>';
		endwhile;

		return $posts_to_exclude;

	}

	/* show category heading only if it's the first item from that category */
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

	/* show who the to-do item was assigned to, if defined */
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

	/* show who added the to-do item */
	public function show_addedby( $author ) {
		if ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['todo_author'] == '0' ) {
			if ( $author != '0' ) {
				$this->list .= ' <small>- '.__( 'added by', 'cleverness-to-do-list' ).' '.esc_attr( $author ).'</small>';
			}
		}
	}

	/* show the deadline for the to-do item */
	public function show_deadline( $deadline ) {
		if ( CTDL_Loader::$settings['show_deadline'] == '1' && $deadline != '' )
			$this->list .= ' <small>['.__( 'Deadline:', 'cleverness-to-do-list' ).' '.esc_attr( $deadline ).']</small>';
	}

	/* show the progress of the to-do item */
	public function show_progress( $progress ) {
		if ( CTDL_Loader::$settings['show_progress'] == '1' && $progress != '' ) {
			$this->list .= ' <small>['.esc_attr( $progress ).'%]</small>';
		}
	}

}

/* @todo not ordering by category when enabled */
class CTDL_Frontend_List extends ClevernessToDoList {
	protected $atts;

	public function __construct() {
		add_shortcode( 'todolist', array( &$this, 'display_list' ) );
		parent::__construct();
	}

	public function display_list( $atts ) {
		$this->atts = $atts;

		$this->display();

		return $this->list;
	}

	/* display the to-do list with checkboxes */
	public function display() {
		extract( shortcode_atts( array(
			'title'      => '',
			'type' => 'list',
			'priorities' => 'show',
			'assigned' => 'show',
			'deadline' => 'show',
			'progress' => 'show',
			'addedby' => 'show',
			'completed' => '',
			'completed_title' => '',
			'list_type' => 'ol',
			'category' => '0'
		), $this->atts ) );

		if ( $category == 'all' ) $category = '0';
		list( $priority, $user, $url, $action ) = CTDL_Lib::set_variables();

		if ( $type == 'table' ) {

			$this->list .= '<table id="todo-list" class="todo-table">';
			if ( $title != '' ) $this->list .= '<caption>'.$title.'</caption>';
			$this->show_table_headings();

			$this->loop_through_todos( $user, $priority, $url, 0, $category );

			$this->list .= '</table>';

		} elseif ( $type == 'list' ) {

			if ( $title != '') {
				$this->list .= '<h3>'.$title.'</h3>';
			}
			$this->list .= '<'.$list_type.'>';

			$this->loop_through_todos( $user, $priority, $url, 0, $category );

			$this->list .= '</'.$list_type.'>';

		}

		if ( $completed == 'show' ) {

			wp_reset_postdata();

			if ( $type == 'table' ) {

				$this->list .= '<table id="todo-list" class="todo-table">';
				if ( $completed_title != '' ) $this->list .= '<caption>'.$completed_title.'</caption>';
				$this->show_table_headings( 1 );

				$this->loop_through_todos( $user, $priority, $url, 1, $category );

				$this->list .= '</table>';

			} elseif ( $type == 'list' ) {

				if ( $completed_title != '') {
					$this->list .= '<h3>'.$completed_title.'</h3>';
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
	 * @param $completed
	 * @return array $posts_to_exclude
	 */
	protected function show_todo_list_items( $todo_items, $priority, $url, $completed = 0 ) {
		extract( shortcode_atts( array(
			'title'             => '',
			'type'              => 'list',
			'priorities'        => 'show',
			'assigned'          => 'show',
			'deadline'          => 'show',
			'progress'          => 'show',
			'addedby'           => 'show',
			'completed'         => '',
			'completed_title'   => '',
			'list_type'         => 'ol',
			'category'          => '0'
		), $this->atts ) );

		while ( $todo_items->have_posts() ) : $todo_items->the_post();
			$id = get_the_ID();
			$posts_to_exclude[] = $id;
			$the_priority = get_post_meta( $id, '_priority', true );
			$priority_class = '';
			if ( $the_priority == '0' ) $priority_class = ' class="todo-important"';
			if ( $the_priority == '2' ) $priority_class = ' class="todo-low"';

			if ( $type == 'list' ) $this->show_category_headings ( get_the_terms( $id, 'todocategories' ), $list_type );

			if ( $type == 'table' ) {
				$this->list .= '<tr id="todo-'.esc_attr( $id ).'"'.$priority_class.'>';
			} else {
				$this->list .= '<li'.$priority_class.'>';
			}

			$this->show_todo_text( get_the_content(), $type );
			if ( $priorities == 'show' && $type == 'table' ) $this->show_priority( $the_priority, $priority );
			if ( $assigned == 'show' ) $this->show_assigned( get_post_meta( $id, '_assign', true ), $type );
			if ( $deadline == 'show' ) $this->show_deadline( get_post_meta( $id, '_deadline', true ), $type );
			if ( $completed == 1 && $type == 'list' ) $this->list .= ' - ';
			if ( $completed == 1 ) $this->show_completed( get_post_meta( $id, '_completed', true ), $type );
			if ( $progress == 'show' ) $this->show_progress( get_post_meta( $id, '_progress', true ), $type );
			if ( $category == 0  && $type == 'table' ) $this->show_category( get_the_terms( $id, 'todocategories' ) );
			if ( $addedby == 'show' ) $this->show_addedby( get_the_author(), $type );

			if ( $type == 'table' ) {
				$this->list .= '</tr>';
			} else {
				$this->list .= '</li>';
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
			'addedby' => 'show',
			'category' => '0'
		), $this->atts ) );

		$this->list .= '<thead><tr>';
		$this->list .= '<th>'.__( 'Item', 'cleverness-to-do-list' ).'</th>';
		if ( $priorities == 'show' ) $this->list .= '<th>'.__( 'Priority', 'cleverness-to-do-list' ).'</th>';
		if ( $assigned == 'show' && ( CTDL_Loader::$settings['assign'] == 0  && (CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 0
				&& ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) || ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 1 )
				&& CTDL_Loader::$settings['assign'] == 0 ) ) $this->list .= '<th>'.__( 'Assigned To', 'cleverness-to-do-list' ).'</th>';
		if ( $deadline == 'show') $this->list .= '<th>'.__( 'Deadline', 'cleverness-to-do-list' ).'</th>';
		if ( $completed == 1 ) $this->list .= '<th>'.__( 'Completed', 'cleverness-to-do-list' ).'</th>';
		if ( $progress == 'show' ) $this->list .= '<th>'.__( 'Progress', 'cleverness-to-do-list' ).'</th>';
		if ( CTDL_Loader::$settings['categories'] == 1 && $category == '0' ) $this->list .= '<th>'.__( 'Category', 'cleverness-to-do-list' ).'</th>';
		if ( $addedby == 'show' ) $this->list .= '<th>'.__( 'Added By', 'cleverness-to-do-list' ).'</th>';
		$this->list .= '</tr></thead>';
	}

	/* show category heading only if it's the first item from that category */
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

	/* show who the to-do item was assigned to, if defined */
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

	/* show who added the to-do item */
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

	/* show the deadline for the to-do item */
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

	/* show the progress of the to-do item */
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

}

?>