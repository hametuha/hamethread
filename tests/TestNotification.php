<?php
/**
 * Notification mail test.
 */

use Hametuha\Thread\Hooks\SupportNotification;

class TestNotification extends WP_UnitTestCase {

	/**
	 * @var int Thread ID.
	 */
	protected $thread = 0;

	/**
	 * @var int Thread author.
	 */
	protected $author = 0;

	/**
	 * @var int Commenter.
	 */
	protected $commenter = 0;

	/**
	 * Create thread and subscribers.
	 */
	public function setUp(): void {
		parent::setUp();
		reset_phpmailer_instance();
		$this->author    = self::factory()->user->create( [ 'user_email' => 'author@example.com' ] );
		$this->commenter = self::factory()->user->create( [ 'user_email' => 'commenter@example.com' ] );
		$this->thread    = self::factory()->post->create( [
			'post_type'   => 'thread',
			'post_status' => 'publish',
			'post_author' => $this->author,
		] );
		$notification = SupportNotification::get_instance();
		$notification->subscribe( $this->thread, $this->author );
		$notification->subscribe( $this->thread, $this->commenter );
	}

	/**
	 * Post comment and send notifications.
	 *
	 * @return MockPHPMailer
	 */
	protected function post_comment() {
		$comment_id = self::factory()->comment->create( [
			'comment_post_ID' => $this->thread,
			'user_id'         => $this->commenter,
			'comment_content' => 'Hello, thread.',
		] );
		SupportNotification::get_instance()->new_comment_created( $comment_id, [], new WP_REST_Request() );
		return tests_retrieve_phpmailer_instance();
	}

	/**
	 * Commenter should not be notified of their own comment.
	 */
	public function test_commenter_is_not_notified() {
		$mailer = $this->post_comment();
		$this->assertCount( 1, $mailer->mock_sent );
		$this->assertSame( 'author@example.com', $mailer->get_recipient( 'to' )->address );
	}

	/**
	 * Mail body tells users not to reply.
	 */
	public function test_mail_has_no_reply_notice() {
		$mailer = $this->post_comment();
		$this->assertStringContainsString( 'Please do not reply to this email.', $mailer->get_sent()->body );
		$this->assertStringContainsString( 'Hello, thread.', $mailer->get_sent()->body );
	}

	/**
	 * Headers are filterable.
	 */
	public function test_headers_are_filterable() {
		$filter = function ( $headers ) {
			$headers[] = 'Reply-To: support@example.com';
			return $headers;
		};
		add_filter( 'hamethread_notification_headers', $filter );
		$mailer = $this->post_comment();
		remove_filter( 'hamethread_notification_headers', $filter );
		$this->assertStringContainsString( 'Reply-To: support@example.com', $mailer->get_sent()->header );
	}
}
