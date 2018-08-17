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
			return new \WP_Error( 'no_thread_found', __( 'Sorry, but thread not found.', 'hamethread' ), [
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
			'avatar'      => get_avatar_url( $this->post->post_author, ['size' => 192 ] ),
			'link'        => get_permalink( $this->post ),
			'count'       => get_comment_count( $this->post->ID ),
			'html'        => hameplate( 'loop-post', '', false, [
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
		$can = ( $post->post_author == $user_id ) || user_can( $user_id, 'edit_others_posts' );
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
		$can = ( $post->post_author == $user_id ) || user_can( $user_id, 'edit_others_posts' );
		return (bool) apply_filters( 'hamethread_user_can_archive_post', $can, $user_id, $post );
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
