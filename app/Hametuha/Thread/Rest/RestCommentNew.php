<?php
namespace Hametuha\Thread\Rest;

use Hametuha\Thread\Hooks\PostType;
use Hametuha\Thread\Model\CommentModel;
use Hametuha\Thread\Pattern\RestBase;
use Hametuha\Thread\Pattern\RushDetector;
use Hametuha\Thread\UI\CommentForm;

class RestCommentNew extends RestBase {

	protected $route = 'comment/(?P<thread_id>\d+)/new';

	/**
	 * Should return arguments.
	 *
	 * @param string $http_method
	 *
	 * @return array
	 */
	protected function get_args( $http_method ) {
		$args = [
			'thread_id' => [
				'required'          => true,
				'type'              => 'int',
				'description'       => 'Post ID to comment to.',
				'validate_callback' => function ( $var ) {
					$post = get_post( $var );
					if ( ! $post || ! CommentForm::get_instance()->is_supported( $post->post_type ) || ! comments_open( $post ) ) {
						return new \WP_Error( 'no_permission', __( 'You have no permission to post comment to the specified thread.', 'hamethread' ), [
							'response' => 403,
							'status'   => 403,
						] );
					} else {
						return true;
					}
				},
			],
			'reply_to'  => [
				'default'           => 0,
				'type'              => 'int',
				'description'       => 'Comment ID to reply.',
				'validate_callback' => function ( $var, $request ) {
					if ( ! $var ) {
						return true;
					}
					$comment = get_comment( $var );
					if ( ! $comment ) {
						return new \WP_Error( 'comment_not_found', __( 'Comment not found.', 'hamethread' ), [
							'status'   => 403,
							'response' => 403,
						] );
					}
					return apply_filters( 'hamethread_current_user_can_reply', true, $comment, $request );
				},
			],
		];
		switch ( $http_method ) {
			case 'POST':
				$args = array_merge( $args, [
					'comment_content' => [
						'required'          => true,
						'type'              => 'string',
						'description'       => 'Comment content.',
						'validate_callback' => function ( $var, \WP_REST_Request $request ) {
							return ! empty( $var );
						},
					],
				] );
				break;
		}
		$args = apply_filters( 'hamethread_new_comment_rest_args', $args, $http_method );
		return $args;
	}

	/**
	 * Get form to post comment.
	 *
	 * @param \WP_REST_Request $request
	 * @return array
	 */
	public function handle_get( $request ) {
		$post_id  = $request->get_param( 'thread_id' );
		$reply_to = $request->get_param( 'reply_to' );
		$args     = [
			'title'    => $reply_to
				? esc_html( sprintf( __( 'Reply to %s', 'hamethread' ), get_comment_author( $reply_to ) ) )
				: esc_html__( 'Post comment', 'hamethread' ),
			'post'     => get_post( $post_id ),
			'action'   => sprintf( 'comment/%d/new', $post_id ),
			'comment'  => null,
			'reply_to' => $reply_to,
		];
		$form     = apply_filters( 'hamethread_form_comment', hamethread_template( 'form-comment', '', false, $args ), $request );
		return [
			'html' => $form,
		];
	}

	/**
	 * Post new comment.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_post( $request ) {
		$post_id = $request->get_param( 'thread_id' );
		$error   = new \WP_Error();
		if ( RushDetector::is_user_rushing( get_current_user_id() ) ) {
			$error->add( 'user_is_rushing.', RushDetector::rushing_message( get_current_user_id() ), [
				'status' => 403,
			] );
		}
		$error = apply_filters( 'hamethread_new_comment_validation', $error, $request );
		if ( $error->get_error_messages() ) {
			return $error;
		}
		$user_data     = get_userdata( get_current_user_id() );
		$comment_param = [
			'comment_IP'           => isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '',
			'comment_approved'     => 1,
			'comment_author'       => $user_data->display_name,
			'comment_author_email' => $user_data->user_email,
			'comment_author_url'   => $user_data->user_url,
			'comment_content'      => $request->get_param( 'comment_content' ),
			'comment_type'         => 'comment',
			'comment_post_ID'      => $post_id,
			'user_id'              => get_current_user_id(),
		];
		$reply_to      = $request->get_param( 'reply_to' );
		if ( $reply_to ) {
			$comment_param['comment_parent'] = $reply_to;
		}
		$comment_param = apply_filters( 'hamethread_new_comment_params', $comment_param, $request );
		$comment_id    = wp_insert_comment( $comment_param );
		if ( ! $comment_id ) {
			return new \WP_Error( 'failed_insert_comment', __( 'Sorry, but failed to insert comment.', 'hamethread' ) );
		}
		// Count up comment count.
		wp_update_comment_count_now( $post_id );
		/**
		 * hamethread_new_comment_inserted
		 *
		 * Fires when new comment posted
		 *
		 * @param int              $comment_id
		 * @param array            $comment_param
		 * @param \WP_REST_Request $request
		 */
		do_action( 'hamethread_new_comment_inserted', $comment_id, $comment_param, $request );
		$comment  = new CommentModel( $comment_id );
		$response = new \WP_REST_Response( $comment->to_array() );
		$response->set_headers( [
			sprintf( 'X-WP-COMMENT-COUNT: %d', get_comment_count( $post_id ) ),
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
		return hamethread_user_can_comment( $request->get_param( 'thread_id' ), get_current_user_id() );
	}
}
