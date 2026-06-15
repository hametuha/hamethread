<?php
defined( 'ABSPATH' ) || die();
if ( ! $can_edit && ! $can_archive ) {
	return;
}
if ( 'private' === get_post_status() ) {
	$key   = 'publish';
	$label = __( 'Make public', 'hamethread' );
} else {
	$key   = 'archive';
	$label = __( 'Make private', 'hamethread' );
}
$lock_action = comments_open( $post ) ? __( 'Close thread', 'hamethread' ) : __( 'Reopen thread', 'hamethread' );
$lists       = [];
ob_start();
if ( $can_edit ) {
	?>
	<li class="hamethread-controller-item">
		<a href="#" data-hamethread="create" data-post-id="<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'Edit', 'hamethread' ); ?></a>
	</li>
	<li class="hamethread-controller-item">
		<?php
		$resolved      = \Hametuha\Thread::is_resolved();
		$resolve_label = $resolved ? __( 'Unmark resolved', 'hamethread' ) : __( 'Mark as resolved', 'hamethread' );
		?>
		<a href="#" data-hamethread="resolve" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
			<?php echo esc_html( $resolve_label ); ?>
		</a>
	</li>
	<?php
	$lists[] = ob_get_contents();
	ob_clean();
}
if ( $can_archive ) {
	?>
	<li class="hamethread-controller-item">
		<a href="<?php echo rest_url( 'hamethread/v1/thread/' . $post->ID ); ?>" rel="nofollow" data-hamethread="<?php echo esc_attr( $key ); ?>" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
			<?php echo esc_html( $label ); ?>
		</a>
	</li>
	<li class="hamethread-controller-item">
		<a href="<?php echo rest_url( 'hamethread/v1/thread/lock/' . $post->ID ); ?>" rel="nofollow" data-hamethread="<?php echo comments_open( $post ) ? 'lock' : 'reopen'; ?>">
			<?php echo esc_html( $lock_action ); ?>
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
<div class="hamethread-controller">
	<div class="hamethread-controller-dropdown">
		<button class="hamethread-controller-toggle" type="button" aria-expanded="false" aria-haspopup="true">
			<?php echo hamethread_icon( 'cog' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Thread actions', 'hamethread' ); ?></span>
		</button>
		<ul class="hamethread-controller-menu">
			<?php
			foreach ( $lists as $list ) {
				echo $list;
			}
			?>
		</ul>
	</div>
</div>
