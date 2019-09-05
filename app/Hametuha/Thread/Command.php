<?php

namespace Hametuha\Thread;

use cli\Table;
use Hametuha\Thread\Hooks\AutoClose;

/**
 * Command utility for hamethread.
 *
 * @package hamethread
 */
class Command extends \WP_CLI_Command {

	/**
	 * Get list
	 *
	 * @synopsis [<diff>]
	 * @param array $arg
	 */
	public function closable( $arg ) {
		$diff = isset( $arg[0] ) ? (int) $arg[0] : 0;
		$posts = AutoClose::get_instance()->get_post_to_close( $diff );
		if ( ! $posts ) {
			\WP_CLI::error( 'No thread to automatically close.' );
		}
		$table = new Table();
		$table->setHeaders( [ 'Title', 'URL', 'Time to close' ] );
		$meta_key = AutoClose::get_instance()->close_key;
		$table->setRows( array_map( function( $post ) use ( $meta_key ) {
			return [
				get_the_title( $post ),
				get_permalink( $post ),
				get_date_from_gmt( date_i18n( 'Y-m-d H:i:s', (int) get_post_meta( $post->ID, $meta_key, true ) ), 'Y-m-d H:i:s' ) . 'UTC',
			];
		}, $posts ) );
		$table->display();
	}

	/**
	 * Close related threads.
	 *
	 * @param $args
	 */
	public function close( $args ) {
		// TODO: Close related threads.
	}
}
