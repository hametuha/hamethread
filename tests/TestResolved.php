<?php
/**
 * Thread "resolved" admin save tests.
 *
 * @package hamethread
 */

use Hametuha\Thread\Hooks\PostType;

/**
 * Regression test for the resolved checkbox in the post submit box.
 *
 * PostType::save_post() detected whether the "resolved" value changed but
 * never persisted it, so toggling the checkbox in wp-admin had no effect.
 * These tests drive save_post() with a valid nonce and assert that the
 * resolved state is actually written (and cleared).
 */
class TestResolved extends WP_UnitTestCase {

	/**
	 * Create a thread post owned by an administrator.
	 *
	 * @return int Post ID.
	 */
	private function create_thread() {
		$admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );
		return self::factory()->post->create( [
			'post_type'   => 'thread',
			'post_author' => $admin,
		] );
	}

	/**
	 * Simulate the submit-box POST and invoke save_post().
	 *
	 * @param int  $post_id  Target post.
	 * @param bool $resolved Whether the checkbox is checked.
	 */
	private function submit( $post_id, $resolved ) {
		$_POST['_hamethreadresolved'] = wp_create_nonce( 'hamethread_resolved' );
		if ( $resolved ) {
			$_POST['hamethread-resolved'] = '1';
		} else {
			unset( $_POST['hamethread-resolved'] );
		}
		PostType::get_instance()->save_post( $post_id, get_post( $post_id ) );
	}

	/**
	 * Reset the request super global between tests.
	 */
	public function tear_down() {
		unset( $_POST['_hamethreadresolved'], $_POST['hamethread-resolved'] );
		parent::tear_down();
	}

	/**
	 * Checking the box marks the thread as resolved.
	 */
	public function test_save_post_marks_resolved() {
		$post_id = $this->create_thread();
		$this->assertFalse( hamethread_is_resolved( $post_id ) );

		$this->submit( $post_id, true );

		$this->assertTrue( hamethread_is_resolved( $post_id ), 'Thread should be resolved after checking the box.' );
	}

	/**
	 * Unchecking the box clears the resolved state.
	 */
	public function test_save_post_clears_resolved() {
		$post_id = $this->create_thread();
		$this->submit( $post_id, true );
		$this->assertTrue( hamethread_is_resolved( $post_id ) );

		$this->submit( $post_id, false );

		$this->assertFalse( hamethread_is_resolved( $post_id ), 'Thread should be unresolved after unchecking the box.' );
	}

	/**
	 * An invalid nonce is ignored (no change is persisted).
	 */
	public function test_invalid_nonce_does_nothing() {
		$post_id = $this->create_thread();

		$_POST['_hamethreadresolved'] = 'bogus';
		$_POST['hamethread-resolved'] = '1';
		PostType::get_instance()->save_post( $post_id, get_post( $post_id ) );

		$this->assertFalse( hamethread_is_resolved( $post_id ), 'Invalid nonce must not change the resolved state.' );
	}
}
