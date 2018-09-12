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
			'archive' => __( 'Are you sure to archive this thread?', 'thread' ),
			'endpoint'  => rest_url( 'hamethread/v1' ),
		] );
		wp_register_style( 'hamethread', hamethread_asset_url() . '/css/hamethread.css', [], hamethread_version() );
	}
}
