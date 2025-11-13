<?php
/*
 * Thread buttons.
 */
/** @var int    $parent */
/** @var string $label */
/** @var bool   $private */

$attr = wp_parse_args( $attr, [
	'class'  => 'primary-button btn btn-primary',
	'prefix' => '',
] );
?>

<?php if ( hamethread_user_can_start() ) : ?>
<button data-hamethread="create" class="<?php echo esc_attr( $attr['class'] ) ?>" data-parent="<?php printf( '%d', $parent ) ?>" data-private="<?= (int) $private ?>">
	<?php echo wp_kses_post( $attr['prefix'] ) ?>
	<?php echo esc_html( $label ); ?>
</button>
<?php else : ?>
<div class="alert alert-warning">
	<?php echo wp_kses_post( sprintf( __( 'To start thread, please <a class="alert-link" rel="nofollow" href="%s">log in</a>.', 'hamethread' ), hamethread_login_url( isset($_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' ) ) ); ?>
</div>
<?php endif; ?>
