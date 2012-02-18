<?php
/**
 * Cleverness To-Do List Plugin Widget
 *
 * Creates the to-do list widget
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.0
 * @todo don't show category if private
 * @todo add option to check off items
 */

/**
 * Widget class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Widget extends WP_Widget {

	function __construct() {
		parent::WP_Widget( 'cleverness-to-do-widget', __( 'To-Do List', 'cleverness-to-do-list' ), array( 'description' => __( 'Displays To-Do List Items', 'cleverness-to-do-list' ) ) );
	}

	/**
	 * Creates the widget
	 * @param $args
	 * @param $instance
	 */
	function widget( $args, $instance ) {
		global $current_user, $userdata, $ClevernessToDoList;
		get_currentuserinfo();
		extract( $args );

		$title      = apply_filters( 'widget_title', $instance['title'] );
		$limit      = $instance['number'];
		$assignedto = $instance['assigned_to'];
		$deadline   = $instance['deadline'];
		$progress   = $instance['progress'];
		$category   = $instance['category'];
		$cat_id     = '';
		$layout     = 'list';

		if ( CTDL_Loader::$settings['list_view'] == '2' ) {
			$user = $current_user->ID;
		} else {
			$user = $userdata->ID;
		}

		echo $before_widget;

		if ( $title ) echo $before_title . $title . $after_title;

		echo '<ol>';

		// get to-do items
		$todo_items = CTDL_Lib::get_todos( $user, $limit, 0, $category );

		if ( $todo_items->have_posts() ) {

			while ( $todo_items->have_posts() ) : $todo_items->the_post();
				$id = get_the_ID();

				/* @todo category titles not all showing up */
				if ( CTDL_Loader::$settings['categories'] == '1' && $category == '0' ) {
					$cats = get_the_terms( $id, 'todocategories' );
					if ( $cats != NULL ) {
						foreach( $cats as $category ) {
							if ( $cat_id != $category->term_id ) {
								$ClevernessToDoList->list .= '</ol><h4>'.esc_attr( $category->name ).'</h4><ol>';
								$cat_id = $category->term_id;
							}
						}
					}
				}

				$ClevernessToDoList->list .= '<li>';
				$ClevernessToDoList->show_todo_text( get_the_content(), $layout );
				if ( $progress == 1  && get_post_meta( $id, '_progress', true ) != '' ) {
					$ClevernessToDoList->list .= ' - ';
					$ClevernessToDoList->show_progress( get_post_meta( $id, '_progress', true ), $layout );
				}
				if ( $deadline == 1 && get_post_meta( $id, '_deadline', true ) != '' ) {
					$ClevernessToDoList->list .= '<br /><span class="deadline">'.__('Deadline: ', 'cleverness-to-do-list');
					$ClevernessToDoList->show_deadline( get_post_meta( $id, '_deadline', true ), $layout );
					$ClevernessToDoList->list .= '</span>';
				}
				if ( $assignedto == 1 && CTDL_Loader::$settings['list_view'] != '2' && get_post_meta( $id, '_assign', true ) != -1 ) {
					$ClevernessToDoList->list .= '<br /><span class="assigned">'.__('Assigned to ', 'cleverness-to-do-list');
					$ClevernessToDoList->show_assigned( get_post_meta( $id, '_assign', true ), $layout );
					$ClevernessToDoList->list .= '</span>';
				}
				$ClevernessToDoList->list .= '</li>';
			endwhile;

			echo $ClevernessToDoList->list;

		} else {
			echo '<p>'.__( 'No items to do.', 'cleverness-to-do-list' ).'</p>';
		}

		echo '</ol>';

		echo $after_widget;
	}

	/**
	 * Updates the widget settings
	 * @param $new_instance
	 * @param $old_instance
	 * @return array
	 */
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

	/**
	 * Creates the form for the widget settings
	 * @param $instance
	 */
	function form( $instance ) {
		$defaults = array( 'title' => __('To-Do List', 'cleverness-to-do-list'), 'number' => '5', 'assigned_to' => false, 'deadline' => false, 'progress' => false, 'category' => 'All');
		$instance = wp_parse_args( ( array ) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'cleverness-to-do-list' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of Items to Display:', 'cleverness-to-do-list' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>">
				<option <?php if ( '1' == $instance['number'] ) echo 'selected="selected"'; ?>>1</option>
				<option <?php if ( '5' == $instance['number'] ) echo 'selected="selected"'; ?>>5</option>
				<option <?php if ( '10' == $instance['number'] ) echo 'selected="selected"'; ?>>10</option>
				<option <?php if ( '15' == $instance['number'] ) echo 'selected="selected"'; ?>>15</option>
				<option <?php if ( '20' == $instance['number'] ) echo 'selected="selected"'; ?>>20</option>
				<option <?php if ( '-1' == $instance['number'] ) echo 'selected="selected"'; ?> value="-1"><?php _e( 'All', 'cleverness-to-do-list' ); ?></option>
			</select>
		</p>

		<?php if ( CTDL_Loader::$settings['categories'] == '1' ) : ?>
			<p>
				<label for="<?php echo $this->get_field_id( 'category' ); ?>"><?php _e( 'Category:', 'cleverness-to-do-list' ); ?></label>
				<?php wp_dropdown_categories( 'taxonomy=todocategories&echo=1&orderby=name&hide_empty=0&show_option_all='.__( 'All', 'cleverness-to-do-list' ).
					'&id='.$this->get_field_id( 'category' ).'&name='.$this->get_field_name( 'category' ).'&selected='.$instance['category'] ); ?>
			</p>
		<?php endif; ?>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['assigned_to'], true ); ?> value="1" id="<?php echo $this->get_field_id( 'assigned_to' ); ?>" name="<?php echo $this->get_field_name( 'assigned_to' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'assigned_to' ); ?>"><?php _e( 'Show Assigned To', 'cleverness-to-do-list' ); ?></label>
			<br />
			<input class="checkbox" type="checkbox" <?php checked( $instance['deadline'] ); ?> value="1" id="<?php echo $this->get_field_id( 'deadline' ); ?>" name="<?php echo $this->get_field_name( 'deadline' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'deadline' ); ?>"><?php _e( 'Show Deadline', 'cleverness-to-do-list' ); ?></label>
			<br />
			<input class="checkbox" type="checkbox" <?php checked( $instance['progress'] ); ?> value="1" id="<?php echo $this->get_field_id( 'progress' ); ?>" name="<?php echo $this->get_field_name( 'progress' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'progress' ); ?>"><?php _e( 'Show Progress', 'cleverness-to-do-list' ); ?></label>
		</p>
		<?php
	}

}

add_action( 'widgets_init', create_function( '', 'register_widget( "CTDL_Widget" );' ) );
?>