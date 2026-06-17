<?php
/**
 * Render callback for the Thread Button block.
 *
 * Used by block.json's "render" property. Reuses the existing
 * hamethread_button() helper so markup and capability checks stay in one place.
 *
 * @package hamethread
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#render
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || die();

if ( ! function_exists( 'hamethread_button' ) ) {
	return;
}

$hamethread_label  = ! empty( $attributes['label'] ) ? $attributes['label'] : '';
$hamethread_parent = ! empty( $attributes['parent'] ) ? (int) $attributes['parent'] : 0;
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core function returns escaped attributes. ?>>
	<?php hamethread_button( $hamethread_parent, $hamethread_label ); ?>
</div>
