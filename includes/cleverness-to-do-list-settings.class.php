<?php
/**
 * Cleverness To-Do List Plugin Settings
 *
 * Creates the settings and page to manage the plugin settings
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.0
 */

/**
 * Settings class, based on class from link
 * @package cleverness-to-do-list
 * @subpackage includes
 * @link http://theme.fm/2011/10/how-to-create-tabs-with-the-settings-api-in-wordpress-2590/
*/
class CTDL_Settings {
	private $general_key = 'CTDL_general';
	private $advanced_key = 'CTDL_advanced';
	private $permissions_key = 'CTDL_permissions';
	private $plugin_key = 'cleverness-to-do-list-settings';
	private $plugin_tabs = array();
	private $general_settings = array();
	private $advanced_settings = array();
	private $permission_settings = array();

	function __construct() {
		add_action( 'admin_init', array( &$this, 'load_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_general_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_advanced_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_permission_settings' ) );
		add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
	}

	function load_settings() {
		$this->general_settings = get_option( $this->general_key );
		$this->advanced_settings = get_option( $this->advanced_key );
		$this->permission_settings = get_option( $this->permissions_key );
	}

	function section_general_desc() {
	}

	function section_advanced_desc() {
		echo '<strong>';
		_e( 'Customize the To-Do List', 'cleverness-to-do-list' );
		echo '</strong>';
	}

	function section_advanced_assign_desc() {
		echo '<strong>';
		_e( 'Configure these settings to be able to assign to-do items to other users.', 'cleverness-to-do-list' );
		echo '</strong>';
	}

	function section_advanced_db_desc() {
		_e( 'If you have recently upgraded and your to-do items are all still visible, you can delete the custom database tables since they are no longer used. You can also delete all your to-do items here.', 'cleverness-to-do-list' );
		echo '<br /><strong><em>'.__( 'These actions cannot be undone. Please be sure you want to proceed. It is advised that you back up your database first.', 'cleverness-to-do-list' ).'</em></strong>';
	}


	function section_permission_desc() {
		_e( 'You should chose the highest level capabilities that the users you want to be able to preform that action will have.', 'cleverness-to-do-list' );
		echo '<br />';
		_e( 'The default general capabilities of each user role are as follows: ', 'cleverness-to-do-list' );
		echo '<br />';
		_e( 'Contributors: Edit Posts, Authors: Publish Posts, Editors: Edit Others Posts, Administrators: Manage Options', 'cleverness-to-do-list' );
		echo '<br /><br /><em>';
		_e( 'When using the Master list type non-administrator users should only be allowed to view and complete items, otherwise they will be able to edit the Master list.', 'cleverness-to-do-list' );
		echo '</em>';
	}

	function register_general_settings() {
		$this->plugin_tabs[$this->general_key] = __( 'To-Do List Settings', 'cleverness-to-do-list' );

		register_setting( $this->general_key, $this->general_key );
		add_settings_section( 'section_general', __( 'To-Do List Settings', 'cleverness-to-do-list' ), array( &$this, 'section_general_desc' ), $this->general_key );
		add_settings_field( 'categories', __( 'Categories', 'cleverness-to-do-list' ), array( &$this, 'categories_option' ), $this->general_key, 'section_general' );
		add_settings_field( 'list_view', __( 'List View', 'cleverness-to-do-list' ), array( &$this, 'list_view_option' ), $this->general_key, 'section_general' );
		add_settings_field( 'todo_author', __( 'Show Added By', 'cleverness-to-do-list' ), array( &$this, 'todo_author_option' ), $this->general_key, 'section_general' );
		add_settings_field( 'show_completed_date', __( 'Show Date Completed', 'cleverness-to-do-list' ), array( &$this, 'show_completed_date_option' ), $this->general_key, 'section_general' );
		add_settings_field( 'show_deadline', __( 'Show Deadline', 'cleverness-to-do-list' ), array( &$this, 'show_deadline_option' ), $this->general_key, 'section_general' );
		add_settings_field( 'show_progress', __( 'Show Progress', 'cleverness-to-do-list' ), array( &$this, 'show_progress_option' ), $this->general_key, 'section_general' );
		add_settings_field( 'sort_order', __( 'Sort Order', 'cleverness-to-do-list' ), array( &$this, 'sort_order_option' ), $this->general_key, 'section_general' );
		add_settings_field( 'admin_bar', __( 'Show Admin Bar Menu', 'cleverness-to-do-list' ), array( &$this, 'admin_bar_option' ), $this->general_key, 'section_general' );
	}

	function categories_option() { ?>
		<select name="<?php echo $this->general_key; ?>[categories]">
			<option value="0"<?php if ( $this->general_settings['categories'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'Disabled', 'cleverness-to-do-list' ); ?>&nbsp;</option>
			<option value="1"<?php if ( $this->general_settings['categories'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'Enabled', 'cleverness-to-do-list' ); ?></option>
		</select>
		<span class="description"><?php _e( 'If you would like to organize your to-do list into categories, enable it here.', 'cleverness-to-do-list' ); ?></span>
	<?php
	}

	function list_view_option() { ?>
		<select name="<?php echo $this->general_key; ?>[list_view]">
			<option value="0"<?php if ( $this->general_settings['list_view'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'Individual', 'cleverness-to-do-list' ); ?>&nbsp;</option>
			<option value="1"<?php if ( $this->general_settings['list_view'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'Group', 'cleverness-to-do-list' ); ?></option>
			<option value="2"<?php if ( $this->general_settings['list_view'] == 2 ) echo ' selected="selected"'; ?>><?php _e( 'Master', 'cleverness-to-do-list' ); ?></option>
		</select>
		<span class="description"><?php _e( 'List View sets how the to-do lists are viewed.<br /> The Individual setting allows each user to have their own private to-do list.
		The Group setting allows all users to share one to-do list. The Master setting allows you to have one master list for all users with individual completion of items.', 'cleverness-to-do-list' ); ?></span>
	<?php }

	function show_deadline_option() { ?>
		<select name="<?php echo $this->general_key; ?>[show_deadline]">
			<option value="0"<?php if ( $this->general_settings['show_deadline'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?></option>
			<option value="1"<?php if ( $this->general_settings['show_deadline'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
		</select>
	<?php }

	function show_progress_option() { ?>
		<select name="<?php echo $this->general_key; ?>[show_progress]">
			<option value="0"<?php if ( $this->general_settings['show_progress'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?></option>
			<option value="1"<?php if ( $this->general_settings['show_progress'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
		</select>
	<?php }

	function show_completed_date_option() { ?>
		<select name="<?php echo $this->general_key; ?>[show_completed_date]">
			<option value="0"<?php if ( $this->general_settings['show_completed_date'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?></option>
			<option value="1"<?php if ( $this->general_settings['show_completed_date'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
		</select>
	<?php }

	function todo_author_option() { ?>
		<select name="<?php echo $this->general_key; ?>[todo_author]">
			<option value="0"<?php if ( $this->general_settings['todo_author'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
			<option value="1"<?php if ( $this->general_settings['todo_author'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?></option>
		</select>
		<span class="description"><?php _e( 'This setting is only used when List View is set to Group.', 'cleverness-to-do-list' ); ?></span>
	<?php
	}

	function sort_order_option() { ?>
		<select name="<?php echo $this->general_key; ?>[sort_order]">
			<option value="ID"<?php if ( $this->general_settings['sort_order'] == 'ID' ) echo ' selected="selected"'; ?>><?php _e( 'ID', 'cleverness-to-do-list' ); ?></option>
			<option value="title"<?php if ( $this->general_settings['sort_order'] == 'title' ) echo ' selected="selected"'; ?>><?php _e( 'Alphabetical', 'cleverness-to-do-list' ); ?>&nbsp;</option>
			<option value="_deadline"<?php if ( $this->general_settings['sort_order'] == '_deadline' ) echo ' selected="selected"'; ?>><?php _e( 'Deadline', 'cleverness-to-do-list' ); ?></option>
			<option value="_progress"<?php if ( $this->general_settings['sort_order'] == '_progress' ) echo ' selected="selected"'; ?>><?php _e( 'Progress', 'cleverness-to-do-list' ); ?></option>
			<option value="cat_id"<?php if ( $this->general_settings['sort_order'] == 'cat_id' ) echo ' selected="selected"'; ?>><?php _e( 'Category', 'cleverness-to-do-list' ); ?></option>
			<option value="_assign"<?php if ( $this->general_settings['sort_order'] == '_assign' ) echo ' selected="selected"'; ?>><?php _e( 'Assigned User', 'cleverness-to-do-list' ); ?></option>
			<option value="post_date"<?php if ( $this->general_settings['sort_order'] == 'post_date' ) echo ' selected="selected"'; ?>><?php _e( 'Date Created', 'cleverness-to-do-list' ); ?></option>
		</select>
		<span class="description"><?php _e( 'Items are first sorted by priority when ordered by ID, Alphabetical, or Category', 'cleverness-to-do-list' ); ?></span>
	<?php }

	function admin_bar_option() {
		?>
	<select name="<?php echo $this->general_key; ?>[admin_bar]">
		<option value="1"<?php if ( $this->general_settings['admin_bar'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
		<option value="0"<?php if ( $this->general_settings['admin_bar'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?></option>
	</select>
	<?php
	}

	function register_advanced_settings() {
		$this->plugin_tabs[$this->advanced_key] = __( 'Advanced Settings', 'cleverness-to-do-list' );

		register_setting( $this->advanced_key, $this->advanced_key );
		add_settings_section( 'section_advanced', __( 'To-Do List Advanced Settings', 'cleverness-to-do-list' ), array( &$this, 'section_advanced_desc' ), $this->advanced_key );
		add_settings_field( 'date_format', __( 'Date Format', 'cleverness-to-do-list' ), array( &$this, 'date_format_option' ), $this->advanced_key, 'section_advanced' );
		add_settings_field( 'priority_0', __( 'Highest Priority Label', 'cleverness-to-do-list' ), array( &$this, 'priority_0_option' ), $this->advanced_key, 'section_advanced' );
		add_settings_field( 'priority_1', __( 'Middle Priority Label', 'cleverness-to-do-list' ), array( &$this, 'priority_1_option' ), $this->advanced_key, 'section_advanced' );
		add_settings_field( 'priority_2', __( 'Lowest Priority Label', 'cleverness-to-do-list' ), array( &$this, 'priority_2_option' ), $this->advanced_key, 'section_advanced' );
		add_settings_field( 'show_id', __( 'Show To-Do Item ID', 'cleverness-to-do-list' ), array ( &$this, 'show_id_option' ), $this->advanced_key, 'section_advanced' );
		add_settings_field( 'show_date_added', __( 'Show Date To-Do Was Added', 'cleverness-to-do-list' ), array ( &$this, 'show_date_added_option' ), $this->advanced_key, 'section_advanced' );
		add_settings_section( 'section_advanced_assign', __( 'Assign To-Do Items Settings', 'cleverness-to-do-list' ), array( &$this, 'section_advanced_assign_desc' ), $this->advanced_key );
		add_settings_field( 'assign', __( 'Assign To-Do Items to Users', 'cleverness-to-do-list' ), array( &$this, 'assign_option' ), $this->advanced_key, 'section_advanced_assign' );
		add_settings_field( 'show_only_assigned', __( 'Show a User Only the To-Do Items Assigned to Them', 'cleverness-to-do-list' ), array( &$this, 'show_only_assigned_option' ), $this->advanced_key,
			'section_advanced_assign' );
		add_settings_field( 'user_roles', __( 'User Roles Allowed', 'cleverness-to-do-list' ), array( &$this, 'user_roles_option' ), $this->advanced_key, 'section_advanced_assign' );
		add_settings_field( 'email_assigned', __( 'Email Assigned To-Do Items to User', 'cleverness-to-do-list' ), array( &$this, 'email_assigned_option' ), $this->advanced_key, 'section_advanced_assign' );
		add_settings_field( 'email_category', __( 'Add Category to Subject', 'cleverness-to-do-list' ), array( &$this, 'email_category_option' ), $this->advanced_key, 'section_advanced_assign' );
		add_settings_field( 'email_show_assigned_by', __( 'Show Who Assigned the To-Do Item in Email', 'cleverness-to-do-list' ), array( &$this, 'email_show_assigned_by_option' ), $this->advanced_key, 'section_advanced_assign' );
		add_settings_field( 'email_from', __( 'From Field for Emails Sent to User', 'cleverness-to-do-list' ), array( &$this, 'email_from_option' ), $this->advanced_key, 'section_advanced_assign' );
		add_settings_field( 'email_subject', __( 'Subject Field for Emails Sent to User', 'cleverness-to-do-list' ), array( &$this, 'email_subject_option' ), $this->advanced_key,
			'section_advanced_assign' );
		add_settings_field( 'email_text', __( 'Text in Emails Sent to User', 'cleverness-to-do-list' ), array( &$this, 'email_text_option' ), $this->advanced_key, 'section_advanced_assign' );
		add_settings_section( 'section_advanced_database', __( 'Database Cleanup', 'cleverness-to-do-list' ), array( &$this, 'section_advanced_db_desc' ), $this->advanced_key );
	}

	function date_format_option() { ?>
		<input class="small-text" type="text" name="<?php echo $this->advanced_key; ?>[date_format]" value="<?php if ( $this->advanced_settings['date_format'] != '' ) echo $this->advanced_settings['date_format']; else
			echo 'm/d/Y';
			?>"	/><br />
		<a href="http://codex.wordpress.org/Formatting_Date_and_Time"><?php _e( 'Documentation on Date Formatting', 'cleverness-to-do-list' ); ?></a>
	<?php }

	function priority_0_option() { ?>
		<input type="text" name="<?php echo $this->advanced_key; ?>[priority_0]" value="<?php echo $this->advanced_settings['priority_0']; ?>" />
		<span class="description"><?php _e( 'The highest priority list items are shown in red in the lists.', 'cleverness-to-do-list' ); ?></span>
	<?php }

	function priority_1_option() { ?>
		<input type="text" name="<?php echo $this->advanced_key; ?>[priority_1]" value="<?php echo $this->advanced_settings['priority_1']; ?>" />
	<?php }

	function priority_2_option() { ?>
		<input type="text" name="<?php echo $this->advanced_key; ?>[priority_2]" value="<?php echo $this->advanced_settings['priority_2']; ?>" />
		<span class="description"><?php _e( 'The lowest priority list items are shown in a lighter grey.', 'cleverness-to-do-list' ); ?></span>
	<?php }

	function show_id_option() { ?>
	<select name="<?php echo $this->advanced_key; ?>[show_id]">
		<option value="0"<?php if ( $this->advanced_settings['show_id'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?>&nbsp;</option>
		<option value="1"<?php if ( $this->advanced_settings['show_id'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list' ); ?></option>
	</select>
	<?php
	}

	function show_date_added_option() { ?>
	<select name="<?php echo $this->advanced_key; ?>[show_date_added]">
		<option value="0"<?php if ( $this->advanced_settings['show_date_added'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?>&nbsp;</option>
		<option value="1"<?php if ( $this->advanced_settings['show_date_added'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list' ); ?></option>
	</select>
	<?php
	}

	function assign_option() { ?>
		<select name="<?php echo $this->advanced_key; ?>[assign]">
			<option value="0"<?php if ( $this->advanced_settings['assign'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
			<option value="1"<?php if ( $this->advanced_settings['assign'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?></option>
		</select>
		<span class="description"><?php _e( 'This setting must be set to Yes for the following settings to work.', 'cleverness-to-do-list' ); ?></span>
	<?php
	}

	function show_only_assigned_option() { ?>
		<select name="<?php echo $this->advanced_key; ?>[show_only_assigned]">
			<option value="0"<?php if ( $this->advanced_settings['show_only_assigned'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list' ); ?>&nbsp;</option>
			<option value="1"<?php if ( $this->advanced_settings['show_only_assigned'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?></option>
		</select>
	<?php
	}

	function user_roles_option() { ?>
		<input class="regular-text" type="text" name="<?php echo $this->advanced_key; ?>[user_roles]" value="<?php if ( $this->advanced_settings['user_roles'] != '' )
		echo $this->advanced_settings['user_roles']; else echo 'contributor, author, editor, administrator'; ?>" />
		<span class="description"><?php _e( ' Separate each role with a comma.', 'cleverness-to-do-list' ); ?></span><br />
		<span class="description"><?php _e( 'Used in displaying the list of users who can be assigned to-do items.', 'cleverness-to-do-list' ); ?></span><br/>
		<a href="http://codex.wordpress.org/Roles_and_Capabilities"><?php _e( 'Documentation on User Roles', 'cleverness-to-do-list' ); ?></a>
	<?php
	}

	function email_assigned_option() { ?>
		<select name="<?php echo $this->advanced_key; ?>[email_assigned]">
			<option value="0"<?php if ( $this->advanced_settings['email_assigned'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?>&nbsp;</option>
			<option value="1"<?php if ( $this->advanced_settings['email_assigned'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list' ); ?></option>
		</select>
	<?php
	}

	function email_category_option() { ?>
		<select name="<?php echo $this->advanced_key; ?>[email_category]">
			<option value="0"<?php if ( $this->advanced_settings['email_category'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?>&nbsp;</option>
			<option value="1"<?php if ( $this->advanced_settings['email_category'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list' ); ?></option>
		</select>
		<span class="description"><?php _e( 'If categories are enabled.', 'cleverness-to-do-list' ); ?></span>
	<?php
	}

	function email_show_assigned_by_option() { ?>
	<select name="<?php echo $this->advanced_key; ?>[email_show_assigned_by]">
		<option value="0"<?php if ( $this->advanced_settings['email_show_assigned_by'] == 0 ) echo ' selected="selected"'; ?>><?php _e( 'No', 'cleverness-to-do-list' ); ?>&nbsp;</option>
		<option value="1"<?php if ( $this->advanced_settings['email_show_assigned_by'] == 1 ) echo ' selected="selected"'; ?>><?php _e( 'Yes', 'cleverness-to-do-list' ); ?></option>
	</select>
	<?php
	}

	function email_from_option() { ?>
		<input class="regular-text" type="text" name="<?php echo $this->advanced_key; ?>[email_from]" value="<?php echo $this->advanced_settings['email_from']; ?>" />
	<?php
	}

	function email_subject_option() { ?>
		<input class="regular-text" type="text" name="<?php echo $this->advanced_key; ?>[email_subject]" value="<?php echo $this->advanced_settings['email_subject']; ?>" />
	<?php
	}

	function email_text_option() { ?>
		<textarea name="<?php echo $this->advanced_key; ?>[email_text]" rows="3" cols="70"><?php echo $this->advanced_settings['email_text']; ?></textarea>
	<?php
	}

	function register_permission_settings() {
		$this->plugin_tabs[$this->permissions_key] = __( 'User Permissions', 'cleverness-to-do-list' );

		register_setting( $this->permissions_key, $this->permissions_key );
		add_settings_section( 'section_permission', __( 'To-Do List User Permissions for Group and Master List Types', 'cleverness-to-do-list' ), array( &$this, 'section_permission_desc' ), $this->permissions_key );
		add_settings_field( 'view_capability', __( 'View To-Do Item Capability', 'cleverness-to-do-list' ), array( &$this, 'permission_option' ), $this->permissions_key, 'section_permission', array( 'label_for' => 'view_capability' ) );
		add_settings_field( 'complete_capability', __( 'Complete To-Do Item Capability', 'cleverness-to-do-list' ), array( &$this, 'permission_option' ), $this->permissions_key, 'section_permission', array( 'label_for' => 'complete_capability' ) );
		add_settings_field( 'add_capability', __( 'Add To-Do Item Capability', 'cleverness-to-do-list' ), array( &$this, 'permission_option' ), $this->permissions_key, 'section_permission', array( 'label_for' => 'add_capability' ) );
		add_settings_field( 'edit_capability', __( 'Edit To-Do Item Capability', 'cleverness-to-do-list' ), array( &$this, 'permission_option' ), $this->permissions_key, 'section_permission', array( 'label_for' => 'edit_capability' ) );
		add_settings_field( 'assign_capability', __( 'Assign To-Do Item Capability', 'cleverness-to-do-list' ), array( &$this, 'permission_option' ), $this->permissions_key, 'section_permission', array( 'label_for' => 'assign_capability' ) );
		add_settings_field( 'view_all_assigned_capability', __( 'View To-Do Items Assigned to Other Users Capability', 'cleverness-to-do-list' ), array( &$this, 'permission_option' ), $this->permissions_key, 'section_permission', array( 'label_for' => 'view_all_assigned_capability' ) );
		add_settings_field( 'delete_capability', __( 'Delete To-Do Item Capability', 'cleverness-to-do-list' ), array( &$this, 'permission_option' ), $this->permissions_key, 'section_permission', array( 'label_for' => 'delete_capability' ) );
		add_settings_field( 'purge_capability', __( 'Delete All To-Do Items Capability', 'cleverness-to-do-list' ), array( &$this, 'permission_option' ), $this->permissions_key, 'section_permission', array( 'label_for' => 'purge_capability' ) );
		add_settings_field( 'add_cat_capability', __( 'Add Categories Capability', 'cleverness-to-do-list' ), array( &$this, 'permission_option' ), $this->permissions_key, 'section_permission', array( 'label_for' => 'add_cat_capability' ) );
	}

	function permission_option($args) { ?>
		<select name="<?php echo $this->permissions_key; ?>[<?php echo $args['label_for']; ?>]">
			<option value="edit_posts"<?php if ( $this->permission_settings[$args['label_for']] == 'edit_posts' ) echo ' selected="selected"'; ?>><?php _e( 'Edit Posts', 'cleverness-to-do-list' ); ?></option>
			<option value="publish_posts"<?php if ( $this->permission_settings[$args['label_for']] == 'publish_posts' ) echo ' selected="selected"'; ?>><?php _e( 'Publish Posts', 'cleverness-to-do-list' ); ?></option>
			<option value="edit_others_posts"<?php if ( $this->permission_settings[$args['label_for']] == 'edit_others_posts' ) echo ' selected="selected"'; ?>><?php _e( 'Edit Others Posts', 'cleverness-to-do-list'); ?></option>
			<option value="publish_pages"<?php if ( $this->permission_settings[$args['label_for']] == 'publish_pages' ) echo ' selected="selected"'; ?>><?php _e( 'Publish Pages', 'cleverness-to-do-list' ); ?></option>
			<option value="edit_users"<?php if ( $this->permission_settings[$args['label_for']] == 'edit_users' ) echo ' selected="selected"'; ?>><?php _e( 'Edit Users', 'cleverness-to-do-list' ); ?></option>
			<option value="manage_options"<?php if ( $this->permission_settings[$args['label_for']] == 'manage_options' ) echo ' selected="selected"'; ?>><?php _e( 'Manage Options', 'cleverness-to-do-list' ); ?></option>
		</select>
	<?php }

	function add_admin_menus() {
		global $cleverness_todo_settings_page;
		$cleverness_todo_settings_page = add_submenu_page( 'cleverness-to-do-list', __( 'To-Do List Settings', 'cleverness-to-do-list' ), __( 'Settings', 'cleverness-to-do-list' ), 'manage_options',
			'cleverness-to-do-list-settings', array( &$this, 'plugin_options_page' ) );
		add_action( "load-$cleverness_todo_settings_page", 'CTDL_Help::cleverness_todo_help_tab' );
	}

	/*
	 * Plugin Options page rendering goes here, checks
	 * for active tab and replaces key with the related
	 * settings key. Uses the plugin_options_tabs method
	 * to render the tabs.
	 */
	function plugin_options_page() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_key;
		?>
	<div class="wrap">
		<?php $this->plugin_options_tabs(); ?>
		<form method="post" action="options.php">
			<?php wp_nonce_field( 'update-options' ); ?>
			<?php settings_fields( $tab ); ?>
			<?php do_settings_sections( $tab ); ?>
			<?php if ( $tab == $this->advanced_key ) {
				$this->show_delete_tables_button();
				$this->show_delete_todos_button();
			} ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
	add_action( 'in_admin_footer', 'CTDL_Lib::cleverness_todo_admin_footer' );
	}

	/*
	 * Renders our tabs in the plugin options page,
	 * walks through the object's tabs array and prints
	 * them one by one. Provides the heading for the
	 * plugin_options_page method.
	 */
	function plugin_options_tabs() {
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_key;

		echo '<div class="icon32"><img src="'.CTDL_PLUGIN_URL.'/images/cleverness-todo-icon.png" alt="" /></div>';
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->plugin_tabs as $tab_key => $tab_caption ) {
			$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
			echo '<a class="nav-tab '.$active.'" href="?page='.$this->plugin_key.'&amp;tab='.$tab_key.'">'.$tab_caption.'</a>';
		}
		echo '</h2>';
		if ( isset( $_GET['settings-updated'] ) ) {
			echo '<div id="message" class="updated"><p><strong>'.__( 'Settings saved.' ).'</strong></p></div>';
		} elseif ( isset( $_GET['action'] ) && $_GET['action'] == 'deletetables' ) {
			echo '<div id="message" class="updated"><p><strong>'.__( 'Tables have been deleted.', 'cleverness-to-do-list' ).'</strong></p></div>';
		} elseif ( isset( $_GET['action'] ) && $_GET['action'] == 'deletealltodos' ) {
			echo '<div id="message" class="updated"><p><strong>'.__( 'To-Do Items have been deleted.', 'cleverness-to-do-list' ).'</strong></p></div>';
		}
	}


	function show_delete_tables_button() {
		$cleverness_todo_delete_tables_nonce = wp_create_nonce( 'tododeletetables' );
		$url = get_admin_url().'admin.php?page=cleverness-to-do-list-settings&amp;&tab=CTDL_advanced&amp;action=deletetables&_wpnonce='.esc_attr( $cleverness_todo_delete_tables_nonce );
		echo '<p><a class="button-secondary" href="'.$url.'" title="'.__( 'Delete Tables', 'cleverness-to-do-list' ).'>" id="delete-tables">'.__( 'Delete Tables', 'cleverness-to-do-list' ).'</a></p>';
	}

	function show_delete_todos_button() {
		$cleverness_todo_delete_todos_nonce = wp_create_nonce( 'tododeletetodos' );
		$url = get_admin_url().'admin.php?page=cleverness-to-do-list-settings&amp;&tab=CTDL_advanced&amp;action=deletealltodos&_wpnonce='.esc_attr( $cleverness_todo_delete_todos_nonce );
		echo '<p><a class="button-secondary" href="'.$url.'" title="'.__( 'Delete All To-Do Items', 'cleverness-to-do-list' ).'>" id="delete-all-todos">'.__( 'Delete All To-Do Items', 'cleverness-to-do-list' ).'</a></p>';
	}

}
?>