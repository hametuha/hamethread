<?php
if ( ! $can_edit && ! $can_archive  ) {
	return;
}
?>
<div class="hamethread-controller btn-toolbar">
	<div class="btn-group right">
		<button class="btn btn-link dropdown-toggle" data-toggle="dropdown" type="button"><i class="icon-cog"></i></button>
		<ul class="dropdown-menu">
			<?php if ( $can_edit ) : ?>
				<li><a href="#" data-hamethread="create" data-post-id="<?php echo esc_attr( $post->ID ) ?>"><?php esc_html_e( 'Edit', 'hamethread' ) ?></a></li>
			<?php endif; ?>
			<?php if ( $can_archive ) : ?>
				<li><a href="<?php echo rest_url( 'hamethread/v1/thread/' . $post->ID ) ?>" rel="nofollow" data-hamethread="archive" data-post-id="<?php echo esc_attr( $post->ID ) ?>"><?php esc_html_e( 'Archive', 'hamethread' ) ?></a></li>
			<?php endif; ?>
		</ul>
	</div>
</div>
