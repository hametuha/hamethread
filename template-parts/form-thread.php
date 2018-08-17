<form class="hamethread-form" id="hamethread-add" method="post" action="<?php echo esc_attr( $action ) ?>">

	<div class="hamethread-form-container">

		<h2 class="hamethread-form-title"><?php esc_html_e( 'Create New Thread', 'hamethread' ) ?></h2>

		<?php do_action( 'hamethread_before_thread_form', $args, $default ); ?>

		<div class="form-group">
			<label for="thread_title">
				<?php esc_html_e( 'Thread Title', 'hamethread' ) ?>
				<span class="label label-danger"><?php esc_html_e( 'Required', 'hamethread' ) ?></span>
			</label>
			<input type="text" class="form-control" name="thread_title" id="thread_title" value="<?php echo $post ? esc_attr( get_the_title( $post ) ) : ''; ?>"
				   placeholder="<?php esc_attr_e( 'e.g. What does "dark matter" mean?', 'hamethread' ) ?>"/>
		</div>

		<div class="form-group">
			<label for="thread_content"><?php esc_html_e( 'body', 'thread' ) ?></label>
			<textarea class="form-control" rows="8" name="thread_content" id="thread_content"
					  placeholder="<?php esc_attr_e( 'e.g. Yesterday, I read an article about galaxy. But I can\'t understand nor even imagine what "dark matter" is. Please ask my question.', 'hamethread' ) ?>"><?php echo $post ? esc_textarea( $post->post_content ) : ''; ?></textarea>
		</div>

		<div class="form-group">
			<label for="topic_id">
				<?php esc_html_e( 'Topic', 'thread' ) ?>
				<?php if ( hamethread_topic_forced( get_current_user_id() ) ) : ?>
					<span class="label label-danger"><?php esc_html_e( 'Required', 'hamethread' ) ?></span>
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

		<?php do_action( 'hamethread_after_thread_form', $args, $default ); ?>

		<div class="hamethread-form-submit text-right">
			<button class="hamethread-form-cancel btn btn-link btn-lg"><?php esc_html_e( 'Cancel', 'hamethread' ) ?></button>
			<input type="submit" class="btn btn-success btn-lg"
			   value="<?php esc_attr_e( 'Submit', 'hamethread' ); ?>"/>
		</div>
	</div>

</form>
