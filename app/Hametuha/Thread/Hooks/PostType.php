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
		add_action( 'post_submitbox_misc_actions', [ $this, 'post_submit_box' ] );
		add_action( 'save_post', [ $this, 'save_post' ], 20, 2 );
		add_filter( 'columns', [ $this, 'manage_columns' ] );
		add_filter( 'manage_thread_posts_columns', [ $this, 'manage_columns' ] );
		add_action( 'manage_thread_posts_custom_column', [ $this, 'do_custom_column' ], 10, 2 );
		// Avoid block editor.
		add_filter( 'use_block_editor_for_post_type', function ( $use_block_editor, $post_type ) {
			if ( 'thread' === $post_type ) {
				$use_block_editor = false;
			}
			return $use_block_editor;
		}, 10, 2 );
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
			'show_ui'         => current_user_can( 'edit_others_posts' ),
			'can_export'      => false,
			'show_in_rest'    => false,
		];
		$args = apply_filters( 'hamethread_post_setting', $args );
		register_post_type( $this->post_type, $args );
	}

	/**
	 * Register taxonomy.
	 */
	public function register_taxonomy() {
		// Thread category.
		$args = [
			'hierarchical'      => false,
			'show_ui'           => current_user_can( 'edit_others_posts' ),
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'topic' ],
			'label'             => __( 'Topic', 'hamethread' ),
			'show_admin_column' => true,
		];
		$args = apply_filters( 'hamethread_taxonomy_setting', $args );
		register_taxonomy( $this->taxonomy, [ $this->post_type ], $args );
	}

	/**
	 * Save resolver
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public function save_post( $post_id, $post ) {
		if ( $this->post_type !== $post->post_type || ! wp_verify_nonce( filter_input( INPUT_POST, '_hamethreadresolved' ), 'hamethread_resolved' ) ) {
			return;
		}
		$current   = hamethread_is_resolved( $post );
		$new_value = (bool) filter_input( INPUT_POST, 'hamethread-resolved' );
		if ( $current == $new_value ) {
			// No change.
			return;
		}
	}


	/**
	 * Render post submit box
	 *
	 * @param \WP_Post $post
	 */
	public function post_submit_box( $post ) {
		if ( ! $this->is_supported( $post->post_type ) ) {
			return;
		}
		wp_nonce_field( 'hamethread_resolved', '_hamethreadresolved', false );
		?>
		<div class="misc-pub-section misc-pub-resolved">
			<label>
				<input type="checkbox" name="hamethread-resolved" value="1" <?php checked( hamethread_is_resolved( $post ) ); ?> />
				<span><?php esc_html_e( 'This thread is resolved', 'hamethread' ); ?></span>
			</label>
		</div>
		<?php
	}

	/**
	 * Add columns
	 *
	 * @param array $columns
	 * @return array
	 */
	public function manage_columns( $columns ) {
		$new_columns = [];
		foreach ( $columns as $col => $label ) {
			$new_columns[ $col ] = $label;
			if ( 'title' === $col ) {
				$new_columns['parent'] = __( 'Parent', 'hamethread' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render column
	 *
	 * @param string $column
	 * @param int    $post_id
	 */
	public function do_custom_column( $column, $post_id ) {
		if ( 'parent' !== $column ) {
			return;
		}
		$parent = wp_get_post_parent_id( $post_id );
		if ( ! $parent ) {
			echo '<span style="color: lightgrey">---</span>';
		} else {
			echo edit_post_link( get_the_title( $parent ), '', '', $parent );
		}
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
