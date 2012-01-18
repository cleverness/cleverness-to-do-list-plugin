<?php
/* Allows administration of items on front-end */
require_once 'cleverness-to-do-list.class.php';

class ClevernessToDoFrontEndAdmin extends ClevernessToDoList {
	protected $atts;

	public function __construct( $settings ) {
		add_shortcode( 'todoadmin', array( &$this,  'cleverness_todo_display_admin' ) );
		parent::__construct( $settings );
		parent::cleverness_todo_checklist_init();
		}

	public function cleverness_todo_display_admin( $atts ) {
		extract( shortcode_atts( array(
			'title'      => '',
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
	 * @param $todoitems
	 * @param $priorities
	 * @param $url
	 * @param $completed
	 */
	protected function show_todo_list_items( $todoitems, $priorities, $url, $completed = 0 ) {
		extract( shortcode_atts( array(
			'priority'   => 0,
			'assigned'   => 0,
			'deadline'   => 0,
			'progress'   => 0,
			'categories' => 0,
			'addedby'    => 0,
			'editlink'   => 1
		), $this->atts ) );

		foreach ( $todoitems as $todoitem ) {
			$user_info = get_userdata( $todoitem->author );
			$priority_class = '';
			if ( $todoitem->priority == '0' ) $priority_class = ' class="todo-important"';
			if ( $todoitem->priority == '2' ) $priority_class = ' class="todo-low"';

			$this->list .= '<tr id="todo-' . $todoitem->id . '"' . $priority_class . '>';
			$this->show_checkbox( $todoitem, $completed );
			$this->show_todo_text( $todoitem, $priority_class );
			if ( $priority == 1 ) $this->show_priority( $todoitem, $priorities );
			if ( $assigned == 1 ) $this->show_assigned( $todoitem );
			if ( $deadline == 1 ) $this->show_deadline( $todoitem );
			if ( $completed == 1 ) $this->show_completed( $todoitem );
			if ( $progress == 1 ) $this->show_progress( $todoitem );
			if ( $categories == 1 ) $this->show_category( $todoitem );
			if ( $addedby == 1 ) $this->show_addedby( $todoitem, $user_info );
			if ( $editlink == 1 ) $this->show_edit_link( $todoitem, $url );
			$this->list .= '</tr>';
		}
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
		if ( $assigned == 1 && ( $this->settings['assign'] == 0 && ( $this->settings['list_view'] == 1 && $this->settings['show_only_assigned'] == 0
				&& ( current_user_can( $this->settings['view_all_assigned_capability'] ) ) ) || ( $this->settings['list_view'] == 1 && $this->settings['show_only_assigned'] == 1 )
				&& $this->settings['assign'] == 0 ) ) $this->list .= '<th>' . __( 'Assigned To', 'cleverness-to-do-list' ) . '</th>';
		if ( $deadline == 1  && $this->settings['show_deadline'] == 1 ) $this->list .= '<th>' . __( 'Deadline', 'cleverness-to-do-list' ) . '</th>';
		if ( $completed == 1 && $this->settings['show_completed_date'] == 1 ) $this->list .= '<th>' . __( 'Completed', 'cleverness-to-do-list' ) . '</th>';
		if ( $progress == 1 && $this->settings['show_progress'] == 1 ) $this->list .= '<th>' . __( 'Progress', 'cleverness-to-do-list' ) . '</th>';
		if ( $categories == 1 && $this->settings['categories'] == 1 ) $this->list .= '<th>' . __( 'Category', 'cleverness-to-do-list' ) . '</th>';
		if ( $addedby == 1 && $this->settings['list_view'] == 1 && $this->settings['todo_author'] == 0 ) $this->list .= '<th>' . __( 'Added By', 'cleverness-to-do-list' ) . '</th>';
		if ( $editlink == 1 && current_user_can( $this->settings['edit_capability'] ) || $this->settings['list_view'] == 0 ) $this->list .= '<th>' . __( 'Action', 'cleverness-to-do-list' ) . '</th>';
		$this->list .= '</tr></thead>';
	}

}

class ClevernessToDoFrontEndChecklist extends ClevernessToDoList {
	protected $atts;

	public function __construct( $settings ) {
		add_shortcode( 'todochecklist', array( &$this,  'cleverness_todo_display_checklist' ) );
		parent::__construct( $settings );
		parent::cleverness_todo_checklist_init();
		}

	public function cleverness_todo_display_checklist( $atts ) {
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
			'editlink'   => 0
		), $this->atts ) );
		global $userdata, $current_user;
		get_currentuserinfo();

		$priority = array( 0 => $this->settings['priority_0'] , 1 => $this->settings['priority_1'], 2 => $this->settings['priority_2'] );
		$user = $this->get_user_id( $current_user, $userdata );

		if ( $title != '') {
			$this->list .= '<h3>'.$title.'</h3>';
			}

		// get to-do items
		$results = cleverness_todo_get_todos( $user, 0, 0, $categories );

		if ( $results ) {

			foreach ( $results as $result ) {
				$user_info = get_userdata( $result->author );
				$priority_class = '';
		   		if ( $result->priority == '0' ) $priority_class = ' class="todo-important"';
				if ( $result->priority == '2' ) $priority_class = ' class="todo-low"';

				$this->show_category_headings ($result, $this->cat_id );

				$this->list .= '<p id="todo-'.$result->id.'" class="todo-list">';

				$this->show_checkbox( $result );
				$this->show_todo_text( $result, $priority_class );
				if ( $priority == 1 ) $this->show_priority( $result, $priorities );
				if ( $assigned == 1 ) $this->show_assigned( $result );
				if ( $deadline == 1 ) $this->show_deadline( $result );
				if ( $progress == 1 ) $this->show_progress( $result );
				if ( $categories == 1 ) $this->show_category( $result );
				if ( $addedby == 1 ) $this->show_addedby( $result, $user_info );

				$this->list .= '</p>';
				}

		} else {
			/* if there are no to-do items, display this message */
			$this->list .= '<p>'.__( 'No items to do.', 'cleverness-to-do-list' ).'</p>';
			}

		}

	/* show category heading only if it's the first item from that category */
	protected function show_category_headings( $result, $cat_id ) {
		if ( $this->settings['categories'] == '1' && $result->cat_id != 0 ) {
			$cat = cleverness_todo_get_cat_name( $result->cat_id );
			if ( isset( $cat ) ) {
				if ( $cat_id != $result->cat_id  && $cat->name != '' ) $this->list .= '<h4>'.$cat->name.'</h4>';
					$this->cat_id = $result->cat_id;
				}
			}
		}

	/* show to-do item, wrapped in a span with the priority class */
	protected function show_todo_text( $result, $priority_class ) {
		$this->list .= ' <span'.$priority_class.'>'.stripslashes( $result->todotext ).'</span>';
		}

	/* show who the to-do item was assigned to, if defined */
	protected function show_assigned( $todofielddata ) {
		if ( ( $this->settings['list_view'] == '1' && $this->settings['show_only_assigned'] == '0' && ( current_user_can( $this->settings['view_all_assigned_capability'] ) ) ) ||
		( $this->settings['list_view'] == '1' && $this->settings['show_only_assigned'] == '1' ) && $this->settings['assign'] == '0' ) {
			$assign_user = '';
			if ( $todofielddata->assign != '-1' && $todofielddata->assign != '' && $todofielddata->assign != '0' ) {
				$assign_user = get_userdata( $todofielddata->assign );
				$this->list .= ' <small>['.__( 'assigned to', 'cleverness-to-do-list' ).' '.$assign_user->display_name.']</small>';
				}
			}
   		}

	/* show who added the to-do item */
	protected function show_addedby( $todofielddata, $user_info ) {
		if ( $this->settings['list_view'] == '1' && $this->settings['todo_author'] == '0' ) {
			if ( $todofielddata->author != '0' ) {
				$this->list .= ' <small>- '.__( 'added by', 'cleverness-to-do-list' ).' '.$user_info->display_name.'</small>';
				}
			}
		}

	/* show the deadline for the to-do item */
	protected function show_deadline( $todofielddata ) {
		if ( $this->settings['show_deadline'] == '1' && $todofielddata->deadline != '' )
			$this->list .= ' <small>['.__( 'Deadline:', 'cleverness-to-do-list' ).' '.$todofielddata->deadline.']</small>';
		}

	/* show the progress of the to-do item */
	protected function show_progress( $todofielddata ) {
		if ( $this->settings['show_progress'] == '1' && $todofielddata->progress != '' ) {
			$this->list .= ' <small>['.$todofielddata->progress.'%]</small>';
			}
		}

}

function has_cleverness_todo_shortcode( $posts ) {
    if ( empty( $posts ) )
        return $posts;

    $cleverness_todo_shortcode_found = false;

    foreach ( $posts as $post ) {
        if ( stripos( $post->post_content, '[todoadmin' ) || stripos( $post->post_content, '[todochecklist' ) )
            $cleverness_todo_shortcode_found = true;
            break;
    }

    if ( $cleverness_todo_shortcode_found ) {
		$cleverness_todo_shortcode_settings = array_merge( get_option( 'cleverness-to-do-list-general' ), get_option( 'cleverness-to-do-list-advanced' ), get_option( 'cleverness-to-do-list-permissions' ) );
		new ClevernessToDoFrontEndChecklist ($cleverness_todo_shortcode_settings );
		new ClevernessToDoFrontEndAdmin( $cleverness_todo_shortcode_settings );
	}
    return $posts;
}

add_action( 'the_posts', 'has_cleverness_todo_shortcode' );
?>