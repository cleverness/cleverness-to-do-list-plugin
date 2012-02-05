<?php
/* Display Dashboard Widget
@todo make class non-static
@todo fix category title in widget
*/

class CTDL_Dashboard_Widget extends ClevernessToDoList {
	public static $dashboard_settings;

	public static function dashboard_widget() {
    	global $userdata, $current_user, $ClevernessToDoList;
		get_currentuserinfo();

		$catid = '';
		$cat_id = self::$dashboard_settings['dashboard_cat'];

		if ( CTDL_Loader::$settings['list_view'] == '2' ) {
			$user = $current_user->ID;
		} else {
			$user = $userdata->ID;
			}
		$limit = self::$dashboard_settings['dashboard_number'];

		// get to-do items
		$todo_items = CTDL_Lib::get_todos( $user, $limit, 0, $cat_id );

		if ( $todo_items->have_posts() ) {

			while ( $todo_items->have_posts() ) : $todo_items->the_post();
				$id = get_the_ID();
				$priority = get_post_meta( $id, '_priority', true );
				$priority_class = '';
				if ( $priority == '0' ) $priority_class = ' class="todo-important"';
				if ( $priority == '2' ) $priority_class = ' class="todo-low"';

				if ( CTDL_Loader::$settings['categories'] == '1' && $cat_id != 1 ) {
					$cats = get_the_terms( $id, 'todocategories' );
					if ( $cats != NULL ) {
						foreach( $cats as $category ) {
							if ( $catid != $category->term_id ) $ClevernessToDoList->list .= '<h4>'.$category->name.'</h4>';
							$catid = $category->term_id;
						}
					}
				}

				$ClevernessToDoList->list .= '<p id="todo-'.$id.'"><span'.$priority_class.'>';

				$ClevernessToDoList->show_checkbox( $id );
				$ClevernessToDoList->show_todo_text( get_the_content() );
				if ( ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '0' && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) ||  ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '1') && CTDL_Loader::$settings['assign'] == '0' ) {
					$assign_user = '';
					if ( get_post_meta( $id, '_assign', true ) != '-1' && get_post_meta( $id, '_assign', true ) != '' && get_post_meta( $id, '_assign', true ) != '0') {
						$assign_user = get_userdata( get_post_meta( $id, '_assign', true ) );
						$ClevernessToDoList->list .= ' <small>['.__( 'assigned to', 'cleverness-to-do-list' ).' ';
						$ClevernessToDoList->show_assigned( get_post_meta( $id, '_assign', true ) );
						$ClevernessToDoList->list .= ']</small>';
					}
				}
				if ( self::$dashboard_settings['show_dashboard_deadline'] == '1' && get_post_meta( $id, '_deadline', true ) != '' ) {
					$ClevernessToDoList->list .=  ' <small>['.__( 'Deadline:', 'cleverness-to-do-list' );
					$ClevernessToDoList->show_deadline( get_post_meta( $id, '_deadline', true ) );
					$ClevernessToDoList->list .= ']</small>';
				}
				if ( CTDL_Loader::$settings['show_progress'] == '1' && get_post_meta( $id, '_progress', true ) != '' ) {
					$ClevernessToDoList->list .= ' <small>[';
					$ClevernessToDoList->show_progress( get_post_meta( $id, '_progress', true ) );
					$ClevernessToDoList->list .= ']</small>';
					}
				if ( CTDL_Loader::$settings['list_view'] == '1' && self::$dashboard_settings['dashboard_author'] == '0' ) {
					if ( get_the_author() != '0') {
						$ClevernessToDoList->list .= ' <small>- '.__('added by', 'cleverness-to-do-list').' ';
						$ClevernessToDoList->show_addedby( get_the_author() );
						$ClevernessToDoList->list .= '</small>';
					}
				}

				if ( current_user_can( CTDL_Loader::$settings['edit_capability']) || CTDL_Loader::$settings['list_view'] == '0' )
					$ClevernessToDoList->list .= ' <small>(<a href="admin.php?page=cleverness-to-do-list&amp;action=edit-todo&amp;id='.$id.'">'.__( 'Edit', 'cleverness-to-do-list' ).'</a>)</small>';

				$ClevernessToDoList->list .= '</span></p>';

			endwhile;

			echo $ClevernessToDoList->list;

		} else {
			echo '<p>'.__( 'No items to do.', 'cleverness-to-do-list' ).'</p>';
		}

		$cleverness_todo_permission = CTDL_Lib::check_permission( 'todo', 'add' );
		if ( $cleverness_todo_permission === true ) {
			echo '<p style="text-align: right">'. '<a href="admin.php?page=cleverness-to-do-list#addtodo">'.__( 'New To-Do Item &raquo;', 'cleverness-to-do-list' ).'</a></p>';
		}

	}

	/* Dashboard Widget Options */
	public static function dashboard_options() {
		$cleverness_todo_dashboard_settings = self::$dashboard_settings;
		if ( isset( $_POST['cleverness_todo_dashboard_settings'] ) ) {
			$cleverness_todo_dashboard_settings = $_POST['cleverness_todo_dashboard_settings'];
			update_option( 'cleverness_todo_dashboard_settings', $cleverness_todo_dashboard_settings );
		}
   	    settings_fields( 'cleverness-todo-dashboard-settings-group' );
 	    $options = get_option( 'cleverness_todo_dashboard_settings' );
		$cat_id = $options['dashboard_cat'];
		?>
		<fieldset>
  		    <p><label for="cleverness_todo_dashboard_settings[dashboard_number]"><?php _e( 'Number of List Items to Show', 'cleverness-to-do-list' ); ?></label>
				<select id="cleverness_todo_dashboard_settings[dashboard_number]" name="cleverness_todo_dashboard_settings[dashboard_number]">
					<option value="1"<?php if ( $options['dashboard_number'] == '1' ) echo ' selected="selected"'; ?>><?php _e( '1', 'cleverness-to-do-list' ); ?></option>
					<option value="5"<?php if ( $options['dashboard_number'] == '5' ) echo ' selected="selected"'; ?>><?php _e( '5', 'cleverness-to-do-list' ); ?></option>
					<option value="10"<?php if ( $options['dashboard_number'] == '10' ) echo ' selected="selected"'; ?>><?php _e( '10', 'cleverness-to-do-list' ); ?></option>
					<option value="15"<?php if ( $options['dashboard_number'] == '15' ) echo ' selected="selected"'; ?>><?php _e( '15', 'cleverness-to-do-list' ); ?></option>
					<option value="20"<?php if ( $options['dashboard_number'] == '20' ) echo ' selected="selected"'; ?>><?php _e( '20', 'cleverness-to-do-list' ); ?>&nbsp;</option>
				</select>
			</p>

			<p><label for="cleverness_todo_dashboard_settings[show_dashboard_deadline]"><?php _e( 'Show Deadline', 'cleverness-to-do-list' ); ?></label>
				<select id="cleverness_todo_dashboard_settings[show_dashboard_deadline]" name="cleverness_todo_dashboard_settings[show_dashboard_deadline]">
					<option value="0"<?php if ( $options['show_dashboard_deadline'] == '0' ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?></option>
					<option value="1"<?php if ( $options['show_dashboard_deadline'] == '1' ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
				</select>
			</p>

	 	    <p><label for="cleverness_todo_dashboard_settings[dashboard_cat]"><?php _e( 'Category', 'cleverness-to-do-list' ); ?></label>
			     <?php wp_dropdown_categories( 'taxonomy=todocategories&echo=1&orderby=name&hide_empty=0&show_option_all='.__( 'All', 'cleverness-to-do-list' ).'&id=cleverness_todo_dashboard_settings[dashboard_cat]&name=cleverness_todo_dashboard_settings[dashboard_cat]&selected='.$cat_id ); ?>
			</p>

			<p class="description"><?php _e( 'This setting is only used when <em>List View</em> is set to <em>Group</em>.', 'cleverness-to-do-list' ); ?></p>
   		    <p><label for="cleverness_todo_dashboard_settings[dashboard_author]"><?php _e( 'Show <em>Added By</em> on Dashboard Widget', 'cleverness-to-do-list' ); ?></label>
				<select id="cleverness_todo_dashboard_settings[dashboard_author]" name="cleverness_todo_dashboard_settings[dashboard_author]">
					<option value="0"<?php if ( $options['dashboard_author'] == '0' ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list') ; ?>&nbsp;</option>
					<option value="1"<?php if ( $options['dashboard_author'] == '1' ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?></option>
				</select>
   		    </p>
		</fieldset>
		<?php
	}

	/* Add Dashboard Widget */
	public static function dashboard_setup() {
		$cleverness_todo_permission = CTDL_Lib::check_permission( 'todo', 'view' );
		if ( $cleverness_todo_permission === true ) {
			self::$dashboard_settings = get_option( 'cleverness_todo_dashboard_settings' );
			wp_add_dashboard_widget( 'cleverness_todo', __(  'To-Do List', 'cleverness-to-do-list' ).' <a href="admin.php?page=cleverness-to-do-list">'. __( '&raquo;', 'cleverness-to-do-list' ).'</a>', __CLASS__.'::dashboard_widget', __CLASS__.'::dashboard_options' );
			}
		}

	public static function dashboard_init() {
		wp_register_script( 'cleverness_todo_dashboard_complete_js', CTDL_PLUGIN_URL.'/js/complete-todo.js', '', 1.0, true );
		add_action( 'admin_print_scripts-index.php',  __CLASS__.'::dashboard_add_js' );
		add_action( 'wp_ajax_cleverness_todo_dashboard_complete', 'CTDL_Lib::complete_todo_callback' );
		add_action( 'admin_print_styles-index.php', 'CTDL_Loader::add_admin_css' );
	}

	public static function dashboard_add_js() {
		wp_enqueue_script( 'cleverness_todo_dashboard_complete_js' );
		wp_localize_script( 'cleverness_todo_dashboard_complete_js', 'ctdl', CTDL_Loader::get_js_vars() );
    }

}
?>