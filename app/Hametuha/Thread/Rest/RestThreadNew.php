<?php
namespace Hametuha\Thread\Rest;

use Hametuha\Thread;
use Hametuha\Thread\Hooks\PostType;
use Hametuha\Thread\Hooks\SupportNotification;
use Hametuha\Thread\Model\ThreadModel;
use Hametuha\Thread\Pattern\RestBase;
use Hametuha\Thread\Pattern\RushDetector;


/**
 * REST API for thread.
 * @package hamethread
 */
class RestThreadNew extends RestBase {
	
	protected $route = 'thread/new/?';

	/**
	 * Should return arguments.
	 *
	 * @param string $http_method
	 * @return array
	 */
	protected function get_args( $http_method ) {
		switch ( $http_method ) {
			case 'GET':
				return [
					'post_id' => [
						'type'     => 'int',
						'description' => 'If set, form will be update form.',
						'validate_callback' => function( $var ) {
							return is_numeric( $var ) && ( $post = get_post( $var ) ) && PostType::get_instance()->is_supported( $post->post_type );
						},
					],
					'parent' => [
						'type' => 'int',
						'description' => 'If set, thread will be a child of this post.',
						'validation_callback' => function( $var ) {
							return is_numeric( $var );
						},
						'default' => 0,
					],
					'private' => [
						'type'        => 'int',
						'description' => 'Display private field or not.',
						'sanitize_callback' => function( $var ) {
							return (int) $var;
						},
						'default'     => 0,
					 ],
				];
				break;
			case 'POST':
				$args = $this->thread_arg_base();
				return apply_filters( 'hamethread_new_thread_post_params', $args );
				break;
			default:
				return [];
				break;
		}
	}

	/**
	 * Get request object.
	 *
	 * @param \WP_REST_Request $request
	 * @return array
	 */
	public function handle_get( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$args = [
			'post'    => $post_id ? get_post( $post_id ) : null,
			'action'  => rest_url( 'hamethread/v1/thread/' ) . ( $post_id ?: 'new' ),
			'parent'  => $request->get_param( 'parent' ),
			'private' => $request->get_param( 'private' ),
		];
		$form = apply_filters( 'hamethread_form_thread', hamethread_template( 'form-thread', '', false, $args ), $request );
		return [
			'html' => $form,
		];
	}

	/**
	 * Handle POST request.
	 *
	 * @param \WP_REST_Request $request
	 * @return array|\WP_Error
	 */
	public function handle_post( $request ) {
		$error = new \WP_Error();
		if ( RushDetector::is_user_rushing( get_current_user_id() ) ) {
			$error->add( 'user_is_rushing', RushDetector::rushing_message( get_current_user_id() ), [
				'response' => 403,
				'status'   => 403,
			] );
		}
		if ( $request->get_param( 'is_private' ) && ! ThreadModel::can_start_private( get_current_user_id() ) ) {
			$error->add( 'private_not_allowed', __( 'You cannot post private thread.', 'hamethread' ), [
				'status'   => 403,
				'response' => 403,
			] );
		}
		/**
		 * hamethread_new_thread_validation
		 *
		 * If error exists, add error here.
		 *
		 * @param \WP_Error        $error
		 * @param \WP_REST_Request $request
		 * @return \WP_Error
		 */
		$error = apply_filters( 'hamethread_new_thread_validation', $error, $request );
		if ( $error->get_error_messages() ) {
			return $error;
		}
		$post_args = [
			'post_type'    => PostType::get_instance()->post_type,
			'post_title'   => $request->get_param( 'thread_title' ),
			'post_content' => $request->get_param( 'thread_content' ),
			'post_status'  => $request->get_param( 'is_private' ) ? 'private' : 'publish',
			'post_author'  => get_current_user_id(),
			'post_parent'  => $request->get_param( 'thread_parent' ),
		];
		$post_args = apply_filters( 'hamethread_new_thread_post_arg', $post_args, $request );
		$post_id = wp_insert_post( $post_args, true );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		/**
		 * hamethread_default_subscribers
		 *
		 * Make thread author subscribe it.
		 *
		 * @param bool $subscribe
		 * @param int  $post_id
		 * @param int  $user_id
		 */
		$default_subscribers = apply_filters( 'hamethread_default_subscribers', [ get_current_user_id() ], $post_id, get_current_user_id(), $request );
		if ( $default_subscribers ) {
			foreach ( $default_subscribers as $subscriber ) {
				SupportNotification::get_instance()->subscribe( $post_id, $subscriber );
			}
		}
		// Update user rush time.
		RushDetector::record_rush_time( get_current_user_id() );
		// Add topic.
		$topic_id = $request->get_param( 'topic_id' );
		if ( $topic_id ) {
			wp_set_object_terms( $post_id, (int) $topic_id, PostType::get_instance()->taxonomy );
		}
		/**
		 * hamethread_new_thread_inserted
		 *
		 * Do something.
		 *
		 * @param int $post_id
		 * @param \WP_REST_Request $request
		 */
		do_action( 'hamethread_new_thread_inserted', $post_id, $request );
		$thread = new ThreadModel( $post_id );
		return $thread->to_array();
	}

	/**
	 * Permission handler
	 *
	 * @param \WP_REST_Request $request
	 * @return bool|\WP_Error
	 */
	public function permission_callback( $request ) {
		$can = ThreadModel::can_post( get_current_user_id() ) ?: new \WP_Error( 'invalid_cap', __( 'You have no permission.', 'hamethread' ), [
			'response' => 403,
		] );
		return apply_filters( 'hamethread_new_thread_capability', $can, $request );
	}
}
