<form class="hamethread-form" id="hamethread-add" method="post" action="<?php echo esc_attr( $action ) ?>">

	<div class="hamethread-form-container">

		<h2 class="hamethread-form-title"><?php esc_html_e( 'Create New Thread', 'hamethread' ) ?></h2>

		<?php do_action( 'hamethread_before_thread_form', $args, $default ); ?>
		<input type="hidden" name="thread_parent" value="<?php echo esc_attr( $parent ); ?>" />

		<div class="form-group">
			<label for="thread_title">
				<?php esc_html_e( 'Thread Title', 'hamethread' ) ?>
				<span class="badge badge-danger"><?php esc_html_e( 'Required', 'hamethread' ) ?></span>
			</label>
			<input type="text" class="form-control" name="thread_title" id="thread_title" value="<?php echo $post ? esc_attr( get_the_title( $post ) ) : ''; ?>"
				   placeholder="<?php esc_attr_e( 'e.g. What does "dark matter" mean?', 'hamethread' ) ?>"/>
		</div>

		<div class="form-group">
			<label for="thread_content">
				<?php esc_html_e( 'Description', 'thread' ) ?>
				<span class="badge badge-danger"><?php esc_html_e( 'Required', 'hamethread' ) ?></span>
			</label>
			<textarea class="form-control" rows="8" name="thread_content" id="thread_content"
					  placeholder="<?php esc_attr_e( 'e.g. Yesterday, I read an article about galaxy. But I can\'t understand nor even imagine what "dark matter" is. Please ask my question.', 'hamethread' ) ?>"><?php echo $post ? esc_textarea( $post->post_content ) : ''; ?></textarea>
		</div>

		<div class="form-group">
			<label for="topic_id">
				<?php esc_html_e( 'Topic', 'thread' ) ?>
				<?php if ( hamethread_topic_forced( get_current_user_id() ) ) : ?>
					<span class="badge badge-danger"><?php esc_html_e( 'Required', 'hamethread' ) ?></span>
				<?php endif; ?>
			</label>
			<select name="topic_id" id="topic_id" class="form-control">
				<option value="0"<?php selected( ! $post ) ?>><?php esc_html_e( 'Please select topic', 'hamethread' ) ?></option>

				<?php foreach ( hamethread_topics() as $topic ) : ?>
					<option <?php selected( has_term( $topic, 'topic', $post ) ); ?>
						value="<?php echo esc_attr( $topic->term_id ); ?>"><?php echo esc_html( $topic->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		
		<?php if ( $private ) : ?>
		<div class="form-check">
			<input class="form-check-input" type="checkbox" value="1" name="is_private" id="is_private" aria-describedby="is_private_description" />
			<label class="form-check-label" for="is_private">
				<?php esc_html_e( 'Make this thread private', 'hamethread' ) ?>
			</label>
			<small id="is_private_description" class="form-text text-muted">
				<?php if ( $post ) : ?>
					<?php echo esc_html( sprintf(  __( 'Author of %s and invited people can see private thread.', 'hamethread' ), get_the_title( $post ) ) ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Only invited people can see private thread.', 'hamethread' ) ?>
				<?php endif; ?>
			</small>
		</div>
		<?php endif; ?>

		<?php do_action( 'hamethread_after_thread_form', $args, $default ); ?>

		<div class="hamethread-form-submit text-right">
			<button class="hamethread-form-cancel btn btn-link"><?php esc_html_e( 'Cancel', 'hamethread' ) ?></button>
			<input type="submit" class="btn btn-success"
			   value="<?php esc_attr_e( 'Submit', 'hamethread' ); ?>"/>
		</div>
	</div>

</form>
