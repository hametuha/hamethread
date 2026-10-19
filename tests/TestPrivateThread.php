<?php
/**
 * Permission test for private threads.
 */

use Hametuha\Thread\Hooks\SupportNotification;
use Hametuha\Thread\Model\CommentModel;

class TestPrivateThread extends WP_UnitTestCase {

	/**
	 * @var int Private thread ID.
	 */
	protected $thread = 0;

	/**
	 * @var int Thread author.
	 */
	protected $author = 0;

	/**
	 * @var int User who cannot read the thread.
	 */
	protected $outsider = 0;

	/**
	 * @var int Editor.
	 */
	protected $editor = 0;

	/**
	 * Create private thread.
	 */
	public function setUp(): void {
		parent::setUp();
		reset_phpmailer_instance();
		$this->author   = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$this->outsider = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$this->editor   = self::factory()->user->create( [ 'role' => 'editor' ] );
		$this->thread   = self::factory()->post->create( [
			'post_type'   => 'thread',
			'post_status' => 'private',
			'post_author' => $this->author,
		] );
	}

	/**
	 * Dispatch follower endpoint.
	 *
	 * @param string     $method
	 * @param int        $user_id Current user.
	 * @param string|int $target  user_id param.
	 * @return int HTTP status.
	 */
	protected function follow( $method, $user_id, $target = 'me' ) {
		wp_set_current_user( $user_id );
		$request = new WP_REST_Request( $method, sprintf( '/hamethread/v1/follower/in/%d', $this->thread ) );
		$request->set_param( 'user_id', $target );
		return rest_get_server()->dispatch( $request )->get_status();
	}

	/**
	 * Outsider cannot follow private thread.
	 */
	public function test_outsider_cannot_follow() {
		$this->assertSame( 403, $this->follow( 'POST', $this->outsider ) );
		$this->assertSame( 403, $this->follow( 'GET', $this->outsider ) );
		$this->assertEmpty( SupportNotification::get_instance()->get_subscribers( $this->thread ) );
	}

	/**
	 * Author and editor can follow private thread.
	 */
	public function test_readers_can_follow() {
		$this->assertSame( 200, $this->follow( 'POST', $this->author ) );
		$this->assertSame( 200, $this->follow( 'POST', $this->editor ) );
		$this->assertEqualSets( [ $this->author, $this->editor ], SupportNotification::get_instance()->get_subscribers( $this->thread ) );
	}

	/**
	 * Others' subscription status is not exposed.
	 */
	public function test_cannot_check_others_subscription() {
		$this->assertSame( 403, $this->follow( 'GET', $this->author, $this->editor ) );
		$this->assertSame( 200, $this->follow( 'GET', $this->editor, $this->author ) );
	}

	/**
	 * Outsider cannot comment on private thread.
	 */
	public function test_outsider_cannot_comment() {
		$this->assertFalse( CommentModel::can_comment( $this->thread, $this->outsider ) );
		$this->assertTrue( CommentModel::can_comment( $this->thread, $this->author ) );
		$this->assertTrue( CommentModel::can_comment( $this->thread, $this->editor ) );
	}

	/**
	 * Subscribers who cannot read the thread are not notified.
	 */
	public function test_outsider_is_not_notified() {
		$notification = SupportNotification::get_instance();
		// Simulate a subscription made before the fix.
		$notification->subscribe( $this->thread, $this->author );
		$notification->subscribe( $this->thread, $this->outsider );
		$comment_id = self::factory()->comment->create( [
			'comment_post_ID' => $this->thread,
			'user_id'         => $this->editor,
			'comment_content' => 'Secret answer.',
		] );
		$this->assertSame( [ $this->author ], array_values( array_map( function ( $user ) {
			return $user->ID;
		}, $notification->get_notify_to( $comment_id ) ) ) );
	}
}
