<?php
/* Allows administration of items on front-end
@todo frontend shortcodes not working
@todo insert todo not working
*/

class CTDL_Frontend_Admin extends ClevernessToDoList {
	protected $atts;

	public function __construct() {
		add_shortcode( 'todoadmin', array ( &$this, 'display_admin' ) ) ;
		parent::__construct();
		parent::cleverness_todo_checklist_init();
		}

	public function display_admin( $atts ) {
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
	 * @param $todo_items
	 * @param $priorities
	 * @param $url
	 * @param $completed
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

/*todo finish conversion */
class CTDL_Frontend_Checklist extends ClevernessToDoList {
	protected $atts;

	public function __construct() {
		add_shortcode( 'todochecklist', array( &$this,  'display_checklist' ) );
		parent::__construct();
		parent::cleverness_todo_checklist_init();
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
			'editlink'   => 0,
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
			$todo_items[] = CTDL_Lib::get_todo( $todoid );
		} else {
			$todo_items = CTDL_Lib::get_todos( $user, 0, 0, $categories );
		}

		if ( $todo_items->have_posts() ) {

		while ( $todo_items->have_posts() ) : $todo_items->the_post();
			$id = get_the_ID();
			$the_priority = get_post_meta( $id, '_priority', true );
			$priority_class = '';
			if ( $the_priority == '0' ) $priority_class = ' class="todo-important"';
			if ( $the_priority == '2' ) $priority_class = ' class="todo-low"';

			//$this->show_category_headings ($todo_items, $this->cat_id );

			$this->list .= '<p id="todo-'.$id.'" class="todo-list">';

			$completed = ( $todoid != '' && get_post_meta( $id, '_status', true )== 1 ? 1 : 0 );

			$this->show_checkbox( $id, $completed );
			$this->show_todo_text( get_the_content(), $priority_class  );
			if ( $priority == 1 ) $this->show_priority( $the_priority, $priorities );
			if ( $assigned == 1 ) $this->show_assigned( get_post_meta( $id, '_assign', true ) );
			if ( $deadline == 1 ) $this->show_deadline( get_post_meta( $id, '_deadline', true ) );
			if ( $progress == 1 ) $this->show_progress( get_post_meta( $id, '_progress', true ) );
			if ( $categories == 1 ) $this->show_category( get_the_terms( $id, 'todocategories' ) );
			if ( $addedby == 1 ) $this->show_addedby( get_the_author(), $user );
			if ( $editlink == 1 ) $this->show_edit_link( $id, $url );
			$this->list .= '</p>';
		endwhile;

		} else {
			/* if there are no to-do items, display this message */
			$this->list .= '<p>'.__( 'No items to do.', 'cleverness-to-do-list' ).'</p>';
		}

	}

	/* show category heading only if it's the first item from that category */
	protected function show_category_headings( $result, $cat_id ) {
		if ( CTDL_Loader::$settings['categories'] == '1' && $result->cat_id != 0 ) {
			$cat = cleverness_todo_get_cat_name( $result->cat_id );
			if ( isset( $cat ) ) {
				if ( $cat_id != $result->cat_id  && $cat->name != '' ) $this->list .= '<h4>'.$cat->name.'</h4>';
					$this->cat_id = $result->cat_id;
				}
			}
		}

	/* show to-do item, wrapped in a span with the priority class */
	public function show_todo_text( $result, $priority_class ) {
		$this->list .= ' <span'.$priority_class.'>'.stripslashes( $result ).'</span>';
		}

	/* show who the to-do item was assigned to, if defined */
	public function show_assigned( $todofielddata ) {
		if ( ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '0' && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) ||
		( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '1' ) && CTDL_Loader::$settings['assign'] == '0' ) {
			$assign_user = '';
			if ( $todofielddata->assign != '-1' && $todofielddata->assign != '' && $todofielddata->assign != '0' ) {
				$assign_user = get_userdata( $todofielddata->assign );
				$this->list .= ' <small>['.__( 'assigned to', 'cleverness-to-do-list' ).' '.$assign_user->display_name.']</small>';
				}
			}
   		}

	/* show who added the to-do item */
	public function show_addedby( $todofielddata, $user_info ) {
		if ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['todo_author'] == '0' ) {
			if ( $todofielddata->author != '0' ) {
				$this->list .= ' <small>- '.__( 'added by', 'cleverness-to-do-list' ).' '.$user_info->display_name.'</small>';
				}
			}
		}

	/* show the deadline for the to-do item */
	public function show_deadline( $todofielddata ) {
		if ( CTDL_Loader::$settings['show_deadline'] == '1' && $todofielddata->deadline != '' )
			$this->list .= ' <small>['.__( 'Deadline:', 'cleverness-to-do-list' ).' '.$todofielddata.']</small>';
		}

	/* show the progress of the to-do item */
	public function show_progress( $todofielddata ) {
		if ( CTDL_Loader::$settings['show_progress'] == '1' && $todofielddata != '' ) {
			$this->list .= ' <small>['.$todofielddata->progress.'%]</small>';
			}
		}

}

class ClevernessToDoFrontEndList extends ClevernessToDoList {
	protected $atts;

	public function __construct() {
		add_shortcode( 'todolist', array( &$this,  'cleverness_todo_display_checklist' ) );
		parent::__construct();
		parent::cleverness_todo_checklist_init();
	}

	public function cleverness_todo_display_checklist( $atts ) {
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
			'category' => 'all'
		), $this->atts ) );
		global $userdata, $current_user;
		get_currentuserinfo();
		$priority = array( 0 => CTDL_Loader::$settings['priority_0'] , 1 => CTDL_Loader::$settings['priority_1'], 2 => CTDL_Loader::$settings['priority_2'] );
		$user = CTDL_Lib::get_user_id( $current_user, $userdata );

		global $wpdb, $cleverness_todo_option, $userdata;
		get_currentuserinfo();
		$table_name = $wpdb->prefix . 'todolist';
		$cat_table_name = $wpdb->prefix . 'todolist_cats';


		$display_todo = '';

		if ( $cleverness_todo_option['list_view'] == '0' && $userdata->ID != NULL ) {
			if ( $cleverness_todo_option['assign'] == '0' )
				$author = "AND ( author = $userdata->ID || assign = $userdata->ID )";
			else
				$author = " AND author = $userdata->ID ";
		}

		// show list in a table format
		?>

	<?php if ( $type == 'table' ) : ?>
		<?php
			$display_todo .= '<table id="todo-list" border="1">';
			if ( $title != '' ) $display_todo .= '<caption>'.$title.'</caption>';
			$display_todo .= '<thead><tr>
	   		<th>'.__('Item', 'cleverness-to-do-list').'</th>';
			if ( $priorities == 'show' ) $display_todo .= '<th>'.__('Priority', 'cleverness-to-do-list').'</th>';
			if ( $cleverness_todo_option['assign'] == '0' && $assigned == 'show') $display_todo .= '<th>'.__('Assigned To', 'cleverness-to-do-list').'</th>';
			if ( $cleverness_todo_option['show_deadline'] == '1' && $deadline == 'show' ) $display_todo .= '<th>'.__('Deadline', 'cleverness-to-do-list').'</th>';
			if ( $cleverness_todo_option['show_progress'] == '1' && $progress == 'show' ) $display_todo .= '<th>'.__('Progress', 'cleverness-to-do-list').'</th>';
			if ( $cleverness_todo_option['categories'] == '1' && $category == 'all' ) $display_todo .= '<th>'.__('Category', 'cleverness-to-do-list').'</th>';
			if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' && $addedby == 'show' ) $display_todo .= '<th>'.__('Added By', 'cleverness-to-do-list').'</th>';
			$display_todo .= '</tr></thead>';

			$sort = $cleverness_todo_option['sort_order'];

			if ( $cleverness_todo_option['categories'] == '0' ) {
				$sql = "SELECT * FROM $table_name WHERE status = 0 $author ORDER BY priority, $sort";
			} else {
				if ( $category != 'all' )
					$sql = "SELECT * FROM $table_name WHERE status = 0 $author AND cat_id = $category ORDER BY priority, $sort";
				else
					$sql = "SELECT * FROM $table_name LEFT JOIN $cat_table_name ON $table_name.cat_id = $cat_table_name.id WHERE status = 0 $author AND $cat_table_name.visibility = 0 ORDER BY cat_id, priority, $table_name.$sort";
			}

			$results = $wpdb->get_results($sql);
			if ($results) {
				foreach ($results as $result) {
					$class = ('alternate' == $class) ? '' : 'alternate';
					$prstr = $priority[$result->priority];
					$priority_class = '';
					$user_info = get_userdata($result->author);
					if ($result->priority == '0') $priority_class = ' todo-important';
					if ($result->priority == '2') $priority_class = ' todo-low';
					$display_todo .= '<tr id="cleverness_todo-'.$result->id.'" class="'.$class.$priority_class.'">
			   	<td>'.stripslashes($result->todotext).'</td>';
					if ( $priorities == 'show' )
						$display_todo .= '<td>'.$prstr.'</td>';
					if ( $cleverness_todo_option['assign'] == '0' && $assigned == 'show' ) {
						$assign_user = '';
						if ( $result->assign != '-1' )
							$assign_user = get_userdata($result->assign);
						$display_todo .= '<td>'.$assign_user->display_name.'</td>';
					}
					if ( $cleverness_todo_option['show_deadline'] == '1' && $deadline == 'show' )
						$display_todo .= '<td>'.$result->deadline.'</td>';
					if ( $cleverness_todo_option['show_progress'] == '1' && $progress == 'show' ) {
						$display_todo .= '<td>'.$result->progress;
						if ( $result->progress != '' ) $display_todo .= '%';
						$display_todo .= '</td>';
					}
					if ( $cleverness_todo_option['categories'] == '1' && $category == 'all' ) {
						$cat = cleverness_todo_get_cat_name($result->cat_id);
						$display_todo .= '<td>'.$cat->name.'</td>';
					}
					if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' && $addedby == 'show' )
						$display_todo .= '<td>'.$user_info->display_name.'</td>';
				}
			} else {
				$display_todo .= '<tr><td ';
				$colspan = 2;
				if ( $cleverness_todo_option['assign'] == '0' ) $colspan += 1;
				if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' ) $colspan += 1;
				if ( $cleverness_todo_option['show_deadline'] == '1' ) $colspan += 1;
				if ( $cleverness_todo_option['show_progress'] == '1' ) $colspan += 1;
				if ( $cleverness_todo_option['categories'] == '1' && $category == 'all' ) $colspan += 1;
				$display_todo .= 'colspan="'.$colspan.'"';
				$display_todo .= '>'.__('There are no items listed.', 'cleverness-to-do-list').'</td></tr>';
			}

			$display_todo .= '</table>';

			if ( $completed == 'show' ) :
				$display_todo .= '<table id="todo-list" border="1">';
				if ( $completed_title != '' ) $display_todo .= '<caption>'.$completed_title.'</caption>';
				$display_todo .= '<thead><tr><th>'.__('Item', 'cleverness-to-do-list').'</th>';
				if ( $priorities == 'show' ) $display_todo .= '<th>'.__('Priority', 'cleverness-to-do-list').'</th>';
				if ( $cleverness_todo_option['assign'] == '0' && $assigned == 'show') $display_todo .= '<th>'.__('Assigned To', 'cleverness-to-do-list').'</th>';
				if ( $cleverness_todo_option['show_deadline'] == '1' && $deadline == 'show' ) $display_todo .= '<th>'.__('Deadline', 'cleverness-to-do-list').'</th>';
				if ( $cleverness_todo_option['show_completed_date'] == '1' )$display_todo .= '<th>'.__('Completed', 'cleverness-to-do-list').'</th>';
				if ( $cleverness_todo_option['categories'] == '1' && $category == 'all' ) $display_todo .= '<th>'.__('Category', 'cleverness-to-do-list').'</th>';
				if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' && $addedby == 'show' ) $display_todo .= '<th>'.__('Added By', 'cleverness-to-do-list').'</th>';
				$display_todo .= '</tr></thead>';

				if ( $cleverness_todo_option['categories'] == '0' ) {
					$sql = "SELECT * FROM $table_name WHERE status = 1 $author ORDER BY completed DESC, $sort";
				} else {
					if ( $category != 'all' )
						$sql = "SELECT * FROM $table_name WHERE status = 1 $author AND cat_id = $category ORDER BY completed DESC, $sort";
					else
						$sql = "SELECT * FROM $table_name LEFT JOIN $cat_table_name ON $table_name.cat_id = $cat_table_name.id WHERE status = 1 $author AND $cat_table_name.visibility = 0 ORDER BY cat_id, completed DESC, $table_name.$sort";
				}
				$results = $wpdb->get_results($sql);
				if ($results) {
					foreach ($results as $result) {
						$class = ('alternate' == $class) ? '' : 'alternate';
						$prstr = $priority[$result->priority];
						$priority_class = '';
						$user_info = get_userdata($result->author);
						if ($result->priority == '0') $priority_class = ' todo-important';
						if ($result->priority == '2') $priority_class = ' todo-low';
						$display_todo .= '<tr id="cleverness_todo-'.$result->id.'" class="'.$class.$priority_class.'">
			   	<td>'.$result->todotext.'</td>';
						if ( $priorities == 'show' )
							$display_todo .= '<td>'.$prstr.'</td>';
						if ( $cleverness_todo_option['assign'] == '0' && $assigned == 'show' ) {
							$assign_user = '';
							if ( $result->assign != '-1' )
								$assign_user = get_userdata($result->assign);
							$display_todo .= '<td>'.$assign_user->display_name.'</td>';
						}
						if ( $cleverness_todo_option['show_deadline'] == '1' && $deadline == 'show' )
							$display_todo .= '<td>'.$result->deadline.'</td>';
						if ( $cleverness_todo_option['show_completed_date'] == '1' ) {
							$date = '';
							if ( $result->completed != '0000-00-00 00:00:00' )
								$date = date($cleverness_todo_option['date_format'], strtotime($result->completed));
							$display_todo .= '<td>'.$date.'</td>';
						}
						if ( $cleverness_todo_option['categories'] == '1' && $category == 'all' ) {
							$cat = cleverness_todo_get_cat_name($result->cat_id);
							$display_todo .= '<td>'.$cat->name.'</td>';
						}
						if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' && $addedby == 'show' )
							$display_todo .= '<td>'.$user_info->display_name.'</td>';
					}
				} else {
					$display_todo .= '<tr><td ';
					$colspan = 2;
					if ( $cleverness_todo_option['assign'] == '0' ) $colspan += 1;
					if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' ) $colspan += 1;
					if ( $cleverness_todo_option['show_deadline'] == '1' ) $colspan += 1;
					if ( $cleverness_todo_option['show_completed'] == '1' ) $colspan += 1;
					if ( $cleverness_todo_option['categories'] == '1' && $category == 'all' ) $colspan += 1;
					$display_todo .= 'colspan="'.$colspan.'"';
					$display_todo .= '>'.__('There are no items listed.', 'cleverness-to-do-list').'</td></tr>';
				}

				$display_todo .= '</table>';
				?>
			<?php endif; ?>


		<?php elseif ( $type == 'list' ) : ?>
		<?php
			// display the list in list format
			if ( $title != '' ) $display_todo = '<h3>'.$title.'</h3>';
			$display_todo .= '<'.$list_type.'>';
			$sort = $cleverness_todo_option['sort_order'];

			if ( $cleverness_todo_option['categories'] == '0' ) {
				$sql = "SELECT * FROM $table_name WHERE status = 0 $author ORDER BY priority, $sort";
			} else {
				if ( $category != 'all' )
					$sql = "SELECT * FROM $table_name WHERE status = 0 $author AND cat_id = $category ORDER BY priority, $sort";
				else
					$sql = "SELECT * FROM $table_name LEFT JOIN $cat_table_name ON $table_name.cat_id = $cat_table_name.id WHERE status = 0 $author AND $cat_table_name.visibility = 0 ORDER BY cat_id, priority, $table_name.$sort";
			}
			$results = $wpdb->get_results($sql);
			if ($results) {
				foreach ($results as $result) {

					if ( $cleverness_todo_option['categories'] == '1' && $category == 'all' ) {
						$cat = cleverness_todo_get_cat_name($result->cat_id);
						if ( $catid != $result->cat_id && $cat->name != '' ) $display_todo .= '<h4>'.$cat->name.'</h4>';
						$catid = $result->cat_id;
					}

					$user_info = get_userdata($result->author);
					$display_todo .= '<li>';
					$display_todo .= stripslashes($result->todotext);
					if ( $cleverness_todo_option['assign'] == '0' && $assigned == 'show' ) {
						$assign_user = '';
						if ( $result->assign != '-1' && $result->assign != '' ) {
							$assign_user = get_userdata($result->assign);
							$display_todo .= ' - '.$assign_user->display_name;
						}
					}
					if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' && $addedby == 'show' )
						$display_todo .= ' - '.$user_info->display_name;
					if ( $cleverness_todo_option['show_progress'] == '1' && $progress == 'show' ) {
						$display_todo .= ' - '.$result->progress;
						if ( $result->progress != '' ) $display_todo .= '%';
					}
					if ( $cleverness_todo_option['show_deadline'] == '1' && $deadline == 'show' )
						$display_todo .= ' - '.$result->deadline.'';
					$display_todo .= '</li>';
				}
			} else {
				$display_todo .= '<li>'.__('There are no items listed.', 'cleverness-to-do-list').'</li>';
			}
			$display_todo .= '</'.$list_type.'>';

			if ( $completed == 'show' ) {
				if ( $completed_title != '' ) $display_todo .= '<h3>'.$completed_title.'</h3>';
				$display_todo .= '<'.$list_type.'>';
				if ( $cleverness_todo_option['categories'] == '0' ) {
					$sql = "SELECT * FROM $table_name WHERE status = 1 $author ORDER BY completed DESC, $sort";
				} else {
					if ( $category != 'all' )
						$sql = "SELECT * FROM $table_name WHERE status = 1 $author AND cat_id = $category ORDER BY ORDER BY completed DESC, $sort";
					else
						$sql = "SELECT * FROM $table_name LEFT JOIN $cat_table_name ON $table_name.cat_id = $cat_table_name.id WHERE status = 1 $author AND $cat_table_name.visibility = 0 ORDER BY cat_id, completed DESC, $table_name.$sort";
				}
				$results = $wpdb->get_results($sql);
				if ($results) {
					foreach ($results as $result) {
						$user_info = get_userdata($result->author);
						$display_todo .= '<li>';
						if ( $cleverness_todo_option['show_completed_date'] == '1' ) {
							$date = '';
							if ( $result->completed != '0000-00-00 00:00:00' ) {
								$date = date($cleverness_todo_option['date_format'], strtotime($result->completed));
								$display_todo .= $date.' - ';
							}
						}
						$display_todo .= $result->todotext;
						if ( $cleverness_todo_option['assign'] == '0' && $assigned == 'show' ) {
							$assign_user = '';
							if ( $result->assign != '-1' && $result->assign != '' ) {
								$assign_user = get_userdata($result->assign);
								$display_todo .= ' - '.$assign_user->display_name;
							}
						}
						if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' && $addedby == 'show' )
							$display_todo .= ' - '.$user_info->display_name;
						$display_todo .= '</li>';
					}
				} else {
					$display_todo .= '<li>'.__('There are no items listed.', 'cleverness-to-do-list').'</li>';
				}
				$display_todo .= '</'.$list_type.'>';
			}
		endif;

		return $display_todo;


		/////////////////////////////////////////////



		// get to-do items
		$results = CTDL_Lib::get_todos( $user, 0, 0, $category );

		if ( $results ) {

			foreach ( $results as $result ) {
				$user_info = get_userdata( $result->author );
				$priority_class = '';
				if ( $result->priority == '0' ) $priority_class = ' class="todo-important"';
				if ( $result->priority == '2' ) $priority_class = ' class="todo-low"';

				$this->show_category_headings ($result, $this->cat_id );

				$this->list .= '<p id="todo-'.$result->id.'" class="todo-list">';

				$this->show_todo_text( $result, $priority_class );
				if ( $priorities == 'show' ) $this->show_priority( $result, $priority );
				if ( $assigned == 'show' ) $this->show_assigned( $result );
				if ( $deadline == 'show' ) $this->show_deadline( $result );
				if ( $progress == 'show' ) $this->show_progress( $result );
				if ( $category == 'all' ) $this->show_category( $result );
				if ( $addedby == 'show' ) $this->show_addedby( $result, $user_info );

				$this->list .= '</p>';
			}

		} else {
			/* if there are no to-do items, display this message */
			$this->list .= '<p>'.__( 'No items to do.', 'cleverness-to-do-list' ).'</p>';
		}

	}

	/* show category heading only if it's the first item from that category */
	protected function show_category_headings( $result, $cat_id ) {
		if ( CTDL_Loader::$settings['categories'] == '1' && $result->cat_id != 0 ) {
			$cat = cleverness_todo_get_cat_name( $result->cat_id );
			if ( isset( $cat ) ) {
				if ( $cat_id != $result->cat_id  && $cat->name != '' ) $this->list .= '<h4>'.$cat->name.'</h4>';
				$this->cat_id = $result->cat_id;
			}
		}
	}

	/* show to-do item, wrapped in a span with the priority class */
	public function show_todo_text( $result, $priority_class ) {
		$this->list .= ' <span'.$priority_class.'>'.stripslashes( $result->todotext ).'</span>';
	}

	/* show who the to-do item was assigned to, if defined */
	public function show_assigned( $todofielddata ) {
		if ( ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '0' && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) ||
				( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '1' ) && CTDL_Loader::$settings['assign'] == '0' ) {
			$assign_user = '';
			if ( $todofielddata->assign != '-1' && $todofielddata->assign != '' && $todofielddata->assign != '0' ) {
				$assign_user = get_userdata( $todofielddata->assign );
				$this->list .= ' <small>['.__( 'assigned to', 'cleverness-to-do-list' ).' '.$assign_user->display_name.']</small>';
			}
		}
	}

	/* show who added the to-do item */
	public function show_addedby( $todofielddata, $user_info ) {
		if ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['todo_author'] == '0' ) {
			if ( $todofielddata->author != '0' ) {
				$this->list .= ' <small>- '.__( 'added by', 'cleverness-to-do-list' ).' '.$user_info->display_name.'</small>';
			}
		}
	}

	/* show the deadline for the to-do item */
	public function show_deadline( $todofielddata ) {
		if ( CTDL_Loader::$settings['show_deadline'] == '1' && $todofielddata->deadline != '' )
			$this->list .= ' <small>['.__( 'Deadline:', 'cleverness-to-do-list' ).' '.$todofielddata->deadline.']</small>';
	}

	/* show the progress of the to-do item */
	public function show_progress( $todofielddata ) {
		if ( CTDL_Loader::$settings['show_progress'] == '1' && $todofielddata->progress != '' ) {
			$this->list .= ' <small>['.$todofielddata->progress.'%]</small>';
		}
	}

}

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
		new ClevernessToDoFrontEndChecklist();
		$CTDL_Frontend_Admin = new CTDL_Frontend_Admin();
		echo 'adflgjkadlsgjadlgkjasldgjaslgdjlasg';
	    new ClevernessToDoFrontEndList();
	}
    return $posts;
}

add_action( 'the_posts', 'has_cleverness_todo_shortcode' );
?>