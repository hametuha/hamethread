<?php
/**
 * Comment content sanitization tests.
 *
 * @package hamethread
 */

use Hametuha\Thread\Model\CommentModel;

/**
 * Regression tests for stored XSS via unsanitized comment content.
 *
 * The plugin stores comments through wp_insert_comment()/wp_update_comment(),
 * which bypass core's comment kses filtering. hamethread_sanitize_comment_content()
 * restores that protection for users without `unfiltered_html`. These tests lock
 * in both the capability gate and the end-to-end result served via REST.
 */
class TestCommentSanitize extends WP_UnitTestCase {

	private $malicious = 'Hello <strong>world</strong> <script>alert(1)</script>';

	public function tear_down() {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * A non-privileged user's script is stripped, safe markup is kept.
	 */
	public function test_subscriber_content_is_filtered() {
		$user = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user );

		$clean = hamethread_sanitize_comment_content( $this->malicious );

		$this->assertStringNotContainsString( '<script>', $clean, 'Script must be stripped for subscribers.' );
		$this->assertStringContainsString( '<strong>world</strong>', $clean, 'Safe markup must be preserved.' );
	}

	/**
	 * Users with unfiltered_html (e.g. admins) keep raw content, matching core.
	 */
	public function test_unfiltered_html_user_content_is_preserved() {
		$user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user );

		// Skip on multisite, where administrators lack unfiltered_html.
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$this->markTestSkipped( 'Administrator lacks unfiltered_html on this install.' );
		}

		$this->assertSame( $this->malicious, hamethread_sanitize_comment_content( $this->malicious ) );
	}

	/**
	 * Non-string input is handled without a TypeError (PHP 8.1+ safety).
	 */
	public function test_non_string_input_returns_empty() {
		$this->assertSame( '', hamethread_sanitize_comment_content( null ) );
		$this->assertSame( '', hamethread_sanitize_comment_content( [ 'x' ] ) );
	}

	/**
	 * Backslashes survive the sanitize + slash + insert round-trip.
	 *
	 * wp_insert_comment() unslashes its input, so callers must wp_slash() the
	 * sanitized content. Without it, backslashes (e.g. "C:\path", "\d+") are lost.
	 */
	public function test_backslashes_are_preserved_when_stored() {
		$user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user );
		$post_id = self::factory()->post->create( [ 'post_type' => 'thread', 'post_author' => $user ] );

		$content    = 'path C:\\Users\\test and regex \\d+ end';
		$comment_id = wp_insert_comment( [
			'comment_post_ID'  => $post_id,
			'comment_content'  => wp_slash( hamethread_sanitize_comment_content( $content ) ),
			'user_id'          => $user,
			'comment_approved' => 1,
		] );

		$this->assertSame( $content, get_comment( $comment_id )->comment_content );
	}

	/**
	 * End-to-end: a stored comment from a subscriber exposes no script in the
	 * REST response HTML (the path that JS injects into the DOM).
	 */
	public function test_rest_payload_has_no_script() {
		$author  = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $author );
		$post_id = self::factory()->post->create( [ 'post_type' => 'thread', 'post_author' => $author ] );

		$comment_id = wp_insert_comment( [
			'comment_post_ID' => $post_id,
			'comment_content' => hamethread_sanitize_comment_content( $this->malicious ),
			'user_id'         => $author,
			'comment_approved' => 1,
		] );

		$payload = ( new CommentModel( $comment_id ) )->to_array();

		$this->assertStringNotContainsString( '<script>', $payload['html'], 'REST html must not contain script.' );
	}
}
