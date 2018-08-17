<?php if ( hamethread_user_can_comment() ) : ?>
	<?php hamethread_template( 'button-comment-post' ) ?>
<?php else : ?>
	<?php hamethread_template( 'form-nocap' ); ?>
<?php endif; ?>
<ul class="hamethread-comments">
	<?php if ( have_comments() ) : ?>
		<?php wp_list_comments( [
			'per_page' => 2,
			'type'     => 'comment',
			'callback' => [ \Hametuha\Thread\UI\CommentForm::get_instance(), 'comment_display' ],
		] ); ?>

    <?php echo paginate_comments_links() ?>
	<?php endif; ?>
</ul>

<?php if ( ! have_comments() ) : ?>
<?php hamethread_template( 'comments-no' ) ?>
<?php endif; ?>
