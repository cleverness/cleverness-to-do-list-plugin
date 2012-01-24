<?php
/* Display a list of to-do items using shortcode */
function cleverness_todo_display_items($atts) {
	global $wpdb, $cleverness_todo_option, $userdata;
	get_currentuserinfo();
	$table_name = $wpdb->prefix . 'todolist';
	$cat_table_name = $wpdb->prefix . 'todolist_cats';
	$priority = array(0 => $cleverness_todo_option['priority_0'] , 1 => $cleverness_todo_option['priority_1'], 2 => $cleverness_todo_option['priority_2']);
	extract(shortcode_atts(array(
	    'title' => '',
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
	), $atts));

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
}

//add_shortcode('todolist', 'cleverness_todo_display_items');
?>