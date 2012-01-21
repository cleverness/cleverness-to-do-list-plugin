<?php
/**
 * Library of functions for the To-Do List
 * @author C.M. Kendrick
 * @version 3.0
 * @package cleverness-to-do-list
 */

class CTDL_Lib {
	/* Add Settings link to plugin */
	public static function add_settings_link( $links, $file ) {
		static $this_plugin;
		if ( !$this_plugin ) $this_plugin = CTDL_BASENAME;

		if ( $file == $this_plugin ) {
			$settings_link = '<a href="admin.php?page=cleverness-to-do-list-settings">'.__( 'Settings', 'cleverness-to-do-list' ).'</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
}
?>