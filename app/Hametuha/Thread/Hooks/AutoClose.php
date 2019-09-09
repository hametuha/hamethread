<?php

namespace Hametuha\Thread\Hooks;


use Hametuha\Pattern\Singleton;
use Hametuha\Thread\Model\ThreadModel;

/**
 * Auto close thread.
 *
 * @package hamethread
 */
class AutoClose extends Singleton {

	const CRON_NAME = 'hamethread_auto_close_clone';

	public $close_key = '_hamethread_expires_at';

	public $closed_key = '_hamethread_closed_at';

	/**
	 * Register hooks.
	 */
	protected function init() {
		add_action( 'hamethread_new_thread_inserted', [ $this, 'set_thread_limit' ], 10, 2 );
		add_action( 'hamethread_new_comment_inserted', [ $this, 'update_limit_with_new_comment' ] );
		// add_action( 'init', [ $this, 'set_cron' ] );
		add_action( self::CRON_NAME, [ $this, 'do_cron' ] );
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
				$recurrence = apply_filters( 'hamethread_autoclose_cron_timing', 60 * 60 * 4 );
				wp_schedule_event( current_time( 'timestamp', true ), $recurrence, self::CRON_NAME );
			}
		}
	}

	/**
	 * Get post to close.
	 *
	 * @param int $time
	 * @return \WP_Post[]
	 */
	public function get_post_to_close( $time = 0 ) {
		if ( ! $time ) {
			$time = current_time( 'timestamp', true );
		}
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
					'value'   => $time,
					'compare' => '<',
					'type'    => 'numeric',
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
	 * @param int $diff Difference in days.
	 * @return int
	 */
	public function do_cron( $diff = 0 ) {
		$diff = (int) $diff;
		// Get posts to notify.
		$time = current_time( 'timestamp', true ) + ( 60 * 60 * 24 * $diff );
		$posts = $this->get_post_to_close( $time );
		$closed = 0;
		foreach ( $posts as $post ) {
			if ( $this->auto_close( $post ) ) {
				$closed++;
			}
		}
		return $closed;
	}
	
	/**
	 * Close post.
	 *
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	public function auto_close( $post ) {
		$should_close = apply_filters( 'hamethread_should_close_post', true, $post );
		if ( ! $should_close ) {
			return false;
		}
		if ( ! wp_update_post( [
			'ID'             => $post->ID,
			'comment_status' => 'closed',
		] ) ) {
			return false;
		}
		delete_post_meta( $post->ID, $this->close_key );
		update_post_meta( $post->ID, $this->closed_key, current_time( 'timestamp', true ) );
		if ( ! ThreadModel::is_resolved( $post->ID ) ) {
			ThreadModel::set_resolved( $post->ID );
		}
		do_action( 'hamethread_automatically_closed', $post );
		return true;
	}

	/**
	 * Set thread limit if enabled.
	 *
	 * @param int              $post_id
	 * @param \WP_REST_Request $request
	 */
	public function set_thread_limit( $post_id ) {
		if ( ! $this->is_auto_closable( $post_id ) ) {
			// Do nothing.
			return;
		}
		if ( ! $this->should_count_down_from_thread_creation( $post_id ) ) {
			return;
		}
		$this->update_close_time( $post_id );
	}

	/**
	 * Detect if thread is auto closable.
	 *
	 * @param null|int|\WP_Post $thread
	 * @return bool
	 */
	public function is_auto_closable( $thread = null ) {
		$thread = get_post( $thread );
		$should = (bool) $this->get_duration( $thread->ID );
		return (bool) apply_filters( 'hamethread_auto_closable', $should, $thread );
	}

	/**
	 * Detect if post limit should be updated with new comment.
	 *
	 * @param int $comment_id
	 * @return void
	 */
	public function update_limit_with_new_comment( $comment_id ) {
		$comment = get_comment( $comment_id );
		$thread = get_post( $comment->comment_post_ID );
		if ( ! $comment || ! $thread ) {
			return;
		}
		if ( ! $this->is_auto_closable( $thread ) ) {
			return;
		}
		if ( ! $this->should_prolong_with_comment( $thread->ID ) ) {
			return;
		}
		$this->update_close_time( $thread->ID );
	}

	/**
	 * How much minutes to close.
	 *
	 * @param null|int|\WP_Post $thread
	 * @return int
	 */
	public function auto_close_time( $post_id ) {
		$duration  = $this->get_duration( $post_id );
		return (int) apply_filters( 'hamethread_prolonged_time', current_time( 'timestamp', true ) + ( $duration * 60 * 60 * 24 ), $post_id );
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
	
	/**
	 * Get auto close duration.
	 *
	 * @param int $post_id
	 *
	 * @return int
	 */
	public function get_duration( $post_id = 0 ) {
		return (int) apply_filters( 'hamethread_auto_close_duration', (int) get_option( AdminSetting::OPTION_AUTO_CLOSE_DURATION, 0 ), $post_id );
	}
	
	/**
	 * Update close time.
	 *
	 * @param int $post_id
	 *
	 * @return bool|\WP_Error
	 */
	public function update_close_time( $post_id ) {
		$duration = $this->get_duration( $post_id );
		if ( ! $duration ) {
			return new \WP_Error( 'hamethread_auto_close_update_failed', __( 'This thread cannot be prolong.', 'hamethread' ), [
				'status' => 400,
			] );
		}
		return (bool) update_post_meta( $post_id, '_hamethread_expires_at', $this->auto_close_time( $post_id ) );
	}
	
	/**
	 * Should start count down?
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public function should_count_down_from_thread_creation( $post_id = 0 ) {
		$should = 2 > (int) get_option( AdminSetting::OPTION_AUTO_CLOSE_PROLONG, 0 );
		return (bool) apply_filters( 'hamethread_should_count_down_from_thread_creation', $should, $post_id );
	}
	
	/**
	 * Should update limit with new comment?
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public function should_prolong_with_comment( $post_id = 0 ) {
		$should = 0 <= (int) get_option( AdminSetting::OPTION_AUTO_CLOSE_PROLONG, 0 );
		return (bool) apply_filters( 'hamethread_should_prolong_with_comment', $should, $post_id );
	}
}
