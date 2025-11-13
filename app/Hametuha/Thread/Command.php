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
	 * @synopsis <diff>
	 * @param array $arg
	 */
	public function closable( $arg ) {
		list( $diff ) = $arg;
		$diff         = absint( $diff );
		$time         = current_time( 'timestamp', true ) + ( 60 * 60 * 24 * $diff );
		$posts        = AutoClose::get_instance()->get_post_to_close( $time );
		if ( ! $posts ) {
			\WP_CLI::error( 'No thread to automatically close.' );
		}
		$table = new Table();
		$table->setHeaders( [ '#', 'Title', 'Comments', 'Time to close', 'URL' ] );
		$meta_key = AutoClose::get_instance()->close_key;
		$table->setRows( array_map( function ( $post ) use ( $meta_key ) {
			return [
				$post->ID,
				get_the_title( $post ),
				number_format_i18n( get_comments_number( $post->ID ) ),
				get_date_from_gmt( date_i18n( 'Y-m-d H:i:s', (int) get_post_meta( $post->ID, $meta_key, true ) ), 'Y-m-d H:i:s' ),
				rawurldecode( get_permalink( $post ) ),
			];
		}, $posts ) );
		$table->display();
		\WP_CLI::line( '' );
		\WP_CLI::success( sprintf(
			__( '%1$s will be automatically closed %2$s', 'hamethread' ),
			sprintf( _n( '%d thread', '%d threads', count( $posts ), 'hamethread' ), count( $posts ) ),
			$diff ? sprintf( _n( 'in %d day', 'in %d days', $diff, 'hamethread' ), $diff ) : __( 'just now', 'hamethread' )
		) );
	}

	/**
	 * Close related threads.
	 *
	 * @synopsis [<diff>]
	 * @param $args
	 */
	public function close( $args ) {
		$diff   = isset( $args[0] ) ? $args[0] : 0;
		$closed = AutoClose::get_instance()->do_cron( $diff );
		\WP_CLI::success( sprintf( __( 'Automacally closed: %d', 'hamethread' ), $closed ) );
	}
}
