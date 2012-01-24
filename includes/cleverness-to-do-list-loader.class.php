<?php
/**
 * Set up and load the To-Do List Plugin
 * @author C.M. Kendrick
 * @version 3.0
 * @package cleverness-to-do-list
 * @todo check out $message, see if its being used
 */

class CTDL_Loader {
	public static $settings;

	public static function init() {

		CTDL_Lib::check_wp_version();
		self::$settings = array_merge( get_option( 'cleverness-to-do-list-general' ), get_option( 'cleverness-to-do-list-advanced' ), get_option( 'cleverness-to-do-list-permissions' ) );
		self::include_files();
		self::call_wp_hooks();

		global $ClevernessToDoList;
        $ClevernessToDoList = new ClevernessToDoList();
		new ClevernessToDoSettings();

	}

	/**
	 * Calls the plugin files for inclusion
	 * @static
	 */
	private static function include_files() {
		include_once( CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-settings.class.php' );
		include_once( CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-dashboard-widget.php' );
		include_once( CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-widget.php' );
		include_once( CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-shortcode.php' );
		include_once( CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-categories.php' );
		include_once( CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-help.php' );
		include_once( CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-functions.php' );
		include_once( CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-frontend.class.php' );
	}

	/**
	 * Adds actions to WordPress hooks
	 * @static
	 */
	private static function call_wp_hooks() {
		add_action( 'init', __CLASS__ . '::load_translation_file' );
		add_action( 'admin_init', __CLASS__ . '::admin_init' );
		add_action( 'widgets_init', 'cleverness_todo_widget' );
		add_action( 'admin_menu', __CLASS__ . '::create_admin_menu' );
   		add_action( 'wp_dashboard_setup', 'cleverness_todo_dashboard_setup' );
		add_action( 'wp_ajax_cleverness_todo_delete', 'cleverness_todo_delete_todo_callback' );
		add_action( 'wp_ajax_cleverness_todo_complete', 'cleverness_todo_checklist_complete_callback' );
		add_filter( 'plugin_action_links', 'CTDL_Lib::add_settings_link', 10, 2 );
		if ( self::$settings['admin_bar'] == 1 ) add_action( 'admin_bar_menu', __CLASS__ . '::add_to_toolbar', 999 );
	}

	/**
	 * Adds the Main plugin page and the categories page to the WordPress backend menu
	 * Also adds the Help tab to those pages
	 * @static
	 */
	public static function create_admin_menu() {
		global $cleverness_todo_page, $cleverness_todo_cat_page;
   		get_currentuserinfo();

        $cleverness_todo_page = add_menu_page( __( 'To-Do List', 'cleverness-to-do-list' ), __( 'To-Do List', 'cleverness-to-do-list' ), self::$settings['view_capability'], 'cleverness-to-do-list',
		        __CLASS__.'::plugin_page', CTDL_PLUGIN_URL.'/images/cleverness-todo-icon-sm.png' );
		if ( self::$settings['categories'] == '1' ) {
			$cleverness_todo_cat_page = add_submenu_page( 'cleverness-to-do-list', __( 'To-Do List Categories', 'cleverness-to-do-list' ), __( 'Categories', 'cleverness-to-do-list' ),
				self::$settings['add_cat_capability'], 'cleverness-to-do-list-cats', 'cleverness_todo_categories' );
			add_action( "load-$cleverness_todo_cat_page", 'cleverness_todo_help_tab' );
		}
		add_action( "load-$cleverness_todo_page", 'cleverness_todo_help_tab' );
	}

	/**
	 * Displays the Main To-Do List page
	 * @static
	 */
	public static function plugin_page() {
   		global $ClevernessToDoList;

		$ClevernessToDoList->display();
		echo $ClevernessToDoList->list;
	}

	/**
	 * Add an Item to the Admin Menu
	 * @param $wp_toolbar
	 */
	public function add_to_toolbar( $wp_toolbar ) {
		$wp_toolbar->add_node( array(
			'id'    => 'todolist',
			'title' => 'To-Do List',
			'href'  => get_admin_url().'admin.php?page=cleverness-to-do-list'
		) );

		if ( current_user_can(self::$settings['add_capability'] ) ) {

		$wp_toolbar->add_node( array(
			'id'     => 'todolist-add',
			'title'  => 'Add New To-Do Item',
			'parent' => 'todolist',
			'href'   => get_admin_url() . 'admin.php?page=cleverness-to-do-list#addtodo'
		) );

		}
	}

	/**
	 * Loads translation files
	 * @static
	 */
	public static function load_translation_file() {
		$plugin_path = CTDL_BASENAME .'/lang';
		load_plugin_textdomain( 'cleverness-to-do-list', '', $plugin_path );
	}

	/**
	 * Loads the CSS file for the WP backend
	 * @static
	 */
	public static function add_admin_css() {
		$cleverness_style_url = CTDL_PLUGIN_URL . '/css/admin.css';
		$cleverness_style_file = CTDL_PLUGIN_DIR . '/css/admin.css';
    	if ( file_exists( $cleverness_style_file ) ) {
   			wp_register_style( 'cleverness_todo_style_sheet', $cleverness_style_url );
    		wp_enqueue_style( 'cleverness_todo_style_sheet' );
        	}
	}

	/**
	 * Loads and localizes JS files for the WP backend
	 * @static
	 */
	public static function add_admin_js() {
		wp_enqueue_script( 'cleverness_todo_js' );
		wp_enqueue_script( 'jquery-color' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style('jquery.ui.theme', CTDL_PLUGIN_URL . '/css/jquery-ui-classic.css');
		wp_localize_script( 'cleverness_todo_js', 'ctdl', CTDL_Loader::get_js_vars() );
    }

	/**
	 * Adds the CSS and JS to the backend pages
	 * @static
	 */
	public static function admin_init() {
		global $cleverness_todo_page;
   		add_action( 'admin_print_styles-' . $cleverness_todo_page, __CLASS__.'::add_admin_css' );
		wp_register_script( 'cleverness_todo_js', CTDL_PLUGIN_URL.'/js/todos.js', '', 1.0, true );
		add_action( 'admin_print_scripts-' . $cleverness_todo_page, __CLASS__.'::add_admin_js' );
	}

	/**
	 * Localize JS variables
	 * @static
	 * @return array
	 */
	public static function get_js_vars() {
		return array(
			'SUCCESS_MSG' => __( 'To-Do Deleted.', 'cleverness-to-do-list'),
			'ERROR_MSG' => __( 'There was a problem performing that action.', 'cleverness-to-do-list' ),
			'PERMISSION_MSG' => __( 'You do not have sufficient privileges to do that.', 'cleverness-to-do-list' ),
			'CONFIRMATION_MSG' => __( "You are about to permanently delete the selected item. \n 'Cancel' to stop, 'OK' to delete.", 'cleverness-to-do-list' ),
			'NONCE' => wp_create_nonce( 'cleverness-todo' ),
			'AJAX_URL' => admin_url( 'admin-ajax.php' )
			);
	}

}
?>