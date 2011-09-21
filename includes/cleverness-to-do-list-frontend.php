<?php
/* Allows administration of items on front-end */
require_once 'cleverness-to-do-list.class.php';

class ClevernessToDoFrontEndAdmin extends ClevernessToDoList {

	public function __construct($settings) {
		add_shortcode('todoadmin', array(&$this,  'cleverness_todo_display_admin') );
		parent::__construct($settings);
		parent::cleverness_todo_checklist_init();
		}

	public function cleverness_todo_display_admin($atts) {
		extract(shortcode_atts(array(
	    	'title' => '',
			'priority' => 0,
			'assigned' => 0,
			'deadline' => 0,
			'progress' => 0,
			'categories' => 0,
			'addedby' => 0,
			'editlink' => 1
		), $atts));

		if ( $title != '') {
			$this->list .= '<h3>'.$title.'</h3>';
			}

		if ( is_user_logged_in() ) {
			$this->display($title, $priority, $assigned, $deadline, $progress, $categories, $addedby, $editlink);
		} else {
			$this->list .= __('You must be logged in to view', 'cleverness-to-do-list');
			}

		return $this->list;
		}

}

class ClevernessToDoFrontEndChecklist extends ClevernessToDoList {

	public function __construct($settings) {
		add_shortcode('todochecklist', array(&$this,  'cleverness_todo_display_checklist') );
		parent::__construct($settings);
		parent::cleverness_todo_checklist_init();
		}

	public function cleverness_todo_display_checklist($atts) {
		extract(shortcode_atts(array(
	    	'title' => '',
			'priority' => 0,
			'assigned' => 0,
			'deadline' => 0,
			'progress' => 0,
			'categories' => 0,
			'addedby' => 0,
			'editlink' => 0
		), $atts));

		if ( is_user_logged_in() ) {
			$this->display($title, $priority, $assigned, $deadline, $progress, $categories, $addedby, $editlink);
		} else {
			$this->list .= __('You must be logged in to view', 'cleverness-to-do-list');
			}

		return $this->list;
		}

	/* display the to-do list with checkboxes */
	public function display($title, $priority, $assigned, $deadline, $progress, $categories, $addedby, $editlink) {
		global $userdata, $current_user;
		get_currentuserinfo();

		$priority = array(0 => $this->settings['priority_0'] , 1 => $this->settings['priority_1'], 2 => $this->settings['priority_2']);
		$user = $this->get_user($current_user, $userdata);

		if ( $title != '') {
			$this->list .= '<h3>'.$title.'</h3>';
			}

		// get to-do items
		$results = cleverness_todo_get_todos($user, 0, 0, $categories);

		if ($results) {

			foreach ($results as $result) {
				$user_info = get_userdata($result->author);
				$priority_class = '';
		   		if ($result->priority == '0') $priority_class = ' class="todo-important"';
				if ($result->priority == '2') $priority_class = ' class="todo-low"';

				$this->show_category_headings($result, $this->cat_id);

				$this->list .= '<p id="todo-'.$result->id.'" class="todo-list">';

				$this->show_checkbox($result);
				$this->show_todo_text($result, $priority_class);
				if ( $priority == 1 ) $this->show_priority($result, $priorities);
				if ( $assigned == 1 ) $this->show_assigned($result);
				if ( $deadline == 1 ) $this->show_deadline($result);
				if ( $progress == 1 ) $this->show_progress($result);
				if ( $categories == 1 ) $this->show_category($result);
				if ( $addedby == 1 ) $this->show_addedby($result, $user_info);

				$this->list .= '</p>';
				}

		} else {
			/* if there are no to-do items, display this message */
			$this->list .= '<p>'.__('No items to do.', 'cleverness-to-do-list').'</p>';
			}

		}

	/* show category heading only if it's the first item from that category */
	protected function show_category_headings($result, $cat_id) {
		if ( $this->settings['categories'] == '1' && $result->cat_id != 0 ) {
			$cat = cleverness_todo_get_cat_name($result->cat_id);
			if ( $cat_id != $result->cat_id  && $cat->name != '' ) $this->list .= '<h4>'.$cat->name.'</h4>';
				$this->cat_id = $result->cat_id;
			}
		}

	/* show to-do item, wrapped in a span with the priority class */
	protected function show_todo_text($result, $priority_class) {
		$this->list .= ' <span'.$priority_class.'>'.stripslashes($result->todotext).'</span>';
		}

	/* show who the to-do item was assigned to, if defined */
	protected function show_assigned($result) {
		if ( ($this->settings['list_view'] == '1' && $this->settings['show_only_assigned'] == '0' && (current_user_can($this->settings['view_all_assigned_capability']))) ||
		($this->settings['list_view'] == '1' && $this->settings['show_only_assigned'] == '1') && $this->settings['assign'] == '0') {
			$assign_user = '';
			if ( $result->assign != '-1' && $result->assign != '' && $result->assign != '0') {
				$assign_user = get_userdata($result->assign);
				$this->list .= ' <small>['.__('assigned to', 'cleverness-to-do-list').' '.$assign_user->display_name.']</small>';
				}
			}
   		}

	/* show who added the to-do item */
	protected function show_addedby($result, $user_info) {
		if ( $this->settings['list_view'] == '1' && $this->settings['todo_author'] == '0' ) {
			if ( $result->author != '0' ) {
				$this->list .= ' <small>- '.__('added by', 'cleverness-to-do-list').' '.$user_info->display_name.'</small>';
				}
			}
		}

	/* show the deadline for the to-do item */
	protected function show_deadline($result) {
		if ( $this->settings['show_deadline'] == '1' && $result->deadline != '' )
			$this->list .= ' <small>['.__('Deadline:', 'cleverness-to-do-list').' '.$result->deadline.']</small>';
		}

	/* show the progress of the to-do item */
	protected function show_progress($result) {
		if ( $this->settings['show_progress'] == '1' && $result->progress != '' ) {
			$this->list .= ' <small>['.$result->progress.'%]</small>';
			}
		}

}

function has_cleverness_todo_shortcode($posts) {
    if ( empty($posts) )
        return $posts;

    $cleverness_todo_shortcode_found = false;

    foreach ($posts as $post) {
        if ( stripos($post->post_content, '[todoadmin') || stripos($post->post_content, '[todochecklist') )
            $cleverness_todo_shortcode_found = true;
            break;
        }

    if ($cleverness_todo_shortcode_found) {

		$cleverness_todo_shortcode_settings = get_option('cleverness_todo_settings');
		$cleverness_todo_frontend_checklist = new ClevernessToDoFrontEndChecklist($cleverness_todo_shortcode_settings);
		$cleverness_todo_frontend_admin = new ClevernessToDoFrontEndAdmin($cleverness_todo_shortcode_settings);

}
    return $posts;
}
add_action('the_posts', 'has_cleverness_todo_shortcode');

?>