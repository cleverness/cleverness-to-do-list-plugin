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

	public function __construct() {
		add_shortcode( 'todoadmin', array ( &$this, 'display_admin' ) ) ;
		parent::__construct();
		CTDL_Loader::frontend_checklist_init();
		}

	public function display_admin( $atts ) {
		extract( shortcode_atts( array(
			'title' => '',
		), $atts ) );
		$this->atts = $atts;

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

			$this->list .= '<tr id="todo-'.$id.'"' . $priority_class . '>';
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
	 * @todo get rid of long assign if statement
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

/* @todo when completing, don't remove item via js, just check the box for single completed item */
class CTDL_Frontend_Checklist extends ClevernessToDoList {
	protected $atts;
	protected $cat_id;

	public function __construct() {
		add_shortcode( 'todochecklist', array( &$this, 'display_checklist' ) );
		parent::__construct();
		CTDL_Loader::frontend_checklist_init();
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
			'categories' => 0,
			'addedby'    => 0,
			'todoid'     => ''
		), $this->atts ) );
		global $userdata, $current_user;
		get_currentuserinfo();

		$priority = array( 0 => CTDL_Loader::$settings['priority_0'] , 1 => CTDL_Loader::$settings['priority_1'], 2 => CTDL_Loader::$settings['priority_2'] );
		$user = CTDL_Lib::get_user_id( $current_user, $userdata );

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

			$this->show_category_headings ( get_the_terms( $id, 'todocategories' ), $this->cat_id );

			$this->list .= '<p id="todo-'.$id.'" class="todo-list">';
			$completed = ( $todoid != '' && get_post_meta( $id, '_status', true ) == 1 ? 1 : 0 );
			$this->show_checkbox( $id, $completed );
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

			$todo_items = CTDL_Lib::get_todos( $user, 0, 0, $categories );
			if ( $todo_items->have_posts() ) {

				while ( $todo_items->have_posts() ) : $todo_items->the_post();
					$id = get_the_ID();
					$the_priority = get_post_meta( $id, '_priority', true );
					$priority_class = '';
					if ( $the_priority == '0' ) $priority_class = ' class="todo-important"';
					if ( $the_priority == '2' ) $priority_class = ' class="todo-low"';

					$this->show_category_headings ( get_the_terms( $id, 'todocategories' ), $this->cat_id );

					$this->list .= '<p id="todo-'.$id.'" class="todo-list">';
					$this->show_checkbox( $id );
					$this->show_todo_text( get_the_content(), $priority_class  );
					if ( $priority == 1 ) $this->show_priority( $the_priority, $priorities );
					if ( $assigned == 1 ) $this->show_assigned( get_post_meta( $id, '_assign', true ) );
					if ( $deadline == 1 ) $this->show_deadline( get_post_meta( $id, '_deadline', true ) );
					if ( $progress == 1 ) $this->show_progress( get_post_meta( $id, '_progress', true ) );
					if ( $addedby == 1 ) $this->show_addedby( get_the_author() );
					$this->list .= '</p>';
				endwhile;

			} else {
				/* if there are no to-do items, display this message */
				$this->list .= '<p>'.__( 'No items to do.', 'cleverness-to-do-list' ).'</p>';
			}

		}

		wp_reset_postdata();

	}

	/* show category heading only if it's the first item from that category */
	protected function show_category_headings( $categories ) {
		if ( CTDL_Loader::$settings['categories'] == '1' && $categories != false ) {
			foreach ( $categories as $category ) {
				$cat = CTDL_Categories::get_category_name( $category->term_id );
				if ( $this->cat_id != $category->term_id  && $cat != '' ) {
					$this->list .= '<h4>'.$cat.'</h4>';
					$this->cat_id = $category->term_id;
				}
			}
		}
	}

	/* show to-do item, wrapped in a span with the priority class */
	public function show_todo_text( $todo_text, $priority_class ) {
		$this->list .= ' <span'.$priority_class.'>'.$todo_text.'</span>';
		}

	/* show who the to-do item was assigned to, if defined */
	public function show_assigned( $assign ) {
		if ( ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '0' && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) ||
		( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '1' ) && CTDL_Loader::$settings['assign'] == '0' ) {
			$assign_user = '';
			if ( $assign != '-1' && $assign != '' && $assign != '0' ) {
				$assign_user = get_userdata( $assign );
				$this->list .= ' <small>['.__( 'assigned to', 'cleverness-to-do-list' ).' '.$assign_user->display_name.']</small>';
			}
		}
   	}

	/* show who added the to-do item */
	public function show_addedby( $author ) {
		if ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['todo_author'] == '0' ) {
			if ( $author != '0' ) {
				$this->list .= ' <small>- '.__( 'added by', 'cleverness-to-do-list' ).' '.$author.'</small>';
			}
		}
	}

	/* show the deadline for the to-do item */
	public function show_deadline( $deadline ) {
		if ( CTDL_Loader::$settings['show_deadline'] == '1' && $deadline != '' )
			$this->list .= ' <small>['.__( 'Deadline:', 'cleverness-to-do-list' ).' '.$deadline.']</small>';
	}

	/* show the progress of the to-do item */
	public function show_progress( $progress ) {
		if ( CTDL_Loader::$settings['show_progress'] == '1' && $progress != '' ) {
			$this->list .= ' <small>['.$progress.'%]</small>';
		}
	}

}

class CTDL_Frontend_List extends ClevernessToDoList {
	protected $atts;

	public function __construct() {
		add_shortcode( 'todolist', array( &$this, 'display_list' ) );
		parent::__construct();
		CTDL_Loader::frontend_checklist_init();
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

		global $userdata, $current_user;
		get_currentuserinfo();

		$priority = array( 0 => CTDL_Loader::$settings['priority_0'] , 1 => CTDL_Loader::$settings['priority_1'], 2 => CTDL_Loader::$settings['priority_2'] );
		$user = CTDL_Lib::get_user_id( $current_user, $userdata );

		// get to-do items
		$todo_items = CTDL_Lib::get_todos( $user, 0, 0, $category );

		if ( $todo_items->have_posts() ) {

			if ( $type == 'table' ) {

				$this->list .= '<table id="todo-list" class="todo-table">';
				if ( $title != '' ) $this->list .= '<caption>'.$title.'</caption>';
				$this->show_table_headings();

				while ( $todo_items->have_posts() ) : $todo_items->the_post();
					$id = get_the_ID();
					$the_priority = get_post_meta( $id, '_priority', true );
					$priority_class = '';
					if ( $the_priority == '0' ) $priority_class = ' class="todo-important"';
					if ( $the_priority == '2' ) $priority_class = ' class="todo-low"';

					$this->list .= '<tr id="todo-'.$id.'"'.$priority_class.'>';
					parent::show_todo_text( get_the_content(), $priority_class  );
					if ( $priorities == 'show' ) parent::show_priority( $the_priority, $priority );
					if ( $assigned == 'show' ) parent::show_assigned( get_post_meta( $id, '_assign', true ) );
					if ( $deadline == 'show' ) parent::show_deadline( get_post_meta( $id, '_deadline', true ) );
					if ( $progress == 'show' ) parent::show_progress( get_post_meta( $id, '_progress', true ) );
					if ( $category == 0 ) $this->show_category( get_the_terms( $id, 'todocategories' ) );
					if ( $addedby == 'show' ) parent::show_addedby( get_the_author() );
					$this->list .= '</tr>';
				endwhile;

				$this->list .= '</table>';

			} elseif ( $type == 'list' ) {

				if ( $title != '') {
					$this->list .= '<h3>'.$title.'</h3>';
				}
				$this->list .= '<'.$list_type.'>';

				while ( $todo_items->have_posts() ) : $todo_items->the_post();
					$id = get_the_ID();
					$the_priority = get_post_meta( $id, '_priority', true );
					$priority_class = '';
					if ( $the_priority == '0' ) $priority_class = ' class="todo-important"';
					if ( $the_priority == '2' ) $priority_class = ' class="todo-low"';

					$this->show_category_headings ( get_the_terms( $id, 'todocategories' ), $this->cat_id );

					$this->list .= '<li>';
					$this->show_todo_text( get_the_content(), $priority_class  );
					if ( $priority == 'show' ) $this->show_priority( $the_priority, $priorities );
					if ( $assigned == 'show' ) $this->show_assigned( get_post_meta( $id, '_assign', true ) );
					if ( $deadline == 'show' ) $this->show_deadline( get_post_meta( $id, '_deadline', true ) );
					if ( $progress == 'show' ) $this->show_progress( get_post_meta( $id, '_progress', true ) );
					if ( $addedby == 'show' ) $this->show_addedby( get_the_author() );
					$this->list .= '</li>';
				endwhile;

				$this->list .= '</'.$list_type.'>';
			}

		} else {
			/* if there are no to-do items, display this message */
			$this->list .= '<p>'.__( 'No items to do.', 'cleverness-to-do-list' ).'</p>';
		}

		if ( $completed == 'show' ) {
			wp_reset_postdata();
			// get to-do items
			$todo_items = CTDL_Lib::get_todos( $user, 0, 1, $category );

			if ( $todo_items->have_posts() ) {

				if ( $type == 'table' ) {

					$this->list .= '<table id="todo-list" class="todo-table">';
					if ( $completed_title != '' ) $this->list .= '<caption>'.$completed_title.'</caption>';
					$this->show_table_headings( 1 );

					while ( $todo_items->have_posts() ) : $todo_items->the_post();
						$id = get_the_ID();
						$the_priority = get_post_meta( $id, '_priority', true );
						$priority_class = '';
						if ( $the_priority == '0' ) $priority_class = ' class="todo-important"';
						if ( $the_priority == '2' ) $priority_class = ' class="todo-low"';

						$this->list .= '<tr id="todo-'.$id.'"'.$priority_class.'>';
						parent::show_todo_text( get_the_content(), $priority_class  );
						if ( $priorities == 'show' ) parent::show_priority( $the_priority, $priority );
						if ( $assigned == 'show' ) parent::show_assigned( get_post_meta( $id, '_assign', true ) );
						if ( $deadline == 'show' ) parent::show_deadline( get_post_meta( $id, '_deadline', true ) );
						parent::show_completed( get_post_meta( $id, '_completed', true ) );
						if ( $progress == 'show' ) parent::show_progress( get_post_meta( $id, '_progress', true ) );
						if ( $category == 0 ) $this->show_category( get_the_terms( $id, 'todocategories' ) );
						if ( $addedby == 'show' ) parent::show_addedby( get_the_author() );
						$this->list .= '</tr>';
					endwhile;

					$this->list .= '</table>';

				} elseif ( $type == 'list' ) {
					if ( $completed_title != '') {
						$this->list .= '<h3>'.$completed_title.'</h3>';
					}
					$this->list .= '<'.$list_type.'>';

					while ( $todo_items->have_posts() ) : $todo_items->the_post();
						$id = get_the_ID();
						$the_priority = get_post_meta( $id, '_priority', true );
						$priority_class = '';
						if ( $the_priority == '0' ) $priority_class = ' class="todo-important"';
						if ( $the_priority == '2' ) $priority_class = ' class="todo-low"';

						$this->show_category_headings ( get_the_terms( $id, 'todocategories' ), $this->cat_id );

						$this->list .= '<li>';
						$this->show_todo_text( get_the_content(), $priority_class  );
						if ( $priority == 'show'  ) $this->show_priority( $the_priority, $priorities );
						if ( $assigned == 'show'  ) $this->show_assigned( get_post_meta( $id, '_assign', true ) );
						if ( $deadline == 'show'  ) $this->show_deadline( get_post_meta( $id, '_deadline', true ) );
						$this->list .= ' - ';
						$this->show_completed( get_post_meta( $id, '_completed', true ) );
						if ( $progress == 'show'  ) $this->show_progress( get_post_meta( $id, '_progress', true ) );
						if ( $addedby == 'show'  ) $this->show_addedby( get_the_author() );
						$this->list .= '</li>';
					endwhile;

					$this->list .= '</'.$list_type.'>';
				}

			}
		}

		wp_reset_postdata();
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
	protected function show_category_headings( $categories ) {
		if ( CTDL_Loader::$settings['categories'] == '1' && $categories != false ) {
			foreach ( $categories as $category ) {
				$cat = CTDL_Categories::get_category_name( $category->term_id );
				if ( $this->cat_id != $category->term_id  && $cat != '' ) {
					$this->list .= '<h4>'.$cat.'</h4>';
					$this->cat_id = $category->term_id;
				}
			}
		}
	}

	/* show to-do item, wrapped in a span with the priority class */
	public function show_todo_text( $todo_text, $priority_class ) {
		$this->list .= ' <span'.$priority_class.'>'.$todo_text.'</span>';
	}

	/* show who the to-do item was assigned to, if defined */
	public function show_assigned( $assign ) {
		if ( ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '0' && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) ||
				( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '1' ) && CTDL_Loader::$settings['assign'] == '0' ) {
			$assign_user = '';
			if ( $assign != '-1' && $assign != '' && $assign != '0' ) {
				$assign_user = get_userdata( $assign );
				$this->list .= ' - '.__( 'assigned to', 'cleverness-to-do-list' ).' '.$assign_user->display_name;
			}
		}
	}

	/* show who added the to-do item */
	public function show_addedby( $author ) {
		if ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['todo_author'] == '0' ) {
			if ( $author != '0' ) {
				$this->list .= ' - '.__( 'added by', 'cleverness-to-do-list' ).' '.$author;
			}
		}
	}

	/* show the deadline for the to-do item */
	public function show_deadline( $deadline ) {
		if ( CTDL_Loader::$settings['show_deadline'] == '1' && $deadline != '' )
			$this->list .= ' - '.__( 'Deadline:', 'cleverness-to-do-list' ).' '.$deadline;
	}

	/* show the progress of the to-do item */
	public function show_progress( $progress ) {
		if ( CTDL_Loader::$settings['show_progress'] == '1' && $progress != '' ) {
			$this->list .= ' - '.$progress.'%';
		}
	}

}

/*todo not working */
function has_cleverness_todo_shortcode( $posts ) {
    if ( empty( $posts ) )
        return $posts;

    $cleverness_todo_shortcode_found = false;

    foreach ( $posts as $post ) {
        if ( stripos( $post->post_content, '[todoadmin' ) || stripos( $post->post_content, '[todochecklist' ) || stripos( $post->post_content, '[todolist' ) ) {
            $cleverness_todo_shortcode_found = true;
            break;
        }
    }

    if ( $cleverness_todo_shortcode_found ) {
	    new CTDL_Frontend_Admin;
	}
    return $posts;
}

add_action( 'the_posts', 'has_cleverness_todo_shortcode' );
?>