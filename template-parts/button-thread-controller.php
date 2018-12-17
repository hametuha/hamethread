<?php
if ( ! $can_edit && ! $can_archive  ) {
	return;
}
if ( 'private' === get_post_status() ) {
	$key   = 'publish';
	$label = __( 'Make public', 'hamethread' );
} else {
	$key   = 'archive';
	$label = __( 'Make private', 'hamethread' );
}
$lists = [];
ob_start();
if ( $can_edit ) {
	?>
	<li class="dropdown-item">
		<a href="#" data-hamethread="create" data-post-id="<?php echo esc_attr( $post->ID ) ?>"><?php esc_html_e( 'Edit', 'hamethread' ) ?></a>
	</li>
	<li class="dropdown-item">
		<?php
		$resolved = \Hametuha\Thread::is_resolved();
		$resolve_label = $resolved ? __( 'Unmark resolved', 'hamethread' ) : __( 'Mark as resolved', 'hamethread' );
		?>
		<a href="#" data-hamethread="resolve" data-post-id="<?php echo esc_attr( $post->ID ) ?>">
			<?php echo esc_html( $resolve_label ) ?>
		</a>
	</li>
	<?php
	$lists[] = ob_get_contents();
	
	
	
	ob_clean();
}
if ( $can_archive ) {
	?>
	<li class="dropdown-item">
		<a href="<?php echo rest_url( 'hamethread/v1/thread/' . $post->ID ) ?>" rel="nofollow" data-hamethread="<?php echo esc_attr( $key ) ?>" data-post-id="<?php echo esc_attr( $post->ID ) ?>">
			<?php echo esc_html( $label ) ?>
		</a>
	</li>
	<?php
	$lists[] = ob_get_contents();
	ob_clean();
}
ob_end_clean();
$lists = apply_filters( 'hamethread_controller_actions', $lists );
if ( ! $lists ) {
	return;
}
?>
<div class="hamethread-controller btn-toolbar justify-content-end">
	<div class="btn-group right">
		<button class="btn btn-link" data-toggle="dropdown" type="button"><i class="fa fa-cog"></i></button>
		<ul class="dropdown-menu dropdown-menu-right">
			<?php foreach ( $lists as $list ) {
				echo $list;
			} ?>
		</ul>
	</div>
</div>
