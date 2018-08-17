<?php

namespace Hametuha\Thread\Hooks;

use Hametuha\Pattern\Singleton;


/**
 * Post type controller
 *
 * @package hamethread
 * @property string $post_type
 * @property string $taxonomy
 */
class PostType extends Singleton {

	/**
	 * Constructor
	 */
	protected function init() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_taxonomy' ] );
	}

	/**
	 * Register post type.
	 *
	 */
	public function register_post_type() {
		// Register thread.
		$args = [
			'label'           => __( 'Thread', 'hamethread' ),
			'description'     => '',
			'public'          => true,
			'menu_icon'       => 'dashicons-feedback',
			'supports'        => [ 'title', 'editor', 'author', 'comments' ],
			'has_archive'     => true,
			'capability_type' => 'post',
			'rewrite'         => [ 'slug' => 'thread' ],
			'show_ui'         => current_user_can('edit_others_posts'),
			'can_export'      => false,
			'show_in_rest'    => false,
		];
		$args = apply_filters( 'hamethread_post_setting', $args );
		register_post_type($this->post_type, $args );
	}

	/**
	 * Register taxonomy.
	 */
	public function register_taxonomy() {
		// Thread category.
		$args = [
			'hierarchical'      => false,
			'show_ui'           => current_user_can('edit_others_posts'),
			'query_var'         => true,
			'rewrite'           => ['slug' => 'topic'],
			'label'             => __( 'Topic', 'hamethread' ),
			'show_admin_column' => true,
		];
		$args = apply_filters( 'hamethread_taxonomy_setting', $args );
		register_taxonomy($this->taxonomy, [ $this->post_type ], $args );
	}

	/**
	 * Detect if post type is supported
	 *
	 * @param string $post_type
	 * @return bool
	 */
	public function is_supported( $post_type ) {
		return $this->post_type === $post_type;
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'post_type':
				return apply_filters( 'hamethread_post_type', 'thread' );
				break;
			case 'taxonomy':
				return apply_filters( 'hamethread_taxonomy', 'topic' );
				break;
			default:
				return null;
				break;
		}
	}

}
