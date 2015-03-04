<?php
global $CTDL_Dashboard_Widget;
$cat_ids = ctdl_dashboard_categories();
?>

	<div class="uncompleted-checklist">
		<?php foreach ( $cat_ids as $cat_id ) : ?>
			<?php $CTDL_Dashboard_Widget->loop_through_todos( 0, $cat_id ); ?>
		<?php endforeach; ?>
	</div>

<?php if ( 1 == CTDL_Loader::$dashboard_settings['show_completed'] ) : ?>
	<div class="completed-checklist">
		<?php foreach ( $cat_ids as $cat_id ) : ?>
			<?php $CTDL_Dashboard_Widget->loop_through_todos( 1, $cat_id ); ?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<?php if ( CTDL_Lib::check_permission( 'todo', 'add' ) ) : ?>
	<p class="add-todo">
		<a href="<?php echo admin_url( '?page=cleverness-to-do-list#addtodo' ); ?>"><?php echo apply_filters( 'ctdl_add_text', esc_attr__( 'Add To-Do Item', 'cleverness-to-do-list' ) ); ?> &raquo;</a>
	</p>
<?php endif; ?>