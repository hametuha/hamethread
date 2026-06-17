<?php

namespace Hametuha\Thread\Hooks;


use Hametuha\Pattern\Singleton;

/**
 * Register Gutenberg blocks built under assets/blocks.
 *
 * Blocks are built with @wordpress/scripts (src/blocks -> assets/blocks).
 * Each built block directory contains a block.json which is registered here,
 * mirroring how Thread::register_assets() scans wp-dependencies.json.
 *
 * @package Hametuha\Thread\Hooks
 */
class Blocks extends Singleton {

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_blocks' ], 11 );
	}

	/**
	 * Register every built block under assets/blocks.
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		$blocks_dir = dirname( __DIR__, 4 ) . '/assets/blocks';
		if ( ! is_dir( $blocks_dir ) ) {
			// Assets are built at release time; nothing to register otherwise.
			return;
		}
		foreach ( glob( $blocks_dir . '/*', GLOB_ONLYDIR ) as $dir ) {
			if ( file_exists( $dir . '/block.json' ) ) {
				register_block_type( $dir );
			}
		}
	}
}
