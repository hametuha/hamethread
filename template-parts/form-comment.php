<form class="hamethread-form" id="hamethread-comment" method="post" action="<?php echo esc_attr( $action ); ?>">

	<div class="hamethread-form-container">

		<h2 class="hamethread-form-title">
			<?php echo esc_html( $title ); ?>
		</h2>

		<?php if ( ! empty( $parent_comment ) ) : ?>
		<blockquote class="hamethread-form-quote">
			<cite class="hamethread-form-quote-author">
				<?php echo get_avatar( $parent_comment, 24 ); ?>
				<?php echo esc_html( get_comment_author( $parent_comment ) ); ?>
				<time datetime="<?php echo esc_attr( get_comment_date( 'c', $parent_comment ) ); ?>">
					<?php echo esc_html( get_comment_date( '', $parent_comment ) ); ?>
				</time>
			</cite>
			<div class="hamethread-form-quote-content">
				<?php echo wpautop( esc_html( $parent_comment->comment_content ) ); ?>
			</div>
		</blockquote>
		<?php endif; ?>

		<?php if ( isset( $reply_to ) && $reply_to ) : ?>
			<input type="hidden" name="reply_to" value="<?php echo esc_html( $reply_to ); ?>" />
		<?php endif; ?>

		<?php do_action( 'hamethread_before_comment_form', $args ); ?>

		<div class="hamethread-form-group">
			<label for="comment_content"><?php esc_html_e( 'Comment', 'hamethread' ); ?></label>
			<textarea class="hamethread-form-control" rows="8" name="comment_content" id="comment_content"
						placeholder="<?php esc_attr_e( 'e.g. I have another opinion.', 'hamethread' ); ?>"><?php echo $comment ? esc_textarea( $comment->comment_content ) : ''; ?></textarea>
		</div>

		<?php do_action( 'hamethread_after_comment_form', $args, $comment ); ?>

		<div class="hamethread-form-submit">
			<button type="button" class="hamethread-form-cancel hamethread-btn hamethread-btn-link"><?php esc_html_e( 'Cancel', 'hamethread' ); ?></button>
			<input type="submit" class="hamethread-btn hamethread-btn-primary"
				value="<?php esc_attr_e( 'Submit', 'hamethread' ); ?>"/>
		</div>
	</div>

</form>
