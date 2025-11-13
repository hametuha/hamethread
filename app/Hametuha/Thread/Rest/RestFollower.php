<?php

namespace Hametuha\Thread\Rest;


use Hametuha\Thread\Hooks\SupportNotification;
use Hametuha\Thread\Pattern\RestBase;

/**
 * Follower Status Endpoint.
 *
 * @package hamethread
 * @property SupportNotification $notification
 */
class RestFollower extends RestBase {

	protected $route = 'follower/in/(?P<thread_id>\d+)/?';

	/**
	 * Should return arguments.
	 *
	 * @param string $http_method
	 * @return array
	 */
	protected function get_args( $http_method ) {
		$args = [
			'user_id'   => [
				'required'          => true,
				'validate_callback' => function ( $var ) {
					return 'me' === $var || is_numeric( $var );
				},
			],
			'thread_id' => [
				'required'          => true,
				'validate_callback' => function ( $var ) {
					return is_numeric( $var ) && get_post( $var );
				},
			],
		];
		return $args;
	}

	/**
	 * Get user id.
	 *
	 * @param \WP_REST_Request $request
	 * @return int
	 */
	protected function get_user_id( \WP_REST_Request $request ) {
		$user_id = $request->get_param( 'user_id' );
		if ( 'me' === $user_id ) {
			$user_id = get_current_user_id();
		}
		return (int) $user_id;
	}

	/**
	 * Handle GET request.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function handle_get( \WP_REST_Request $request ) {
		$user_id     = $this->get_user_id( $request );
		$thread_id   = $request->get_param( 'thread_id' );
		$subscribers = $this->notification->get_subscribers( $thread_id );
		return new \WP_REST_Response( [
			'subscribing' => in_array( $user_id, $subscribers, true ),
		] );
	}

	/**
	 * Handle POST request.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_post( \WP_REST_Request $request ) {
		$user_id   = $this->get_user_id( $request );
		$thread_id = $request->get_param( 'thread_id' );
		$result    = $this->notification->subscribe( $thread_id, $user_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		} else {
			return new \WP_REST_Response( [
				'subscribing' => true,
			] );
		}
	}

	/**
	 * Handle DELETE request.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_delete( \WP_REST_Request $request ) {
		$user_id   = $this->get_user_id( $request );
		$thread_id = $request->get_param( 'thread_id' );
		$result    = $this->notification->unsubscribe( $thread_id, $user_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		} else {
			return new \WP_REST_Response( [
				'subscribing' => false,
			] );
		}
	}

	/**
	 * Permission callback
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function permission_callback( \WP_REST_Request $request ) {
		switch ( strtoupper( $request->get_method() ) ) {
			case 'GET':
				return current_user_can( 'read' );
			case 'POST':
			case 'DELETE':
				return 'me' === $request->get_param( 'user_id' ) ? current_user_can( 'read' ) : current_user_can( 'edit_others_post' );
			default:
				return false;
				break;
		}
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'notification':
				return SupportNotification::get_instance();
				break;
			default:
				return null;
				break;
		}
	}
}
