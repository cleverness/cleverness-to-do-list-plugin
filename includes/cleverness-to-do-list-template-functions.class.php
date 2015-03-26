<?php
/**
 * Cleverness To-Do List Plugin Template Functions
 *
 * Template functions for the To-Do List
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.4
 */

/**
 * Template Functions
 * @package cleverness-to-do-list
 * @subpackage includes
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get category heading
 * @return null
 *
 * @since 3.4
 */
function ctdl_category_heading() {
	global $CTDL_category_id;
	$cats = get_the_terms( get_the_ID(), 'todocategories' );
	if ( $cats != null ) {
		foreach ( $cats as $category ) {
			$CTDL_category_id = $category->term_id;
			return $category->name;
		}
	}
	return null;
}

/**
 * Get Dashboard Widget category settings
 * @return array
 *
 * @since 3.4
 */
function ctdl_dashboard_categories() {
	$categories = ( isset( CTDL_Loader::$dashboard_settings['dashboard_cat'] ) ? CTDL_Loader::$dashboard_settings['dashboard_cat'] : 0 );
	return ( is_array( $categories ) ? $categories : array( $categories ) );
}

/**
 * Create the HTML to show the to-do's priority class
 * @return string
 *
 * @since 3.4
 */
function ctdl_priority_class() {
	$priority = get_post_meta( get_the_ID(), '_priority', true );
	$priority_class = '';

	if ( $priority == '0' ) {
		$priority_class = 'todo-important';
	} elseif ( $priority == '1' ) {
		$priority_class = 'todo-normal';
	} elseif ( $priority == '2' ) {
		$priority_class = 'todo-low';
	}

	return $priority_class;
}

/**
 * Create the HTML to show a To-Do List Checkbox
 *
 * @since 3.4
 */
function ctdl_checkbox() {
	global $CTDL_status;
	if ( CTDL_Lib::check_permission( 'todo', 'complete' ) ) {
		if ( $CTDL_status == 0 ) {
			return sprintf( '<input type="checkbox" id="ctdl-%d" class="todo-checkbox todo-uncompleted" />', absint( get_the_ID() ) );
		} else {
			return sprintf( '<input type="checkbox" id="ctdl-%d" class="todo-checkbox todo-completed" checked="checked" />', absint( get_the_ID() ) );
		}
	}
	return null;
}

/**
 * Show the To-Do Text
 *
 * @since 3.4
 */
function ctdl_todo_text() {
	$todo_text = ( CTDL_Loader::$settings['autop'] == 1 ? wpautop( get_the_content() ) : get_the_content() );
	$todo_text = ( CTDL_Loader::$settings['wysiwyg'] == 1 ? $todo_text : stripslashes( $todo_text ) );
	return $todo_text;
}

/**
 * Show the To-Do Category
 *
 * @since 3.4
 */
function ctdl_category() {
	$categories = get_the_terms( get_the_ID(), 'todocategories' );
	$cats = '';
	foreach ( $categories as $category ) {
		$cats .= $category->name;
	}
	return $cats;
}

/**
 * Show the User that a To-Do Item is Assigned To
 *
 * @since 3.4
 */
function ctdl_assigned() {
	$assign = get_post_meta( get_the_ID(), '_assign' );
	if ( is_array( $assign ) ) {
		$assign_users = '';
		foreach ( $assign as $value ) {
			if ( $value != '-1' && $value != '' && $value != 0 ) {
				$user = get_userdata( $value );
				$assign_users .= $user->display_name . ', ';
			}
		}
		return substr( $assign_users, 0, -2 );
	} else {
		if ( $assign != '-1' && $assign != '' && $assign != 0 ) {
			$assign_user = get_userdata( $assign );
			return $assign_user->display_name;
		}
	}
	return null;
}

/**
 * Show the Deadline for a To-Do Item
 *
 * @since 3.4
 */
function ctdl_deadline() {
	$deadline = date( CTDL_Loader::$settings['date_format'], get_post_meta( get_the_ID(), '_deadline', true ) );
	return $deadline;
}

/**
 * Show the Progress of a To-Do Item
 *
 * @since 3.4
 */
function ctdl_progress() {
	global $CTDL_status;
	$progress = ( $CTDL_status == 1 ? '100' : get_post_meta( get_the_ID(), '_progress', true ) );
	return $progress;
}

/**
 * Show the Post Planner associated with a To-Do Item
 * @return mixed
 *
 * @since 3.4
 */
function ctdl_planner() {
	$url = admin_url( 'post.php?post=' . absint( get_post_meta( get_the_ID(), '_planner', true ) ) . '&action=edit' );
	$planner = '<a href="' . $url . '">' . get_the_title( get_post_meta( get_the_ID(), '_planner', true ) ) . '</a>';
	return $planner;
}

/**
 * Show the Date Completed of a To-Do Item
 * @return mixed
 *
 * @since 3.4
 */
function ctdl_completed_date() {
	$date = date( CTDL_Loader::$settings['date_format'], strtotime( get_post_meta( get_the_ID(), '_completed', true ) ) );
	return $date;
}

/**
 * Check if a field should be displayed
 * @static
 *
 * @param $field
 *
 * @return bool
 */
function ctdl_check_field( $field ) {
	global $CTDL_widget_settings, $CTDL_category_id;
	$permission = false;

	switch ( $field ) {
		case 'completed':
			$permission = ( CTDL_Loader::$settings['show_completed_date'] == 1 && get_post_meta( get_the_ID(), '_completed', true ) != '0000-00-00 00:00:00' ? true : false );
			break;
		case 'planner':
			$permission = ( CTDL_Loader::$settings['post_planner'] == 1 && get_post_meta( get_the_ID(), '_planner', true ) != null &&  PostPlanner_Lib::planner_exists( get_post_meta( get_the_ID(), '_planner', true ) ) ? true : false );
			break;
		case 'progress':
			$permission = ( CTDL_Loader::$settings['show_progress'] == 1 && get_post_meta( get_the_ID(), '_progress', true ) != null ? true : false );
			break;
		case 'assigned':
			$data = get_post_meta( get_the_ID(), '_assign', true );
			$permission = ( ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 0 && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) )
			    || ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 1 ) && CTDL_Loader::$settings['assign'] == 0 ? true : false );
			$permission = ( $permission == true && ( $data != 0 && $data != null
			    && $data != '-1' && ! ( is_array( $data ) && in_array( '-1', $data ) ) ) ? true : false );
			break;
		case 'dashboard-deadline':
			$permission = ( ( CTDL_Loader::$settings['show_deadline'] == 1 && isset( CTDL_Loader::$dashboard_settings['show_dashboard_deadline'] ) &&
			                  CTDL_Loader::$dashboard_settings['show_dashboard_deadline'] == 1 && get_post_meta( get_the_ID(), '_deadline', true ) != null ) ? true : false );
			break;
		case 'dashboard-edit':
			$permission = ( CTDL_Loader::$dashboard_settings['show_edit_link'] == 1 && ( current_user_can( CTDL_Loader::$settings['edit_capability'] )
			                                                                             || CTDL_Loader::$settings['list_view'] == 0 ) ? true : false );
			break;
		case 'dashboard-author':
			$permission = ( ( CTDL_Loader::$settings['list_view'] == 1 && isset( CTDL_Loader::$dashboard_settings['dashboard_author'] ) &&
			                  CTDL_Loader::$dashboard_settings['dashboard_author'] == 0 ) ? true : false );
			break;
		case 'dashboard-category-heading':
			$data = get_the_terms( get_the_ID(), 'todocategories' );
			$cat = '';
			if ( $data != NULL ) {
				foreach ( $data as $category ) {
					$cat = $category->term_id;
				}
			}
			$permission = ( CTDL_Loader::$settings['categories'] == 1 && CTDL_Loader::$settings['sort_order'] == 'cat_id' && ( 0 == $CTDL_category_id || $cat != $CTDL_category_id ) ? true : false );
			break;
		case 'dashboard-category':
			$data = get_the_terms( get_the_ID(), 'todocategories' );
			$permission = ( CTDL_Loader::$settings['categories'] == 1 && CTDL_Loader::$settings['sort_order'] != 'cat_id' && $data != NULL ? true : false );
			break;
		case 'widget-deadline':
			$permission = ( CTDL_Loader::$settings['show_deadline'] == 1 && $CTDL_widget_settings['deadline'] == 1 && get_post_meta( get_the_ID(), '_deadline', true ) != null ? true : false );
			break;
		case 'widget-progress':
			$permission = ( CTDL_Loader::$settings['show_progress'] == 1 && $CTDL_widget_settings['progress'] == 1 && get_post_meta( get_the_ID(), '_progress', true ) != null ? true : false );
			break;
		case 'widget-assigned':
			$data = get_post_meta( get_the_ID(), '_assign', true );
			$permission = ( CTDL_Loader::$settings['assign'] == 0 && $CTDL_widget_settings['assigned_to'] == 1 && CTDL_Loader::$settings['list_view'] != 0 ? true : false );
			$permission = ( $permission == true && ( $data != 0 && $data != null && $data != '-1' && ! ( is_array( $data ) && in_array( '-1', $data ) ) ) ? true : false );
			break;
	}

	return $permission;
}