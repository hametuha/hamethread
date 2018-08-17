<form class="hamethread-form" id="hamethread-comment" method="post" action="<?php echo esc_attr( $action ) ?>">

	<div class="hamethread-form-container">

		<h2 class="hamethread-form-title">
			<?php echo $title; ?>
		</h2>

		<?php if ( isset( $reply_to ) && $reply_to ) : ?>
			<input type="hidden" name="reply_to" value="<?php echo esc_html( $reply_to ) ?>" />
		<?php endif; ?>

		<?php do_action( 'hamethread_before_comment_form', $args ); ?>

		<div class="form-group">
			<label for="comment_content"><?php esc_html_e( 'Comment', 'hamethread' ) ?></label>
			<textarea class="form-control" rows="8" name="comment_content" id="comment_content"
					  placeholder="<?php esc_attr_e( 'e.g. Your opinion is collect.', 'hamethread' ) ?>"><?php echo $comment ? esc_textarea( $comment->comment_content ) : ''; ?></textarea>
		</div>

		<?php do_action( 'hamethread_after_comment_form', $args ); ?>

		<div class="hamethread-form-submit text-right">
			<button class="hamethread-form-cancel btn btn-link btn-lg"><?php esc_html_e( 'Cancel', 'hamethread' ) ?></button>
			<input type="submit" class="btn btn-success btn-lg"
			   value="<?php esc_attr_e( 'Submit', 'hamethread' ); ?>"/>
		</div>
	</div>

</form>
