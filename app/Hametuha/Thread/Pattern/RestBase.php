<?php

namespace Hametuha\Thread\Pattern;


use Hametuha\Pattern\RestApi;
use Hametuha\Thread\Hooks\PostType;

abstract class RestBase extends RestApi {

	protected $namespace = 'hamethread';

	protected $version   = '1';


	/**
	 * Thread API arguments
	 *
	 * @return array
	 */
	protected function thread_arg_base() {
		return [
			'thread_title' => [
				'required' => true,
				'type'     => 'string',
				'description' => 'Thread title',
				'validate_callback' => function( $var ) {
					return ! empty( $var );
				},
			],
			'thread_content' => [
				'type' => 'string',
				'description' => 'Thread content',
			],
			'topic_id' => [
				'type' => 'int',
				'required' => hamethread_topic_forced( get_current_user_id() ),
				'description' => 'term_id',
				'validate_callback' => function( $var ) {
					if ( hamethread_topic_forced( get_current_user_id() ) ) {
						$term = get_term_by( 'id', $var, PostType::get_instance()->taxonomy );
						if ( ! $term || is_wp_error( $term ) ) {
							return new \WP_Error( 'invalid_topic', __( 'Topic is not specified.', 'hamethread' ), [
								'response' => 401,
							] );
						} else {
							return true;
						}
					} else {
						return is_numeric( $var );
					}
				},
				'default' => 0,
			],
			'thread_parent' => [
				'type' => 'int',
				'description' => 'If set, thread will be a child of this post.',
				'validation_callback' => function( $var ) {
					return is_numeric( $var );
				},
				'default' => 0,
			],
			'is_private' => [
				'type' => 'int',
				'description' => 'If set, thread will be private.',
				'default' => 0,
			],
		];
	}
}
