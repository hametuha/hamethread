<?php

namespace Hametuha\Thread\Hooks;


use Hametuha\Pattern\Singleton;
use Hametuha\Thread;

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
		add_action( 'hamethread_new_comment_inserted', [ $this, 'new_comment_created' ], 10, 3 );
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
		$comment = get_comment( $comment_id );
		$users = $this->get_notify_to( $comment );
		if ( ! $users ) {
			// No user, do nothing.
			return;
		}
		if ( ! apply_filters( 'hamethread_should_send_notifications', true, $comment, $users, $request ) ) {
			// If false, avoid sending.
			return;
		}
		/**
		 * hamethread_notification_title
		 *
		 * Notification mail title.
		 *
		 * @param string      $subject
		 * @param \WP_Comment $comment
		 */
		$subject = apply_filters( 'hamethread_notification_title', sprintf(
			__( '%s - A new comment is posted to your subscribing thread.', 'hamethread' ), // translator: %s is site title.
			get_bloginfo( 'name' ) ),
		$comment );

		// translators: %1$s is user name, %2$s is comment author, %3$s is thread title, %4$s is URL of thread.
		$body = __( 'Dear %1$s,

%2$s posted new comment on %3$s.

>>>

%5$s

>>>

URL: %4$s

You get this notification because you subscribed thread.
To change notification setting, plese go to thread page. 
', 'hamethread' );

		/**
		 * hamethread_notification_body
		 *
		 * Notification body text.
		 *
		 * @param string      $body
		 * @param \WP_Comment $comment
		 */
		$body = apply_filters( 'hamethread_notification_body', $body, $comment );

		$commenter      = get_userdata( $comment->user_id );
		$commenter_name = $commenter ? $commenter->display_name : __( 'A guest', 'hamethread' );
		$thread_title   = get_the_title( $comment->comment_post_ID );
		$thread_url     = get_permalink( $comment->comment_post_ID );
		if ( function_exists( 'hamail_simple_mail' ) ) {
			// Hamail exsits, so
			foreach ( [
				'%1$s' => '-name-',
				'%2$s' => $commenter_name,
				'%3$s' => $thread_title,
				'%4$s' => $thread_url,
				'%5$s' => $comment->comment_content,
					  ] as $str => $replaced ) {
				$body = str_replace( $str, $replaced, $body );
			}
			hamail_simple_mail( array_map( function( \WP_User $user ) {
				return $user->ID;
			}, $users ), $subject, $body );
		} else {
			// Send notification email 1 by 1.
			foreach ( $users as $user ) {
				wp_mail( $user->user_email, $subject, sprintf( $body, $user->display_name, $commenter_name, $thread_title, $thread_url, $comment->comment_content ), [
					sprintf( 'From: %s <%s>', get_bloginfo( 'name' ), get_option( 'admin_email' ) ),
				] );
			}
		}

	}

	/**
	 * Get users to whom notifications should be sent.
	 *
	 * @param int|\WP_Comment $comment
	 * @return \WP_User[]
	 */
	public function get_notify_to( $comment ) {
		$comment = get_comment( $comment );
		$subscribers = $this->get_subscribers( $comment->comment_post_ID );
		// If this is reply, add comment parent.
		if ( $comment->comment_parent ) {
			$parent = get_comment( $comment->comment_parent );
			if ( $parent && $parent->user_id ) {
				$subscribers[] = $parent->user_id;
				array_unique( $subscribers, SORT_ASC );
			}
		}
		// Filter commenter ID.
		$commenter_id = $comment->user_id;
		$subscribers = array_filter( $subscribers, function( $user_id ) use ( $commenter_id ) {
			return $user_id != $commenter_id;
		} );
		if ( ! $subscribers ) {
			return [];
		} else {
			$query = new \WP_User_Query( [
				'include' => $subscribers,
				'number'  => -1,
			] );
			return $query->get_results();
		}
	}

	/**
	 * Get subscribers IDs.
	 *
	 * @param null|int|\WP_Post $post
	 * @return int[]
	 */
	public function get_subscribers( $post = null ) {
		$post = get_post( $post );
		$subscribers = array_filter( array_map( function( $var ) {
			$user_id = trim( $var );
			return is_numeric( $user_id ) ? (int) $user_id : '';
		}, explode( ',', (string) get_post_meta( $post->ID, '_hamethread_subscribers', true ) ) ) );
		/**
		 * hamethread_subscribers
		 *
		 * ID of subscribers.
		 *
		 * @param int[]    $subscribers
		 * @param \WP_Post $post
		 * @return int[]
		 */
		return apply_filters( 'hamethread_subscribers', $subscribers, $post );
	}

	/**
	 * Subscribe thread.
	 *
	 * @param null|int|\WP_Post $thread
	 * @param null|int          $user_id
	 * @return bool|\WP_Error
	 */
	public function subscribe( $thread = null, $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$thread = get_post( $thread );
		if ( ! $thread || ! $user_id ) {
			return new \WP_Error( 'not_found', __( 'Thread or user not found.', 'hamethread' ), [
				'response' => 404,
				'status'   => 404,
			] );
		}
		$subscribers = $this->get_subscribers( $thread );
		if ( in_array( $user_id, $subscribers ) ) {
			return new \WP_Error( 'already_subscribed', __( 'Already subscribing this thread.', 'hamethread' ), [
				'response' => 400,
				'status'   => 400,
			] );
		}
		$subscribers[] = (int) $user_id;
		sort( $subscribers );
		return (bool) update_post_meta( $thread->ID, '_hamethread_subscribers', implode( ',', $subscribers ) );
	}

	/**
	 * Unsubscribe thread.
	 *
	 * @param null|int|\WP_Post $thread
	 * @param null|int          $user_id
	 *
	 * @return bool|\WP_Error
	 */
	public function unsubscribe( $thread = null, $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$thread = get_post( $thread );
		if ( ! $thread || ! $user_id ) {
			return new \WP_Error( 'not_found', __( 'Thread or user not found.', 'hamethread' ), [
				'response' => 404,
				'status'   => 404,
			] );
		}
		$subscribers = $this->get_subscribers( $thread );
		if ( ! in_array( $user_id, $subscribers ) ) {
			return new \WP_Error( 'not_in_list', __( 'Not in the list of subscribers.', 'hamethread' ), [
				'response' => 404,
				'status'   => 404,
			] );
		}
		$filtered = [];
		foreach ( $subscribers as $id ) {
			if ( $id == $user_id ) {
				continue;
			}
			$filtered[] = $id;
		}
		sort( $filtered );
		return (bool) update_post_meta( $thread->ID, '_hamethread_subscribers', implode( ',', $filtered ) );
	}
}
