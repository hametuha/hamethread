<?php
/**
 * Hashboard screen tests.
 *
 * @package hamethread
 */

use Hametuha\Thread\Screen\HashboardScreen;

/**
 * Regression test for #98.
 */
class TestHashboardScreen extends WP_UnitTestCase {

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		if ( ! class_exists( 'Hametuha\Hashboard\Pattern\Screen' ) ) {
			$this->markTestSkipped( 'Hashboard is not available.' );
		}
		// wp_enqueue_*() ignores unregistered handles, but in CI neither handle is registered:
		// - hb-hashboard-helper: Hashboard is initialized only in the local env.
		// - hamethread-hashboard: registered from wp-dependencies.json, a build artifact.
		// Register stubs so that the test checks only what head() requests.
		if ( ! wp_script_is( 'hb-hashboard-helper', 'registered' ) ) {
			wp_register_script( 'hb-hashboard-helper', false, array(), '1.0.0', true );
		}
		if ( ! wp_style_is( 'hamethread-hashboard', 'registered' ) ) {
			wp_register_style( 'hamethread-hashboard', false, array(), '1.0.0' );
		}
		wp_dequeue_script( 'hb-hashboard-helper' );
		wp_dequeue_style( 'hamethread-hashboard' );
	}

	/**
	 * head() must load hashboard's common helper (side menu toggle).
	 */
	public function test_head_enqueues_hashboard_helper() {
		HashboardScreen::get_instance()->head();
		$this->assertTrue( wp_script_is( 'hb-hashboard-helper', 'enqueued' ), 'hb-hashboard-helper should be enqueued.' );
		$this->assertTrue( wp_style_is( 'hamethread-hashboard', 'enqueued' ), 'hamethread-hashboard style should be enqueued.' );
	}
}
