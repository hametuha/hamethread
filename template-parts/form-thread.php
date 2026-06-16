<?php
defined( 'ABSPATH' ) || die();
/**
 * Form to create thread.
 *
 */
?>
<form class="hamethread-form" id="hamethread-add" method="post" action="<?php echo esc_attr( $action ); ?>">

	<div class="hamethread-form-container">

		<h2 class="hamethread-form-title"><?php esc_html_e( 'Create New Thread', 'hamethread' ); ?></h2>

		<?php do_action( 'hamethread_before_thread_form', $args, $default ); ?>
		<input type="hidden" name="thread_parent" value="<?php echo esc_attr( $parent ); ?>" />

		<div class="hamethread-form-group">
			<label for="thread_title">
				<?php esc_html_e( 'Thread Title', 'hamethread' ); ?>
				<span class="hamethread-badge-required"><?php esc_html_e( 'Required', 'hamethread' ); ?></span>
			</label>
			<input type="text" class="hamethread-form-control" name="thread_title" id="thread_title" value="<?php echo $post ? esc_attr( get_the_title( $post ) ) : ''; ?>"
					placeholder="<?php esc_attr_e( 'e.g. What does "dark matter" mean?', 'hamethread' ); ?>"/>
		</div>

		<div class="hamethread-form-group">
			<label for="thread_content">
				<?php esc_html_e( 'Description', 'hamethread' ); ?>
				<span class="hamethread-badge-required"><?php esc_html_e( 'Required', 'hamethread' ); ?></span>
			</label>
			<textarea class="hamethread-form-control" rows="8" name="thread_content" id="thread_content"
						placeholder="<?php esc_attr_e( 'e.g. Yesterday, I read an article about galaxy. But I can\'t understand nor even imagine what "dark matter" is. Please ask my question.', 'hamethread' ); ?>"><?php echo $post ? esc_textarea( $post->post_content ) : ''; ?></textarea>
		</div>

		<div class="hamethread-form-group">
			<label for="topic_id">
				<?php esc_html_e( 'Topic', 'hamethread' ); ?>
				<?php if ( hamethread_topic_forced( get_current_user_id() ) ) : ?>
					<span class="hamethread-badge-required"><?php esc_html_e( 'Required', 'hamethread' ); ?></span>
				<?php endif; ?>
			</label>
			<select name="topic_id" id="topic_id" class="hamethread-form-control">
				<option value="0"<?php selected( ! $post ); ?>><?php esc_html_e( 'Please select topic', 'hamethread' ); ?></option>

				<?php foreach ( hamethread_topics() as $topic ) : ?>
					<option <?php selected( has_term( $topic, 'topic', $post ) ); ?>
						value="<?php echo esc_attr( $topic->term_id ); ?>"><?php echo esc_html( $topic->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<?php if ( $private ) : ?>
		<div class="hamethread-form-check">
			<input class="hamethread-form-check-input" type="checkbox" value="1" name="is_private" id="is_private" aria-describedby="is_private_description" />
			<label class="hamethread-form-check-label" for="is_private">
				<?php esc_html_e( 'Make this thread private', 'hamethread' ); ?>
			</label>
			<small id="is_private_description" class="hamethread-form-help">
				<?php if ( $post ) : ?>
					<?php
					// translators: %s is the thread title.
					echo esc_html( sprintf( __( 'Author of %s and invited people can see private thread.', 'hamethread' ), get_the_title( $post ) ) );
					?>
				<?php else : ?>
					<?php esc_html_e( 'Only invited people can see private thread.', 'hamethread' ); ?>
				<?php endif; ?>
			</small>
		</div>
		<?php endif; ?>

		<?php do_action( 'hamethread_after_thread_form', $args, $default ); ?>

		<div class="hamethread-form-submit">
			<button type="button" class="hamethread-form-cancel hamethread-btn hamethread-btn-link"><?php esc_html_e( 'Cancel', 'hamethread' ); ?></button>
			<input type="submit" class="hamethread-btn hamethread-btn-primary"
				value="<?php esc_attr_e( 'Submit', 'hamethread' ); ?>"/>
		</div>
	</div>

</form>
