<?php
/**
 * Cleverness To-Do List Plugin Library
 *
 * Library of functions for the To-Do List
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.4
 */

/**
 * Library class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Lib {

	/**
	 * Get to-do list item
	 * @static
	 * @param $id
	 * @return mixed
	 */
	public static function get_todo( $id ) {
		$post = get_post( $id );
		return $post;
	}

	/**
	 * Get to-do list items
	 * @static
	 * @param $user
	 * @param $limit
	 * @param int $status
	 * @param int $cat_id
	 * @param array $to_exclude
	 * @return WP_Query
	 */
	public static function get_todos( $user = 0, $limit = 5000, $status = 0, $cat_id = 0, $to_exclude = array() ) {

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
		if ( CTDL_Loader::$settings['list_view'] == 0 && $user != 0 ) {
			$author = $user;
		} else {
			$author = NULL;
		}

		/* View Settings */

		// In Group View, Show Only Tasks Assigned to That User when Set
		if ( CTDL_Loader::$settings['list_view'] == '1' && $user != 0 && CTDL_Loader::$settings['show_only_assigned'] == '0' && ( !current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) {
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
		} elseif ( CTDL_Loader::$settings['list_view'] == '2' && $user != 0 && !current_user_can( CTDL_Loader::$settings['edit_capability'] ) ) {

			if ( $status == 0 ) {
				// first get all the posts where _user_USERID_status = 1 and put them into an array
				// then exclude those items from the query where you get all the posts that have status = 0
				$posts_to_exclude_args = array(
					'post_type'      => 'todo',
					'author'         => $author,
					'post_status'    => 'publish',
					'posts_per_page' => 10000,
					'no_found_rows'  => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'fields'         => 'ids',
					'meta_query'     => array(
											array(
												'key' => '_user_'.$user.'_status',
												'value' => 1,
											) )
					);
				$posts_to_exclude = new WP_Query( $posts_to_exclude_args );
				if ( $posts_to_exclude->have_posts() ):
					foreach ( $posts_to_exclude->posts as $id ):
						$to_exclude[] = $id;
					endforeach;
				endif;
				wp_reset_postdata();

				if ( CTDL_Loader::$settings['show_only_assigned'] == '0' && $user != 0 && ( !current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) {
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

			} elseif ( $status == 1 && $user != 0 )
				if ( CTDL_Loader::$settings['show_only_assigned'] == '0' && ( ! current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) {
					$metaquery = array(
						array(
							'key'   => '_status',
							'value' => 0,
						),
						array(
							'key'   => '_user_' . $user . '_status',
							'value' => 1,
						),
						array(
							'key'   => '_assign',
							'value' => $user,
						)
					);
				} else {
					$metaquery = array(
						array(
							'key'   => '_status',
							'value' => 0,
						),
						array(
							'key'   => '_user_' . $user . '_status',
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
				'meta_key'       => $metakey,
				'order'          => 'ASC',
				'no_found_rows'  => true,
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
				'no_found_rows'  => true,
				'meta_query'     => $metaquery,
				'post__not_in'   => $to_exclude,
			);
			$results = new WP_Query( $args );
		}

		return $results;
	}

	/**
	 * Ajax callback for getting todos
	 */
	public static function dashboard_display_todos_callback() {
		global $CTDL_Dashboard_Widget;
		check_ajax_referer( 'ctdl-todo' );

		$response = $CTDL_Dashboard_Widget->dashboard_widget();

		echo $response;
		die();
	}

	/**
	 * Ajax callback for getting todos on the frontend
	 */
	public static function frontend_display_todos_callback() {
		global $CTDL_Frontend_Admin;
		check_ajax_referer( 'ctdl-todo' );

		$CTDL_Frontend_Admin->atts = $_POST['ctdl_shortcode_atts'];

		$response = $CTDL_Frontend_Admin->display( $_POST['ctdl_status'] );

		echo $response;
		die();
	}

	/**
	 * Complete to-do item ajax callback
	 * @static
	 */
	public static function complete_todo_callback() {
		check_ajax_referer( 'ctdl-todo' );

		if ( CTDL_Lib::check_permission( 'todo', 'complete' )) {
			self::complete_todo( absint( $_POST['ctdl_todo_id'] ), absint( $_POST['ctdl_todo_status'] ) );
		} else {
			$message = esc_html__( 'You do not have sufficient privileges to complete items.', 'cleverness-to-do-list' );
		}
		if ( isset( $message ) ) echo $message;

		die(); // this is required to return a proper result
	}

	/**
	 * Complete to-do item
	 * @static
	 * @param $id
	 * @param $status
	 */
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

	/**
	 * Add To-Do Ajax
	 * @static
	 */
	public static function add_todo_callback() {
		global $CTDL_Frontend_Admin;
		check_ajax_referer( 'ctdl-todo' );

		$CTDL_Frontend_Admin->atts = json_decode( stripslashes( $_POST['ctdl_shortcode_atts'] ) );

		self::insert_todo();

		$response = $CTDL_Frontend_Admin->display( 0 );

		echo $response;

		die(); // this is required to return a proper result
	}

	/**
	 * Insert new to-do item into the database
	 * @static
	 * @return mixed
	 */
	public static function insert_todo() {
		global $current_user;

		if ( $_POST['cleverness_todo_description'] == '' ) return;

		$permission = CTDL_Lib::check_permission( 'todo', 'add' );

		if ( $permission === true ) {

			if ( ! wp_verify_nonce( $_REQUEST['todoadd'], 'todoadd' ) ) die( esc_html__( 'Security check failed', 'cleverness-to-do-list' ) );

			$send_email = apply_filters( 'ctdl_send_email', CTDL_Loader::$settings['email_assigned'] );

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

			$assign_permission = CTDL_Lib::check_permission( 'todo', 'assign' );
			// if user can assign to-do items
			if ( $assign_permission == true ) {
				$assign = ( isset( $_POST['cleverness_todo_assign'] ) ? $_POST['cleverness_todo_assign'] : -1 );
				if ( is_array( $assign ) ) {
					foreach ( $assign as $value ) {
						add_post_meta( $post_id, '_assign', $value );
					}
				} else {
					add_post_meta( $post_id, '_assign', $assign );
				}

				if ( $send_email == '1' && CTDL_Loader::$settings['assign'] == '0' ) {
					$deadline = ( isset( $_POST['cleverness_todo_deadline'] ) ? $_POST['cleverness_todo_deadline'] : 0 );
					$cat = ( isset( $_POST['cat'] ) ? $_POST['cat'] : 0 );
					$planner = ( isset( $_POST['cleverness_todo_planner'] ) ? $_POST['cleverness_todo_planner'] : 0 );
					CTDL_Lib::email_user( $assign, $deadline, $cat, $planner );
				}
			} else {
				// if user can't assign items, but settings are set to assign items and show only assigned items, then assign it to that user
				if ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['assign'] == 0 && CTDL_Loader::$settings['show_only_assigned'] == 0 ) {
					add_post_meta( $post_id, '_assign', $current_user->ID );
				}
			}
			$deadline = ( isset( $_POST['cleverness_todo_deadline'] ) && $_POST['cleverness_todo_deadline'] != '' ? strtotime( $_POST['cleverness_todo_deadline'] ) : '' );
			add_post_meta( $post_id, '_deadline', $deadline, true );
			$progress = ( isset( $_POST['cleverness_todo_progress'] ) ? $_POST['cleverness_todo_progress'] : 0 );
			add_post_meta( $post_id, '_progress', $progress, true );
			if ( isset( $_POST['cleverness_todo_planner'] ) ) add_post_meta( $post_id, '_planner', absint( $_POST['cleverness_todo_planner'] ) );

		}

		return;
	}

	/**
	 * Set email content type as HTML
	 * @since 3.4
	 * @return string
	 */
	public static function set_html_email() {
		return 'text/html';
	}

	/**
	 * Send an email to assigned user
	 * @static
	 * @param $assign
	 * @param $deadline
	 * @param int $category
	 * @param int $planner
	 */
	protected static function email_user( $assign, $deadline, $category = 0, $planner = 0 ) {
		global $current_user;
		get_currentuserinfo();
		add_filter( 'wp_mail_content_type', array( 'CTDL_Lib', 'set_html_email' ) );

		$priority = $_POST['cleverness_todo_priority'];
		$todo_text = $_POST['cleverness_todo_description'];
		$priority_array = array( 0 => CTDL_Loader::$settings['priority_0'], 1 => CTDL_Loader::$settings['priority_1'], 2 => CTDL_Loader::$settings['priority_2'] );
		if ( $category != 0 && $category != -1 ) $category_name = CTDL_Categories::get_category_name( $category );

		if ( is_array( $assign ) ) {
			foreach ( $assign as $assign_value ) {
				if ( current_user_can( CTDL_Loader::$settings['assign_capability'] ) && $assign_value != '' && $assign_value != '-1' && $assign_value != '0' ) {
					$headers = 'From: '.CTDL_Loader::$settings['email_from'].' <'.CTDL_Loader::$settings['email_from_email'].'>'."\r\n\\";
					$subject = CTDL_Loader::$settings['email_subject'];
					if ( CTDL_Loader::$settings['email_category'] == 1 && $category != 0 && $category != -1 ) {
						$subject .= ' - '.$category_name;
					}
					$assign_user   = get_userdata( $assign_value );
					$email         = $assign_user->user_email;
					$email_message = CTDL_Loader::$settings['email_text']."<br>";
					$email_message .= "<br>".__( 'Priority', 'cleverness-to-do-list' ).': '.$priority_array[$priority]."<br>";
					if ( CTDL_Loader::$settings['email_show_assigned_by'] == 1 ) $email_message .= "<br>".__( 'From', 'cleverness-to-do-list' ).': '.$current_user->display_name.' ('.$current_user->user_email.')'."<br>";
					if ( $category != 0 && $category != -1 ) $email_message .= __( 'Category', 'cleverness-to-do-list' ).': '.$category_name."<br>";
					if ( $deadline != '' && $deadline != 0 ) $email_message .= __( 'Deadline:', 'cleverness-to-do-list' ).' '.date( CTDL_Loader::$settings['date_format'],
						strtotime( $deadline ) )."<br>";
					if ( CTDL_Loader::$settings['post_planner'] == 1 && $planner != 0 ) {
						$url = admin_url( 'post.php?post='.absint( $planner ).'&action=edit' );
						$email_message .= esc_html__( 'Post Planner', 'post-planner' ).': <a href="'.$url.'">'.esc_html__( 'View', 'cleverness-to-do-list' )."</a><br>";
					}
					$email_message .= __( 'To-Do:', 'cleverness-to-do-list' ).' '.$todo_text."<br>";
					wp_mail( $email, $subject, $email_message, $headers );
				}
			}
		} else {
			if ( current_user_can( CTDL_Loader::$settings['assign_capability'] ) && $assign != '' && $assign != '-1' && $assign != '0' ) {
				$headers = 'From: '.CTDL_Loader::$settings['email_from'].' <'.get_bloginfo( 'admin_email' ).'>'."\r\n\\";
				$subject = CTDL_Loader::$settings['email_subject'];
				if ( CTDL_Loader::$settings['email_category'] == 1 && $category != 0 && $category != -1 ) {
					$subject .= ' - '.$category_name;
				}
				$assign_user   = get_userdata( $assign );
				$email         = $assign_user->user_email;
				$email_message = CTDL_Loader::$settings['email_text']."<br>";
				$email_message .= "<br>".__( 'Priority', 'cleverness-to-do-list' ).': '.$priority_array[$priority]."<br>";
				if ( CTDL_Loader::$settings['email_show_assigned_by'] == 1 ) $email_message .= "<br>".__( 'From', 'cleverness-to-do-list' ).': '.$current_user->display_name.' ('.$current_user->user_email.')'."<br>";
				if ( $category != 0 && $category != -1 ) $email_message .= __( 'Category', 'cleverness-to-do-list' ).': '.$category_name."<br>";
				if ( $deadline != '' ) $email_message .= __( 'Deadline:', 'cleverness-to-do-list' ).' '.date( CTDL_Loader::$settings['date_format'], strtotime( $deadline ) )."<br>";
				if ( CTDL_Loader::$settings['post_planner'] == 1 && $planner != 0 ) {
					$url = admin_url( 'post.php?post='.absint( $planner ).'&action=edit' );
					$email_message .= esc_html__( 'Post Planner URL', 'post-planner' ).': '.$url."<br>";
				}
				$email_message .= __( 'To-Do:', 'cleverness-to-do-list' ).' '.$todo_text."<br>";
				wp_mail( $email, $subject, $email_message, $headers );
			}
		}
		remove_filter( 'wp_mail_content_type', array( 'CTDL_Lib', 'set_html_email' ) );

	}

	/**
	 * Update to-do list item
	 * @static
	 * @return mixed
	 */
	public static function edit_todo() {
		$permission = CTDL_Lib::check_permission( 'todo', 'edit' );

		if ( $permission === true ) {

			if ( !wp_verify_nonce( $_REQUEST['todoupdate'], 'todoupdate' ) ) die( esc_html__( 'Security check failed', 'cleverness-to-do-list' ) );

			$my_post = array(
				'ID'            => absint( $_POST['id'] ),
				'post_title'    => substr( $_POST['cleverness_todo_description'], 0, 100 ),
				'post_content'  => $_POST['cleverness_todo_description'],
			);

			$post_id = wp_update_post( $my_post );

			if ( isset( $_POST['cat'] ) ) wp_set_post_terms( $post_id, absint( $_POST['cat'] ), 'todocategories', false);
			if ( isset( $_POST['cleverness_todo_priority'] ) ) update_post_meta( $post_id, '_priority', esc_attr( $_POST['cleverness_todo_priority'] ) );

			$assign_permission = CTDL_Lib::check_permission( 'todo', 'assign' );
			if ( $assign_permission == true ) {
				$assign = ( isset( $_POST['cleverness_todo_assign'] ) ? $_POST['cleverness_todo_assign'] : -1 );
				if ( is_array( $assign ) ) {
					delete_post_meta( $post_id, '_assign' );
					foreach ( $assign as $value ) {
						add_post_meta( $post_id, '_assign', $value );
					}
				} else {
					update_post_meta( $post_id, '_assign', $assign );
				}
			}

			if ( isset( $_POST['cleverness_todo_deadline'] ) ) update_post_meta( $post_id, '_deadline', strtotime( $_POST['cleverness_todo_deadline'] ) );
			if ( isset( $_POST['cleverness_todo_progress'] ) ) update_post_meta( $post_id, '_progress', $_POST['cleverness_todo_progress'] );
			if ( isset( $_POST['cleverness_todo_planner'] ) ) update_post_meta( $post_id, '_planner', absint( $_POST['cleverness_todo_planner'] ) );
		}

		return;
	}

	/**
	 * Delete To-Do Ajax
	 * @static
	 */
	public static function delete_todo_callback() {
		check_ajax_referer( 'ctdl-todo' );
		$permission = CTDL_Lib::check_permission( 'todo', 'delete' );
		$status = ( $permission === true ? CTDL_Lib::delete_todo( absint( $_POST['cleverness_todo_id'] ) ) : -1 );
		echo $status;
		die(); // this is required to return a proper result
	}

	/**
	 * Delete to-do list item
	 * @static
	 * @param $id
	 * @return int
	 */
	public static function delete_todo( $id ) {
		wp_delete_post( absint( $id ), true );
		return 1;
	}

	/**
	 * Delete all completed to-do list items
	 * @static
	 */
	public static function delete_all_completed_todos() {
		global $userdata;

		$permission = CTDL_LIB::check_permission( 'todo', 'purge' );

		if ( $permission === true ) {
			$cleverness_todo_purge_nonce = $_REQUEST['_wpnonce'];
			if ( !wp_verify_nonce( $cleverness_todo_purge_nonce, 'todopurge' ) ) die( esc_html__( 'Security check failed', 'cleverness-to-do-list' ) );

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

			wp_reset_postdata();
		}
	}

	/**
	 * Delete all to-do list items
	 * @static
	 */
	public static function delete_all_todos() {

		$permission = CTDL_LIB::check_permission( 'todo', 'purge' );

		if ( $permission === true ) {
			$cleverness_todo_delete_all_nonce = $_REQUEST['_wpnonce'];
			if ( !wp_verify_nonce( $cleverness_todo_delete_all_nonce, 'tododeletetodos' ) ) die( esc_html__( 'Security check failed', 'cleverness-to-do-list' ) );

			$args = array(
				'post_type' => 'todo',
				'posts_per_page' => -1,
				);

			$todo_items = new WP_Query( $args );

			while ( $todo_items->have_posts() ) : $todo_items->the_post();
				$id = get_the_ID();
				wp_delete_post( absint( $id ), true );
			endwhile;

			wp_reset_postdata();
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
			if ( !wp_verify_nonce( $cleverness_todo_delete_tables_nonce, 'tododeletetables' ) ) die( esc_html__( 'Security check failed', 'cleverness-to-do-list' ) );
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
	 * Set url and action variables
	 * @return array
	 */
	public static function set_variables() {
		$url = CTDL_Lib::get_page_url();
		$url = strtok( $url, '?' );
		$action = ( isset( $_GET['action'] ) ? $_GET['action'] : '' );
		return array( $url, $action );
	}

	/**
	 * Get the To-Do post meta and assign to variables
	 * @static
	 * @param $id
	 * @return array
	 * @since 3.1
	 * @todo deprecate in 3.5
	 */
	public static function get_todo_meta( $id ) {
		$post_meta = get_post_custom( $id );
		$priority = ( isset( $post_meta['_priority'][0] ) ? $post_meta['_priority'][0] : 1 );
		$assign_meta = ( isset( $post_meta['_assign'] ) ? $post_meta['_assign'] : 0 );
		$deadline_meta = ( isset( $post_meta['_deadline'][0] ) ? $post_meta['_deadline'][0] : '' );
		$completed_meta = ( isset( $post_meta['_completed'][0] ) ? $post_meta['_completed'][0] : NULL );
		$progress_meta = ( isset( $post_meta['_progress'][0] ) ? $post_meta['_progress'][0] : '' );
		$planner_meta = ( isset( $post_meta['_planner'][0] ) ? $post_meta['_planner'][0] : '' );
		return array( $priority, $assign_meta, $deadline_meta, $completed_meta, $progress_meta, $planner_meta );
	}

	/**
	 * Get Planners
	 * @static
	 * @return WP_Query
	 * @since 1.0
	 */
	public static function get_planners() {

		$results = get_posts( array(
			'post_type'      => 'planner',
			'posts_per_page' => 1000,
			'post_status'    => 'any',
			'no_found_rows' => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false
		) );

		return $results;
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
		if ( $priority == '0' ) $priority_class = ' class="todo-important todo-list"';
		if ( $priority == '1' ) $priority_class = ' class="todo-normal todo-list"';
		if ( $priority == '2' ) $priority_class = ' class="todo-low todo-list"';
		return $priority_class;
	}

	/**
	 * Check if User Has Permission
	 * @static
	 * @param $type
	 * @param $action
	 * @return bool
	 */
	public static function check_permission( $type, $action = NULL ) {

		switch ( $type ) {
			case 'category':
				// check if categories are enabled and the user has the capability or the list view is individual
				$permission = ( CTDL_Loader::$settings['categories'] == '1' && ( current_user_can( CTDL_Loader::$settings[$action . '_capability'] )
						|| CTDL_Loader::$settings['list_view'] == '0' ) ? true : false );
				break;
			case 'todo':
				$permission = ( current_user_can( CTDL_Loader::$settings[$action . '_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ? true : false );
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

	/**
	 * Get list of users
	 * @static
	 * @param $role
	 * @return array
	 */
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
	 * Convert PHP date to jQuery date
	 * @static
	 * @param $dateFormat
	 * @return string
	 * @since 3.1.5
	 */
	public static function dateFormatTojQueryUIDatePickerFormat( $dateFormat ) {

		$chars = array(
			// Day
			'd' => 'dd',
			'j' => 'd',
			'l' => 'DD',
			'D' => 'D',
			// Month
			'm' => 'mm',
			'n' => 'm',
			'F' => 'MM',
			'M' => 'M',
			// Year
			'Y' => 'yy',
			'y' => 'y',
		);

		return strtr( (string)$dateFormat, $chars );
	}

	/**
	 * Add an Item to the Admin Menu
	 * @param $wp_admin_bar
	 */
	public static function add_to_toolbar( $wp_admin_bar ) {

		if ( current_user_can( CTDL_Loader::$settings['view_capability'] ) ) {
			$wp_admin_bar->add_node( array(
				'id'     => 'todolist',
				'title'  => __( 'To-Do List', 'cleverness-to-do-list' ),
				'href'   => get_admin_url().'admin.php?page=cleverness-to-do-list',
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
	}

	/**
	 * Add Settings link to plugin
	 * @static
	 * @param $links
	 * @param $file
	 * @return array
	 */
	public static function add_settings_link( $links, $file ) {
		static $this_plugin;
		if ( !$this_plugin ) $this_plugin = CTDL_BASENAME;

		if ( $file == $this_plugin ) {
			$settings_link = '<a href="admin.php?page=cleverness-to-do-list-settings">'.__( 'Settings', 'cleverness-to-do-list' ).'</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}

	/**
	 * Add plugin info to admin footer
	 * @static
	 */
	public static function cleverness_todo_admin_footer() {
		$plugin_data = get_plugin_data( CTDL_FILE );
		printf( "%s plugin | Version %s | by %s |
			<img src='".CTDL_PLUGIN_URL."/images/codebrainmedialogo.gif' width='25' />
			<a href='http://codebrainmedia.com'>CodeBrain Media</a>
			| <a href='https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=cindy@cleverness.org'>Donate</a><br />", $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author'] );
	}

	/**
	 * Update stored options when a term gets split.
	 *
	 * @param int    $term_id          ID of the formerly shared term.
	 * @param int    $new_term_id      ID of the new term created for the $term_taxonomy_id.
	 * @param int    $term_taxonomy_id ID for the term_taxonomy row affected by the split.
	 * @param string $taxonomy         Taxonomy for the split term.
	 */
	public static function split_shared_term( $term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {

		if ( 'todocategories' == $taxonomy ) {

			//update dashboard options
			$dashboard = get_option( 'CTDL_dashboard_settings', array() );

			$found_term = array_search( $term_id, $dashboard['dashboard_cat'] );
			if ( false !== $found_term ) {
				$dashboard['dashboard_cat'][ $found_term ] = $new_term_id;
				update_option( 'CTDL_dashboard_settings', $dashboard );
			}

			// update category options
			$visibility = get_option( 'CTDL_categories', array() );

			$found_term = array_search( 'category_'.$term_id, $visibility );
			if ( false !== $found_term ) {
				$visibility[ 'category_' . $new_term_id ] = $visibility[ 'category_'.$term_id ];
				update_option( 'CTDL_categories', $visibility );
			}

			// update widget options
			$widgets = get_option( 'widget_cleverness-to-do-widget', array() );

			foreach ( $widgets as &$widget ) {
				$found_term = array_search( $term_id, $widget['category'] );
				if ( false !== $found_term ) {
					$widget['category'] = $new_term_id;
				}
			}
			update_option( 'widget_cleverness-to-do-widget', $widgets );

		}

	}

	/**
	 * Create database table and add default options
	 * @static
	 */
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
			if ( ! post_type_exists( 'todo' ) ) CTDL_Loader::setup_custom_post_type();
			if ( ! taxonomy_exists( 'todocategories' ) ) CTDL_Loader::create_taxonomies();

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

				self::set_options( $installed_version );

				// update db version to current versions
				update_option( 'CTDL_db_version', CTDL_DB_VERSION );

				// if the db version is < 3.0
				if ( $installed_version < 3 ) {

					// check to see if there's existing to-do items. if so, convert them to custom posts
					$existing_todos = self::check_for_todos();

					if ( $existing_todos == 0 ) {
						self::convert_todos();
					}

				}

				// if db version < 3.2.1, convert deadlines
				if ( version_compare( $installed_version, '3.21', '<' ) ) {
					self::convert_deadlines();
				}

				// if db version < 3.4, split taxonomies
				if ( version_compare( $installed_version, '3.4', '<' ) ) {
					global $wp_version;
					if ( version_compare( $wp_version, '4.2', '>=' ) ) {
						self::split_taxonomies();
					}
				}

			}

		}

	}

	/**
	 * Set Plugin Default Options
	 * @static
	 * @param $version
	 */
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
				'admin_bar'             => 1,
				'post_planner'          => 0,
				'wysiwyg'               => 1,
				'autop'                 => 1,
			);

			$advanced_options = array(
				'date_format'               => 'm/d/Y',
				'priority_0'                => esc_html__( 'Important', 'cleverness-to-do-list' ),
				'priority_1'                => esc_html__( 'Normal', 'cleverness-to-do-list' ),
				'priority_2'                => esc_html__( 'Low', 'cleverness-to-do-list' ),
				'assign'                    => 1,
				'show_only_assigned'        => 1,
				'user_roles'                => 'contributor, author, editor, administrator',
				'email_assigned'            => 0,
				'email_from'                => html_entity_decode( get_bloginfo( 'name' ) ),
				'email_subject'             => esc_html__( 'A to-do list item has been assigned to you', 'cleverness-to-do-list' ),
				'email_text'                => esc_html__( 'The following item has been assigned to you:', 'cleverness-to-do-list' ),
				'email_category'            => 1,
				'email_show_assigned_by'    => 0,
				'show_id'                   => 0,
				'show_date_added'           => 0,
				'email_from_email'          => esc_html( get_bloginfo( 'admin_email' ) ),
			);

			$permissions_options = array(
				'view_capability'              => 'edit_posts',
				'add_capability'               => 'edit_posts',
				'edit_capability'              => 'edit_posts',
				'delete_capability'            => 'manage_options',
				'purge_capability'             => 'manage_options',
				'complete_capability'          => 'edit_posts',
				'assign_capability'            => 'manage_options',
				'view_all_assigned_capability' => 'manage_options',
				'add_cat_capability'           => 'manage_options',
			);

			$dashboard_options = array(
				'dashboard_number'        => -1,
				'dashboard_cat'           => 0,
				'show_dashboard_deadline' => 0,
				'show_edit_link'          => 0,
				'dashboard_author'        => 1,
				'show_completed'          => 0,
				'dashboard_heading'       => esc_html__( 'To-Do List', 'cleverness-to-do-list' )
			);

			add_option( 'CTDL_general', $general_options );
			add_option( 'CTDL_advanced', $advanced_options );
			add_option( 'CTDL_permissions', $permissions_options );
			add_option( 'CTDL_dashboard_settings', $dashboard_options );
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
					'admin_bar'             => 1,
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
					'email_from_email'          => get_bloginfo( 'admin_email' ),
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

				$dashboard_options = array(
					'dashboard_number'        => -1,
					'dashboard_cat'           => $the_options['dashboard_cat'],
					'show_dashboard_deadline' => 0,
					'dashboard_author'        => 1,
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
				update_option( 'CTDL_dashboard_settings', $dashboard_options );
				delete_option( 'atd_db_version' );
				delete_option( 'cleverness_todo_db_version' );
				delete_option( 'cleverness_todo_settings' );

			} elseif ( $version < 3.1 ) {
				$advanced_options = get_option( 'CTDL_advanced' );
				$advanced_options['email_show_assigned_by'] = 0;
				$advanced_options['show_date_added'] = 0;
				update_option( 'CTDL_advanced', $advanced_options );
			}

			if ( $version < 3.2 ) {
				$dashboard_options = get_option( 'CTDL_dashboard_settings' );
				$dashboard_options['show_edit_link'] = 1;
				update_option( 'CTDL_dashboard_settings', $dashboard_options );

				$general_options = get_option( 'CTDL_general' );
				$general_options['wysiwyg'] = 1;
				$general_options['post_planner'] = 0;
				$general_options['autop'] = 1;
				update_option( 'CTDL_general', $general_options );
			}

			if ( $version < 3.3 ) {
				$advanced_options                     = get_option( 'CTDL_advanced' );
				$advanced_options['email_from_email'] = get_bloginfo( 'admin_email' );
				update_option( 'CTDL_advanced', $advanced_options );
			}

			if ( $version < 3.4 ) {
				$dashboard_options = get_option( 'CTDL_dashboard_settings' );
				$dashboard_options['show_completed'] = 0;
				$dashboard_options['dashboard_heading'] = esc_html__( 'To-Do List', 'cleverness-to-do-list' );
				update_option( 'CTDL_dashboard_settings', $dashboard_options );
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

	/**
	 * Convert deadlines using strtotime so you can sort by them
	 * @static
	 * @since 3.2.1
	 */
	public static function convert_deadlines() {

		$results = get_posts( array(
			'post_type'      => 'todo',
			'posts_per_page' => -1,
			'post_status'    => 'any'
		) );

		foreach( $results as $result ) {
			$deadline = get_post_meta( $result->ID, '_deadline', true );
			if ( $deadline != '' ) {
				update_post_meta( $result->ID, '_deadline', strtotime( $deadline ) );
			}
		}

	}

	/**
	 * Split todocategories taxonomy options
	 *
	 * @static
	 * @since 3.4
	 */
	public static function split_taxonomies() {
		// update dashboard options
		$dashboard = get_option( 'CTDL_dashboard_settings', array() );

		if ( is_array( $dashboard['category'] ) ) {
			foreach ( $dashboard['category'] as $key => $value ) {
				$new_term_id = wp_get_split_term( $value, 'todocategories' );

				if ( $new_term_id ) {
					$dashboard['category'][ $key ] = $new_term_id;
					update_option( 'CTDL_dashboard_settings', $dashboard );
				}
			}
		} else {
			$new_term_id = wp_get_split_term( $dashboard['category'], 'todocategories' );

			if ( $new_term_id ) {
				$dashboard['category'] = $new_term_id;
				update_option( 'CTDL_dashboard_settings', $dashboard );
			}
		}

		// update category options
		$visibility = get_option( 'CTDL_categories', array() );

		foreach ( $visibility as $key => $value ) {
			$key = substr( $key, 9 );
			$new_term_id = wp_get_split_term( $key, 'todocategories' );

			if ( $new_term_id ) {
				$visibility['category_'.$new_term_id ] = $value;
				update_option( 'CTDL_categories', $visibility );
			}
		}

		// update widget options
		$widgets = get_option( 'widget_cleverness-to-do-widget', array() );

		foreach ( $widgets as &$widget ) {
			$new_term_id = wp_get_split_term( $widget['category'], 'todocategories' );

			if ( $new_term_id ) {
				$widget[ 'category' ] = $new_term_id;
			}
		}
		update_option( 'widget_cleverness-to-do-widget', $widgets );

	}

}