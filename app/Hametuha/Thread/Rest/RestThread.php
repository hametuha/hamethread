<?php

namespace Hametuha\Thread\Rest;


use Hametuha\Thread;
use Hametuha\Thread\Hooks\PostType;
use Hametuha\Thread\Model\ThreadModel;
use Hametuha\Thread\Pattern\RestBase;
use Hametuha\Thread\Pattern\RushDetector;

class RestThread extends RestBase {

	protected $route = 'thread/(?P<thread_id>\d+)';

	/**
	 * Should return arguments.
	 *
	 * @param string $http_method
	 * @return array
	 */
	protected function get_args( $http_method ) {
		$args = [
			'thread_id' => [
				'required' => true,
				'type' => 'int',
				'description' => 'Thread ID',
				'validate_callback' => function( $var ) {
					return PostType::get_instance()->is_supported( get_post_type( $var ) );
				},
			],
		];
		switch ( $http_method ) {
			case 'POST':
				$args = array_merge( $args, $this->thread_arg_base() );
				break;
		}
		return $args;
	}

	/**
	 * Handle request object.
	 *
	 * @param \WP_REST_Request $request
	 * @return array|\WP_Error
	 */
	protected function handle_post( $request ) {
		$thread_id = $request->get_param( 'thread_id' );
		$error = new \WP_Error();
		$error = apply_filters( 'hamethread_edit_thread_validation', $error, $request, $thread_id );
		if ( $error->get_error_messages() ) {
			return $error;
		}
		$post_args = [
			'ID'           => $thread_id,
			'post_title'   => $request->get_param( 'thread_title' ),
			'post_content' => $request->get_param( 'thread_content' ),
		];
		$post_args = apply_filters( 'hamethread_edit_thread_post_arg', $post_args, $request, $thread_id );

		$post_id = wp_update_post( $post_args, true );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		// Update user rush time.
		RushDetector::record_rush_time( get_current_user_id() );
		// Save user log.
		add_post_meta( $post_id, '_hamethread_thread_log', array_merge( $post_args, [
			'updated' => current_time( 'mysql' ),
		] ) );
		// Add topic.
		$topic_id = $request->get_param( 'topic_id' );
		if ( $topic_id ) {
			wp_set_object_terms( $post_id, (int) $topic_id, PostType::get_instance()->taxonomy );
		}
		/**
		 * hamethread_thread_updated
		 *
		 * Do something.
		 *
		 * @param int $post_id
		 * @param \WP_REST_Request $request
		 */
		do_action( 'hamethread_thread_updated', $post_id, $request );
		$thread = new ThreadModel( $post_id );
		return $thread->to_array();
	}

	/**
	 * Handle put request.
	 *
	 * @param \WP_REST_Request $request
	 * @return array
	 */
	protected function handle_put( $request ) {
		$thread_id = $request->get_param( 'thread_id' );
		$response = [
			'url' => get_permalink( $thread_id ),
		];
		if ( ThreadModel::is_resolved( $thread_id ) ) {
			ThreadModel::unset_resolved( $thread_id );
			$response['message'] = __( 'Thread is now not resolved.', 'hamethread' );
		} else {
			ThreadModel::set_resolved( $thread_id );
			$response['message'] = __( 'Thread is marked as resolved.', 'hamethread' );
		}
		return $response;
	}

	/**
	 * Handle delete method.
	 *
	 * @param \WP_REST_Request $request
	 * @return array|\WP_Error
	 */
	protected function handle_delete( $request ) {
		$current_status = get_post_status( $request->get_param( 'thread_id' ) );
		if ( 'private' == $current_status ) {
			$new_status = 'publish';
		} else {
			$new_status = 'private';
		}
		$error = new \WP_Error();
		$error = apply_filters( 'hamethread_toggle_post_status_error', $error, $request );
		if ( $error->get_error_messages() ) {
			return $error;
		}
		$args = apply_filters( 'hamethread_archive_thread_post_arg', [
			'ID'          => $request->get_param( 'thread_id' ),
			'post_status' => $new_status,
		], $request );
		$post_id = wp_update_post( $args );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		if ( 'private' === $new_status ) {
			$message = __( 'Thread %s has been successfully private.', 'hamethread' );
		} else {
			$message = __( 'Thread %s has been successfully published.', 'hamethread' );
		}
		return [
			'message' => sprintf( $message, get_the_title( $post_id ) ),
			'url'     => get_permalink( $request->get_param( 'thread_id' ) ),
		];
	}

	/**
	 * Permission callback
	 *
	 * @param \WP_REST_Request $request
	 * @return bool|\WP_Error
	 */
	public function permission_callback( $request ) {
		switch ( $request->get_method() ) {
			case 'GET':
				return true;
				break;
			case 'POST':
				return ThreadModel::can_edit( get_current_user_id(), $request->get_param( 'thread_id' ) );
				break;
			case 'PUT':
				return ThreadModel::can_edit( get_current_user_id(), $request->get_param( 'thread_id' ) );
				break;
			case 'DELETE':
				return ThreadModel::can_archive( get_current_user_id(), $request->get_param( 'thread_id' ) );
				break;
		}
	}
}
