<?php
/**
 * Cleverness To-Do List Plugin Loader
 *
 * Loads the plugin
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.2.1
 */

/**
 * Loader class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Loader {
	public static $settings;
	public static $dashboard_settings;

	public static function init() {
		global $ClevernessToDoList, $CTDL_Frontend_Checklist, $CTDL_Frontend_Admin, $CTDL_templates, $CTDL_Dashboard_Widget;

		if ( is_admin() ) self::check_for_upgrade();
		$general_options      = ( get_option( 'CTDL_general' ) ? get_option( 'CTDL_general' ) : array() );
		$advanced_options     = ( get_option( 'CTDL_advanced' ) ? get_option( 'CTDL_advanced' ) : array() );
		$permissions_options  = ( get_option( 'CTDL_permissions' ) ? get_option( 'CTDL_permissions' ) : array() );
		self::$settings       = array_merge( $general_options, $advanced_options, $permissions_options );
		self::$dashboard_settings = get_option( 'CTDL_dashboard_settings' );

		self::include_files();
		if ( !post_type_exists( 'todo' ) ) self::setup_custom_post_type();
		if ( !taxonomy_exists( 'todocategories' ) ) self::create_taxonomies();
		self::call_wp_hooks();

        $ClevernessToDoList = new ClevernessToDoList();
		$CTDL_templates = new CTDL_Template_Loader;

		if ( is_admin() ) {
			new CTDL_Settings();
			$CTDL_Dashboard_Widget = new CTDL_Dashboard_Widget();
		}
		$CTDL_Frontend_Admin     = new CTDL_Frontend_Admin;
		$CTDL_Frontend_Checklist = new CTDL_Frontend_Checklist;
		new CTDL_Frontend_List;
	}

	/**
	 * Check to see if plugin has an upgrade
	 * @static
	 * @since 3.1
	 */
	public static function check_for_upgrade() {
		global $wp_version;

		$exit_msg = esc_html__( 'To-Do List requires WordPress 3.8 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update.</a>', 'cleverness-to-do-list' );
		if ( version_compare( $wp_version, '3.8', '<' ) ) {
			exit( $exit_msg );
		}

		cleverness_todo_activation();
	}

	/**
	 * Set up custom post types for to-do items
	 * @static
	 * @since 3.0
	 */
	public static function setup_custom_post_type() {
		register_post_type( 'todo',
			array(
				'labels'              => array(
					'name'          => __( 'To-Do', 'cleverness-to-do-list' ),
					'singular_name' => __( 'To-Do', 'cleverness-to-do-list' )
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'hierarchical'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => true,
				'show_in_nav_menus'   => false,
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
			'name'          => _x( 'Categories', 'taxonomy general name', 'cleverness-to-do-list' ),
			'singular_name' => _x( 'Category', 'taxonomy singular name', 'cleverness-to-do-list' ),
		);

		register_taxonomy( 'todocategories', array( 'todo' ), array(
			'hierarchical' => true,
			'labels'       => $labels,
			'show_ui'      => false,
			'query_var'    => false,
			'rewrite'      => false,
		) );
	}

	/**
	 * Calls the plugin files for inclusion
	 * @static
	 */
	private static function include_files() {
		include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list-library.class.php';
		include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list.class.php';
		if ( !class_exists( 'Gamajo_Template_Loader' ) ) {
			include_once CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-template.class.php';
		}
		include_once CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-template-loader.class.php';
		include_once CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-template-functions.class.php';
		if ( self::$settings['categories'] == 1 ) include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list-categories.class.php';
		if ( is_admin() ) {
			include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list-settings.class.php';
			include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list-help.class.php';
			include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list-dashboard-widget.class.php';
		}
		include_once CTDL_PLUGIN_DIR.'includes/cleverness-to-do-list-frontend.class.php';
	}

	/**
	 * Adds actions to WordPress hooks
	 * @static
	 */
	private static function call_wp_hooks() {
		if ( self::$settings['admin_bar'] == 1 ) add_action( 'admin_bar_menu', array( 'CTDL_Lib', 'add_to_toolbar' ), 999 );
		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
			add_action( 'admin_menu', array( __CLASS__, 'create_admin_menu' ) );
			add_filter( 'plugin_action_links', array( 'CTDL_Lib', 'add_settings_link' ), 10, 2 );
			if ( self::$settings['categories'] == 1 ) add_action( 'admin_init', array( 'CTDL_Categories', 'initialize_categories' ) );
		}
		add_action( 'split_shared_term', array( 'CTDL_Lib', 'split_shared_term' ), 10, 4 );
		add_action( 'wp_ajax_cleverness_add_todo', array( 'CTDL_Lib', 'add_todo_callback' ) );
		add_action( 'wp_ajax_cleverness_delete_todo', array( 'CTDL_Lib', 'delete_todo_callback' ) );
		add_action( 'wp_ajax_cleverness_todo_complete', array( 'CTDL_Lib', 'complete_todo_callback' ) );
		add_action( 'wp_ajax_cleverness_frontend_display_todos', array( 'CTDL_Lib', 'frontend_display_todos_callback' ) );
	}

	/**
	 * Adds the Main plugin page and the categories page to the WordPress backend menu
	 * Also adds the Help tab to those pages
	 * @static
	 */
	public static function create_admin_menu() {
		global $cleverness_todo_page, $cleverness_todo_cat_page;

		$cleverness_todo_page = add_menu_page( apply_filters( 'ctdl_todo_list', __( 'To-Do List', 'cleverness-to-do-list' ) ), apply_filters( 'ctdl_todo_list', __( 'To-Do List', 'cleverness-to-do-list' ) ),
			self::$settings['view_capability'], 'cleverness-to-do-list', array( __CLASS__, 'plugin_page' ), apply_filters( 'ctdl_icon', 'dashicons-yes' ) );
		if ( self::$settings['categories'] == 1 ) {
			$cleverness_todo_cat_page = add_submenu_page( 'cleverness-to-do-list', apply_filters( 'ctdl_categories_title', __( 'To-Do List Categories', 'cleverness-to-do-list' ) ),
				apply_filters( 'ctdl_categories', __( 'Categories', 'cleverness-to-do-list' ) ),
				self::$settings['add_cat_capability'], 'cleverness-to-do-list-cats', array( 'CTDL_Categories', 'create_category_page' ) );
			add_action( "load-$cleverness_todo_cat_page", array( 'CTDL_Help', 'cleverness_todo_help_tab' ) );
		}
		add_action( "load-$cleverness_todo_page", array( 'CTDL_Help', 'cleverness_todo_help_tab' ) );
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
		global $cleverness_todo_page, $cleverness_todo_settings_page, $cleverness_todo_cat_page;

		add_action( 'admin_print_styles-'.$cleverness_todo_page, array( __CLASS__, 'add_admin_css' ) );
		add_action( 'admin_print_scripts-'.$cleverness_todo_page, array( __CLASS__, 'add_admin_js' ) );
		add_action( 'admin_print_styles-'.$cleverness_todo_cat_page, array( __CLASS__, 'add_admin_css' ) );
		add_action( 'admin_print_scripts-'.$cleverness_todo_cat_page, array( __CLASS__, 'add_admin_js' ) );
		add_action( 'admin_print_scripts-'.$cleverness_todo_settings_page, array( __CLASS__, 'add_admin_js' ) );
	}

	/**
	 * Loads the CSS file for the WP backend
	 * @static
	 */
	public static function add_admin_css() {
		$cleverness_style_url = CTDL_PLUGIN_URL.'/css/cleverness-to-do-list-admin.css';
		$cleverness_style_file = CTDL_PLUGIN_DIR.'/css/cleverness-to-do-list-admin.css';
		if ( file_exists( $cleverness_style_file ) ) {
			wp_register_style( 'cleverness_todo_style_sheet', $cleverness_style_url, array(), CTDL_PLUGIN_VERSION );
			wp_enqueue_style( 'cleverness_todo_style_sheet' );
			wp_enqueue_style( 'jquery.ui.theme', CTDL_PLUGIN_URL.'/css/jquery-ui-fresh.css', array(), CTDL_PLUGIN_VERSION );
			wp_enqueue_style( 'cleverness_todo_select_css', CTDL_PLUGIN_URL.'/css/cleverness-to-do-list-select2.css', array(), CTDL_PLUGIN_VERSION );
		}
	}

	/**
	 * Loads and localizes JS files for the WP backend
	 * @static
	 */
	public static function add_admin_js() {
		wp_register_script( 'cleverness_todo_js', CTDL_PLUGIN_URL.'/js/cleverness-to-do-list-admin.js', '', CTDL_PLUGIN_VERSION, true );
		wp_register_script( 'cleverness_metadata_js', CTDL_PLUGIN_URL.'/js/jquery.metadata.js', '', CTDL_PLUGIN_VERSION, true );
		wp_register_script( 'cleverness_tablesorter_js', CTDL_PLUGIN_URL.'/js/jquery.tablesorter.min.js', '', CTDL_PLUGIN_VERSION, true );
		wp_register_script( 'cleverness_todo_select2_js', CTDL_PLUGIN_URL.'/js/select2.min.js', '', CTDL_PLUGIN_VERSION, true );
		wp_enqueue_script( 'cleverness_todo_js' );
		wp_enqueue_script( 'cleverness_metadata_js' );
		wp_enqueue_script( 'cleverness_tablesorter_js' );
		wp_enqueue_script( 'jquery-color' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-slider' );
		wp_enqueue_script( 'jquery-ui-widget' );
		wp_enqueue_script( 'jquery-ui-mouse' );
		wp_enqueue_script( 'jquery-ui-resizable' );
		wp_enqueue_script( 'cleverness_todo_select2_js' );
		wp_localize_script( 'cleverness_todo_js', 'ctdl', CTDL_Loader::get_js_vars() );
	}

	/**
	 * Localize JS variables
	 * @static
	 * @return array
	 */
	public static function get_js_vars() {
		global $CTDL_Frontend_Admin;
		$array = array(
			'INSERT_MSG'                  => __( 'New To-Do Added', 'cleverness-to-do-list' ),
			'SUCCESS_MSG'                 => __( 'To-Do Deleted.', 'cleverness-to-do-list' ),
			'ERROR_MSG'                   => __( 'There was a problem performing that action.', 'cleverness-to-do-list' ),
			'PERMISSION_MSG'              => __( 'You do not have sufficient privileges to do that.', 'cleverness-to-do-list' ),
			'CONFIRMATION_MSG'            => __( "You are about to permanently delete the selected item. \n 'Cancel' to stop, 'OK' to delete.", 'cleverness-to-do-list' ),
			'CONFIRMATION_ALL_MSG'        => __( "You are about to permanently delete all completed items. \n 'Cancel' to stop, 'OK' to delete.", 'cleverness-to-do-list' ),
			'CONFIRMATION_DELETE_ALL_MSG' => __( "You are about to permanently delete all to-do items. \n 'Cancel' to stop, 'OK' to delete.", 'cleverness-to-do-list' ),
			'CONFIRMATION_DEL_TABLES_MSG' => __( "You are about to permanently delete database tables. This cannot be undone. \n 'Cancel' to stop, 'OK' to delete.", 'cleverness-to-do-list' ),
			'SELECT_USER'                 => __( 'Select a User', 'cleverness-to-do-list' ),
			'NONCE'                       => wp_create_nonce( 'ctdl-todo' ),
			'AJAX_URL'                    => admin_url( 'admin-ajax.php' ),
		);
		if ( isset( $CTDL_Frontend_Admin->atts ) ) {
			$array['TODOADMIN_ATTS'] = $CTDL_Frontend_Admin->atts;
		}

		return $array;
	}

	/**
	 * Register the scripts for the To-Do List admin frontend
	 */
	public static function frontend_admin_register_scripts() {
		wp_register_script( 'cleverness_metadata_js', CTDL_PLUGIN_URL . '/js/jquery.metadata.js', '', CTDL_PLUGIN_VERSION, true );
		wp_register_script( 'cleverness_tablesorter_js', CTDL_PLUGIN_URL . '/js/jquery.tablesorter.min.js', '', CTDL_PLUGIN_VERSION, true );
		wp_register_script( 'cleverness_todo_select2_js', CTDL_PLUGIN_URL . '/js/select2.min.js', '', CTDL_PLUGIN_VERSION, true );
		wp_register_script( 'cleverness_todo_frontend_admin_js', CTDL_PLUGIN_URL . '/js/cleverness-to-do-list-frontend-admin.js', array( 'jquery' ), CTDL_PLUGIN_VERSION, true );
	}

	/**
	 * Enqueue the scripts for the To-Do List admin frontend
	 */
	public static function frontend_admin_enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-color' );
		wp_enqueue_script( 'jquery-ui-slider' );
		wp_enqueue_script( 'jquery-ui-widget' );
		wp_enqueue_script( 'jquery-ui-mouse' );
		wp_enqueue_script( 'cleverness_metadata_js' );
		wp_enqueue_script( 'cleverness_tablesorter_js' );
		wp_enqueue_script( 'cleverness_todo_select2_js' );
		wp_enqueue_script( 'cleverness_todo_frontend_admin_js' );

		wp_localize_script( 'cleverness_todo_frontend_admin_js', 'ctdl', CTDL_Loader::get_js_vars() );
	}

	/**
	 * Register the scripts for the To-Do List frontend checklist
	 */
	public static function frontend_checklist_register_scripts() {
		wp_register_script( 'cleverness_todo_checklist_complete_js', CTDL_PLUGIN_URL . '/js/cleverness-to-do-list-frontend.js', array( 'jquery' ), CTDL_PLUGIN_VERSION, true );
	}

	/**
	 * Enqueue and Localize JavaScript for the To-Do List frontend checklist
	 */
	public static function frontend_checklist_enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'cleverness_todo_checklist_complete_js' );

		wp_localize_script( 'cleverness_todo_checklist_complete_js', 'ctdl', CTDL_Loader::get_js_vars() );
	}

	/**
	 * Register and enqueue the css for the frontend
	 */
	public static function frontend_css() {
		wp_enqueue_style( 'cleverness_todo_list_frontend', CTDL_PLUGIN_URL . '/css/cleverness-to-do-list-frontend.css', array(), CTDL_PLUGIN_VERSION );
		wp_enqueue_style( 'jquery.ui.theme', CTDL_PLUGIN_URL . '/css/jquery-ui-fresh.css', array(), CTDL_PLUGIN_VERSION );
	}

}