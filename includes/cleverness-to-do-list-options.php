<?php
/* Creates a page to manage the To-Do List settings */
function cleverness_todo_settings_page() {
	global $wpdb;
?>
<div class="wrap">
<div class="icon32"><img src="<?php echo CTDL_PLUGIN_URL; ?>/images/cleverness-todo-icon.png" alt="" /></div> <h2><?php _e('To-Do List Settings', 'cleverness-to-do-list'); ?></h2>

<?php
if (!current_user_can('manage_options')) {
	wp_die( __('You do not have sufficient permissions to access this page.') );
	}
?>

<form method="post" action="options.php">
    <?php settings_fields( 'cleverness-todo-settings-group' ); ?>
	<?php $options = get_option('cleverness_todo_settings'); ?>

	<p><?php _e('Category support is turned off by default. If you would like to organize your to-do list into categories, enable it here.', 'cleverness-to-do-list'); ?></p>

	<table class="form-table">
	<tbody>
        <tr>
        <th scope="row"><label for="cleverness_todo_settings[categories]"><?php _e('Categories', 'cleverness-to-do-list'); ?></label></th>
        <td>
			<select id="cleverness_todo_settings[categories]" name="cleverness_todo_settings[categories]">
				<option value="0"<?php if ( $options['categories'] == '0' ) echo ' selected="selected"'; ?>><?php _e('Disable', 'cleverness-to-do-list'); ?>&nbsp;</option>
				<option value="1"<?php if ( $options['categories'] == '1' ) echo ' selected="selected"'; ?>><?php _e('Enable', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
        </tr>
	</tbody>
	</table>

	<p><?php _e('<em>List View</em> sets how the to-do lists are viewed.<br /> The <em>Individual</em> setting allows each user to have their own private to-do list. The <em>Group</em> setting allows all users to share one to-do list. The <em>Master</em> setting allows you to have one master list for all users with indvidual completing of items.', 'cleverness-to-do-list'); ?></p>

    <table class="form-table">
	<tbody>
        <tr>
        <th scope="row"><label for="cleverness_todo_settings[list_view]"><?php _e('List View', 'cleverness-to-do-list'); ?></label></th>
        <td>
			<select id="cleverness_todo_settings[list_view]" name="cleverness_todo_settings[list_view]">
				<option value="0"<?php if ( $options['list_view'] == '0' ) echo ' selected="selected"'; ?>><?php _e('Individual', 'cleverness-to-do-list'); ?>&nbsp;</option>
				<option value="1"<?php if ( $options['list_view'] == '1' ) echo ' selected="selected"'; ?>><?php _e('Group', 'cleverness-to-do-list'); ?></option>
				<option value="2"<?php if ( $options['list_view'] == '2' ) echo ' selected="selected"'; ?>><?php _e('Master', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
        </tr>
        <tr>
        <th scope="row"><label for="cleverness_todo_settings[show_deadline]"><?php _e('Show Deadline', 'cleverness-to-do-list'); ?></label></th>
        <td>
			<select id="cleverness_todo_settings[show_deadline]" name="cleverness_todo_settings[show_deadline]">
				<option value="0"<?php if ( $options['show_deadline'] == '0' ) echo ' selected="selected"'; ?>><?php _e('No', 'cleverness-to-do-list'); ?></option>
				<option value="1"<?php if ( $options['show_deadline'] == '1' ) echo ' selected="selected"'; ?>><?php _e('Yes', 'cleverness-to-do-list'); ?>&nbsp;</option>
			</select>
		</td>
        </tr>
        <tr>
        <th scope="row"><label for="cleverness_todo_settings[show_progress]"><?php _e('Show Progress', 'cleverness-to-do-list'); ?></label></th>
        <td>
			<select id="cleverness_todo_settings[show_progress]" name="cleverness_todo_settings[show_progress]">
				<option value="0"<?php if ( $options['show_progress'] == '0' ) echo ' selected="selected"'; ?>><?php _e('No', 'cleverness-to-do-list'); ?></option>
				<option value="1"<?php if ( $options['show_progress'] == '1' ) echo ' selected="selected"'; ?>><?php _e('Yes', 'cleverness-to-do-list'); ?>&nbsp;</option>
			</select>
		</td>
        </tr>
		<tr>
        <th scope="row"><label for="cleverness_todo_settings[show_completed_date]"><?php _e('Show Date Completed', 'cleverness-to-do-list'); ?></label></th>
        <td>
			<select id="cleverness_todo_settings[show_completed_date]" name="cleverness_todo_settings[show_completed_date]">
				<option value="0"<?php if ( $options['show_completed_date'] == '0' ) echo ' selected="selected"'; ?>><?php _e('No', 'cleverness-to-do-list'); ?></option>
				<option value="1"<?php if ( $options['show_completed_date'] == '1' ) echo ' selected="selected"'; ?>><?php _e('Yes', 'cleverness-to-do-list'); ?>&nbsp;</option>
			</select>
		</td>
        </tr>
		<tr>
        <th scope="row"><label for="cleverness_todo_settings[date_format]"><?php _e('Date Format', 'cleverness-to-do-list'); ?></label></th>
        <td>
			<input type="text" id="cleverness_todo_settings[date_format]" name="cleverness_todo_settings[date_format]" value="<?php if ( $options['date_format'] != '' ) echo $options['date_format']; else echo 'm-d-Y'; ?>" /><br /><a href="http://codex.wordpress.org/Formatting_Date_and_Time"><?php _e('Documentation on Date Formatting', 'cleverness-to-do-list'); ?></a>
		</td>
        </tr>
		<tr>
        <th scope="row"><label for="cleverness_todo_settings[sort_order]"><?php _e('Sort Order', 'cleverness-to-do-list'); ?></label></th>
        <td>
			<select id="cleverness_todo_settings[sort_order]" name="cleverness_todo_settings[sort_order]">
				<option value="id"<?php if ( $options['sort_order'] == 'id' ) echo ' selected="selected"'; ?>><?php _e('ID', 'cleverness-to-do-list'); ?></option>
				<option value="todotext"<?php if ( $options['sort_order'] == 'todotext' ) echo ' selected="selected"'; ?>><?php _e('Alphabetical', 'cleverness-to-do-list'); ?>&nbsp;</option>
				<option value="deadline"<?php if ( $options['sort_order'] == 'deadline' ) echo ' selected="selected"'; ?>><?php _e('Deadline', 'cleverness-to-do-list'); ?></option>
				<option value="progress"<?php if ( $options['sort_order'] == 'progress' ) echo ' selected="selected"'; ?>><?php _e('Progress', 'cleverness-to-do-list'); ?></option>
				<option value="cat_id"<?php if ( $options['sort_order'] == 'cat_id' ) echo ' selected="selected"'; ?>><?php _e('Category', 'cleverness-to-do-list'); ?></option>
				<option value="assign"<?php if ( $options['sort_order'] == 'assign' ) echo ' selected="selected"'; ?>><?php _e('Assigned User', 'cleverness-to-do-list'); ?></option>
			</select>
			<br /><?php _e('Items are first sorted by priority', 'cleverness-to-do-list'); ?>
		</td>
        </tr>
	</tbody>
	</table>



	<h3><?php _e('Priority Label Settings', 'cleverness-to-do-list'); ?></h3>
	<p><?php _e('The highest priority list items are shown in red in the lists. The lowest priority list items are shown in a lighter grey.', 'cleverness-to-do-list'); ?></p>
	<table class="form-table">
	<tbody>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[priority_0]"><?php _e('Highest Priority Label', 'cleverness-to-do-list'); ?></label></th>
		<td>
			<input type="text" id="cleverness_todo_settings[priority_0]" name="cleverness_todo_settings[priority_0]" value="<?php echo $options['priority_0']; ?>" />
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[priority_1]"><?php _e('Middle Priority Label', 'cleverness-to-do-list'); ?></label></th>
		<td>
			<input type="text" id="cleverness_todo_settings[priority_1]" name="cleverness_todo_settings[priority_1]" value="<?php echo $options['priority_1']; ?>" />
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[priority_2]"><?php _e('Lowest Priority Label', 'cleverness-to-do-list'); ?></label></th>
		<td>
			<input type="text" id="cleverness_todo_settings[priority_2]" name="cleverness_todo_settings[priority_2]" value="<?php echo $options['priority_2']; ?>" />
		</td>
		</tr>
	</tbody>
    </table>

	<h3><?php _e('Group View Settings', 'cleverness-to-do-list'); ?></h3>
	<p><?php _e('This setting is only used when <em>List View</em> is set to <em>Group</em>.', 'cleverness-to-do-list'); ?></p>
	<table class="form-table">
	<tbody>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[todo_author]"><?php _e('Show <em>Added By</em> on To-Do List page', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<select id="cleverness_todo_settings[todo_author]" name="cleverness_todo_settings[todo_author]">
				<option value="0"<?php if ( $options['todo_author'] == '0' ) echo ' selected="selected"'; ?>><?php _e('Yes', 'cleverness-to-do-list'); ?>&nbsp;</option>
				<option value="1"<?php if ( $options['todo_author'] == '1' ) echo ' selected="selected"'; ?>><?php _e('No', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
		</tr>
	</tbody>
	</table>

	<h3><?php _e('Assigned Tasks Settings', 'cleverness-to-do-list'); ?></h3>
	<table class="form-table">
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[assign]"><?php _e('Assign Tasks to Users', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<select id="cleverness_todo_settings[assign]" name="cleverness_todo_settings[assign]">
				<option value="0"<?php if ( $options['assign'] == '0' ) echo ' selected="selected"'; ?>><?php _e('Yes', 'cleverness-to-do-list'); ?>&nbsp;</option>
				<option value="1"<?php if ( $options['assign'] == '1' ) echo ' selected="selected"'; ?>><?php _e('No', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[email_assigned]"><?php _e('Email Assigned Task to User', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<select id="cleverness_todo_settings[email_assigned]" name="cleverness_todo_settings[email_assigned]">
				<option value="0"<?php if ( $options['email_assigned'] == '0' ) echo ' selected="selected"'; ?>><?php _e('No', 'cleverness-to-do-list'); ?>&nbsp;</option>
				<option value="1"<?php if ( $options['email_assigned'] == '1' ) echo ' selected="selected"'; ?>><?php _e('Yes', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[email_from]"><?php _e('From for Email Assigned Task to User', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<textarea id="cleverness_todo_settings[email_from]" name="cleverness_todo_settings[email_from]"><?php echo $options['email_from']; ?></textarea>
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[email_subject]"><?php _e('Subject for Email Assigned Task to User', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<textarea id="cleverness_todo_settings[email_subject]" name="cleverness_todo_settings[email_subject]"><?php echo $options['email_subject']; ?></textarea>
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[email_text]"><?php _e('Text in Email Assigned Task to User', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<textarea id="cleverness_todo_settings[email_text]" name="cleverness_todo_settings[email_text]"><?php echo $options['email_text']; ?></textarea>
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[show_only_assigned]"><?php _e('Show Each User Only Their Assigned Tasks', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<select id="cleverness_todo_settings[show_only_assigned]" name="cleverness_todo_settings[show_only_assigned]">
				<option value="0"<?php if ( $options['show_only_assigned'] == '0' ) echo ' selected="selected"'; ?>><?php _e('Yes', 'cleverness-to-do-list'); ?>&nbsp;</option>
				<option value="1"<?php if ( $options['show_only_assigned'] == '1' ) echo ' selected="selected"'; ?>><?php _e('No', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
		</tr>
		<tr>
        <th scope="row"><label for="cleverness_todo_settings[user_roles]"><?php _e('User Roles', 'cleverness-to-do-list'); ?></label></th>
        <td>
			<?php _e('This is used in displaying the list of users to-do items can be assigned to.', 'cleverness-to-do-list'); ?><br />
			<?php _e('Separate each role with a comma.', 'cleverness-to-do-list'); ?><br />
			<input type="text" id="cleverness_todo_settings[user_roles]" name="cleverness_todo_settings[user_roles]" value="<?php if ( $options['user_roles'] != '' ) echo $options['user_roles']; else echo 'contributor, author, editor, administrator'; ?>" style="width: 300px;" /><br /><a href="http://codex.wordpress.org/Roles_and_Capabilities"><?php _e('Documentation on User Roles', 'cleverness-to-do-list'); ?></a>
		</td>
        </tr>
		</table>

	<h3><?php _e('Permissions', 'cleverness-to-do-list'); ?></h3>
	<p><?php _e('These settings are used in <em>Group</em> and <em>Master</em> views.', 'cleverness-to-do-list'); ?></p>
	<p><em><?php _e('When using the Master view, you only want to allow regular users to view and complete items, otherwise they will be able to edit the Master list.', 'cleverness-to-do-list'); ?></em></p>
	<table class="form-table">
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[view_capability]"><?php _e('View To-Do Item Capability', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<select id="cleverness_todo_settings[view_capability]" name="cleverness_todo_settings[view_capability]">
				<option value="edit_posts"<?php if ( $options['view_capability'] == 'edit_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_posts"<?php if ( $options['view_capability'] == 'publish_posts' ) echo ' selected="selected"'; ?>><?php _e('Publish Posts', 'cleverness-to-do-list'); ?></option>
				<option value="edit_others_posts"<?php if ( $options['view_capability'] == 'edit_others_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Others Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_pages"<?php if ( $options['view_capability'] == 'publish_pages' ) echo ' selected="selected"'; ?>><?php _e('Publish Pages', 'cleverness-to-do-list'); ?></option>
				<option value="edit_users"<?php if ( $options['view_capability'] == 'edit_users' ) echo ' selected="selected"'; ?>><?php _e('Edit Users', 'cleverness-to-do-list'); ?></option>
				<option value="manage_options"<?php if ( $options['view_capability'] == 'manage_options' ) echo ' selected="selected"'; ?>><?php _e('Manage Options', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[complete_capability]"><?php _e('Complete To-Do Item Capability', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<select id="cleverness_todo_settings[complete_capability]" name="cleverness_todo_settings[complete_capability]">
				<option value="edit_posts"<?php if ( $options['complete_capability'] == 'edit_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_posts"<?php if ( $options['complete_capability'] == 'publish_posts' ) echo ' selected="selected"'; ?>><?php _e('Publish Posts', 'cleverness-to-do-list'); ?></option>
				<option value="edit_others_posts"<?php if ( $options['complete_capability'] == 'edit_others_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Others Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_pages"<?php if ( $options['complete_capability'] == 'publish_pages' ) echo ' selected="selected"'; ?>><?php _e('Publish Pages', 'cleverness-to-do-list'); ?></option>
				<option value="edit_users"<?php if ( $options['complete_capability'] == 'edit_users' ) echo ' selected="selected"'; ?>><?php _e('Edit Users', 'cleverness-to-do-list'); ?></option>
				<option value="manage_options"<?php if ( $options['complete_capability'] == 'manage_options' ) echo ' selected="selected"'; ?>><?php _e('Manage Options', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[add_capability]"><?php _e('Add To-Do Item Capability', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<select id="cleverness_todo_settings[add_capability]" name="cleverness_todo_settings[add_capability]">
				<option value="edit_posts"<?php if ( $options['add_capability'] == 'edit_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_posts"<?php if ( $options['add_capability'] == 'publish_posts' ) echo ' selected="selected"'; ?>><?php _e('Publish Posts', 'cleverness-to-do-list'); ?></option>
				<option value="edit_others_posts"<?php if ( $options['add_capability'] == 'edit_others_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Others Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_pages"<?php if ( $options['add_capability'] == 'publish_pages' ) echo ' selected="selected"'; ?>><?php _e('Publish Pages', 'cleverness-to-do-list'); ?></option>
				<option value="edit_users"<?php if ( $options['add_capability'] == 'edit_users' ) echo ' selected="selected"'; ?>><?php _e('Edit Users', 'cleverness-to-do-list'); ?></option>
				<option value="manage_options"<?php if ( $options['add_capability'] == 'manage_options' ) echo ' selected="selected"'; ?>><?php _e('Manage Options', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[edit_capability]"><?php _e('Edit To-Do Item Capability', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<select id="cleverness_todo_settings[edit_capability]" name="cleverness_todo_settings[edit_capability]">
				<option value="edit_posts"<?php if ( $options['edit_capability'] == 'edit_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_posts"<?php if ( $options['edit_capability'] == 'publish_posts' ) echo ' selected="selected"'; ?>><?php _e('Publish Posts', 'cleverness-to-do-list'); ?></option>
				<option value="edit_others_posts"<?php if ( $options['edit_capability'] == 'edit_others_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Others Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_pages"<?php if ( $options['edit_capability'] == 'publish_pages' ) echo ' selected="selected"'; ?>><?php _e('Publish Pages', 'cleverness-to-do-list'); ?></option>
				<option value="edit_users"<?php if ( $options['edit_capability'] == 'edit_users' ) echo ' selected="selected"'; ?>><?php _e('Edit Users', 'cleverness-to-do-list'); ?></option>
				<option value="manage_options"<?php if ( $options['edit_capability'] == 'manage_options' ) echo ' selected="selected"'; ?>><?php _e('Manage Options', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[assign_capability]"><?php _e('Assign To-Do Item Capability', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<select id="cleverness_todo_settings[assign_capability]" name="cleverness_todo_settings[assign_capability]">
				<option value="edit_posts"<?php if ( $options['assign_capability'] == 'edit_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_posts"<?php if ( $options['assign_capability'] == 'publish_posts' ) echo ' selected="selected"'; ?>><?php _e('Publish Posts', 'cleverness-to-do-list'); ?></option>
				<option value="edit_others_posts"<?php if ( $options['assign_capability'] == 'edit_others_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Others Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_pages"<?php if ( $options['assign_capability'] == 'publish_pages' ) echo ' selected="selected"'; ?>><?php _e('Publish Pages', 'cleverness-to-do-list'); ?></option>
				<option value="edit_users"<?php if ( $options['assign_capability'] == 'edit_users' ) echo ' selected="selected"'; ?>><?php _e('Edit Users', 'cleverness-to-do-list'); ?></option>
				<option value="manage_options"<?php if ( $options['assign_capability'] == 'manage_options' ) echo ' selected="selected"'; ?>><?php _e('Manage Options', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[view_all_assigned_capability]"><?php _e('View All Assigned Tasks Capability', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<select id="cleverness_todo_settings[view_all_assigned_capability]" name="cleverness_todo_settings[view_all_assigned_capability]">
				<option value="none"<?php if ( $options['view_all_assigned_capability'] == 'none' ) echo ' selected="selected"'; ?>><?php _e('None', 'cleverness-to-do-list'); ?></option>
				<option value="edit_posts"<?php if ( $options['view_all_assigned_capability'] == 'edit_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_posts"<?php if ( $options['view_all_assigned_capability'] == 'publish_posts' ) echo ' selected="selected"'; ?>><?php _e('Publish Posts', 'cleverness-to-do-list'); ?></option>
				<option value="edit_others_posts"<?php if ( $options['view_all_assigned_capability'] == 'edit_others_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Others Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_pages"<?php if ( $options['view_all_assigned_capability'] == 'publish_pages' ) echo ' selected="selected"'; ?>><?php _e('Publish Pages', 'cleverness-to-do-list'); ?></option>
				<option value="edit_users"<?php if ( $options['view_all_assigned_capability'] == 'edit_users' ) echo ' selected="selected"'; ?>><?php _e('Edit Users', 'cleverness-to-do-list'); ?></option>
				<option value="manage_options"<?php if ( $options['view_all_assigned_capability'] == 'manage_options' ) echo ' selected="selected"'; ?>><?php _e('Manage Options', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[delete_capability]"><?php _e('Delete To-Do Item Capability', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<select id="cleverness_todo_settings[delete_capability]" name="cleverness_todo_settings[delete_capability]">
				<option value="edit_posts"<?php if ( $options['delete_capability'] == 'edit_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_posts"<?php if ( $options['delete_capability'] == 'publish_posts' ) echo ' selected="selected"'; ?>><?php _e('Publish Posts', 'cleverness-to-do-list'); ?></option>
				<option value="edit_others_posts"<?php if ( $options['delete_capability'] == 'edit_others_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Others Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_pages"<?php if ( $options['delete_capability'] == 'publish_pages' ) echo ' selected="selected"'; ?>><?php _e('Publish Pages', 'cleverness-to-do-list'); ?></option>
				<option value="edit_users"<?php if ( $options['delete_capability'] == 'edit_users' ) echo ' selected="selected"'; ?>><?php _e('Edit Users', 'cleverness-to-do-list'); ?></option>
				<option value="manage_options"<?php if ( $options['delete_capability'] == 'manage_options' ) echo ' selected="selected"'; ?>><?php _e('Manage Options', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[purge_capability]"><?php _e('Purge To-Do Items Capability', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<select id="cleverness_todo_settings[purge_capability]" name="cleverness_todo_settings[purge_capability]">
				<option value="edit_posts"<?php if ( $options['purge_capability'] == 'edit_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_posts"<?php if ( $options['purge_capability'] == 'publish_posts' ) echo ' selected="selected"'; ?>><?php _e('Publish Posts', 'cleverness-to-do-list'); ?></option>
				<option value="edit_others_posts"<?php if ( $options['purge_capability'] == 'edit_others_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Others Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_pages"<?php if ( $options['purge_capability'] == 'publish_pages' ) echo ' selected="selected"'; ?>><?php _e('Publish Pages', 'cleverness-to-do-list'); ?></option>
				<option value="edit_users"<?php if ( $options['purge_capability'] == 'edit_users' ) echo ' selected="selected"'; ?>><?php _e('Edit Users', 'cleverness-to-do-list'); ?></option>
				<option value="manage_options"<?php if ( $options['purge_capability'] == 'manage_options' ) echo ' selected="selected"'; ?>><?php _e('Manage Options', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="cleverness_todo_settings[add_cat_capability]"><?php _e('Add Categories Capability', 'cleverness-to-do-list'); ?></label></th>
        <td valign="top">
			<select id="cleverness_todo_settings[add_cat_capability]" name="cleverness_todo_settings[add_cat_capability]">
				<option value="edit_posts"<?php if ( $options['add_cat_capability'] == 'edit_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_posts"<?php if ( $options['add_cat_capability'] == 'publish_posts' ) echo ' selected="selected"'; ?>><?php _e('Publish Posts', 'cleverness-to-do-list'); ?></option>
				<option value="edit_others_posts"<?php if ( $options['add_cat_capability'] == 'edit_others_posts' ) echo ' selected="selected"'; ?>><?php _e('Edit Others Posts', 'cleverness-to-do-list'); ?></option>
				<option value="publish_pages"<?php if ( $options['add_cat_capability'] == 'publish_pages' ) echo ' selected="selected"'; ?>><?php _e('Publish Pages', 'cleverness-to-do-list'); ?></option>
				<option value="edit_users"<?php if ( $options['add_cat_capability'] == 'edit_users' ) echo ' selected="selected"'; ?>><?php _e('Edit Users', 'cleverness-to-do-list'); ?></option>
				<option value="manage_options"<?php if ( $options['add_cat_capability'] == 'manage_options' ) echo ' selected="selected"'; ?>><?php _e('Manage Options', 'cleverness-to-do-list'); ?></option>
			</select>
		</td>
		</tr>
		</tbody>
		</table>

    <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes', 'cleverness-to-do-list') ?>" /></p>

</form>
</div>
<?php
}
?>