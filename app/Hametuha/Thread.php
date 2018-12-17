<?php

namespace Hametuha;


use Hametuha\Pattern\Singleton;

/**
 * Thread Bootstrap plugin.
 *
 * @package hamethread
 */
class Thread extends Singleton {

	/**
	 * Do something in constructor.
	 */
	protected function init() {
		// Load all files.
		foreach ( [ 'Hooks', 'Rest', 'UI' ] as $dir ) {
			$dir_path = __DIR__ . '/Thread/' . $dir;
			if ( ! is_dir( $dir_path ) ) {
				continue;
			}
			foreach ( scandir( $dir_path ) as $file ) {
				if ( ! preg_match( '#^([^._].*)\.php$#u', $file, $match ) ) {
					continue;
				}
				$class_name = "Hametuha\\Thread\\{$dir}\\{$match[1]}";
				if ( class_exists( $class_name ) ) {
					call_user_func( "{$class_name}::get_instance" );
				}
			}
		}
		// Register script.
		add_action( 'init', [ $this, 'register_assets' ], 20 );
	}

	/**
	 * Register assets.
	 */
	public function register_assets() {
		wp_register_script( 'hamethread', hamethread_asset_url() . '/js/hamethread.js', ['jquery-effects-highlight'], hamethread_version(), true );
		wp_localize_script( 'hamethread', 'HameThread', [
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'error'   => __( 'Sorry but request failed.', 'hamethread' ),
			'archive' => __( 'Are you sure to make this thread private?', 'thread' ),
			'publish' => __( 'Are you sure to make this thread public? Please confirm your comments are ready to be public.', 'thread' ),
			'endpoint'  => rest_url( 'hamethread/v1' ),
		] );
		wp_register_style( 'hamethread', hamethread_asset_url() . '/css/hamethread.css', [], hamethread_version() );
	}
	
	/**
	 * Detect if post is resolved.
	 *
	 * @param null|int|\WP_Post $post
	 *
	 * @return bool
	 */
	public static function is_resolved( $post = null ) {
		$post = get_post( $post );
		return (bool) get_post_meta( $post->ID, '_thread_resolved', true );
	}
	
	/**
	 * Get resolved time.
	 *
	 * @param null|int|\WP_Post $post   Default current post.
	 * @param string            $format Default, WordPress date format.
	 *
	 * @return string
	 */
	public static function resolved_time( $post = null, $format = '' ) {
		if ( ! self::is_resolved( $post ) ) {
			return '';
		}
		if ( ! $format ) {
			$format = get_option( 'date_format' );
		}
		return get_date_from_gmt( get_post_meta( get_post( $post )->ID, '_thread_resolved', true ), $format );
	}
}
