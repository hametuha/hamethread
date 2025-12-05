<?php
/** @var WP_Query $query */
if ( $query->have_posts() ) :
?>

<p class="hamethread-text-muted"><?php printf( __( 'You have %s.', 'hamethread' ), sprintf( _nx( '%d thread', '%d threads', $query->found_posts, 'owning-thread', 'hamethread' ), $query->found_posts ) ) ?></p>

<ul class="hamethread-my-threads">
	<?php while ( $query->have_posts() ) : $query->the_post(); ?>
	<li class="hamethread-my-threads-item">
		<div class="hamethread-my-threads-header">
			<h5 class="hamethread-my-threads-title">
				<?php if ( 'private' === get_post_status() ) : ?>
					<?php echo hamethread_icon( 'lock' ); ?>
				<?php endif; ?>
				<a href="<?php the_permalink() ?>"><?php the_title() ?></a>
			</h5>
			<small class="hamethread-my-threads-date"><?php the_time( get_option( 'date_format' ) ) ?></small>
		</div>
		<p class="hamethread-my-threads-meta">
			<?php echo hamethread_icon( 'admin-comments' ); ?> <?php comments_number() ?>
			<?php if ( hamethread_is_resolved() ) : ?>
				<?php echo hamethread_icon( 'yes-alt' ); ?> <?php esc_html_e( 'Resolved', 'hamethread' ) ?>
			<?php endif; ?>
		</p>
		<small class="hamethread-my-threads-updated">
			<?php echo hamethread_icon( 'clock' ); ?>
			<?php
				esc_html_e( 'Last Updated: ', 'hamethread' );
				echo esc_html( hamethread_last_commented() );
			?>
		</small>
	</li>
	<?php endwhile; wp_reset_postdata(); ?>
</ul>
<?php echo apply_filters( 'hamethread_pagination', '', $query ) ?>
<?php
// Query has no post.
else :
?>

<div class="hamethread-alert hamethread-alert-muted hamethread-notification hamethread-notification-error">
	<?php esc_html_e( 'You have no thread yet.', 'hamethread' ) ?>
</div>

<?php endif;
