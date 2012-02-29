<?php
/**
 * Cleverness To-Do List Plugin Loader
 *
 * Loads the plugin
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.0.4
 */

/**
 * Loader class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Loader {
	public static $settings;

	public static function init() {

		self::check_wp_version();
		self::check_plugin_version();
		$general_options = ( get_option( 'CTDL_general' ) ? get_option( 'CTDL_general' ) : array() );
		$advanced_options = ( get_option( 'CTDL_advanced' ) ? get_option( 'CTDL_advanced' ) : array() );
		$permissions_options = ( get_option( 'CTDL_permissions' ) ? get_option( 'CTDL_permissions' ) : array() );
		self::$settings = array_merge( $general_options, $advanced_options, $permissions_options );
		self::include_files();
		self::setup_custom_post_type();
		self::create_taxonomies();
		self::call_wp_hooks();

		global $ClevernessToDoList, $CTDL_Frontend_Checklist, $CTDL_Frontend_Admin;
        $ClevernessToDoList = new ClevernessToDoList();
		if ( is_admin() ) {
			new CTDL_Settings();
			new CTDL_Dashboard_Widget();
		} else {
			$CTDL_Frontend_Admin = new CTDL_Frontend_Admin;
			$CTDL_Frontend_Checklist = new CTDL_Frontend_Checklist;
			new CTDL_Frontend_List;
		}

	}

	/**
	 * Checks the WordPress version to make sure the plugin is compatible
	 * @static
	 */
	public static function check_wp_version() {
		global $wp_version;
		$exit_msg = __( 'To-Do List requires WordPress 3.3 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update.</a>', 'cleverness-to-do-list' );
		if ( version_compare( $wp_version, "3.3", "<" ) ) {
			exit( $exit_msg );
		}
	}

	/**
	 * Check the plugins version against the stored plugin database version and upgrade plugin if needed
	 * @static
	 * @since 3.0.3
	 */
	public static function check_plugin_version() {
		if ( get_option( 'CTDL_db_version' ) ) {
			$installed_ver = get_option( 'CTDL_db_version' );
		} else {
			$installed_ver = 0;
		}

		if ( CTDL_DB_VERSION != $installed_ver ) {

			if ( CTDL_PLUGIN_VERSION != '3.0.4' && $installed_ver != '3.1' ) cleverness_todo_activation();
		}
	}

	/**
	 * Set up custom post types
	 * @static
	 * @since 3.0
	 */
	public static function setup_custom_post_type() {
		register_post_type( 'todo',
			array(
				'labels' => array(
					'name' => __( 'To-Do' ),
					'singular_name' => __( 'To-Do' )
				),
				'public' => false,
				'has_archive' => false,
				'rewrite' => false,
				'query_var' => false,
			)
		);
	}

	/**
	 * Setup categories
	 * @static
	 * @since 3.0
	 */
	public static function create_taxonomies() {
		$labels = array(
			'name' => _x( 'Categories', 'taxonomy general name' ),
			'singular_name' => _x( 'Category', 'taxonomy singular name' ),
		);

		register_taxonomy( 'todocategories', array( 'todo' ), array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => false,
			'query_var' => false,
			'rewrite' => false,
		) );
	}

	/**
	 * Calls the plugin files for inclusion
	 * @static
	 */
	private static function include_files() {
		include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list-library.class.php';
		include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list.class.php';
		if ( self::$settings['categories'] == 1 ) include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list-categories.class.php';
		if ( is_admin() ) {
			include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list-settings.class.php';
			include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list-help.class.php';
			include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list-dashboard-widget.class.php';
		} else {
			include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list-frontend.class.php';
		}
	}

	/**
	 * Adds actions to WordPress hooks
	 * @static
	 */
	private static function call_wp_hooks() {
		add_action( 'init', __CLASS__.'::load_translation_file' );
		add_action( 'wp_ajax_cleverness_delete_todo', 'CTDL_Lib::delete_todo_callback' );
		add_action( 'wp_ajax_cleverness_todo_complete', 'CTDL_Lib::complete_todo_callback' );
		if ( self::$settings['admin_bar'] == 1 ) add_action( 'admin_bar_menu', array( 'CTDL_Lib', 'add_to_toolbar' ), 999 );
		if ( is_admin() ) {
			add_action( 'admin_init', __CLASS__.'::admin_init' );
			add_action( 'admin_menu', __CLASS__.'::create_admin_menu' );
			add_filter( 'plugin_action_links', 'CTDL_Lib::add_settings_link', 10, 2 );
			if ( self::$settings['categories'] ==1 ) add_action( 'admin_init', 'CTDL_Categories::initialize_categories' );
		}
	}

	/**
	 * Loads translation files
	 * @static
	 */
	public static function load_translation_file() {
		$plugin_path = CTDL_BASENAME.'/languages';
		load_plugin_textdomain( 'cleverness-to-do-list', '', $plugin_path );
	}

	/**
	 * Adds the Main plugin page and the categories page to the WordPress backend menu
	 * Also adds the Help tab to those pages
	 * @static
	 */
	public static function create_admin_menu() {
		global $cleverness_todo_page, $cleverness_todo_cat_page;

		$cleverness_todo_page = add_menu_page( __( 'To-Do List', 'cleverness-to-do-list' ), __( 'To-Do List', 'cleverness-to-do-list' ), self::$settings['view_capability'], 'cleverness-to-do-list',
				__CLASS__.'::plugin_page', CTDL_PLUGIN_URL.'/images/cleverness-todo-icon-sm.png' );
		if ( self::$settings['categories'] == '1' ) {
			$cleverness_todo_cat_page = add_submenu_page( 'cleverness-to-do-list', __( 'To-Do List Categories', 'cleverness-to-do-list' ), __( 'Categories', 'cleverness-to-do-list' ),
				self::$settings['add_cat_capability'], 'cleverness-to-do-list-cats', 'CTDL_Categories::create_category_page' );
			add_action( "load-$cleverness_todo_cat_page", 'CTDL_Help::cleverness_todo_help_tab' );
		}
		add_action( "load-$cleverness_todo_page", 'CTDL_Help::cleverness_todo_help_tab' );
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
	 * Adds the CSS and JS to the backend pages
	 * @static
	 */
	public static function admin_init() {
		global $cleverness_todo_page, $cleverness_todo_settings_page ;
		add_action( 'admin_print_styles-' . $cleverness_todo_page, __CLASS__.'::add_admin_css' );
		add_action( 'admin_print_scripts-' . $cleverness_todo_page, __CLASS__.'::add_admin_js' );
		add_action( 'admin_print_scripts-' . $cleverness_todo_settings_page, __CLASS__.'::add_admin_js' );
	}

	/**
	 * Loads the CSS file for the WP backend
	 * @static
	 */
	public static function add_admin_css() {
		$cleverness_style_url = CTDL_PLUGIN_URL . '/css/cleverness-to-do-list-admin.css';
		$cleverness_style_file = CTDL_PLUGIN_DIR . '/css/cleverness-to-do-list-admin.css';
		if ( file_exists( $cleverness_style_file ) ) {
			wp_register_style( 'cleverness_todo_style_sheet', $cleverness_style_url );
			wp_enqueue_style( 'cleverness_todo_style_sheet' );
			wp_enqueue_style( 'jquery.ui.theme', CTDL_PLUGIN_URL . '/css/jquery-ui-classic.css' );
		}
	}

	/**
	 * Loads and localizes JS files for the WP backend
	 * @static
	 */
	public static function add_admin_js() {
		wp_register_script( 'cleverness_todo_js', CTDL_PLUGIN_URL.'/js/cleverness-to-do-list-admin.js', '', 1.0, true );
		wp_enqueue_script( 'cleverness_todo_js' );
		wp_enqueue_script( 'jquery-color' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_localize_script( 'cleverness_todo_js', 'ctdl', CTDL_Loader::get_js_vars() );
	}

	/**
	 * Localize JS variables
	 * @static
	 * @return array
	 */
	public static function get_js_vars() {
		return array(
			'SUCCESS_MSG' => __( 'To-Do Deleted.', 'cleverness-to-do-list' ),
			'ERROR_MSG' => __( 'There was a problem performing that action.', 'cleverness-to-do-list' ),
			'PERMISSION_MSG' => __( 'You do not have sufficient privileges to do that.', 'cleverness-to-do-list' ),
			'CONFIRMATION_MSG' => __( "You are about to permanently delete the selected item. \n 'Cancel' to stop, 'OK' to delete.", 'cleverness-to-do-list' ),
			'CONFIRMATION_ALL_MSG' => __( "You are about to permanently delete all completed items. \n 'Cancel' to stop, 'OK' to delete.", 'cleverness-to-do-list' ),
			'CONFIRMATION_DEL_TABLES_MSG' => __( "You are about to permanently delete database tables. This cannot be undone. \n 'Cancel' to stop, 'OK' to delete.", 'cleverness-to-do-list' ),
			'NONCE' => wp_create_nonce( 'cleverness-todo' ),
			'AJAX_URL' => admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Add the Files for the To-Do List frontend
	 */
	public static function frontend_checklist_init() {
		global $CTDL_Frontend_Admin;
		if ( ! $CTDL_Frontend_Admin->add_script )
			return;

		wp_enqueue_style( 'jquery.ui.theme', CTDL_PLUGIN_URL . '/css/jquery-ui-classic.css' );
		wp_print_styles( 'jquery.ui.theme' );
	}

	/**
	 * Enqueue and Localize JavaScript for the To-Do List frontend
	 */
	public static function frontend_checklist_add_js() {
		global $CTDL_Frontend_Checklist, $CTDL_Frontend_Admin;
		if ( !$CTDL_Frontend_Checklist->add_script && !$CTDL_Frontend_Admin->add_script )
			return;

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-color' );
		wp_register_script( 'cleverness_todo_checklist_complete_js', CTDL_PLUGIN_URL.'/js/cleverness-to-do-list-frontend.js', array( 'jquery' ), 1.0, true );
		wp_enqueue_script( 'cleverness_todo_checklist_complete_js' );
		wp_localize_script( 'cleverness_todo_checklist_complete_js', 'ctdl', CTDL_Loader::get_js_vars() );
		wp_print_scripts( 'cleverness_todo_checklist_complete_js' );
	}

}
?>