<?php

namespace Hametuha\Thread\Model;


use Hametuha\Thread\UI\CommentForm;

class CommentModel {

	protected $comment = null;

	/**
	 * CommentModel constructor.
	 *
	 * @param $comment_id
	 */
	public function __construct( $comment_id ) {
		$this->comment = get_comment( $comment_id );
	}

	/**
	 * Get comment array object.
	 *
	 * @return array
	 */
	public function to_array() {
		$array = [
			'id'      => $this->comment->comment_ID,
			'user_id' => $this->comment->user_id,
			'parent'  => $this->comment->comment_parent,
			'content' => apply_filters( 'comment_text', get_comment_text( $this->comment ), $this->comment, [] ),
			'html'    => CommentForm::get_instance()->comment_display( $this->comment, $this->get_comment_argument(), $this->comment->comment_parent ? 2 : 1, true, false ),
		];
		return $array;
	}

	/**
	 * Get comment argument
	 *
	 * @return array
	 */
	public function get_comment_argument() {
		return apply_filters( 'hamethread_comment_argument', [
			'style' => 'li',
		], $this->comment );
	}

	/**
	 * User can comment.
	 *
	 * @param int|null|\WP_Post $post
	 * @param int|null $user_id
	 *
	 * @return bool
	 */
	public static function can_comment( $post = null, $user_id = null ) {
		$post = get_post( $post );
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$can = user_can( $user_id, 'read' );
		return (bool) apply_filters( 'hamethread_user_can_comment', $can, $post, $user_id );
	}

}
