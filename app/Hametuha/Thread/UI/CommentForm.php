<?php

namespace Hametuha\Thread\UI;


use Hametuha\Thread\Hooks\PostType;
use Hametuha\Thread\Pattern\AbstractUI;

/**
 * Comment hacker.
 *
 * @package hamethread
 */
class CommentForm extends AbstractUI {
	
	/**
	 * Constructor
	 */
	protected function init() {
		parent::init();
		add_filter( 'comments_template', [ $this, 'comments_template' ] );
	}

	/**
	 * Register assets.
	 */
	public function register_script() {
		wp_register_script( 'hamethread-comment', hamethread_asset_url() . '/js/hamethread-comment.js', ['hamethread'], hamethread_version(), true );
	}
	
	/**
	 * Is comment supported?
	 *
	 * @param string $post_type
	 * @return bool
	 */
	public function is_supported( $post_type ) {
		if ( ! PostType::get_instance()->is_supported( $post_type ) ) {
			$post_types = apply_filters( 'hamethread_dynamic_comment_post_types', [] );
			if ( ! in_array( $post_type, $post_types ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns file path.
	 *
	 * @param string $path
	 * @return string
	 */
	public function comments_template( $path ) {
		if ( ! $this->is_supported( get_post_type() ) ) {
			return $path;
		}
		// Let's override comment template.
		wp_enqueue_script( 'hamethread-comment' );
		wp_localize_script( 'hamethread-comment', 'HameThreadComment', [
			'noComment' => hamethread_template( 'comments-no', '', false ),
			'confirm'   => __( 'Are you sure to delete comment?', 'hamethread' ),
			'follow'    => __( 'Follow This Thread', 'hamethread' ),
			'following' => __( 'Following', 'hamethread' ),
			'unfollow'  => __( 'Unfollow This Thread', 'hamethread' ),
		] );
		wp_enqueue_style( 'hamethread' );
		return hamethread_file_path( 'comments' );
	}

	/**
	 * Render comment.
	 *
	 * @param \WP_Comment $comment
	 * @param array $args
	 * @param int $depth
	 * @param bool $close
	 * @param bool $echo  Default is true.
	 * @return string
	 */
	public function comment_display( $comment, $args, $depth, $close = false, $echo = true ) {
		if ( 'div' === $args[ 'style' ] ) {
			$tag       = 'div';
			$add_below = 'comment';
		} else {
			$tag       = 'li';
			$add_below = 'div-comment';
		}
		$html = sprintf( '<%s %s id="comment-%d">', $tag, comment_class(  ['hamethread-comment-item-wrapper'], $comment, $comment->comment_post_ID, false ), $comment->comment_ID );
		$html .= hamethread_template( 'comment-loop', $comment->comment_type, false, [
			'comment' => $comment,
			'depth'  => $depth,
			'params' => $args,
		] );
		if ( $close ) {
			$html .= "</{$tag}>";
		}
		if ( $echo  ) {
			echo $html;
		}
		return $html;
	}

	/**
	 * Get comment capability.
	 *
	 * @param int|\WP_Comment $comment
	 * @return bool
	 */
	public function user_can_edit_comment( $comment ) {
		$comment = get_comment( $comment );
		if ( $comment->user_id == get_current_user_id() ) {
			// Comment owner
			$can = true;
		} elseif ( current_user_can( 'moderate_comments' ) ) {
			$can = true;
		} else {
			$can = false;
		}
		return (bool) apply_filters( 'hamethread_current_user_can_edit_comment', $can, $comment );
	}

	/**
	 * Get column actions.
	 *
	 * @param \WP_Comment $comment
	 * @return array
	 */
	public function comment_actions( $comment ) {
		return [
			'reply' => '',
			'like'  => '',
		];
	}

}
