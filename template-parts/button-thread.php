<?php
/*
 * Thread buttons.
 */
/** @var int    $parent */
/** @var string $label */
?>

<?php if ( hamethread_user_can_start() ) : ?>
<button data-hamethread="create" class="primary-button btn btn-primary btn-block" data-parent="<?php printf( '%d', $parent ) ?>">
	<?php echo esc_html( $label ); ?>
</button>
<?php else : ?>
<div class="alert alert-warning">
	<?php echo wp_kses_post( sprintf( __( 'To start thread, please <a class="alert-link" rel="nofollow" href="%s">log in</a>.', 'hamethread' ), wp_login_url( isset($_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' ) ) ); ?>
</div>
<?php endif; ?>
