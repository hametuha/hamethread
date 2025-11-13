<?php

namespace Hametuha;


use Hametuha\Pattern\Singleton;
use Hametuha\Thread\Hooks\SupportNotification;

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
		// Register command.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'thread', Thread\Command::class );
		}
	}

	/**
	 * Register assets.
	 */
	public function register_assets() {
		$json = dirname( __DIR__, 2 ) . '/wp-dependencies.json';
		if ( ! file_exists( $json ) ) {
			trigger_error( __( 'Dependnecies file missing.', 'hamethread' ), E_USER_WARNING );
		}
		$dependencies = json_decode( file_get_contents( $json ), true );
		if ( $dependencies ) {
			foreach ( $dependencies as $dependency ) {
				if ( empty( $dependency['path'] ) ) {
					continue;
				}
				switch ( $dependency['ext'] ) {
					case 'js':
						$footer = [
							'in_footer' => $dependency['footer'],
						];
						if ( ! empty( $dependency['strategy'] ) ) {
							$footer['strategy'] = $dependency['strategy'];
						}
						wp_register_script( $dependency['handle'], hamethread_asset_url( $dependency['path'] ), $dependency['deps'], $dependency['hash'], $footer );
						break;
					case 'css':
						wp_register_style( $dependency['handle'], hamethread_asset_url( $dependency['path'] ), $dependency['deps'], $dependency['hash'], $dependency['media'] );
						break;
				}
			}
		}
		wp_localize_script( 'hamethread', 'HameThread', [
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'error'    => __( 'Sorry but request failed.', 'hamethread' ),
			'archive'  => __( 'Are you sure to make this thread private?', 'hamethread' ),
			'publish'  => __( 'Are you sure to make this thread public? Please confirm your comments are ready to be public.', 'hamethread' ),
			'endpoint' => rest_url( 'hamethread/v1' ),
			'lock'     => __( 'Are you sure to lock this thread? None can post new comment on this thread.', 'hamethread' ),
			'reopen'   => __( 'Are you sure to reopen this thread? Uses can post new comment on this thread.', 'hamethread' ),
		] );
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

	/**
	 * Get subscribers.
	 *
	 * @param null|int|\WP_Post $post
	 * @return \WP_User[]
	 */
	public static function subscribers( $post = null ) {
		$subscribers = SupportNotification::get_instance()->get_subscribers( $post );
		if ( ! $subscribers ) {
			return [];
		}
		$query = new \WP_User_Query( [
			'include' => $subscribers,
			'number'  => -1,
		] );
		return $query->get_results();
	}
}
