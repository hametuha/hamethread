<?php

namespace Hametuha\Thread\Hooks;


use Hametuha\Pattern\Singleton;

/**
 * Notification for Support
 *
 * @package Hametuha\Thread\Hooks
 */
class SupportNotification extends Singleton {
	
	/**
	 * Register hooks.
	 */
	protected function init() {
		add_action( 'hamethread_new_thread_inserted', [ $this, 'new_thread_created' ], 10, 2 );
		add_action( 'hamethread_new_comment_inserted', [ $this, 'new_comment_created' ], 10, 3 );
	}
	
	/**
	 * Send notification if created.
	 *
	 * @todo Send notification if thread created.
	 * @param int              $post_id
	 * @param \WP_REST_Request $request
	 */
	public function new_thread_created( $post_id, $request ) {
		// Product owner,
	}
	
	/**
	 * Send notification if comment created.
	 *
	 * Thread owner, reply target,
	 *
	 * @param int              $comment_id
	 * @param array            $comment_param
	 * @param \WP_REST_Request $request
	 */
	public function new_comment_created( $comment_id, $comment_param, $request ) {
		// Product users.
	}
	
}
