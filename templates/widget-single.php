<li class="<?php echo esc_attr( ctdl_priority_class() ); ?> todo-list">

	<?php echo wp_kses_post( ctdl_todo_text() ); ?>

	<?php if ( ctdl_check_field( 'widget-progress' ) ) : ?>
		- <?php echo esc_html( ctdl_progress() ); ?>%
	<?php endif; ?>

	<?php if ( ctdl_check_field( 'widget-deadline' ) ) : ?>
		<br />
		<span class="deadline"><?php echo apply_filters( 'ctdl_deadline', esc_html__( 'Deadline', 'cleverness-to-do-list' ) ); ?>:
			<?php echo esc_html( ctdl_deadline() ); ?></span>
	<?php endif; ?>

	<?php if ( ctdl_check_field( 'widget-assigned' ) ) : ?>
		<br />
		<span class="assigned"><?php echo apply_filters( 'ctdl_assigned', esc_html__( 'Assigned to', 'cleverness-to-do-list' ) ); ?>
			<?php echo esc_html( ctdl_assigned() ); ?></span>
	<?php endif; ?>

	<?php do_action( 'ctdl_widget_list_items' ); ?>

</li>
