<?php

namespace Hametuha\Thread\Rest;


use Hametuha\Thread\Model\ThreadModel;
use Hametuha\Thread\Pattern\RestBase;

class RestThreadLock extends RestBase {

	protected $route = 'thread/lock/(?P<thread_id>\d+)/?';

	/**
	 * Should return arguments.
	 *
	 * @param string $http_method
	 * @return array
	 */
	protected function get_args( $http_method ) {
		return [
			'thread_id' => [
				'required' => true,
				'validate_callback' => function( $var ) {
					return is_numeric( $var ) && get_post( $var );
				}
			],
		];
	}

	/**
	 * Lock thread.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_post( $request ) {
		$thread = get_post( $request->get_param( 'thread_id' ) );
		if ( ! comments_open( $thread ) ) {
			return new \WP_Error( 'hamethread_invalid_lock', __( 'This thread is already locked.', 'hamethread' ), [
				'status' => 400,
			] );
		}
		$result = wp_update_post( [
			'ID'             => $thread->ID,
			'comment_status' => 'closed',
		], true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		add_post_meta( $thread->ID, '_hamethread_comments_closed', current_time( 'timestamp', true ) );
		return new \WP_REST_Response( [
			'url'     => get_permalink( $thread ),
			'message' => __( 'This thread is closed.', 'hamethread' ),
		] );
	}

	/**
	 * Unlock thread.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_delete( $request ) {
		$thread = get_post( $request->get_param( 'thread_id' ) );
		if ( comments_open( $thread ) ) {
			return new \WP_Error( 'hamethread_invalid_lock', __( 'This thread is already opened.', 'hamethread' ), [
				'status' => 400,
			] );
		}
		$result = wp_update_post( [
			'ID'             => $thread->ID,
			'comment_status' => 'open',
		], true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		add_post_meta( $thread->ID, '_hamethread_comments_reopened', current_time( 'timestamp', true ) );
		return new \WP_REST_Response( [
			'url'     => get_permalink( $thread ),
			'message' => __( 'This thread is reopened.', 'hamethread' ),
		] );
	}

	/**
	 * Permission handler.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function permission_callback( $request ) {
		return ThreadModel::can_edit( get_current_user_id(), $request->get_param( 'thread_id' ) );
	}
}
