<?php

namespace Hametuha\Thread\Model;
use Hametuha\Thread\Hooks\PostType;
use Hametuha\Thread\Pattern\RushDetector;

/**
 * Thread model object.
 *
 * @package hamethread
 */
class ThreadModel {

	protected $post = null;

	/**
	 * ThreadModel constructor.
	 *
	 * @param int $post_id
	 */
	public function __construct( $post_id ) {
		$post = get_post( $post_id );
		if ( $post && PostType::get_instance()->post_type === $post->post_type ) {
			$this->post = $post;
		}
	}

	/**
	 * Convert thread object to array.
	 *
	 * @return mixed|void|\WP_Error
	 */
	public function to_array() {
		if ( ! $this->post ) {
			return new \WP_Error( 'no_thread_found', __( 'Sorry, but no thread found.', 'hamethread' ), [
				'response' => 404,
			] );
		}
		setup_postdata( $this->post );
		$array = [
			'title'       => get_the_title( $this->post ),
			'content'     => apply_filters( 'the_content', $this->post->post_content ),
			'raw_content' => $this->post->post_content,
			'date'        => $this->post->post_date,
			'date_atom'   => mysql2date( DATE_ATOM, $this->post->post_date_gmt ),
			'author_id'   => $this->post->post_author,
			'author'      => get_the_author_meta( 'display_name', $this->post->post_author ),
			'avatar'      => get_avatar_url( $this->post->post_author, [ 'size' => 192 ] ),
			'link'        => get_permalink( $this->post ),
			'count'       => get_comment_count( $this->post->ID ),
			'status'      => $this->post->post_status,
			'resolved'    => hamethread_is_resolved( $this->post ),
			'latest'      => hamethread_get_latest_comment_date( $this->post ),
			'html'        => hamethread_template( 'loop-post', '', false, [
				'post' => $this->post,
			] ),
		];
		wp_reset_postdata();
		/**
		 * hamethread_post_object
		 */
		return apply_filters( 'hamethread_post_object', $array, $this->post );
	}

	/**
	 * Check if user can post.
	 *
	 * @param null $user_id
	 * @return bool
	 */
	public static function can_post( $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$can = user_can( $user_id, self::thread_capability() );
		return apply_filters( 'hamethread_user_can_post', $can, $user_id );
	}

	/**
	 * Check if user can edit thread
	 *
	 * @param $user_id
	 * @param $post
	 * @return bool
	 */
	public static function can_edit( $user_id, $post ) {
		$post = get_post( $post );
		$can  = ( (int) $post->post_author === $user_id ) || user_can( $user_id, 'edit_others_posts' );
		return (bool) apply_filters( 'hamethread_user_can_archive_post', $can, $user_id, $post );
	}

	/**
	 * Check if user can archive thread.
	 *
	 * @param int               $user_id
	 * @param int|null|\WP_Post $post
	 * @return bool
	 */
	public static function can_archive( $user_id, $post ) {
		$post = get_post( $post );
		$can  = ( (int) $post->post_author === $user_id ) || user_can( $user_id, 'edit_others_posts' );
		return (bool) apply_filters( 'hamethread_user_can_archive_post', $can, $user_id, $post );
	}

	/**
	 * Check if user can start private thread.
	 *
	 * @param null|int $user_id
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public static function can_start_private( $user_id = null, $post_id = 0 ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		return (bool) apply_filters( 'hamethread_user_can_start_private_thread', false, $user_id, $post_id );
	}

	/**
	 * Check if user can mark thread as resolved.
	 *
	 * @param int               $user_id
	 * @param int|null|\WP_Post $post
	 * @return bool
	 */
	public static function can_resolve( $user_id, $post ) {
		$post = get_post( $post );
		$can  = ( (int) $post->post_author === $user_id ) || user_can( $user_id, 'edit_others_posts' );
		return (bool) apply_filters( 'hamethread_user_can_resolve_post', $can, $user_id, $post );
	}

	/**
	 * Change resolution status
	 *
	 * @param bool              $new_status
	 * @param null|int|\WP_Post $post
	 *
	 * @return bool|\WP_Error
	 */
	public static function change_resolution_status( $new_status, $post = null ) {
		$new_status = (bool) $new_status;
		$post       = get_post( $post );
		if ( ! $post ) {
			return new \WP_Error( 'no_thread_found', __( 'No post found.', 'hamethread' ), [
				'response' => 404,
			] );
		}
	}

	/**
	 * Detect if thread is resolved.
	 *
	 * @param int $thread_id
	 * @return bool
	 */
	public static function is_resolved( $thread_id ) {
		return (bool) get_post_meta( $thread_id, '_thread_resolved', true );
	}

	/**
	 * Get resolved count.
	 *
	 * @param int $thread_id
	 * @return int
	 */
	public static function get_resolved_count( $thread_id ) {
		return (int) get_post_meta( $thread_id, '_thread_resolved_count', true );
	}

	/**
	 * Set thread as resolved.
	 *
	 * @param int $thread_id
	 * @return bool
	 */
	public static function set_resolved( $thread_id ) {
		update_post_meta( $thread_id, 'thread_resolved_count', self::get_resolved_count( $thread_id ) + 1 );
		update_post_meta( $thread_id, '_thread_resolved', current_time( 'mysql', true ) );
		do_action( 'hamethread_update_resolved', $thread_id, self::get_resolved_count( $thread_id ) );
		return true;
	}

	/**
	 * Unset resolved thread.
	 *
	 * @param int $thread_id
	 * @return bool
	 */
	public static function unset_resolved( $thread_id ) {
		delete_post_meta( $thread_id, '_thread_resolved' );
		add_post_meta( $thread_id, '_thread_unresolved', current_time( 'mysql', true ) );
		do_action( 'hamethread_update_unresolved', $thread_id, self::get_resolved_count( $thread_id ) );
		return true;
	}

	/**
	 * Get thread capability.
	 *
	 * @return string
	 */
	public static function thread_capability() {
		return apply_filters( 'hamethread_user_cap', 'read' );
	}
}
