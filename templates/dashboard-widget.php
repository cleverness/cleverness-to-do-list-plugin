<?php
if ( CTDL_Loader::$settings['categories'] == '1' && $cat_id != 1 ) {
	$cats = get_the_terms( $id, 'todocategories' );
	if ( $cats != NULL ) {
		foreach ( $cats as $category ) {
			//if ( $catid != $category->term_id ) $this->list .= '<h4>' . esc_html( $category->name ) . '</h4>';
			$catid = $category->term_id;
		}
	}
}
?>

<div id="todo-<?php echo absint( $id ); ?>" <?php esc_attr_e( $priority_class ); ?>>

<?php
CTDL_Templates::show_checkbox( $id, $completed );
/*$this->list .= '<div class="todoitem">';
$this->show_todo_text( get_the_content() );
if ( ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 0 && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) )
		|| ( CTDL_Loader::$settings['list_view'] != 0 && CTDL_Loader::$settings['show_only_assigned'] == 1 ) && CTDL_Loader::$settings['assign'] == 0 && $assign_meta != 0 && $assign_meta != ''
		&& $assign_meta != -1 && !in_array( -1, $assign_meta )
) {
	$this->show_assigned( $assign_meta );
}
if ( CTDL_Loader::$settings['show_deadline'] == 1 && isset( $this->dashboard_settings['show_dashboard_deadline'] ) && $this->dashboard_settings['show_dashboard_deadline'] == 1 && $deadline_meta != '' ) {
	$this->list .= ' <small>[' . apply_filters( 'ctdl_deadline', esc_html__( 'Deadline', 'cleverness-to-do-list' ) ) . ' ';
	$this->show_deadline( $deadline_meta );
	$this->list .= ']</small>';
}
if ( CTDL_Loader::$settings['show_progress'] == 1 && $progress_meta != '' ) {
	$this->list .= ' <small>[';
	$this->show_progress( $progress_meta, 'list', $completed );
	$this->list .= ']</small>';
}
if ( CTDL_Loader::$settings['list_view'] == 1 && isset( $this->dashboard_settings['dashboard_author'] ) && $this->dashboard_settings['dashboard_author'] == 0 ) {
	if ( get_the_author() != '0' ) {
		$this->list .= ' <small>- ' . apply_filters( 'ctdl_added_by', esc_html__( 'Added By', 'cleverness-to-do-list' ) ) . ' ';
		$this->show_addedby( get_the_author() );
		$this->list .= '</small>';
	}
}*/
?>
<?php do_action( 'ctdl_list_items' ); ?>

<?php if ( CTDL_Lib::check_permission( 'edit' ) ) : ?>
	<small>(<a href="admin.php?page=cleverness-to-do-list&amp;action=edit-todo&amp;id=<?php echo absint( $id ); ?>"><?php _e( 'Edit' ); ?></a>)</small>
<?php endif; ?>

</div></div>