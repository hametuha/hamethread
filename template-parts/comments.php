<?php if ( hamethread_user_can_comment() && comments_open() ) : ?>
	<?php hamethread_template( 'button-comment-post' ) ?>
<?php else : ?>
	<?php hamethread_template( 'form-nocap' ); ?>
<?php endif; ?>

<?php if ( have_comments() ) : ?>
<ul class="hamethread-comments">
		<?php wp_list_comments( [
			'per_page' => 2,
			'type'     => 'comment',
			'callback' => [ \Hametuha\Thread\UI\CommentForm::get_instance(), 'comment_display' ],
		] ); ?>

    <?php echo paginate_comments_links() ?>
</ul>
<?php elseif( comments_open() ) : ?>
	<?php hamethread_template( 'comments-no' ) ?>
<?php endif; ?>
