<?php

namespace Hametuha\Thread\Hooks;


use Hametuha\Pattern\Singleton;
use Hametuha\Thread\Model\ThreadModel;

/**
 * Best answer
 *
 * @package hamethread
 */
class BestAnswer extends Singleton {

	const BA_KEY = '_marked_as_ba';

	/**
	 * Executed inside constructor.
	 */
	protected function init() {
		add_filter( 'hamethread_comment_actions', [ $this, 'comment_actions' ], 1, 2 );
		add_filter( 'comment_class', [ $this, 'comment_class' ], 10, 4 );
		add_filter( 'map_meta_cap', [ $this, 'best_answer_caps' ], 10, 4 );
	}

	/**
	 * Set meta cap for best answer.
	 *
	 * @param array  $caps
	 * @param string $cap
	 * @param int    $user_id
	 * @param array  $args
	 * @return array
	 */
	public function best_answer_caps( $caps, $cap, $user_id, $args ) {
		switch ( $cap ) {
			case 'set_best_answer':
			case 'unset_best_answer':
				$post_id = isset( $args[0] ) ? $args[0] : 0;
				$thread  = get_post( $post_id );
				if ( ! $thread ) {
					$caps = [ 'do_not_allow' ];
					break;
				}
				if ( $user_id == $thread->post_author ) {
					$caps = [ 'edit_posts' ];
				} else {
					$caps = [ 'edit_others_posts' ];
				}
				break;
			default:
				// Do nothing.
				break;
		}
		return $caps;
	}

	/**
	 * Get comment class.
	 *
	 * @param string[]    $classes
	 * @param string      $csv
	 * @param int         $comment_id
	 * @param \WP_Comment $comment
	 * @return string[]
	 */
	public function comment_class( $classes, $csv, $comment_id, $comment ) {
		if ( hamethread_is_best_answer( $comment_id ) ) {
			$classes[] = 'best-answer';
		}
		return $classes;
	}

	/**
	 * Add button to comment loop.
	 *
	 * @param string[]    $actions
	 * @param \WP_Comment $comment
	 * @return string[]
	 */
	public function comment_actions( $actions, $comment ) {
		if ( ! hamethread_best_answer_supported( get_post_type( $comment->comment_post_ID ) ) ) {
			return $actions;
		}
		if ( ! is_user_logged_in() ) {
			return $actions;
		}
		$toggle = false;
		if ( hamethread_has_best_answer( $comment->comment_post_ID ) ) {
			if ( ! hamethread_is_best_answer( $comment ) ) {
				return $actions;
			} else {
				$toggle = true;
			}
		}
		if ( $toggle ) {
			if ( ! current_user_can( 'unset_best_answer', $comment->comment_post_ID ) ) {
				return $actions;
			}
		} else {
			if ( ! current_user_can( 'set_best_answer', $comment->comment_post_ID ) ) {
				return $actions;
			}
		}
		$method = $toggle ? 'DELETE' : 'POST';
		$action = sprintf( 'best-answer/%d', $comment->comment_ID );
		$label  = $toggle ? __( 'Cancel Best Answer', 'hamethread' ) : __( 'Best Answer', 'hamethread' );
		$icon   = $toggle ? 'times-circle' : 'star';
		$actions = array_merge( [ 'ba' => sprintf(
			'<button class="hamethread-ba-toggle" data-path="%s" data-method="%s"><i class="fa fa-%s"></i> %s</button>',
			esc_attr( $action ),
			esc_attr( $method ),
			$icon,
			esc_html( $label )
		) ], $actions );
		return $actions;
	}

	/**
	 * Mark comment as best answer
	 *
	 * @param int|\WP_Comment $comment
	 * @return bool|\WP_Error
	 */
	public function mark_as_the_best( $comment = null ) {
		$comment = get_comment( $comment );
		if ( ! $comment ) {
			return $this->no_comment();
		}
		if ( hamethread_is_best_answer( $comment ) ) {
			return new \WP_Error( 'already_ba', __( 'This comment is already best answer.', 'hamethread' ), [
				'status' => 403,
			] );
		}
		// Get existing BA and remove it.
		$ba = hamethread_get_best_answer( $comment->comment_post_ID );
		if ( $ba ) {
			$this->unmark_the_best( $ba );
		}
		// Add new BA
		$now = current_time( 'timestamp', true );
		update_comment_meta( $comment->comment_ID, self::BA_KEY, $now );
		if ( ! ThreadModel::is_resolved( $comment->comment_post_ID ) ) {
			// Resolved.
			ThreadModel::set_resolved( $comment->comment_post_ID );
		}
		do_action( 'hamethread_best_answer_selected', $comment, $now );
		return true;
	}

	/**
	 * Unmark best answer.
	 *
	 * @param null|int|\WP_Comment $comment
	 * @return bool|\WP_Error
	 */
	public function unmark_the_best( $comment = null ) {
		$comment = get_comment( $comment );
		if ( ! $comment ) {
			return $this->no_comment();
		}
		$old_value = get_comment_meta( $comment->comment_ID, self::BA_KEY, true );
		delete_comment_meta( $comment->comment_ID, self::BA_KEY );
		do_action( 'hamethread_best_answer_unmarked', $comment, $old_value );
		return true;
	}

	/**
	 * Return error if no comment.
	 *
	 * @return \WP_Error
	 */
	public function no_comment() {
		return new \WP_Error( 'no_comment', __( 'No comment found.', 'hamethread' ), [
			'status' => 404,
		] );
	}
}
