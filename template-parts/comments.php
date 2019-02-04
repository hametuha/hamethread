<?php if ( hamethread_user_can_comment() && comments_open() ) : ?>
	<?php hamethread_template( 'button-comment-post' ) ?>
<?php else : ?>
	<?php hamethread_template( 'form-nocap' ); ?>
<?php endif; ?>

<ul class="hamethread-comments" data-comment-count="<?php comments_number( 0, 1, '%' ) ?>">
	<?php if ( have_comments() ) : ?>
		<?php wp_list_comments( [
			'type'     => 'comment',
			'callback' => [ \Hametuha\Thread\UI\CommentForm::get_instance(), 'comment_display' ],
		] ); ?>

	<?php echo hamethread_comment_links() ?>
	<?php endif; ?>
</ul>

<?php if ( comments_open() ) :
    hamethread_template( 'comments-no' );
endif; ?>

<?php
// Display comment watchers.
if ( comments_open() ) :
   hamethread_template( 'comment-watcher' );
endif;
