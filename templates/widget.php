<?php
/** Template for the Widget */

global $CTDL_status;
list( $priority, $assigned_meta, $deadline_meta, $completed_meta, $progress_meta ) = CTDL_Lib::get_todo_meta( $id );
$priority_class = CTDL_Lib::set_priority_class( $priority );
?>

	<li<?php _e( $priority_class ); ?>>

		<?php CTDL_Templates::show_todo_text( get_the_content() ); ?>

		<?php if ( CTDL_Lib::check_field( 'widget-progress', $progress_meta ) ) : ?>
			- <?php CTDL_Templates::show_progress( $progress_meta, $CTDL_status ); ?>%
		<?php endif; ?>

		<?php if ( CTDL_Lib::check_field( 'widget-deadline', $deadline_meta ) ) : ?>
			<br /><span class="deadline"><?php echo apply_filters( 'ctdl_deadline', esc_html__( 'Deadline', 'cleverness-to-do-list' ) ); ?>:
				<?php CTDL_Templates::show_deadline( $deadline_meta ); ?></span>
		<?php endif; ?>

		<?php if ( CTDL_Lib::check_field( 'widget-assigned', $assigned_meta ) ) : ?>
			<br/><span class="assigned"><?php echo apply_filters( 'ctdl_assigned', esc_html__( 'Assigned to', 'cleverness-to-do-list' ) ); ?>
				<?php CTDL_Templates::show_assigned( $assigned_meta ); ?>
			</span>
		<?php endif; ?>

		<?php do_action( 'ctdl_widget_list_items' ); ?>

	</li>
