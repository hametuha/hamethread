<?php

namespace Hametuha\Thread\Rest;


use Hametuha\Thread\Hooks\BestAnswer;
use Hametuha\Thread\Model\CommentModel;
use Hametuha\Thread\Pattern\RestBase;

/**
 * REST API for Best answer
 *
 * @package hamethread
 */
class RestBestAnswer extends RestBase {

	protected $route = 'best-answer/(?P<comment_id>\d+)/?';

	/**
	 * Should return arguments.
	 *
	 * @param string $http_method
	 * @return array
	 */
	protected function get_args( $http_method ) {
		return [
			'comment_id' => [
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => function( $var ) {
					return is_numeric( $var ) && get_comment( $var );
				},
			],
		];
	}

	/**
	 * Handle post request.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_post( $request ) {
		$comment = $this->get_comment( $request );
		if ( is_wp_error( $comment ) ) {
			return $comment;
		}
		$result = BestAnswer::get_instance()->mark_as_the_best( $comment );
		if ( is_wp_error( $result ) ) {
			return $result;
		} else {
			$model = new CommentModel( $comment->comment_ID );
			return new \WP_REST_Response( array_merge( [
				'message' => __( 'The comment now becomes the best answer.', 'hamethread' ),
				'url'     => get_permalink( $comment->comment_post_ID ),
			], $model->to_array() ) );
		}
	}

	/**
	 * Handle delete request.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_delete( $request ) {
		$comment = $this->get_comment( $request );
		if ( is_wp_error( $comment ) ) {
			return $comment;
		}
		$result = BestAnswer::get_instance()->unmark_the_best( $comment );
		if ( is_wp_error( $result ) ) {
			return $result;
		} else {
			$model = new CommentModel( $comment->comment_ID );
			return new \WP_REST_Response( array_merge( [
				'message' => __( 'The best answer is canceled.', 'hamethread' ),
				'url'     => get_permalink( $comment->comment_post_ID ),
			], $model->to_array() ) );
		}
	}

	/**
	 * Detect permission.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function permission_callback( $request ) {
		$comment = $this->get_comment( $request );
		switch ( $request->get_method() ) {
			case 'POST':
				return current_user_can( 'set_best_answer', $comment->comment_post_ID );
			case 'DELETE':
				return current_user_can( 'unset_best_answer', $comment->comment_post_ID );
			default:
				return false;
		}
	}

	/**
	 * Get comment object.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_Comment|\WP_Error
	 */
	private function get_comment( $request ) {
		$comment_id = $request->get_param( 'comment_id' );
		return get_comment(  $comment_id ) ?: new \WP_Error( 'no_comment', __( 'Comment not found.', 'hamethread'), [
			'status' => 404,
		] );
	}
}
