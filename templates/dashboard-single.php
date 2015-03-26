<?php
/** Template for the Dashboard Widget */
global $CTDL_status;
?>

<?php if ( ctdl_check_field( 'dashboard-category-heading' ) ) : ?>
	<h4><?php echo esc_html( ctdl_category_heading() ); ?></h4>
<?php endif; ?>

<div id="todo-<?php echo absint( get_the_ID() ); ?>" <?php echo ctdl_priority_class(); ?>>

	<?php echo ctdl_checkbox(); ?>

	<div class="todo-item<?php echo ( $CTDL_status == 1 ? ' completed-to-do' : NULL ); ?>">

		<?php echo wp_kses_post( ctdl_todo_text() ); ?>

		<?php if ( ctdl_check_field( 'dashboard-category' ) ) : ?>
			<small>[<?php echo apply_filters( 'ctdl_category', esc_html__( 'Category', 'cleverness-to-do-list' ) ); ?>:
			<?php echo esc_html( ctdl_category() ); ?>]</small>
		<?php endif; ?>

		<?php if ( ctdl_check_field( 'assigned' ) ) : ?>
			<small>[<?php echo apply_filters( 'ctdl_assigned', esc_html__( 'Assigned to', 'cleverness-to-do-list' ) ); ?>
			<?php echo esc_html( ctdl_assigned() ); ?>]</small>
		<?php endif; ?>

		<?php if ( ctdl_check_field( 'dashboard-deadline' ) ) : ?>
			<small>[<?php echo apply_filters( 'ctdl_deadline', esc_html__( 'Deadline', 'cleverness-to-do-list' ) ); ?>:
			<?php echo esc_html( ctdl_deadline() ); ?>]</small>
		<?php endif; ?>

		<?php if ( ctdl_check_field( 'progress' ) ) : ?>
			<small>[<?php echo esc_html( ctdl_progress() ); ?>%]</small>
		<?php endif; ?>

		<?php if ( ctdl_check_field( 'dashboard-author' ) ) : ?>
			<small>- <?php echo apply_filters( 'ctdl_added_by', esc_html__( 'Added by', 'cleverness-to-do-list' ) ); ?>
			<?php echo esc_html( get_the_author() ); ?></small>
		<?php endif; ?>

		<?php do_action( 'ctdl_dashboard_list_items' ); ?>

		<?php if ( ctdl_check_field( 'dashboard-edit' ) ) : ?>
			<small>(<a href="<?php echo admin_url( '?page=cleverness-to-do-list&action=edit-todo&id='.absint( $id ) ); ?>"><?php _e( 'Edit' ); ?></a>)</small>
		<?php endif; ?>

	</div>

</div>