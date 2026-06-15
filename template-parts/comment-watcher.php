<?php
defined( 'ABSPATH' ) || die();
/**
 * Comment watcher list.
 */
?>
<div class="hamethread-watchers">

	<h3 class="hamethread-watchers-title"><?php esc_html_e( 'People Following This Thread', 'hamethread' ); ?></h3>

	<?php if ( is_user_logged_in() ) : ?>
		<div id="hamthread-watchers-toggle" class="hamethread-watchers-toggle" data-id="<?php the_ID(); ?>">
			<button class="hamethread-btn hamethread-btn-default" style="visibility: hidden;">button</button>
		</div>
	<?php else : ?>
		<p class="hamethread-text-muted">
			<?php echo wp_kses_post( sprintf( __( 'Please <a href="%s" rel="nofollow">login</a> to follow this thread.', 'hamethread' ), hamethread_login_url( get_permalink() ) ) ); ?>
		</p>
	<?php endif; ?>

	<?php
	$followers = \Hametuha\Thread::subscribers();
	if ( $followers ) :
		?>
		<div class="hamethread-watchers-list">
			<p class="hamethread-text-muted">
				<?php echo esc_html( sprintf( _n( '%d people following.', '%d people following.', count( $followers ), 'hamethread' ), count( $followers ) ) ); ?>
			</p>
			<?php foreach ( $followers as $user ) : ?>
				<div class="hamethread-watchers-item">
					<?php if ( $user->has_cap( 'edit_posts' ) ) : ?>
						<a class="hamethread-watcher-link"
							href="<?php echo esc_url( get_author_posts_url( $user->ID ) ); ?>"
							title="<?php echo esc_attr( $user->display_name ); ?>">
							<?php echo get_avatar( $user->ID ); ?>
						</a>
					<?php else : ?>
						<?php echo get_avatar( $user->ID ); ?>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="hamethread-watchers-empty hamethread-alert hamethread-alert-muted">
			<?php esc_html_e( 'No one is following this thread.', 'hamethread' ); ?>
		</div>
	<?php endif; ?>
</div>
