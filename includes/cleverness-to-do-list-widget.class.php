<?php
class CTDL_Widget extends WP_Widget {

	function __construct() {
		parent::WP_Widget( 'cleverness-to-do-widget', 'To-Do List', array( 'description' => __('Lists To-Do Items', 'cleverness-to-do-list') ) );
	}

	function widget( $args, $instance ) {
		global $wpdb, $userdata;
		get_currentuserinfo();
		extract( $args );

		$title      = apply_filters( 'widget_title', $instance['title'] );
		$number     = $instance['number'];
		$assignedto = $instance['assigned_to'];
		$deadline   = $instance['deadline'];
		$progress   = $instance['progress'];
		$category   = $instance['category'];

		if ( CTDL_Loader::$settings['list_view'] == '0' && $userdata->ID != NULL ) {
			if ( CTDL_Loader::$settings['assign'] == '0' )
				$author = "AND ( author = $userdata->ID || assign = $userdata->ID )";
			else
				$author = " AND author = $userdata->ID ";
		}
		echo $before_widget;

		if ( $title )
			echo $before_title . $title . $after_title;

		echo '<ol>';
		$table_name = $wpdb->prefix . 'todolist';
		$cat_table_name = $wpdb->prefix . 'todolist_cats';
		$sort = $cleverness_todo_option['sort_order'];

		if ( CTDL_Loader::$settings['categories'] == '0' ) {
			$sql = "SELECT * FROM $table_name WHERE status = 0 $author ORDER BY priority, $sort LIMIT $number";
		} else {
			if ( $category != 'All' )
				$sql = "SELECT * FROM $table_name WHERE status = 0 $author AND cat_id = $category ORDER BY priority, $sort LIMIT $number";
			else
				$sql = "SELECT * FROM $table_name LEFT JOIN $cat_table_name ON $table_name.cat_id = $cat_table_name.id WHERE status = 0 $author AND $cat_table_name.visibility = 0 ORDER BY cat_id, priority, $table_name.$sort LIMIT $number";
		}

		$results = $wpdb->get_results($sql);
		if ($results) {
			foreach ($results as $result) {

				if ( CTDL_Loader::$settings['categories'] == '1' && $category == 'All' ) {
					$cat = CTDL_Categories::get_category_name($result->cat_id);
					if ( $catid != $result->cat_id && $cat->name != '' ) echo '</ol><h4>'.$cat->name.'</h4><ol>';
					$catid = $result->cat_id;
				}

				echo '<li>'.stripslashes($result->todotext);
				if ( $result->progress != '' && $progress == true )
					echo ' - '.$result->progress.'%';
				if ( $deadline == true && $result->deadline != '' )
					echo '<br /><span class="deadline">'.__('Deadline: ', 'cleverness-to-do-list').$result->deadline.'</span>';
				if ( $assignedto == true && $result->assign != '') {
					$assign_user = '';
					if ( $result->assign != '-1' )
						$assign_user = get_userdata($result->assign);
					echo '<br /><span class="assigned">'.__('Assigned to ', 'cleverness-to-do-list').$assign_user->display_name.'</span>';
				}
				echo '</li>';
			}
		} else {
			echo '<li>'.__('No items to do.', 'cleverness-to-do-list').'</li>';
		}

		echo '</ol>';

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = $new_instance['number'];
		$instance['assigned_to'] = $new_instance['assigned_to'];
		$instance['deadline'] = $new_instance['deadline'];
		$instance['progress'] = $new_instance['progress'];
		$instance['category'] = $new_instance['category'];
		return $instance;
	}

	function form( $instance ) {
		global $wpdb;
		$defaults = array( 'title' => __('To-Do List', 'cleverness-to-do-list'), 'number' => '5', 'assigned_to' => false, 'deadline' => false, 'progress' => false, 'category' => 'All');
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'cleverness-to-do-list'); ?></label>
		<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e('Number of Items to Display:', 'cleverness-to-do-list'); ?></label>
		<select id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>">
			<option <?php if ( '1' == $instance['number'] ) echo 'selected="selected"'; ?>>1</option>
			<option <?php if ( '5' == $instance['number'] ) echo 'selected="selected"'; ?>>5</option>
			<option <?php if ( '10' == $instance['number'] ) echo 'selected="selected"'; ?>>10</option>
			<option <?php if ( '15' == $instance['number'] ) echo 'selected="selected"'; ?>>15</option>
			<option <?php if ( '20' == $instance['number'] ) echo 'selected="selected"'; ?>>20</option>
		</select>
		</p>

		<?php if ( CTDL_Loader::$settings['categories'] == '1' ) : ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'category' ); ?>"><?php _e('Category:', 'cleverness-to-do-list'); ?></label>
			<select id="<?php echo $this->get_field_id( 'category' ); ?>" name="<?php echo $this->get_field_name( 'category' ); ?>">
				<option value="All"<?php if ( 'All' == $instance['category'] ) echo ' selected="selected"'; ?>><?php _e('All', 'cleverness-to-do-list'); ?></option>
				<?php
				$cat_table_name = $wpdb->prefix . 'todolist_cats';
				$sql = "SELECT * FROM $cat_table_name ORDER BY name";
				$results = $wpdb->get_results($sql);
				if ($results) {
					foreach ($results as $result) { ?>
						<option value="<?php echo $result->id; ?>"<?php if ( $result->id == $instance['category'] ) echo ' selected="selected"'; ?>><?php echo $result->name; ?></option>
						<?php
					}
				}
				?>
			</select>
		</p>
		<?php endif; ?>

		<p>
		<input class="checkbox" type="checkbox" <?php checked( $instance['assigned_to'], on ); ?> id="<?php echo $this->get_field_id( 'assigned_to' ); ?>" name="<?php echo $this->get_field_name( 'assigned_to' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'assigned_to' ); ?>"><?php _e('Show Assigned To', 'cleverness-to-do-list'); ?></label>
		<br />
		<input class="checkbox" type="checkbox" <?php checked( $instance['deadline'], on ); ?> id="<?php echo $this->get_field_id( 'deadline' ); ?>" name="<?php echo $this->get_field_name( 'deadline' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'deadline' ); ?>"><?php _e('Show Deadline', 'cleverness-to-do-list'); ?></label>
		<br />
		<input class="checkbox" type="checkbox" <?php checked( $instance['progress'], on ); ?> id="<?php echo $this->get_field_id( 'progress' ); ?>" name="<?php echo $this->get_field_name( 'progress' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'progress' ); ?>"><?php _e('Show Progress', 'cleverness-to-do-list'); ?></label>
		</p>
	<?php
	}

}

add_action( 'widgets_init', create_function( '', 'register_widget("CTDL_Widget");' ) );
?>