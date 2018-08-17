<?php
namespace Hametuha\Thread\Rest;

use Hametuha\Thread\Hooks\PostType;
use Hametuha\Thread\Model\CommentModel;
use Hametuha\Thread\Pattern\RestBase;
use Hametuha\Thread\Pattern\RushDetector;
use Hametuha\Thread\UI\CommentForm;

class RestComment extends RestCommentNew {

	protected $route = 'comment/(?P<thread_id>\d+)/(?P<comment_id>\d+)/?';

	/**
	 * Should return arguments.
	 *
	 * @param string $http_method
	 *
	 * @return array
	 */
	protected function get_args( $http_method ) {
		$args = parent::get_args( $http_method );
		unset( $args['reply_to'] );
		$args['comment_id'] = [
			'required' => true,
			'type' => 'int',
			'description' => 'Comment ID',
			'validate_callback' => function( $var ) {
				$comment = get_comment( $var );
				if ( ! $comment ) {
					return new \WP_Error( 'no_comment', __( 'Comment not found.', 'hamethread' ), [
						'status' => 404,
					] );
				} else {
					return true;
				}
			},
		];
		switch ( $http_method ) {
			case '':
				break;
		}
		$args = apply_filters( 'hamethread_comment_rest_args', $args, $http_method );
		return $args;
	}

	/**
	 * Get form to post comment.
	 *
	 * @param \WP_REST_Request $request
	 * @return array
	 */
	public function handle_get( $request ) {
		$post_id    = $request->get_param( 'thread_id' );
		$comment_id = $request->get_param( 'comment_id' );
		$args = [
			'title'   => __( 'Edit comment', 'hamethread' ),
			'post'    => get_post( $post_id ),
			'action'  => sprintf( 'comment/%d/%d', $post_id, $comment_id ),
			'comment' => get_comment( $comment_id ),
		];
		$form = apply_filters( 'hamethread_form_comment', hamethread_template( 'form-comment', '', false, $args ), $request );
		return [
			'html' => $form
		];
	}

	/**
	 * Delete comment.
	 *
	 * @param \WP_REST_Request $request
	 * @return array|\WP_Error
	 */
	public function handle_delete( $request ) {
		$comment_id = $request->get_param( 'comment_id' );
		$comment = get_comment( $comment_id );
		$result = wp_delete_comment( $comment );
		if ( ! $result ) {
			return new \WP_Error( 'failed_to_delete_comment', __( 'Sorry, but failed to delete comment.', 'hamethread' ) );
		} else {
			do_action( 'hamethread_after_comment_deleted', $comment, $request );
			return [
				'message' => __( 'Comment is successfully deleted.', 'hamethread' ),
			];
		}
	}

	/**
	 * Post new comment.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_post( $request ) {
		$comment_id = $request->get_param( 'comment_id' );
		$error = new \WP_Error();
		$error = apply_filters( 'hamethread_edit_comment_validation', $error, $request );
		if ( $error->get_error_messages() ) {
			return $error;
		}
		$old_content = get_comment_text( $comment_id );
		$comment_param = [
			'comment_ID'       => $comment_id,
			'comment_content'  => $request->get_param( 'comment_content' ),
		];
		$comment_param = apply_filters( 'hamethread_edit_comment_params', $comment_param, $request );
		if ( ! wp_update_comment( $comment_param ) ) {
			return new \WP_Error( 'failed_update_comment', __( 'Sorry, but failed to update comment.', 'hamethread' ) );
		}
		// Save diff
		add_comment_meta( $comment_id, '_comment_diff', [
			'updated' => current_time( 'mysql', true ),
			'text'    => $old_content,
			'user'    => get_current_user_id(),
		] );
		do_action( 'hamethread_comment_updated', $comment_id, $request );
		$comment = new CommentModel( $comment_id );
		$response = new \WP_REST_Response( $comment->to_array() );
		$response->set_headers( [
			sprintf( 'X-WP-COMMENT-COUNT: %d', get_comment_count( $request->get_param( 'thread_id' ) ) ),
		] );
		return $response;
	}

	/**
	 * Get permission callback.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|bool
	 */
	public function permission_callback( $request ) {
		return CommentForm::get_instance()->user_can_edit_comment( $request->get_param( 'comment_id' ) )
			? true
			: new \WP_Error( 'no_permisison', __( 'You have no permission to edit comment.', 'hamethread' ), [
				'status' => 403,
			] );
	}
}
