<div class="alert alert-muted hamethread-nocap text-center">
	<?php if ( comments_open() ) : ?>
		<?php printf( wpautop( __( 'You have no permission to comment. Please <a href="%s" class="alert-link">log in</a> and continue.', 'hamethread' ) ), hamethread_login_url( get_permalink() ) ); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Comments are closed.', 'hamethread' ) ?></p>
	<?php endif; ?>
</div>
