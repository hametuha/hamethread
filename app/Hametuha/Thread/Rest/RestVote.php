<?php
namespace Hametuha\Thread\Rest;


use Hametuha\Thread\Pattern\RestBase;

/**
 * Rest
 *
 * @package hamethread
 */
class RestVote extends RestBase {

	protected $route = 'vote/(?P<comment_id>\d+)';

	protected $vote_meta_key = '_user_upvote';

	/**
	 * Register init hook.
	 *
	 * @throws \Exception
	 */
	public function init() {
		parent::init();
		add_action( 'delete_user', [ $this, 'delete_user_handler' ] );
	}

	/**
	 * Upvote comment.
	 *
	 * @param int $comment_id
	 * @param int $user_id
	 */
	public function upvote( $comment_id, $user_id ) {
		add_comment_meta( $comment_id, $this->vote_meta_key, $user_id );
	}

	/**
	 * Get comment upvoted.
	 *
	 * @param int $comment_id
	 * @return int
	 */
	public function get_count( $comment_id ) {
		return count( get_comment_meta( $comment_id, $this->vote_meta_key, false ) );
	}

	/**
	 * Check if user is voted.
	 *
	 * @param int $comment_id
	 * @param int $user_id
	 * @return bool
	 */
	public function is_voted( $comment_id, $user_id ) {
		$meta_array = get_comment_meta( $comment_id, $this->vote_meta_key, false );
		return in_array( $user_id, $meta_array );
	}

	/**
	 * Return arguments
	 *
	 * @param string $http_method
	 * @return array
	 */
	protected function get_args( $http_method ) {
		return [
			'comment_id' => [
				'required'          => true,
				'type'              => 'int',
				'description'       => 'Comment ID to vote.',
				'validate_callback' => function ( $var ) {
					if ( ! is_numeric( $var ) || ! get_comment( $var ) ) {
						return new \WP_Error( 'comment_not_exists', __( 'Comment not found.', 'hamethread' ), [
							'status' => 404,
						] );
					} else {
						return true;
					}
				},
			],
		];
	}

	/**
	 * Get voted count.
	 *
	 * @param \WP_REST_Request $request
	 * @return array|\WP_Error
	 */
	public function handle_get( $request ) {
		return [
			'count' => $this->get_count( $request->get_param( 'comment_id' ) ),
			'voted' => $this->is_voted( $request->get_param( 'comment_id' ), get_current_user_id() ),
		];
	}

	/**
	 * Vote for comment.
	 *
	 * @param \WP_REST_Request $request
	 * @return array|\WP_Error
	 */
	public function handle_post( $request ) {
		$comment_id = $request->get_param( 'comment_id' );
		if ( $this->is_voted( $comment_id, get_current_user_id() ) ) {
			return new \WP_Error( 'already_voted', __( 'You already upvoted this comment.', 'hamethread' ), [
				'status' => 400,
			] );
		}
		$this->upvote( $comment_id, get_current_user_id() );
		return $this->handle_get( $request );
	}

	/**
	 * Remove vote
	 *
	 * @param \WP_REST_Request $request
	 * @return array|\WP_Error
	 */
	public function handle_delete( $request ) {
		$comment_id = $request->get_param( 'comment_id' );
		if ( ! $this->is_voted( $comment_id, get_current_user_id() ) ) {
			return new \WP_Error( 'not_voted', __( 'You never voted for this comment.', 'hamethread' ), [
				'status' => 400,
			] );
		}
		delete_comment_meta( $comment_id, $this->vote_meta_key, get_current_user_id() );
		return $this->handle_get( $request );
	}

	/**
	 * Check permission.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function permission_callback( $request ) {
		switch ( $request->get_method() ) {
			case 'GET':
				$can = true;
				break;
			default:
				$can = current_user_can( 'read' );
				break;
		}
		return apply_filters( 'hamethread_user_can_vote', $can, $request );
	}

	/**
	 * Remove comment vote if user is deleted.
	 *
	 * @param int $user_id
	 * @return int|false
	 */
	public function delete_user_handler( $user_id ) {
		global $wpdb;
		$query  = <<<SQL
			DELETE FROM {$wpdb->commentmeta}
			WHERE meta_key   = %s
			  AND meta_value = %d
SQL;
		$result = $wpdb->query( $wpdb->prepare( $query, $this->vote_meta_key, $user_id ) );
		return $result;
	}
}
