<?php
/** @var WP_Query $query */
if ( $query->have_posts() ) :
?>

<p class="description text-muted"><?php printf( __( 'You have %s.', 'hamethread' ), sprintf( _nx( '%d thread', '%d threads', $query->found_posts, 'owning-thread', 'hamethread' ), $query->found_posts ) ) ?></p>

<ul class="list-group hamethread-my-threads">
	<?php while ( $query->have_posts() ) : $query->the_post(); ?>
	<li class="list-group-item hamethread-my-threads-item">
		<div class="d-flex w-100 justify-content-between">
			<h5 class="mb-2 mt-0">
				<?php if ( 'private' !== get_post_status() ) : ?>
					<i class="fa fa-lock"></i>
				<?php endif; ?>
				<a href="<?php the_permalink() ?>"><?php the_title() ?></a>
			</h5>
			<small><?php the_time( get_option( 'date_format' ) ) ?></small>
		</div>
		<p class="mb-1">
			<i class="fa fa-comment"></i> <?php comments_number() ?>
			<?php if ( hamethread_is_resolved() ) : ?>
				<i class="fa fa-check-circle"></i> <?php esc_html_e( 'Resolved', 'hamethread' ) ?>
			<?php endif; ?>
		</p>
		<small>
			<i class="fa fa-clock"></i>
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

<div class="alert alert-light hamethread-notification hamethread-notification-error">
	
	<?php esc_html_e( 'You have no thread yet.', 'hamethread' ) ?>
	
</div>

<?php endif;
