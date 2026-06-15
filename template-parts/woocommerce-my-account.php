<?php
defined( 'ABSPATH' ) || die();
/** @var WP_Query $query */
if ( $query->have_posts() ) :
	?>

	<?php
	// translators: %d is the number of threads the user owns.
	$thread_count = sprintf( _nx( '%d thread', '%d threads', $query->found_posts, 'owning-thread', 'hamethread' ), $query->found_posts );
	?>
<p class="hamethread-text-muted">
	<?php
	// translators: %s is the number of threads (e.g. "3 threads").
	echo esc_html( sprintf( __( 'You have %s.', 'hamethread' ), $thread_count ) );
	?>
</p>

<ul class="hamethread-my-threads">
	<?php
	while ( $query->have_posts() ) :
		$query->the_post();
		?>
	<li class="hamethread-my-threads-item">
		<div class="hamethread-my-threads-header">
			<h5 class="hamethread-my-threads-title">
				<?php if ( 'private' === get_post_status() ) : ?>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe HTML escaped in hamethread_icon().
					echo hamethread_icon( 'lock' );
					?>
				<?php endif; ?>
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h5>
			<small class="hamethread-my-threads-date"><?php the_time( get_option( 'date_format' ) ); ?></small>
		</div>
		<p class="hamethread-my-threads-meta">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe HTML escaped in hamethread_icon().
			echo hamethread_icon( 'admin-comments' );
			?>
			<?php comments_number(); ?>
			<?php if ( hamethread_is_resolved() ) : ?>
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe HTML escaped in hamethread_icon().
				echo hamethread_icon( 'yes-alt' );
				?>
				<?php esc_html_e( 'Resolved', 'hamethread' ); ?>
			<?php endif; ?>
		</p>
		<small class="hamethread-my-threads-updated">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe HTML escaped in hamethread_icon().
			echo hamethread_icon( 'clock' );
			?>
			<?php
				esc_html_e( 'Last Updated: ', 'hamethread' );
				echo esc_html( hamethread_last_commented() );
			?>
		</small>
	</li>
		<?php
	endwhile;
	wp_reset_postdata();
	?>
</ul>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted pagination HTML provided via the hamethread_pagination filter.
	echo apply_filters( 'hamethread_pagination', '', $query );
	?>
	<?php
	// Query has no post.
else :
	?>

<div class="hamethread-alert hamethread-alert-muted hamethread-notification hamethread-notification-error">
	<?php esc_html_e( 'You have no thread yet.', 'hamethread' ); ?>
</div>

	<?php
endif;
