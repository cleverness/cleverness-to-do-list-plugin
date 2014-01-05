<?php
/** Template for the Widget */

global $CTDL_status, $CTDL_visibility, $CTDL_category;
list( $priority, $assigned_meta, $deadline_meta, $completed_meta, $progress_meta ) = CTDL_Lib::get_todo_meta( $id );
$priority_class = CTDL_Lib::set_priority_class( $priority );

if ( CTDL_Loader::$settings['categories'] == 1 && CTDL_Loader::$settings['sort_order'] == 'cat_id' && $CTDL_category == '0' ) {
	$cats = get_the_terms( $id, 'todocategories' );
	if ( $cats != NULL ) {
		foreach ( $cats as $category ) {
			$visible = $CTDL_visibility["category_$category->term_id"];
			if ( $this->cat_id != $category->term_id && $visible == 0 ) {
				echo '</ol><h4>' . esc_html( $category->name ) . '</h4><ol>';
				$this->cat_id = $category->term_id;
			}
		}
	}
}

if ( $visible == 0 ) : ?>

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
			<<br/><span class="assigned"><?php echo apply_filters( 'ctdl_assigned', esc_html__( 'Assigned to', 'cleverness-to-do-list' ) ); ?>
				<?php CTDL_Templates::show_assigned( $assigned_meta ); ?>
			</span>
		<?php endif; ?>

		<?php do_action( 'ctdl_widget_list_items' ); ?>

	</li>

<?php endif; ?>