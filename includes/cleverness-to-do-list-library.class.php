<?php
/**
 * Cleverness To-Do List Plugin Library
 *
 * Library of functions for the To-Do List
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.0.2
 */

/**
 * Library class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Lib {

	/* Get to-do list item */
	public static function get_todo( $id ) {
		$post = get_post( absint( $id ) );
		return $post;
	}

	public static function get_todos( $user, $limit = -1, $status = 0, $cat_id = 0, $to_exclude = array() ) {

		/* Sort Order */
		// if sort_order is post_date, order by that first
		if ( CTDL_Loader::$settings['sort_order'] == 'post_date' ) {
			$orderby = 'post_date';
			$metakey = '';
		// if sort order is deadline, progress, or assigned user, order by that
		} elseif ( CTDL_Loader::$settings['sort_order'] == '_deadline' || CTDL_Loader::$settings['sort_order'] == '_progress' || CTDL_Loader::$settings['sort_order'] == '_assign' ) {
			$orderby = 'meta_value title';
			$metakey = CTDL_Loader::$settings['sort_order'];
		// otherwise, order first by priority
		} else {
			$orderby = 'meta_value '.CTDL_Loader::$settings['sort_order'].' title';
			$metakey = '_priority';
		}

		/* Author */
		if ( CTDL_Loader::$settings['list_view'] == 0 ) {
			$author = $user;
		} else {
			$author = NULL;
		}

		/* View Settings */

		// In Group View, Show Only Tasks Assigned to That User when Set
		if ( CTDL_Loader::$settings['list_view'] == '1' && CTDL_Loader::$settings['show_only_assigned'] == '0' && ( !current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) {
			$metaquery = array(
				array(
					'key'   => '_status',
					'value' => $status,
				),
				array (
					'key'   => '_assign',
					'value' => $user,
				)
			);

		// Master view with No Editing Capabilities
		} elseif ( CTDL_Loader::$settings['list_view'] == '2' && !current_user_can( CTDL_Loader::$settings['edit_capability'] ) ) {

			if ( $status == 0 ) {
				// first get all the posts where _user_USERID_status = 1 and put them into an array
				// then exclude those items from the query where you get all the posts that have status = 0
				$posts_to_exclude_args = array(
					'post_type'      => 'todo',
					'author'         => $author,
					'post_status'    => 'publish',
					'meta_query'     => array(
											array(
												'key' => '_user_'.$user.'_status',
												'value' => 1,
											) )
					);
				$posts_to_exclude = new WP_Query( $posts_to_exclude_args );
				while ( $posts_to_exclude->have_posts() ) : $posts_to_exclude->the_post();
					$to_exclude[] = get_the_ID();
				endwhile;

				if ( CTDL_Loader::$settings['show_only_assigned'] == '0' && ( !current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) {
					$metaquery = array(
						array(
							'key'   => '_status',
							'value' => $status,
						),
						array (
							'key'   => '_assign',
							'value' => $user,
						)
					);
				} else {
					$metaquery = array(
						array(
							'key'   => '_status',
							'value' => 0,
						)
					);
				}

			} elseif ( $status == 1 ) {
				$metaquery = array(
					array(
						'key'   => '_status',
						'value' => 0,
					),
					array(
						'key'   => '_user_'.$user.'_status',
						'value' => 1,
					)
				);
			}

		} else {
			$metaquery = array(
				array(
					'key'   => '_status',
					'value' => $status,
				) );
		}

		// if a category id has been defined
		if ( $cat_id != 0 ) {
			$args = array(
				'post_type'      => 'todo',
				'author'         => $author,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => $orderby,
				'order'          => 'ASC',
				'post__not_in'   => $to_exclude,
				'tax_query'      => array(
										array(
											'taxonomy' => 'todocategories',
											'field' => 'id',
											'terms' => $cat_id
										) ),
				'meta_query'     => $metaquery
			);
			$results = new WP_Query( $args );

		// if no category id has been defined
		} else {
			$args = array(
				'post_type'      => 'todo',
				'author'         => $author,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => $orderby,
				'meta_key'       => $metakey,
				'order'          => 'ASC',
				'meta_query'     => $metaquery,
				'post__not_in'   => $to_exclude,
			);
			$results = new WP_Query( $args );
		}

		return $results;
	}

	public static function complete_todo_callback() {
		check_ajax_referer( 'cleverness-todo' );
		$permission = CTDL_Lib::check_permission( 'todo', 'complete' );

		if ( $permission === true ) {
			$message = CTDL_Lib::complete_todo( absint( $_POST['cleverness_id'] ), absint( $_POST['cleverness_status'] ) );
		} else {
			$message = __( 'You do not have sufficient privileges to do that.', 'cleverness-to-do-list' );
		}
		echo $message;

		die(); // this is required to return a proper result
	}

	public static function complete_todo( $id, $status ) {
		global $current_user;

		// if individual view, group view with complete capability, or master view with edit capability
		if ( CTDL_Loader::$settings['list_view'] == '0' || ( CTDL_Loader::$settings['list_view'] == '1' && current_user_can( CTDL_Loader::$settings['complete_capability'] ) )
			|| ( CTDL_Loader::$settings['list_view'] == '2' && current_user_can( CTDL_Loader::$settings['edit_capability'] ) ) ) {

			update_post_meta( $id, '_status', $status );
			update_post_meta( $id, '_completed', date( 'Y-m-d' ) );

		// else if master view with no edit capability
		} elseif ( CTDL_Loader::$settings['list_view'] == '2' ) {
			$user = $current_user->ID;

			update_post_meta( absint( $id ), '_user_'.$user.'_status', $status );

			if ( $status == 1 ) {
				update_post_meta( absint( $id ), '_user_'.$user.'_completed', date( 'Y-m-d' ));
			}

		}

	}

	/* Insert new to-do item into the database */
	public static function insert_todo() {
		global $current_user;

		if ( $_POST['cleverness_todo_description'] == '' ) return;

		$permission = CTDL_LIB::check_permission( 'todo', 'add' );

		if ( $permission === true ) {

			if ( !wp_verify_nonce( $_REQUEST['todoadd'], 'todoadd' ) ) die( 'Security check failed' );

			if ( CTDL_Loader::$settings['email_assigned'] == '1' && CTDL_Loader::$settings['assign'] == '0' ) {
				CTDL_Lib::email_user( $_POST['cleverness_todo_assign'], $_POST['cleverness_todo_deadline'], $_POST['cat'] );
			}

			$my_post = array(
				'post_type'        => 'todo',
				'post_title'       => substr( $_POST['cleverness_todo_description'], 0, 100 ),
				'post_content'     => $_POST['cleverness_todo_description'],
				'post_status'      => 'publish',
				'post_author'      => $current_user->ID,
				'comment_status'   => 'closed',
				'ping_status'      => 'closed',
			);

			$post_id = wp_insert_post( $my_post );

			if ( isset( $_POST['cat'] ) ) wp_set_post_terms( $post_id, absint( $_POST['cat'] ), 'todocategories', false);
			add_post_meta( $post_id, '_status', 0, true );
			$priority = ( isset( $_POST['cleverness_todo_priority'] ) ? absint( $_POST['cleverness_todo_priority'] ) : 1 );
			add_post_meta( $post_id, '_priority', $priority, true );
			$assign = ( isset( $_POST['cleverness_todo_assign'] ) ? esc_attr( $_POST['cleverness_todo_assign'] ) : -1 );
			add_post_meta( $post_id, '_assign', $assign, true );
			$deadline = ( isset( $_POST['cleverness_todo_deadline'] ) ? esc_attr( $_POST['cleverness_todo_deadline'] ) : '' );
			add_post_meta( $post_id, '_deadline', $deadline, true );
			$progress = ( isset( $_POST['cleverness_todo_progress'] ) ? absint( $_POST['cleverness_todo_progress'] ) : 0 );
			add_post_meta( $post_id, '_progress', $progress, true );

		}

		return;
	}

	/* Send an email to assigned user */
	protected static function email_user( $assign, $deadline, $category = 0 ) {
		global $current_user;
		get_currentuserinfo();

		$priority = esc_attr( $_POST['cleverness_todo_priority'] );
		$todo_text = esc_html( $_POST['cleverness_todo_description'] );
		$priority_array = array( 0 => CTDL_Loader::$settings['priority_0'], 1 => CTDL_Loader::$settings['priority_1'], 2 => CTDL_Loader::$settings['priority_2'] );
		if ( $category != 0 && $category != -1 ) $category_name = CTDL_Categories::get_category_name( $category );

		if ( current_user_can( CTDL_Loader::$settings['assign_capability'] ) && $assign != '' && $assign != '-1' && $assign != '0' ) {
			$headers = 'From: '.CTDL_Loader::$settings['email_from'].' <'.get_bloginfo( 'admin_email' ).'>' . "\r\n\\";
			$subject = CTDL_Loader::$settings['email_subject'];
			if ( CTDL_Loader::$settings['email_category'] == 1 && $category != 0 && $category != -1 ) {
	            $subject .= ' - '.$category_name;
			}
			$assign_user = get_userdata( $assign );
			$email = $assign_user->user_email;
			$email_message = CTDL_Loader::$settings['email_text']."\r\n";
			$email_message .= "\r\n".__( 'Priority', 'cleverness-to-do-list' ).': '.$priority_array[$priority]."\r\n";
			if ( CTDL_Loader::$settings['email_show_assigned_by'] == 1 ) $email_message .= "\r\n".__( 'From', 'cleverness-to-do-list' ).': '.$current_user->display_name.' ('.$current_user->user_email.')'."\r\n";
			if ( $category != 0 && $category != -1 ) $email_message .= __( 'Category', 'cleverness-to-do-list' ).': '.$category."\r\n";
			if ( $deadline != '' ) $email_message .= __( 'Deadline:', 'cleverness-to-do-list' ).' '.$deadline."\r\n";
			$email_message .= __( 'To-Do:', 'cleverness-to-do-list' ).$todo_text."\r\n";
			wp_mail( $email, $subject, $email_message, $headers );
		}

	}

	/* Update to-do list item */
	public static function edit_todo() {
		$permission = CTDL_LIB::check_permission( 'todo', 'edit' );

		if ( $permission === true ) {

			if ( !wp_verify_nonce( $_REQUEST['todoupdate'], 'todoupdate' ) ) die( 'Security check failed' );

			$my_post = array(
				'ID'            => absint( $_POST['id'] ),
				'post_title'    => substr( $_POST['cleverness_todo_description'], 0, 100 ),
				'post_content'  => $_POST['cleverness_todo_description'],
			);

			$post_id = wp_update_post( $my_post );

			if ( isset( $_POST['cat'] ) ) wp_set_post_terms( $post_id, absint( $_POST['cat'] ), 'todocategories', false);
			if ( isset( $_POST['cleverness_todo_priority'] ) ) update_post_meta( $post_id, '_priority', esc_attr( $_POST['cleverness_todo_priority'] ) );
			if ( isset( $_POST['cleverness_todo_assign'] ) ) update_post_meta( $post_id, '_assign', esc_attr( $_POST['cleverness_todo_assign'] ) );
			if ( isset( $_POST['cleverness_todo_deadline'] ) ) update_post_meta( $post_id, '_deadline', esc_attr( $_POST['cleverness_todo_deadline'] ) );
			if ( isset( $_POST['cleverness_todo_progress'] ) ) update_post_meta( $post_id, '_progress', absint( $_POST['cleverness_todo_progress'] ) );

		}

		return;
	}

	/* Delete To-Do Ajax */
	public static function delete_todo_callback() {
		check_ajax_referer( 'cleverness-todo' );
		$permission = CTDL_Lib::check_permission( 'todo', 'delete' );
		$status = ( $permission === true ? CTDL_Lib::delete_todo( absint( $_POST['cleverness_todo_id'] ) ) : -1 );
		echo $status;
		die(); // this is required to return a proper result
	}

	/* Delete to-do list item */
	public static function delete_todo( $id ) {
		wp_delete_post( absint( $id ), true );
		return 1;
	}

	/* Delete all completed to-do list items */
	public static function delete_all_completed_todos() {
		global $userdata;

		$permission = CTDL_LIB::check_permission( 'todo', 'purge' );

		if ( $permission === true ) {
			$cleverness_todo_purge_nonce = $_REQUEST['_wpnonce'];
			if ( !wp_verify_nonce( $cleverness_todo_purge_nonce, 'todopurge' ) ) die( 'Security check failed' );

			if ( CTDL_Loader::$settings['list_view'] == '0' ) {
				$args = array(
					'post_type' => 'todo',
					'author' => $userdata->ID,
					'post_status' => 'publish',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => '_status',
							'value' => 1,
						)
					)
				);

			} elseif ( CTDL_Loader::$settings['list_view'] == '1' || CTDL_Loader::$settings['list_view'] == '2' ) {
				$args = array(
					'post_type' => 'todo',
					'post_status' => 'publish',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => '_status',
							'value' => 1,
						)
					)
				);
			}

			$todo_items = new WP_Query( $args );

			while ( $todo_items->have_posts() ) : $todo_items->the_post();
				$id = get_the_ID();
				wp_delete_post( absint( $id ), true );
			endwhile;
		}
	}

	/* Delete all to-do list items */
	public static function delete_all_todos() {

		$permission = CTDL_LIB::check_permission( 'todo', 'purge' );

		if ( $permission === true ) {
			$cleverness_todo_delete_all_nonce = $_REQUEST['_wpnonce'];
			if ( !wp_verify_nonce( $cleverness_todo_delete_all_nonce, 'tododeletetodos' ) ) die( 'Security check failed' );

			$args = array(
				'post_type' => 'todo',
				'posts_per_page' => -1,
				);

			$todo_items = new WP_Query( $args );

			while ( $todo_items->have_posts() ) : $todo_items->the_post();
				$id = get_the_ID();
				wp_delete_post( absint( $id ), true );
			endwhile;
		}
	}

	/**
	 * Delete old custom database tables
	 * @static
	 */
	public static function delete_tables() {

		$permission = CTDL_LIB::check_permission( 'todo', 'purge' );

		if ( $permission === true ) {
			global $wpdb;

			$cleverness_todo_delete_tables_nonce = $_REQUEST['_wpnonce'];
			if ( !wp_verify_nonce( $cleverness_todo_delete_tables_nonce, 'tododeletetables' ) ) die( 'Security check failed' );
			if ( !function_exists( 'is_plugin_active_for_network' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			if ( is_plugin_active_for_network( __FILE__ ) ) {
				$prefix = $wpdb->base_prefix;
			} else {
				$prefix = $wpdb->prefix;
			}

			$thetable = $prefix."todolist";
			$wpdb->query( "DROP TABLE IF EXISTS $thetable" );
			$thecattable = $prefix."todolist_cats";
			$wpdb->query( "DROP TABLE IF EXISTS $thecattable" );
			$thestatustable = $prefix."todolist_status";
			$wpdb->query( "DROP TABLE IF EXISTS $thestatustable" );
		}
	}

	/**
	 * Set priority, user, url, and action variables
	 * @return array
	 */
	public static function set_variables() {
		global $current_user, $userdata;
		$priorities = array( 0 => CTDL_Loader::$settings['priority_0'],
		                     1 => CTDL_Loader::$settings['priority_1'],
		                     2 => CTDL_Loader::$settings['priority_2'] );
		$user = CTDL_Lib::get_user_id( $current_user, $userdata );
		$url = CTDL_Lib::get_page_url();
		$url = strtok( $url, '?' );
		$action = ( isset( $_GET['action'] ) ? $_GET['action'] : '' );
		return array( $priorities, $user, $url, $action );
	}

	/**
	 * Get the To-Do post meta and assign to variables
	 * @static
	 * @param $id
	 * @return array
	 * @since 3.1
	 */
	public static function get_todo_meta( $id ) {
		$post_meta = get_post_custom( $id );
		$priority = ( isset( $post_meta['_priority'][0] ) ? $post_meta['_priority'][0] : 1 );
		$assign_meta = ( isset( $post_meta['_assign'][0] ) ? $post_meta['_assign'][0] : 0 );
		$deadline_meta = ( isset( $post_meta['_deadline'][0] ) ? $post_meta['_deadline'][0] : '' );
		$completed_meta = ( isset( $post_meta['_completed'][0] ) ? $post_meta['_completed'][0] : NULL );
		$progress_meta = ( isset( $post_meta['_progress'][0] ) ? $post_meta['_progress'][0] : '' );
		return array( $priority, $assign_meta, $deadline_meta, $completed_meta, $progress_meta );
	}

	/**
	 * Sets the priority class of a to-do item
	 * @static
	 * @param $priority
	 * @return string
	 * @since 3.1
	 */
	public static function set_priority_class( $priority ) {
		$priority_class = '';
		if ( $priority == '0' ) $priority_class = ' class="todo-important"';
		if ( $priority == '1' ) $priority_class = ' class="todo-normal"';
		if ( $priority == '2' ) $priority_class = ' class="todo-low"';
		return $priority_class;
	}

	/* Check if User Has Permission */
	public static function check_permission( $type, $action ) {

		switch ( $type ) {
			case 'category':
				// check if categories are enabled and the user has the capability or the list view is individual
				$permission = ( CTDL_Loader::$settings['categories'] == '1' && ( current_user_can( CTDL_Loader::$settings[$action.'_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) ? true : false );
				break;
			case 'todo':
				$permission = ( current_user_can( CTDL_Loader::$settings[$action.'_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ? true : false );
				break;
		}

		return $permission;
	}

	/**
	 * Gets the ID of a user
	 * @param $current_user
	 * @param $userdata
	 * @return int
	 */
	public static function get_user_id( $current_user, $userdata ) {
		$user = ( CTDL_Loader::$settings['list_view'] == 2 ? $current_user->ID : $userdata->ID );
		return $user;
	}

	/* Get list of users */
	public static function get_users( $role ) {
		$wp_user_search = new WP_User_Query( array( 'role' => $role ) );
		return $wp_user_search->get_results();
	}

	/**
	 * Get the Correct URL of a Page
	 * @return string
	 */
	public static function get_page_url() {
		$pageURL = 'http';
		if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) { $pageURL .= "s"; }
		$pageURL .= "://";
		if ( $_SERVER["SERVER_PORT"] != "80" ) {
			$pageURL .= $_SERVER["HTTP_HOST"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

	/**
	 * Add an Item to the Admin Menu
	 * @param $wp_admin_bar
	 */
	public function add_to_toolbar( $wp_admin_bar ) {
		$wp_admin_bar->add_node( array(
			'id'    => 'todolist',
			'title' => __( 'To-Do List', 'cleverness-to-do-list' ),
			'href'  => get_admin_url().'admin.php?page=cleverness-to-do-list',
			'parent' => false
		) );

		if ( current_user_can( CTDL_Loader::$settings['add_capability'] ) ) {

			$wp_admin_bar->add_node( array(
				'id'     => 'todolist-add',
				'title'  => __( 'Add New To-Do Item', 'cleverness-to-do-list' ),
				'parent' => 'todolist',
				'href'   => get_admin_url().'admin.php?page=cleverness-to-do-list#addtodo'
			) );

		}
	}

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

	/* Add plugin info to admin footer */
	public static function cleverness_todo_admin_footer() {
		$plugin_data = get_plugin_data( CTDL_FILE );
		printf( __( "%s plugin | Version %s | by %s | <a href='https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=cindy@cleverness.org' target='_blank'>Donate</a><br />", 'cleverness-to-do-list' ), $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author'] );
	}

	/* Create database table and add default options */
	public static function install_plugin () {

		// get database version from options table
		if ( get_option( 'CTDL_db_version' ) ) {
			$installed_version = get_option( 'CTDL_db_version' );
		} elseif ( get_option( 'cleverness_todo_db_version' ) ) {
			$installed_version  = get_option( 'cleverness_todo_db_version' );
		} else {
			$installed_version  = 0;
		}

		// check if the db version is the same as the db version constant
		if ( $installed_version != CTDL_DB_VERSION ) {

			include_once plugin_dir_path( __FILE__ ).'/cleverness-to-do-list-loader.class.php';
			if ( !post_type_exists( 'todo' ) ) CTDL_Loader::setup_custom_post_type();
			if ( !taxonomy_exists( 'todocategories' ) ) CTDL_Loader::create_taxonomies();

			// if there was no db version option
			if ( $installed_version  == 0 ) {

				// check to see if there are any to-do custom posts
				$existing_todos = self::check_for_todos();

				// if not, add the first to-do item
				if ( $existing_todos == 0 ) {
					global $current_user;
					get_currentuserinfo();

					// add first post
					$first_post = __( 'Add your first To-Do List item', 'cleverness-to-do-list' );

					$the_post = array(
						'post_type'        => 'todo',
						'post_title'       => substr( $first_post, 0, 100 ),
						'post_content'     => $first_post,
						'post_status'      => 'publish',
						'post_author'      => $current_user->ID,
						'comment_status'   => 'closed',
						'ping_status'      => 'closed',
					);

					$post_id = wp_insert_post( $the_post );
					add_post_meta( $post_id, '_status', 0, true );
					add_post_meta( $post_id, '_priority', 1, true );
					add_post_meta( $post_id, '_assign', -1, true );
					add_post_meta( $post_id, '_deadline', '', true );
					add_post_meta( $post_id, '_progress', 0, true );
				}

				self::set_options( $installed_version );

			} else {

				// if the db version is < 3.0
				if ( $installed_version < 3 ) {

					// check to see if there's existing to-do items. if so, convert them to custom posts
					$existing_todos = self::check_for_todos();

					if ( $existing_todos == 0 ) {
						self::convert_todos();
					}

				}

				self::set_options( $installed_version );

				// update db version to current versions
				update_option( 'CTDL_db_version', CTDL_DB_VERSION );

			}

		}

	}

	public static function set_options( $version ) {

		if ( $version == 0 ) {
			// add default options
			$general_options = array(
				'categories'            => 0,
				'list_view'             => 0,
				'todo_author'           => 0,
				'show_completed_date'   => 0,
				'show_deadline'         => 0,
				'show_progress'         => 0,
				'sort_order'            => 'ID',
				'admin_bar'             => 1
			);

			$advanced_options = array(
				'date_format'               => 'm/d/Y',
				'priority_0'                => __( 'Important', 'cleverness-to-do-list' ),
				'priority_1'                => __( 'Normal', 'cleverness-to-do-list' ),
				'priority_2'                => __( 'Low', 'cleverness-to-do-list' ),
				'assign'                    => 1,
				'show_only_assigned'        => 1,
				'user_roles'                => 'contributor, author, editor, administrator',
				'email_assigned'            => 0,
				'email_from'                => html_entity_decode( get_bloginfo( 'name' ) ),
				'email_subject'             => __( 'A to-do list item has been assigned to you', 'cleverness-to-do-list' ),
				'email_text'                => __( 'The following item has been assigned to you:', 'cleverness-to-do-list' ),
				'email_category'            => 1,
				'email_show_assigned_by'    => 0,
				'show_id'                   => 0,
				'show_date_added'           => 0,
			);

			$permissions_options = array(
				'view_capability'              => 'publish_posts',
				'add_capability'               => 'publish_posts',
				'edit_capability'              => 'publish_posts',
				'delete_capability'            => 'manage_options',
				'purge_capability'             => 'manage_options',
				'complete_capability'          => 'publish_posts',
				'assign_capability'            => 'manage_options',
				'view_all_assigned_capability' => 'manage_options',
				'add_cat_capability'           => 'manage_options',
			);

			add_option( 'CTDL_general', $general_options );
			add_option( 'CTDL_advanced', $advanced_options );
			add_option( 'CTDL_permissions', $permissions_options );
			add_option( 'CTDL_db_version', CTDL_DB_VERSION );

		} else {

			if ( get_option( 'cleverness_todo_settings' ) ) {
				$the_options = get_option( 'cleverness_todo_settings' );
				if ( $the_options['categories'] == '' ) $the_options['categories'] = '0';
				if ( $the_options['sort_order'] == '' ) $the_options['sort_order'] = 'id';
				if ( $the_options['add_cat_capability'] == '' ) $the_options['add_cat_capability'] = 'manage_options';
				if ( $the_options['dashboard_cat'] == '' ) $the_options['dashboard_cat'] = 'All';
				if ( $the_options['email_text'] == '' ) $the_options['email_text'] = __( 'The following item has been assigned to you:', 'cleverness-to-do-list' );
				if ( $the_options['email_subject'] == '' ) $the_options['email_subject'] = __( 'A to-do list item has been assigned to you', 'cleverness-to-do-list' );
				if ( $the_options['email_from'] == '' ) $the_options['email_from'] = html_entity_decode( get_bloginfo( 'name' ) );

				$general_options = array(
					'categories'            => $the_options['categories'],
					'list_view'             => $the_options['list_view'],
					'todo_author'           => $the_options['todo_author'],
					'show_completed_date'   => $the_options['show_completed_date'],
					'show_deadline'         => $the_options['show_deadline'],
					'show_progress'         => $the_options['show_progress'],
					'sort_order'            => $the_options['sort_order'],
					'admin_bar'             => 1
				);

				$advanced_options = array(
					'date_format'               => $the_options['date_format'],
					'priority_0'                => $the_options['priority_0'],
					'priority_1'                => $the_options['priority_1'],
					'priority_2'                => $the_options['priority_2'],
					'assign'                    => $the_options['assign'],
					'show_only_assigned'        => $the_options['show_only_assigned'],
					'user_roles'                => $the_options['user_roles'],
					'email_assigned'            => $the_options['email_assigned'],
					'email_from'                => $the_options['email_from'],
					'email_subject'             => $the_options['email_subject'],
					'email_text'                => $the_options['email_text'],
					'email_category'            => 1,
					'email_show_assigned_by'    => 0,
					'show_id'                   => 0,
					'show_date_added'           => 0,
				);

				$permissions_options = array(
					'view_capability'              => $the_options['view_capability'],
					'add_capability'               => $the_options['add_capability'],
					'edit_capability'              => $the_options['edit_capability'],
					'delete_capability'            => $the_options['delete_capability'],
					'purge_capability'             => $the_options['purge_capability'],
					'complete_capability'          => $the_options['complete_capability'],
					'assign_capability'            => $the_options['assign_capability'],
					'view_all_assigned_capability' => $the_options['view_all_assigned_capability'],
					'add_cat_capability'           => $the_options['add_cat_capability'],
				);

				if ( $general_options['sort_order'] == 'todotext' ) {
					$general_options['sort_order'] = 'title';
				} elseif ( $general_options['sort_order'] == 'id' ) {
					$general_options['sort_order'] = 'ID';
				} elseif ( $general_options['sort_order'] == 'deadline' ) {
					$general_options['sort_order'] = '_deadline';
				} elseif ( $general_options['sort_order'] == 'progress' ) {
					$general_options['sort_order'] = '_progress';
				} elseif ( $general_options['sort_order'] == 'assign' ) {
					$general_options['sort_order'] = '_assign';
				}

				update_option( 'CTDL_general', $general_options );
				update_option( 'CTDL_advanced', $advanced_options );
				update_option( 'CTDL_permissions', $permissions_options );
				delete_option( 'atd_db_version' );
				delete_option( 'cleverness_todo_db_version' );
				delete_option( 'cleverness_todo_settings' );

			} elseif ( $version < 3.1 ) {
				$advanced_options = get_option( 'CTDL_advanced' );
				$advanced_options['email_show_assigned_by'] = 0;
				$advanced_options['show_date_added'] = 0;
				update_option( 'CTDL_advanced', $advanced_options );
			}

		}
	}

	/**
	 * Check for existing to-do custom post types
	 * @static
	 * @since 3.1
	 * @return int
	 */
	public static function check_for_todos() {
		$args = array(
			'post_type' => 'todo',
		);
		$todo_items = new WP_Query( $args );
		if ( $todo_items->have_posts() ) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Convert to-dos from old custom tables to custom post types
	 * @static
	 * @since 3.0
	 */
	public static function convert_todos() {
		global $wpdb;

		if ( !function_exists( 'is_plugin_active_for_network' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		if ( is_plugin_active_for_network( CTDL_FILE ) ) {
			$prefix = $wpdb->base_prefix;
		} else {
			$prefix = $wpdb->prefix;
		}

		$table_name         = $prefix.'todolist';
		$cat_table_name     = $prefix.'todolist_cats';
		$status_table_name  = $prefix.'todolist_status';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$cat_table_name'" ) == $cat_table_name ) {

			$cats = $wpdb->get_results( "SELECT * FROM $cat_table_name" );

			if ( !empty( $cats ) ) {

				foreach ( $cats as $cat ) {
					$term = wp_insert_term( $cat->name, 'todocategories' );
					if ( !is_wp_error( $term ) ) {
						$category_id = $term['term_id'];
						$options = get_option( 'CTDL_categories' );
						$options["category_$category_id"] = $cat->visibility;
						update_option( "CTDL_categories", $options );
					}
				}

			}

		}

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {

			$todos = $wpdb->get_results( "SELECT * FROM $table_name" );

			if ( !empty( $todos ) ) {

				foreach ( $todos as $todo ) {

					$post['post_type']      = 'todo';
					$post['post_title']     = substr( $todo->todotext, 0, 100 );
					$post['post_content']   = $todo->todotext;
					$post['post_parent']    = 0;
					$post['post_author']    = $todo->author;
					$post['post_status']    = 'publish';
					$post['comment_status'] = 'closed';
					$post['ping_status']    = 'closed';

					$post_id = wp_insert_post( $post );

					add_post_meta( $post_id, '_status', $todo->status, true );
					add_post_meta( $post_id, '_priority', $todo->priority, true );
					add_post_meta( $post_id, '_assign', $todo->assign, true );
					add_post_meta( $post_id, '_deadline', $todo->deadline, true );
					add_post_meta( $post_id, '_progress', $todo->progress, true );
					add_post_meta( $post_id, '_completed', $todo->completed, true );

					// add any master view statuses
					$statuses = $wpdb->get_results( "SELECT * FROM $status_table_name WHERE id = '$todo->id'" );

					foreach ( $statuses as $status ) {
						add_post_meta( $post_id, '_user_'.$status->user.'_status', $status->status );
					}

					// add category if set
					if ( $todo->cat_id != 0 ) {
						$sql = "SELECT name FROM ".$cat_table_name." WHERE id = '".$todo->cat_id."' LIMIT 1";
						$category_name = $wpdb->get_row( $sql );
						$category = get_term_by( 'name', $category_name->name, 'todocategories' );
						wp_set_post_terms( $post_id, $category->term_id, 'todocategories', false );
					}

				}

			}

		}
	}

}
?>