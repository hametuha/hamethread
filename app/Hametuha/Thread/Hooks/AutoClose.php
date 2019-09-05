<?php

namespace Hametuha\Thread\Hooks;


use Hametuha\Pattern\Singleton;

/**
 * Auto close thread.
 *
 * @package hamethread
 */
class AutoClose extends Singleton {

	const CRON_NAME = 'hamethread_auto_close_clone';

	public $close_key = '_hamethread_close_time';

	public $closed_key = '_hamethread_closed_at';

	/**
	 * Register hooks.
	 */
	protected function init() {
		// add_action( 'hamethread_new_thread_inserted', [ $this, 'set_thread_limit' ], 10, 2 );
		// add_action( 'init', [ $this, 'set_cron' ] );
	}

	/**
	 * Register cron
	 */
	public function set_cron() {
		$do_not_cron = apply_filters( 'hamethread_do_not_cron', false );
		if ( $do_not_cron ) {
			if ( wp_next_scheduled( self::CRON_NAME ) ) {
				wp_clear_scheduled_hook( self::CRON_NAME );
			}
			return;
		} else {
			if ( ! wp_next_scheduled( self::CRON_NAME ) ) {
				$recurrence = apply_filters( 'hamethread_autoclose_cron_timing', 60 * 60 );
				wp_schedule_event( current_time( 'timestamp', true ), $recurrence, self::CRON_NAME );
			}
			add_action( self::CRON_NAME, [ $this, 'do_cron' ] );
		}
	}

	/**
	 * Get post to close.
	 *
	 * @param int $diff
	 * @return \WP_Post[]
	 */
	public function get_post_to_close( $diff = 0 ) {
		$post_type = PostType::get_instance()->post_type;
		// Get posts to close.
		$posts = get_posts( [
			'post_type'      => $post_type,
			'post_status'    => [ 'publish', 'private' ],
			'comment_status' => 'open',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'     => $this->close_key,
					'value'   => current_time( 'timestamp', true ) + $diff,
					'compare' => '<',
				],
				[
					'key'     => '_thread_resolved',
					'compare' => 'NOT EXISTS',
				],
			],
		] );
		return $posts;
	}

	/**
	 * Check thread to close and notify.
	 *
	 */
	public function do_cron() {
		// Get posts to notify.

	}

	/**
	 * Set thread limit if enabled.
	 *
	 * @param int              $post_id
	 * @param \WP_REST_Request $request
	 */
	public function set_thread_limit( $post_id, $request ) {
		if ( $this->is_auto_closable( $post_id ) ) {
			$limit = $this->auto_close_time() + current_time( 'timestamp', true );
			update_post_meta( $post_id, $this->close_key, $limit );
		}
	}

	/**
	 * Detect if thread is auto closable.
	 *
	 * @param null|int|\WP_Post $thread
	 * @return bool
	 */
	public function is_auto_closable( $thread = null ) {
		$thread = get_post( $thread );
		return (bool) apply_filters( 'hamethread_auto_closable', false, $thread );
	}

	/**
	 * Detect if post limit should be updated with new comment.
	 *
	 * @param null|int|\WP_Post $thread
	 * @return bool
	 */
	public function update_limit_with_new_comment( $thread = null ) {
		$thread = get_post( $thread );
		return (bool) apply_filters( 'hamethred_update_limit_with_new_comment', true, $thread );
	}

	/**
	 * How much minutes to close.
	 *
	 * @param null|int|\WP_Post $thread
	 * @return int Default 3 days.
	 */
	public function auto_close_time( $thread = null ) {
		return (int) apply_filters( 'hamethread_auto_close_time', 60 * 60 * 24 * 3, $thread );
	}

	/**
	 * How much minutes to notify before closing.
	 *
	 * @param null|int|\WP_Post $thread
	 * @return int Default 1 day.
	 */
	public function auto_close_notification_time( $thread = null ) {
		return (int) apply_filters( 'hamethread_auto_close_notification_time', 60 * 60 * 24, $thread );
	}
}
