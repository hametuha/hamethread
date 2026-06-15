<?php defined( 'ABSPATH' ) || die(); ?>
<div class="hamethread-alert hamethread-alert-muted hamethread-nocap" style="text-align: center;">
	<?php if ( comments_open() ) : ?>
		<?php
		// translators: %s is the login URL.
		printf( wpautop( __( 'You have no permission to comment. Please <a href="%s">log in</a> and continue.', 'hamethread' ) ), hamethread_login_url( get_permalink() ) );
		?>
	<?php else : ?>
		<p><?php esc_html_e( 'Comments are closed.', 'hamethread' ); ?></p>
	<?php endif; ?>
</div>
