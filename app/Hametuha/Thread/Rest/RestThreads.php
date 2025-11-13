<?php
namespace Hametuha\Thread\Rest;


use Hametuha\Thread\Model\ThreadModel;
use Hametuha\Thread\Pattern\RestBase;

/**
 * Get REST related
 */
class RestThreads extends RestBase {

	protected $route = 'threads/(?P<user_id>me|\d+)';

	protected function get_args( $http_method ) {
		return [
			'user_id'  => [
				'type'              => 'string',
				'validate_callback' => function ( $var ) {
					return 'me' === $var || is_numeric( $var );
				},
				'required'          => true,
			],
			'page'     => [
				'type'              => 'integer',
				'validate_callback' => [ $this, 'is_numeric' ],
				'default'           => 1,
			],
			'per_page' => [
				'type'              => 'integer',
				'validate_callback' => [ $this, 'is_numeric' ],
				'default'           => 20,
			],
			's'        => [
				'type'    => 'string',
				'default' => '',
			],
			'resolved' => [
				'type'    => 'integer',
				'default' => 0,
			],
			'status'   => [
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => function ( $var ) {
					return implode( ',', array_filter( array_map( 'trim', explode( ',', $var ) ), function ( $status ) {
						return get_post_status_object( $status );
					} ) );
				},
			],
		];
	}

	/**
	 * Get user's specified threads.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function handle_get( $request ) {
		$user_id = $request->get_param( 'user_id' );
		if ( 'me' === $user_id ) {
			$user_id = get_current_user_id();
		}
		// Check permission.
		$page        = max( 1, $request->get_param( 'page' ) );
		$per_page    = $request->get_param( 'per_page' );
		$search_term = $request->get_param( 's' );
		$args        = [
			'post_type'      => 'thread',
			'author'         => $user_id,
			'paged'          => $page,
			'posts_per_page' => $per_page,
			'orderby'        => [ 'date' => 'DESC' ],
		];
		// If search term exists, set it.
		if ( $search_term ) {
			$args['s'] = $search_term;
		}
		// Set status.
		// If query is 'me', allow private threads.
		$post_status = array_filter( explode( ',', $request->get_param( 'status' ) ), function ( $status ) use ( $request ) {
			if ( 'me' !== $request->get_param( 'user_id' ) ) {
				return current_user_can( 'edit_others_posts' ) || 'publish' === $status;
			} else {
				// This is "me", so everything is O.K.
				return true;
			}
		} );
		if ( ! $post_status ) {
			if ( 'me' === $request->get_param( 'user_id' ) ) {
				$post_status = [ 'publish', 'private' ];
			} else {
				$post_status = [ 'publish' ];
			}
		}
		$args['post_status'] = $post_status;
		// Set resolved.
		switch ( $request->get_param( 'resolved' ) ) {
			case 1:
				// Only resolved.
				$args['meta_query'] = [
					[
						'key'     => '_thread_resolved',
						'value'   => '',
						'compare' => '!=',
					],
				];
				break;
			case -1:
				// Not resolved.
				$args['meta_query'] = [
					[
						'key'     => '_thread_resolved',
						'compare' => 'NOT EXISTS',
					],
				];
				break;
			case 0:
				// Do nothing.
				break;
		}
		$args = apply_filters( 'hamethread_threads_search_query_args', $args, $request );
		// Build query.
		$query   = new \WP_Query( $args );
		$results = [];
		while ( $query->have_posts() ) {
			$query->the_post();
			$thread    = new ThreadModel( get_the_ID() );
			$results[] = $thread->to_array();
		}
		$response = new \WP_REST_Response( $results );
		$response->set_headers( [
			'X-WP-Total'    => $query->found_posts,
			'X-WP-Max-Page' => $query->max_num_pages,
		] );
		return $response;
	}

	/**
	 * Check request permission.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function permission_callback( $request ) {
		if ( 'me' === $request->get_param( 'user_id' ) ) {
			return is_user_logged_in();
		} else {
			return true;
		}
	}
}
