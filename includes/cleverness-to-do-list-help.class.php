<?php
/**
 * Cleverness To-Do List Plugin Help
 *
 * Creates the help tabs and displays the help content
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.0
 */

/**
 * Help tabs class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Help {

	/**
	 * Creates the Help tab
	 * @static
	 * @return mixed
	 */
	public static function cleverness_todo_help_tab() {
		global $cleverness_todo_page, $cleverness_todo_cat_page, $cleverness_todo_settings_page;
		$screen = get_current_screen();

		$cleverness_todo_help_sidebar = '<p><strong>'.__( 'Like This Plugin?', 'cleverness-to-do-list' ).'</strong><br />
			<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=cindy@cleverness.org" target="_blank">'.__( 'Please Donate', 'cleverness-to-do-list' ).'</a></p>
			<p><a href="http://cleverness.org/plugins/to-do-list/" target="_blank">'.__( 'Plugin Website', 'cleverness-to-do-list' ).'</a></p>';

		if ( $screen->id != $cleverness_todo_page && $screen->id != $cleverness_todo_cat_page && $screen->id != $cleverness_todo_settings_page ) return;

		$screen->add_help_tab( array(
		'id'         => 'cleverness_todo_list_help_tab',
		'title'      => __( 'To-Do List Help' ),
		'callback'	 => __CLASS__.'::cleverness_todo_help',
		) );

		$screen->add_help_tab( array(
		'id'          => 'cleverness_todo_list_shortcode_tab',
		'title'       => __( 'Shortcodes' ),
		'callback'	  => __CLASS__.'::cleverness_todo_shortcodes_help',
		) );

		$screen->add_help_tab( array(
		'id'          => 'cleverness_todo_list_faqs_tab',
		'title'       => __( 'FAQs' ),
		'callback'	  => __CLASS__.'::cleverness_todo_faqs_help',
		) );

		if ( current_user_can( 'manage_options' ) ) {
			$screen->add_help_tab( array(
			'id'          => 'cleverness_todo_list_permissions_tab',
			'title'       => __( 'User Permissions' ),
			'callback'	  => __CLASS__.'::cleverness_todo_permissions_help',
			) );
		}

		$screen->set_help_sidebar( $cleverness_todo_help_sidebar );

	}

	/**
	 * Creates the main help content
	 * @static
	 */
	public static function cleverness_todo_help() { ?>
		<h3><?php _e( 'To-Do List', 'cleverness-to-do-list' ); ?></h3>
		<p><?php _e( 'This plugin provides users with a to-do list feature. You can configure the plugin to have private to-do lists for each user, for all users to share a to-do list, or a master list with individual completing of items. The shared to-do list has a variety of settings available. You can assign to-do items to a specific user (includes a setting to email a new to-do item to the assigned user) and have only those to-do items assigned viewable to a user. You can also assign different permission levels using capabilities. There are also settings to show deadline and progress fields. Category support is included as well as front-end administration.', 'cleverness-to-do-list' ); ?></p>
		<p><?php _e( 'A new menu item is added to manage your list and it is also listed on a dashboard widget. A sidebar widget is available as well as shortcode to display the to-do list items on your site. There are two shortcodes for front-end administration of the list. Management of categories is restricted to the back-end.', 'cleverness - to -do-list' ); ?></p>
	<?php }

	/**
	 * Creates the FAQs help section
	 * @static
	 */
	public static function cleverness_todo_faqs_help() { ?>
		<h3><?php _e( 'Frequently Asked Questions', 'cleverness-to-do-list' ); ?></h3>

		<p><strong><?php _e( 'I upgraded and all my to-do items are gone', 'cleverness-to-do-list' ); ?></strong><br/>
		<?php _e( 'If you did not do the automatic upgrade from the Plugins page, make sure you deactivate and then activate the plugin. Your to-do list items should be converted to the new format once you activate the plugin.', 'cleverness - to -do-list' ); ?></p>

		<p><strong><?php _e( 'What should I do if I find a bug?', 'cleverness-to-do-list' ); ?></strong><br/>
		<?php _e( 'Visit the plugin website and leave a comment or contact me.', 'cleverness-to-do-list' ); ?><br/>
		<a href="http://cleverness.org/plugins/to-do-list/#respond" target="_blank">http://cleverness.org/plugins/to-do-list/#respond</a><br/>
		<a href="http://cleverness.org/contact/" target="_blank">http://cleverness.org/contact/</a>
		</p>
	<?php }

	/**
	 * Creates the shortcode help section
	 * @static
	 */
	public static function cleverness_todo_shortcodes_help() { ?>
		<h3><?php _e( 'Shortcode Documentation', 'cleverness-to-do-list' ); ?></h3>
		<h4><?php _e( 'Display a List of To-Do Items', 'cleverness-to-do-list' ); ?></h4>
		<p><strong>&#91;todolist&#93;</strong></p>

		<ul>
			<li><strong>title</strong> &#8211; <?php _e( 'default is no title', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>type</strong> &#8211; <?php _e( 'you can chose <em>list</em> or <em>table</em> view. Default is <em>list</em>', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>priorities</strong> &#8211; <?php _e( 'default is <em>show</em>. Use a blank value to hide (only applies to table view)', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>assigned</strong> &#8211; <?php _e( 'default is <em>show</em>. Use a blank value to hide', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>deadline</strong> &#8211; <?php _e( 'default is <em>show</em>. Use a blank value to hide', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>progress</strong> &#8211; <?php _e( 'default is <em>show</em>. Use a blank value to hide', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>addedby</strong> &#8211; <?php _e( 'default is <em>show</em>. Use a blank value to hide', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>completed</strong> &#8211; <?php _e( 'default is blank. Set to <em>show</em> to display completed items', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>completed_title</strong> &#8211; <?php _e( 'default is no title', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>list_type</strong> &#8211; <?php _e( 'default is <em>ol</em> (ordered list). Use <em>ul</em> to show an unordered list', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>category</strong> &#8211; <?php _e( 'default is <em>all</em>. Use the category ID to show a specific category', 'cleverness-to-do-list' ); ?>.</li>
		</ul>

		<p><strong><?php _e( 'Example:', 'cleverness-to-do-list' ); ?></strong><br/>
		<?php _e( 'Table view with the title of Upcoming Articles and showing the progress and who the item was assigned to.', 'cleverness-to-do-list' ); ?><br/>
		&#91;todolist title="Upcoming Articles" type="table" priorities="" deadline="" addedby=""&#93;</p>

		<hr/>

		<h4><?php _e( 'Display a Checklist of To-Do Items', 'cleverness-to-do-list' ); ?></h4>
		<p><strong>&#91;todochecklist&#93;</strong></p>

		<ul>
			<li><strong>title</strong> &#8211; <?php _e( 'default is no title', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>priority</strong> &#8211; <?php _e( 'default is hide (0). Use 1 to show', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>assigned</strong> &#8211; <?php _e( 'default is hide (0). Use 1 to show', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>deadline</strong> &#8211; <?php _e( 'default is hide (0). Use 1 to show', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>progress</strong> &#8211; <?php _e( 'default is hide (0). Use 1 to show', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>categories</strong> &#8211; <?php _e( 'default is hide (0). Use 1 to show', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>addedby</strong> &#8211; <?php _e( 'default is hide (0). Use 1 to show', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>editlink</strong> &#8211; <?php _e( 'default is hide (0). Use 1 to show', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>todoid</strong> &#8211; <?php _e( 'default is blank (""). Use the ID of the to-do item to display just one item', 'cleverness-to-do-list' ); ?>.</li>
		</ul>
		<p><strong><?php _e( 'Example:', 'cleverness-to-do-list' ); ?></strong><br/>
		<?php _e( 'Set the title to "My To-Do List" and show the deadline and the category.', 'cleverness-to-do-list' ); ?><br/>
		&#91;todoadmin title="My To-Do List" deadline=1 categories=1&#93;</p>

		<hr/>

		<h4><?php _e( 'Display a To-Do List Administration Area', 'cleverness-to-do-list' ); ?></h4>
		<p><strong>&#91;todoadmin&#93;</strong></p>

		<ul>
			<li><strong>title</strong> &#8211; <?php _e( 'default is no title', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>priority</strong> &#8211; <?php _e( 'default is hide (0). Use 1 to show', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>assigned</strong> &#8211; <?php _e( 'default is hide (0). Use 1 to show', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>deadline</strong> &#8211; <?php _e( 'default is hide (0). Use 1 to show', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>progress</strong> &#8211; <?php _e( 'default is hide (0). Use 1 to show', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>categories</strong> &#8211; <?php _e( 'default is hide (0). Use 1 to show', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>addedby</strong> &#8211; <?php _e( 'default is hide (0). Use 1 to show', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong>editlink</strong> &#8211; <?php _e( 'default is show (1). Use 0 to hide', 'cleverness-to-do-list' ); ?>.</li>
		</ul>
		<p><strong><?php _e( 'Example:', 'cleverness-to-do-list' ); ?></strong><br/>
		<?php _e( 'Set the title to "Things to Do" and show the priority and the progress.', 'cleverness-to-do-list' ); ?><br/>
		&#91;todoadmin title="Things to Do" priority=1 progress=1&#93;</p>
		<?php
	}

	/**
	 * Creates the user permission help section
	 * @static
	 */
	public static function cleverness_todo_permissions_help() { ?>
		<h3><?php _e( 'Additional Information on User Permissions', 'cleverness-to-do-list' ); ?></h3>

		<ul>
			<li><strong><?php _e( 'View To-Do Item Capability', 'cleverness-to-do-list' ); ?></strong> &#8211; <?php _e( 'This allows the selected capability to view to-do items', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong><?php _e( 'Complete To-Do Item Capability', 'cleverness-to-do-list' ); ?></strong>
			&#8211; <?php _e( 'This allows the selected capability to mark to-do items as completed or uncompleted', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong><?php _e( 'Add To-Do Item Capability', 'cleverness-to-do-list' ); ?></strong> &#8211; <?php _e( 'This allows the selected capability to add new to-do items', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong><?php _e( 'Edit To-Do Item Capability', 'cleverness-to-do-list' ); ?></strong> &#8211; <?php _e( 'This allows the selected capability to edit existing to-do items', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong><?php _e( 'Assign To-Do Item Capability', 'cleverness-to-do-list' ); ?></strong>
			&#8211; <?php _e( 'This allows the selected capability to assign to-do items to individual users', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong><?php _e( 'View All Assigned Tasks Capability', 'cleverness-to-do-list' ); ?></strong>
			&#8211; <?php _e( 'This allows the selected capability to view all tasks even if <em>Show Each User Only Their Assigned Tasks</em> is set to <em>Yes</em>', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong><?php _e( 'Delete To-Do Item Capability', 'cleverness-to-do-list' ); ?></strong> &#8211; <?php _e( 'This allows the selected capability to delete individual to-do items', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong><?php _e( 'Purge To-Do Items Capability', 'cleverness-to-do-list' ); ?></strong>
			&#8211; <?php _e( 'This allows the selected capability to purge all the completed to-do items', 'cleverness-to-do-list' ); ?>.</li>
			<li><strong><?php _e( 'Add Categories Capability', 'cleverness-to-do-list' ); ?></strong> &#8211; <?php _e( 'This allows the selected capability to add new categories', 'cleverness-to-do-list' ); ?>.</li>
		</ul>

	<?php
	}

}
?>