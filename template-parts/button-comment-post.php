<div class="hamethread-post-comment">
	<?php if ( hamethread_current_user_can_comment() ) : ?>
	<button class="button hamethread-post-button" data-hamethread="comment" data-end-point="<?php printf( 'comment/%d/new', get_the_ID() ); ?>">
        <i class="icon-plus-circle"></i>
        <?php esc_html_e( 'Post Comment', 'hamethread' ) ?>
    </button>
	<?php else : ?>
	<div class="alert alert-warning">
		<?php echo wp_kses_post( sprintf( __('Please <a class="alert-link" href="%s">log in</a> to post a comment.', 'hamethread' ), hamethread_login_url( get_permalink() ) ) ); ?>
	</div>
	<?php endif; ?>
</div>
