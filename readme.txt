=== Cleverness To-Do List ===
Contributors: elusivelight
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=cindy@cleverness.org
Author URI: http://cleverness.org
Plugin URI: http://cleverness.org/plugins/to-do-list
Tags: to-do, to do list, to-do list, list, todo, to do, assign, task, assignments, multi-author
Requires at least: 3.3
Tested up to: 3.6
Stable tag: 3.3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrates a customizable, multi-featured to-do list.

== Description ==

This plugin provides users with a to-do list feature.

You can configure the plugin to have private to-do lists for each user, to have all users share a to-do list, or to have a master list with individual completion of items. The shared to-do list has a variety of settings
available. You can assign to-do items to a specific user (includes a setting to email a new to-do item to the assigned user) and optionally have those items only viewable by that user. You can also assign different
permission levels using capabilities. There are also settings to show deadline and progress fields. Category support is included as well as front-end administration.

A new menu item is added to the backend to manage your list and the to-do list is also listed on a dashboard widget.

A sidebar widget is available as well as shortcode to display the to-do list items on your site.

There are two shortcodes for front-end administration of the list. Management of categories is restricted to the back-end.

You can also use this plugin to create custom to-do lists for your Post Planners if you own my premium plugin, [Post Planner](http://codecanyon.net/item/wordpress-post-planner/2496996?ref=seaserpentstudio).

[Plugin Website](http://cleverness.org/plugins/to-do-list/)

== Installation ==

1. Upload the folder `/cleverness-to-do-list/` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the settings on the Settings page under the To-Do List menu

== Frequently Asked Questions ==

= What is the shortcode to display items in a post or page? =
[todolist]

Several options are available:

* **title** - default is no title.
* **type** - you can chose *list* or *table* view. Default is *list*.
* **priorities** - default is *show*. Use a blank value to hide (only applies to table view).
* **assigned** - default is *show*. Use a blank value to hide.
* **deadline** - default is *show*. Use a blank value to hide.
* **progress** - default is *show*. Use a blank value to hide.
* **addedby** - default is *show*. Use a blank value to hide.
* **date** - default is hide (0). Use 1 to show.
* **completed** - default is blank. Set to *show* to display completed items.
* **completed_title** - default is no title.
* **list_type** - default is *ol* (ordered list). Use *ul* to show an unordered list.
* **category** - default is *all*. Use the category ID to show a specific category.

Example:

Table view with the title of Upcoming Articles and showing the progress and who the item was assigned to.

[todolist title="Upcoming Articles" type="table" priorities="" deadline="" addedby=""]

= What is the shortcode to display a checklist in a post or page? =
[todochecklist]

The options are:

* **title** - default is no title.
* **priority** - default is hide (0). Use 1 to show.
* **assigned** - default is hide (0). Use 1 to show.
* **deadline** - default is hide (0). Use 1 to show.
* **progress** - default is hide (0). Use 1 to show.
* **category** - default is all categories (0). Use the category ID to show a specific category.
* **addedby** - default is hide (0). Use 1 to show.
* **date** - default is hide (0). Use 1 to show.
* **editlink** - default is hide (0). Use 1 to show.
* **todoid** - default is blank (""). Use the ID of the to-do item to display just one item.

Example:

Set the title to "My To-Do List" and show the deadline and the category.

[todoadmin title="My To-Do List" deadline=1 categories=1]

= What is the shortcode to display the administration page in the front-end? =
Permalinks must be enabled on the site to be able to use this feature.
[todoadmin]

The options are:

* **title** - default is no title.
* **priority** - default is hide (0). Use 1 to show.
* **assigned** - default is hide (0). Use 1 to show.
* **deadline** - default is hide (0). Use 1 to show.
* **progress** - default is hide (0). Use 1 to show.
* **categories** - default is hide (0). Use 1 to show.
* **addedby** - default is hide (0). Use 1 to show.
* **date** - default is hide (0). Use 1 to show.
* **editlink** - default is show (1). Use 0 to hide.
* **category** - default is all categories (0).  Use the category ID to show a specific category.

Example:

Set the title to "Things to Do" and show the priority and the progress.

[todoadmin title="Things to Do" priority=1 progress=1]

= Can you explain the permissions in more detail? =

* **View To-Do Item Capability** - This allows the selected capability to view to-do items.
* **Complete To-Do Item Capability** - This allows the selected capability to mark to-do items as completed or uncompleted.
* **Add To-Do Item Capability** - This allows the selected capability to add new to-do items.
* **Edit To-Do Item Capability** - This allows the selected capability to edit existing to-do items.
* **Assign To-Do Item Capability** - This allows the selected capability to assign to-do items to individual users.
* **View All Assigned Tasks Capability** - This allows the selected capability to view all tasks even if *Show Each User Only Their Assigned Tasks* is set to *Yes*.
* **Delete To-Do Item Capability** - This allows the selected capability to delete individual to-do items.
* **Purge To-Do Items Capability** - This allows the selected capability to purge all the completed to-do items.
* **Add Categories Capability** - This allows the selected capability to add new categories.

= What should I do if I find a bug? =

Visit [the plugin website](http://cleverness.org/plugins/to-do-list/) and [leave a comment](http://cleverness.org/plugins/to-do-list/#respond) or [contact me](http://cleverness.org/contact/).

== Screenshots ==

1. Dashboard Widget
2. To-Do List Page
3. Settings Page
4. Everything Enabled

== Changelog ==

= 3.3.2 =
* Updated Polish translation from Michał Wielkopolski
* Fixed Trying to get property of non-object notice in todolist shortcode

= 3.3.1 =
* Updated Russian translation from Sergei Zastavnyi
* Updated Tablesorter jQuery plugin to forked version from http://mottie.github.io/tablesorter/
* Fixed sorting when getting specific categories
* Fixed issue with category not being set when using todoadmin and todolist shortcodes together
* Fixed todo text not being red in admin when priority is set to important
* Fixed todo text not being grey in admin when priority is set to low
* Fixed jQuery sorting of Date Added not working after first sorting
* Fixed dash showing for Date Completed even when field set to not show
* Fixed date formatting setting width
* Added filters for front-end progress display
* Added category attribute to todoadmin to show a specific category

= 3.3 =
* Added Slovak translation by Branco [WebHostingGeeks.com](http://webhostinggeeks.com/user-reviews/)
* Added vertical-align: text-top; to frontend admin table in case theme CSS sets it differently
* Added ability to select multiple categories to display in Dashboard Widget
* Added setting for the From email address
* Added Post Planner URL to assigned user email if Post Planner integration is enabled
* Fixed sorting by Date Added
* Fixed [todolist] list format HTML when categories are enabled and to only show category headings when sort order is set to category
* Fixed jQuery 1.9 deprecated functions
* Adjusted table heading widths in backend

= 3.2.3 =
* Updated Select2 jQuery plugin to version 3.2 (fixes assignment issue with WordPress 3.5)
* Added todolist-completed class to completed items using todolist shortcode
* Added plugin version to enqueue script/styles
* Added passing of Planner ID from Post Planner plugin
* Fixed Delete All To-Dos/Delete Completed Items/Delete Category/Delete To-Do Javascript Confirm Cancel button issue
* Adjusted width of WYSIWYG editor

= 3.2.2 =
* Fixed Emailing of Assigned Items

= 3.2.1 =
* Added Post Planner plugin integration
* Added ability to assign to-do items to multiple users
* Added the option for the textarea to use WP_Editor
* Added option to use wpautop to automatically add paragraph tags
* Added dashboard setting to hide the Edit link on the Dashboard widget
* Added jQuery table sorting of the To-Do List using tablesorter
* Added filters and hooks
* Added Widget option to show a logged-in user's own items only
* Added Import/Export of Settings
* Added some CSS classes to items
* Added ability to collapse the Completed To-Do table when you click on the table headings
* Added Polish translation by [Adam Zienkowicz](http://i2biz.pl)
* Fixed missing jQuery UI CSS images
* Fixed completed date not showing in front-end shortcode
* Fixed issue with multiple widgets
* Fixed sorting by deadlines
* Changed User Roles in Settings to checkboxes instead of text field
* Changed Progress dropdown to slider
* Changed Assign dropdown to use Select2
* Changed some default user permissions to edit_posts instead of publish_posts
* Removed closing PHP tags in files

= 3.1.7 =
* Fixed issue with front-end shortcode

= 3.1.6 =
* Changed JavaScript variable name to avoid potential conflicts

= 3.1.5 =
* Fixed Deadline field not using the Date Format

= 3.1.4 =
* Fixed issue with Assign column not showing

= 3.1.3 =
* Added ability to chose Subscriber level capabilities in User Permissions
* Fixed bug where the Assign dropdown was showing in Individual list view

= 3.1.2 =
* Fixed Added By showing in under Individual setting
* Fixed Date Added showing 1970 under some circumstances

= 3.1.1 =
* Fixed issue with todochecklist shortcode and categories
* Fixed issue with todolist shortcode and fields appearing that aren't enabled

= 3.1 =
* Lowered the number of database calls when showing to-do items
* Added option to show date to-do item was added
* Added button under Settings to delete all to-do items
* Added setting to show who assigned the to-do item in email
* Fixed master view not showing only assigned items
* Fixed translations not loading
* Changed plugin activation set-up
* Changed the field order in the display table
* Deleting the plugin via WordPress will now delete to-do items and categories
* Fixed issues with fields that should be hidden appearing when using the todoadmin shortcode

= 3.0.6 =
* Removed code that was causing the duplicated to-dos (it was the code for checking to see if the plugin database version matched the one stored in an option)

= 3.0.5 =
* Additional bug fix for duplicated to-dos

= 3.0.4 =
* Bug fix for duplicated to-dos

= 3.0.3 =
* Added check to see if plugin version matched stored option. If it didn't, run upgrade function.

= 3.0.2 =
* Make sure constant was defined for install

= 3.0.1 =
* Fixed an issue with the plugin not activating correctly on multi-site installs with the plugin network activated

= 3.0 =
* Converted rest of the code to classes
* Converted custom database tables to custom post type
* Added option to show all items in Widget
* Added To-Do List menu to Admin Bar (with option to remove in Settings)
* Renamed cleverness-to-do-list-options.php to cleverness-to-do-list-settings.php
* Divided settings into three sections
* Moved Help page to the Help Admin Tab
* Added tabs to Settings page
* Changed some wording on the Settings page
* Fixed master list view
* Added date picker to deadline field

= 2.3 =
* Moved dashboard widgets settings to the dashboard widget
* Added ajax to dashboard widget, main plugin page, and category page
* Added front-end shortcode
* HTML in tasks has been fixed
* Started moving code into classes and redoing a lot of it
* Fixed categories not working in multi-site with the plugin network-activated
* Added Czech translation by Tomas Vesely
* Added updated German translation by Janne Fleischer

= 2.2.8 =
* Fix issue where completed items would not show using list in the shortcode

= 2.2.7 =
* Fixed fatal error

= 2.2.6 =
* Added value check for assign and priority variables
* Echoing the email values to the screen on failed email sending, for troubleshooting

= 2.2.5 =
* Language files were not successfully committed to the SVN on last update

= 2.2.4 =
* Removed site title from email subject
* Added ability to change From value for email
* Added French translation by Thibault Guerpillon

= 2.2.3 =
* Added default values to assign and priority variables
* Email an assigned task function now returns success or fail messages

= 2.2.2 =
* Added Assign ability to Individual view
* HTML is now allowed in tasks
* Added error message displays for inserting, updating, and deleting items

= 2.2.1 =
* Added updated Spanish translation (contributed by [Ricardo](http://yabocs.avytes.com/))
* Changed shortcode and widget so that if the list is individual and a user is logged in, it will show their own list

= 2.2 =
* Added assign to sorting options
* Added the master list view feature

= 2.1.5 =
* Fixed a typo in the show assigned user code in the widget

= 2.1.4 =
* Updated German translation by Ascobol

= 2.1.3 =
* Added stripslashes() to item display

= 2.1.2 =
* Fixed option bug

= 2.1.1 =
* Fixed shortcode bug
* Added option for email text

= 2.1 =
* Added category support
* Added sort option

= 2.0.4 =
* Added German translation by Ascobol
* Added Japanese translation by [Takemi Tasaki](http://route58.org)

= 2.0.3 =
* Moved a nonce check to the correct function and added some additional code

= 2.0.2 =
* Removed require_once for pluggable.php from main body of plugin into functions

= 2.0.l =
* Fixed bug where users could not edit or delete other user's item when they had the ability

= 2.0 =
* Changed backend code for better error control and improved performance
* Compatible with WordPress 3.0
* Minor bug fixes
* The page is no longer redirected to the main To-Do List page when marking at item on the dashboard as completed
* Russian translation added

= 1.5.2 =
* Changed the url in the location variable again to work when WP is placed outside the root directory

= 1.5.1 =
* Fixed a problem with the install function
* Changed the url in the location variable

= 1.5 =
* Changed the way CSS is added to the admin pages
* Added more shortcode options
* Changed the way users are selected for the dropdown list
* Added option to show completed date and an option to format the date

= 1.4.1 =
* Bug fix affecting updating table and viewing items

= 1.4 =
* Added progress field
* Added sidebar widget
* Added post/page shortcode to display list
* Added ability to email users a new to-do item
* Removed permission check on install (may help fix WPMU issue)

= 1.3.4 =
* Added Spanish translation (contributed by [Ricardo](http://yabocs.avytes.com/))

= 1.3.3 =
* Fixed a typo in the default options that caused items to be unable to be marked as completed. Please visit the To-Do List settings page and click on Save Changes if you are having difficult marking items as completed

= 1.3.2 =
* Fixed a bug where "assigned by" would show on the dashboard widget when empty
* Renamed functions
* Added a check to prevent blank to-do items

= 1.3.1 =
* Fixed an incompatibility with PHP 4
* Added a call to the userdata global in the complete function

= 1.3 =
* Added a deadline field and settings
* Only shows users above Subscribers in the Assign To dropdown

= 1.2.1 =
* Removed a div tag from the dashboard widget that did not belong there

= 1.2 =
* Added ability to check off items from dashboard
* Added uninstall function
* Added group support
* Added settings page
* Added permissions based on capabilities
* Cleaned up code some more
* Added ability to set custom priorities
* Improved security
* Added translation support

= 1.1 =
* Enabled the plugin to work from inside a directory

= 1.0 =
* Improved the security of the plugin
* Updated the formatting to match the admin interface
* Cleaned up the code
* Fixed to work in WordPress 2.8

== Upgrade Notice ==

= 3.3.2 =
Bug fix, updated translation

= 3.3.1 =
Bug fix

= 3.3 =
New translation, bug fix, features

= 3.2.3 =
Bug fix

= 3.2.2 =
Bug fix

= 3.2.1 =
New features

= 3.1.7 =
Bug fix

= 3.1.6 =
Bug fix

= 3.1.5 =
Bug fix

= 3.1.4 =
Bug fix

= 3.1.3 =
Bug fix

= 3.1.2 =
Bug fix

= 3.1.1 =
Bug fix

= 3.1 =
Bug fix, new features

= 3.0.6 =
Bug fix

= 3.0.5 =
Bug fix

= 3.0.4 =
Bug fix

= 3.0.3 =
Bug fix

= 3.0.2 =
Bug fix

= 3.0.1 =
Bug fix

= 3.0 =
Major code rewrite and custom tables were converted to custom post types.

= 2.3 =
Code rewrite and front-end administration

= 2.2.8 =
Bug fix

= 2.2.7 =
Bug fix

= 2.2.6 =
Bug fix

= 2.2.5 =
Bug fix

= 2.2.4 =
Bug fix

= 2.2.3 =
Bug fix

= 2.2.2 =
Two small features added

= 2.2.1 =
Spanish translation updated, bug fix

= 2.2 =
Master list feature added

= 2.1.5 =
Bug fix

= 2.1.4 =
Updated German translation

= 2.1.3 =
Bug fix

= 2.1.2 =
Bug fix

= 2.1.1 =
Bug fix and new email option

= 2.1 =
Categories and sort order added

= 2.0.4 =
Two new translations added

= 2.0.3 =
Bug fix

= 2.0.2 =
Bug fix

= 2.0.1 =
Bug fix

= 2.0 =
Backend code changes, bug fixes

= 1.5.2 =
Bug fix

= 1.5.1 =
Bug fix

= 1.5 =
New features added

= 1.4.1 =
Bug fix

= 1.4 =
Added several new features and settings, added new field to database table

= 1.3.4 =
Spanish translation added

= 1.3.3 =
Bug fix, Go to the To-Do List settings page and click on Save Changes if unable to mark items as completed

= 1.3.2 =
Bug fixes
Changed function names

= 1.3.1 =
Bug fixes

= 1.3 =
Added features, changed database structure. Be sure to deactivate and activate plugin.

= 1.2.1
Bug fix

= 1.2 =
Major changes to plugin

== Credits ==

This plugin was originally from Abstract Dimensions (site no longer available) with a patch to display the list in the dashboard by WordPress by Example (site also no longer available). It was abandoned prior to WordPress 2.7.

Icon by [Hylke Bons](http://www.iconfinder.com/icondetails/30045/32/list_shopping_list_todo_todo_list_icon)

Spanish translation by [Ricardo](http://yabocs.avytes.com/)

Russian translation by [Almaz](http://alm.net.ru) - Updated by Sergei Zastavnyi

German translation by Ascobol

Japanese translation by [Takemi Tasaki](http://route58.org)

French translation by Thibault Guerpillon

Czech translation by Tomas Vesely

German translation updated by Janne Fleischer

Polish translation by [Adam Zienkowicz](http://i2biz.pl) - Updated by [Michał Wielkopolski](http://www.oikoslab.pl/)

Slovak translation by Branco [WebHostingGeeks.com](http://webhostinggeeks.com/user-reviews/)

== License ==

This file is part of Cleverness To-Do List.

Cleverness To-Do List is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

Cleverness To-Do List is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this plugin. If not, see <http://www.gnu.org/licenses/>.