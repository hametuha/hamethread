<?php
/** @var WP_Comment $comment */
?>
<div class="hamethread-comment-item">
	<?php if ( \Hametuha\Thread\UI\CommentForm::get_instance()->user_can_edit_comment( $comment ) ) : ?>
	<div class="hamethread-controller btn-toolbar">
		<div class="btn-group right">
			<button class="btn btn-link" data-bs-toggle="dropdown" type="button"><i class="fa fa-cog"></i></button>
			<ul class="dropdown-menu dropdown-menu-end">
				<li class="dropdown-item">
					<a href="<?php printf( 'comment/%d/%d', $comment->comment_post_ID, $comment->comment_ID ) ?>"
						data-hamethread="comment-edit" rel="nofollow">
						<?php esc_html_e( 'Edit', 'hamethread' ) ?>
					</a>
				</li>
				<li class="dropdown-item">
					<a href="<?php printf( 'comment/%d/%d', $comment->comment_post_ID, $comment->comment_ID ) ?>"
					   rel="nofollow" data-hamethread="comment-delete">
						<?php esc_html_e( 'Delete', 'hamethread' ) ?>
					</a>
				</li>
				<?php do_action( 'hamethread_comment_control_actions', $comment ) ?>
			</ul>
		</div>
	</div>
	<?php endif; ?>
	<div class="hamethread-comment-avatar">
		<?php echo get_avatar( $comment->user_id ?: $comment->comment_author_email ) ?>
	</div>
	<div class="hamethread-comment-body">
		<header class="hamethread-comment-header">
			<span class="hamethread-comment-author"><?php comment_author( $comment ) ?></span>
			<span class="<?php echo hamethread_commentor_label_class( $comment, 'hamethread-comment-role' ); ?>"><?php echo hamethread_commentor_label( $comment ) ?></span>
			<span class="hamethread-comment-date">
				<?php comment_date( '', $comment ) ?>
				<?php if ( hamethread_is_edited( $comment ) ) : ?>
					<?php esc_html_e( '(edited)', 'hamethread' ) ?>
				<?php endif; ?>
			</span>
		</header>
		<div class="hamethread-comment-content">
			<?php if ( hamethread_is_best_answer( $comment ) ) : ?>
				<p class="text-success hamethread-comment-best-answer">
					<i class="fa fa-crown"></i> <strong><?php esc_html_e( 'Best Answer', 'hamethread' ) ?></strong>
				</p>
			<?php endif; ?>
			<?php comment_text() ?>
		</div>
		<footer class="hamethread-comment-actions">
			<?php foreach ( hamethread_comment_actions( $comment ) as $action ) {
				echo $action;
			}?>
		</footer>
	</div>
</div>
