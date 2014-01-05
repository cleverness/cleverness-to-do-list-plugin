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
 * Template Functions class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class CTDL_Templates {

	/**
	 * Create the HTML to show a To-Do List Checkbox
	 * @param int $id
	 * @param boolean $completed
	 * @param string $single
	 * @since 3.2
	 */
	public static function show_checkbox( $id, $completed = NULL, $single = '' ) {
		if ( CTDL_Lib::check_permission( 'todo', 'complete' ) ) {
			echo sprintf( '<input type="checkbox" id="ctdl-%d" class="todo-checkbox uncompleted floatleft' . $single . '"/>', esc_attr( $id ) );
			$ctdl_complete_nonce = wp_create_nonce( 'todocomplete' );
			echo '<input type="hidden" name="ctdl_complete_nonce" value="' . esc_attr( $ctdl_complete_nonce ) . '" />';
		}
	}

	/**
	 * Show the To-Do Text
	 * @param string $todo_text
	 */
	public static function show_todo_text( $todo_text ) {
		$todo_text = ( CTDL_Loader::$settings['autop'] == 1 ? wpautop( $todo_text ) : $todo_text );
		echo ( CTDL_Loader::$settings['wysiwyg'] == 1 ? $todo_text : stripslashes( $todo_text ) );
	}

	/**
	 * Show the User that a To-Do Item is Assigned To
	 * @param int $assign
	 * @since 3.4
	 */
	public static function show_assigned( $assign ) {
		if ( is_array( $assign ) ) {
			$assign_users = '';
			foreach ( $assign as $value ) {
				if ( $value != '-1' && $value != '' && $value != 0 ) {
					$user = get_userdata( $value );
					$assign_users .= $user->display_name . ', ';
				}
			}
			echo substr( $assign_users, 0, -2 );
		} else {
			if ( $assign != '-1' && $assign != '' && $assign != 0 ) {
				$assign_user = get_userdata( $assign );
				esc_html_e( $assign_user->display_name );
			}
		}
	}

	/**
	 * Show the Deadline for a To-Do Item
	 * @param string $deadline
	 * @since 3.4
	 */
	public static function show_deadline( $deadline ) {
		echo ( $deadline != NULL ? sprintf( '%s', date( CTDL_Loader::$settings['date_format'], $deadline ) ) : NULL );
	}

	/**
	 * Show the Progress of a To-Do Item
	 * @param int $progress
	 * @param int $completed
	 * @since 3.4
	 */
	public static function show_progress( $progress, $completed = 0 ) {
		$progress = ( $completed == 1 ? '100' : $progress );
		echo ( $progress != NULL ? sprintf( '%d', esc_attr( $progress ) ) : NULL );
	}

	/**
	 * Show To-Do Item Author
	 * @param int $author
	 * @since 3.4
	 */
	public static function show_added_by( $author ) {
		esc_attr_e( $author );
	}


}