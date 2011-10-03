<?php
/* SETUP AND LOAD PLUGIN */

class ClevernessToDoLoader {
	protected static $settings;

	public static function init($settings) {

		self::$settings = $settings;
		self::check_version();
		self::include_files();
		self::call_hooks();

		global $ClevernessToDoList;
        $ClevernessToDoList = new ClevernessToDoList(self::$settings);

		}

	private static function include_files() {
		include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-options.php');
		include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-dashboard-widget.php');
		include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-widget.php');
		include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-shortcode.php');
		include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-categories.php');
		include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-help.php');
		include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-functions.php');
		include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-frontend.php');
		}

	private static function call_hooks() {
		add_filter('plugin_action_links', 'cleverness_add_settings_link', 10, 2 );
   		add_action('admin_menu', __CLASS__.'::create_admin_menu');
   		add_action('admin_init', __CLASS__.'::register_settings');
   		add_action('wp_dashboard_setup', 'cleverness_todo_dashboard_setup');
  		add_action('widgets_init', 'cleverness_todo_widget');
   		add_action('init', __CLASS__.'::load_translation_file');
	   	add_action('admin_init', __CLASS__.'::admin_init');
		add_action('wp_ajax_cleverness_todo_delete', 'cleverness_todo_delete_todo_callback');
		add_action('wp_ajax_cleverness_todo_complete', 'cleverness_todo_checklist_complete_callback' );
		}

	private static function check_version() {
		global $wp_version;
		$exit_msg = __('To-Do List requires WordPress 3.2 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update.</a>', 'cleverness-to-do-list');
		if (version_compare($wp_version, "3.2", "<")) {
   			exit($exit_msg);
  			}
		}

	/* Add Page under admin and Add Settings Page */
	public static function create_admin_menu() {
		global $userdata, $cleverness_todo_page, $cleverness_todo_cat_page;
   		get_currentuserinfo();

        $cleverness_todo_page = add_menu_page( __('To-Do List', 'cleverness-to-do-list'), __('To-Do List', 'cleverness-to-do-list'), self::$settings['view_capability'], 'cleverness-to-do-list', __CLASS__.'::admin_subpanel', CTDL_PLUGIN_URL.'/images/cleverness-todo-icon-sm.png');
		if ( self::$settings['categories'] == '1' ) {
		$cleverness_todo_cat_page = add_submenu_page( 'cleverness-to-do-list', __('To-Do List Categories', 'cleverness-to-do-list'), __('Categories', 'cleverness-to-do-list'), self::$settings['add_cat_capability'], 'cleverness-to-do-list-cats', 'cleverness_todo_categories');
			}
		add_submenu_page( 'cleverness-to-do-list', __('To-Do List Settings', 'cleverness-to-do-list'), __('Settings', 'cleverness-to-do-list'), 'manage_options', 'cleverness-to-do-list-options', 'cleverness_todo_settings_page');
		add_submenu_page( 'cleverness-to-do-list', __('To-Do List Help', 'cleverness-to-do-list'), __('Help', 'cleverness-to-do-list'), self::$settings['view_capability'], 'cleverness-to-do-list-help', 'cleverness_todo_help');
		}

	/* Create admin page */
	public static function admin_subpanel() {
   		global $ClevernessToDoList;

   		$priority = array(0 => self::$settings['priority_0'] , 1 => self::$settings['priority_1'], 2 => self::$settings['priority_2']);

		$ClevernessToDoList->display(self::$settings);
		echo $ClevernessToDoList->list;
		}

	/* Translation Support */
	public static function load_translation_file() {
		$plugin_path = CTDL_BASENAME .'/lang';
		load_plugin_textdomain( 'cleverness-to-do-list', '', $plugin_path );
		}

	/* Register the options field */
	public static function register_settings() {
  		register_setting( 'cleverness-todo-settings-group', 'cleverness_todo_settings' );
		}

	/* Add CSS file to admin header */
	public static function add_admin_css() {
		$cleverness_style_url = CTDL_PLUGIN_URL . '/css/admin.css';
		$cleverness_style_file = CTDL_PLUGIN_DIR . '/css/admin.css';
    	if ( file_exists($cleverness_style_file) ) {
   			wp_register_style('cleverness_todo_style_sheet', $cleverness_style_url);
    		wp_enqueue_style( 'cleverness_todo_style_sheet');
        	}
		}

	public static function add_admin_js() {
		wp_enqueue_script( 'cleverness_todo_js' );
		wp_enqueue_script( 'jquery-color' );
		wp_localize_script( 'cleverness_todo_js', 'ctdl', ClevernessToDoLoader::get_js_vars());
    	}

	public static function admin_init() {
		global $cleverness_todo_page;
   		add_action('admin_print_styles-' . $cleverness_todo_page, __CLASS__.'::add_admin_css');
		wp_register_script( 'cleverness_todo_js', CTDL_PLUGIN_URL.'/js/todos.js', '', 1.0, true );
		add_action('admin_print_scripts-' . $cleverness_todo_page, __CLASS__.'::add_admin_js');
		}

	/* returns various JavaScript vars needed for the scripts */
	public static function get_js_vars() {
		return array(
			'SUCCESS_MSG' => __('To-Do Deleted.', 'cleverness-to-do-list'),
			'ERROR_MSG' => __('There was a problem performing that action.', 'cleverness-to-do-list'),
			'PERMISSION_MSG' => __('You do not have sufficient privileges to do that.', 'cleverness-to-do-list'),
			'CONFIRMATION_MSG' => __("You are about to permanently delete the selected item. \n 'Cancel' to stop, 'OK' to delete.", 'cleverness-to-do-list'),
			'NONCE' => wp_create_nonce('cleverness-todo'),
			'AJAX_URL' => admin_url('admin-ajax.php')
			);
		}

} // end class
?>