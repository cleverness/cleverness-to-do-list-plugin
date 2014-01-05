<?php
/** Template for the Dashboard Widget */

global $CTDL_status, $CTDL_cat_id;
$cat_id = '';
list( $priority, $assigned_meta, $deadline_meta, $completed_meta, $progress_meta ) = CTDL_Lib::get_todo_meta( $id );
$priority_class = CTDL_Lib::set_priority_class( $priority );

if ( CTDL_Loader::$settings['categories'] == '1' && $CTDL_cat_id != 1 ) {
	$cats = get_the_terms( $id, 'todocategories' );
	if ( $cats != NULL ) {
		foreach ( $cats as $category ) {
			if ( $cat_id != $category->term_id ) : ?>
				<h4><?php esc_html_e( $category->name ); ?></h4>
			<?php endif; ?>
			<?php $cat_id = $category->term_id;
		}
	}
}
?>

<div id="todo-<?php echo absint( $id ); ?>" <?php _e( $priority_class ); ?>>

	<?php CTDL_Templates::show_checkbox( $id, $CTDL_status ); ?>

	<div class="todo-item<?php echo ( $CTDL_status == 1 ? ' completed-item' : NULL ); ?>">

		<?php CTDL_Templates::show_todo_text( get_the_content() ); ?>

		<?php if ( CTDL_Lib::check_field( 'assigned', $assigned_meta ) ) : ?>
			<small>[<?php echo apply_filters( 'ctdl_assigned', esc_html__( 'Assigned to', 'cleverness-to-do-list' ) ); ?>
			<?php CTDL_Templates::show_assigned( $assigned_meta ); ?>]</small>
		<?php endif; ?>

		<?php if ( CTDL_Lib::check_field( 'dashboard-deadline', $deadline_meta ) ) : ?>
			<small>[<?php echo apply_filters( 'ctdl_deadline', esc_html__( 'Deadline', 'cleverness-to-do-list' ) ); ?>
			<?php CTDL_Templates::show_deadline( $deadline_meta ); ?>]</small>
		<?php endif; ?>

		<?php if ( CTDL_Lib::check_field( 'progress', $progress_meta ) ) : ?>
			<small>[<?php CTDL_Templates::show_progress( $progress_meta, $CTDL_status ); ?>%]</small>
		<?php endif; ?>

		<?php if ( CTDL_Lib::check_field( 'dashboard-author', get_the_author() ) ) : ?>
			<small>-<?php echo apply_filters( 'ctdl_added_by', esc_html__( 'Added By', 'cleverness-to-do-list' ) ); ?>
			<?php CTDL_Templates::show_added_by( get_the_author() ); ?></small>
		<?php endif; ?>

	<?php do_action( 'ctdl_dashboard_list_items' ); ?>

	<?php if ( CTDL_Lib::check_field( 'dashboard-edit' ) ) : ?>
		<small>(<a href="admin.php?page=cleverness-to-do-list&amp;action=edit-todo&amp;id=<?php echo absint( $id ); ?>"><?php _e( 'Edit' ); ?></a>)</small>
	<?php endif; ?>

	</div>

</div>