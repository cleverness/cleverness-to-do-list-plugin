<?php
/* Allows administration of items on front-end */
require_once 'cleverness-to-do-list.class.php';

class ClevernessToDoFrontEnd extends ClevernessToDoList {
	public function __construct($settings) {
		add_shortcode('todochecklist', array(&$this,  'cleverness_todo_display_checklist') );
		parent::__construct($settings);
		}

public function cleverness_todo_display_checklist($atts) {
	extract(shortcode_atts(array(
	    'title' => ''
	), $atts));

	parent::display();
	}

	protected function show_edit_link($result) {
		if ( current_user_can($this->settings['edit_capability']) || $this->settings['list_view'] == '0' ) {
			echo ' <small>(<a href="admin.php?page=cleverness-to-do-list&amp;action=edittodo&amp;id='. $result->id . '">'. __('Edit', 'cleverness-to-do-list') . '</a>)</small>';
			}
		}

	protected function show_todo_text($result, $priority_class) {
		echo ' <span'.$priority_class.'>'.stripslashes($result->todotext).'</span>';
		}

	protected function show_assigned($result) {
		if ( ($this->settings['list_view'] == '1' && $this->settings['show_only_assigned'] == '0' && (current_user_can($this->settings['view_all_assigned_capability']))) ||
		($this->settings['list_view'] == '1' && $this->settings['show_only_assigned'] == '1') && $this->settings['assign'] == '0') {
			$assign_user = '';
			if ( $result->assign != '-1' && $result->assign != '' && $result->assign != '0') {
				$assign_user = get_userdata($result->assign);
				echo ' <small>['.__('assigned to', 'cleverness-to-do-list').' '.$assign_user->display_name.']</small>';
				}
			}
   		}

	protected function show_addedby($result, $user_info) {
		if ( $this->settings['list_view'] == '1' && $this->settings['todo_author'] == '0' ) {
			if ( $result->author != '0' ) {
				echo ' <small>- '.__('added by', 'cleverness-to-do-list').' '.$user_info->display_name.'</small>';
				}
			}
		}

	protected function show_deadline($result) {
		if ( $this->settings['show_deadline'] == '1' && $result->deadline != '' )
			echo ' <small>['.__('Deadline:', 'cleverness-to-do-list').' '.$result->deadline.']</small>';
		}

	protected function show_progress($result) {
		if ( $this->settings['show_progress'] == '1' && $result->progress != '' ) {
			echo ' <small>['.$result->progress.'%]</small>';
			}
		}

}

$settings = get_option('cleverness_todo_settings');
$cleverness_todo_frontend = new ClevernessToDoFrontEnd($settings);// NEED TO ONLY ADD ON SHORTCODE PAGE

?>