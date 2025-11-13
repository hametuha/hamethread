<?php
/**
 * Subscription helper
 */


class TestSubscription extends WP_UnitTestCase {

	/**
	 * @var WP_Post Post for test.
	 */
	protected $thread = null;

	/**
	 * Create therad.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->thread = wp_insert_post( [
			'post_title'   => 'Test Thread',
			'post_content' => 'This thread is new and clean.',
			'post_type'    => 'thread',
			'post_status'  => 'publish',
		] );
	}

	/**
	 * Check if notification works.
	 */
	public function test_subscription() {
		$notification = \Hametuha\Thread\Hooks\SupportNotification::get_instance();
		$this->assertTrue( $notification->subscribe( $this->thread, 10 ) );
		$this->assertEqualSets( [ 10 ], $notification->get_subscribers( $this->thread ) );
		$this->assertTrue( $notification->unsubscribe( $this->thread, 10 ) );
	}

}
