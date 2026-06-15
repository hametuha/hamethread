<?php defined( 'ABSPATH' ) || die(); ?>
<div class="hamethread-post-comment">
	<?php if ( hamethread_current_user_can_comment() ) : ?>
	<button class="button hamethread-post-button" data-hamethread="comment" data-end-point="<?php printf( 'comment/%d/new', get_the_ID() ); ?>">
		<?php echo hamethread_icon( 'plus-alt' ); ?>
		<?php esc_html_e( 'Post Comment', 'hamethread' ); ?>
	</button>
	<?php else : ?>
	<div class="hamethread-alert hamethread-alert-warning">
		<?php
		// translators: %s is the login URL.
		echo wp_kses_post( sprintf( __( 'Please <a href="%s">log in</a> to post a comment.', 'hamethread' ), hamethread_login_url( get_permalink() ) ) );
		?>
	</div>
	<?php endif; ?>
</div>
