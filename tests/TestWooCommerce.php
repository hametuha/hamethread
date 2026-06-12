<?php
/**
 * WooCommerce support integration tests.
 *
 * @package hamethread
 */

use Hametuha\Thread\Hooks\SupportWooCommerce;

if ( ! function_exists( 'WC' ) ) {
	/**
	 * Minimal WooCommerce stub.
	 *
	 * SupportWooCommerce::init() bails out unless WooCommerce is active
	 * ( `function_exists( 'WC' )` ). The CI test environment does not install
	 * WooCommerce, so we provide a no-op stub to let the hook registration run.
	 * When the real WooCommerce is loaded this declaration is skipped.
	 *
	 * @return null
	 */
	function WC() {
		return null;
	}
}

/**
 * Ensure SupportWooCommerce only registers callable hook callbacks.
 *
 * Regression test for #46: commit cb89a1e removed the `register_assets()`
 * method but left `add_action( 'init', [ $this, 'register_assets' ] )` behind,
 * which threw a fatal error on every WooCommerce-enabled site. This test
 * re-runs the hook registration and asserts that every callback pointing back
 * to the instance resolves to a real, callable method.
 */
class TestWooCommerce extends WP_UnitTestCase {

	/**
	 * Every hook SupportWooCommerce registers must be callable.
	 */
	public function test_hook_callbacks_are_callable() {
		// The Singleton caches its instance and guards init() behind WC(),
		// so build a fresh instance and invoke the protected init() directly.
		$ref      = new ReflectionClass( SupportWooCommerce::class );
		$instance = $ref->newInstanceWithoutConstructor();
		$init     = $ref->getMethod( 'init' );
		$init->setAccessible( true );
		$init->invoke( $instance );

		// Collect every registered callback that is bound to this instance.
		global $wp_filter;
		$invalid = [];
		foreach ( $wp_filter as $tag => $hook ) {
			foreach ( $hook->callbacks as $callbacks ) {
				foreach ( $callbacks as $cb ) {
					$fn = $cb['function'];
					if ( is_array( $fn ) && isset( $fn[0] ) && $fn[0] === $instance && ! is_callable( $fn ) ) {
						$invalid[] = $tag . ' => ' . $fn[1];
					}
				}
			}
		}

		$this->assertSame( [], $invalid, 'Non-callable hook callbacks: ' . implode( ', ', $invalid ) );
	}
}
