<?php

namespace Hametuha\Thread\Pattern;


use Hametuha\Pattern\Singleton;
use Hametuha\Thread\Hooks\PostType;

/**
 * Abstract UI
 *
 * @package hamethread
 * @property PostType $post_object
 */
abstract class AbstractUI extends Singleton {

	/**
	 * Executed inside constructor.
	 */
	protected function init() {
		add_action( 'init', [ $this, 'register_script' ] );
	}

	/**
	 * Register scripts.
	 */
	public function register_script() {
		// Do something.
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'post_object':
				return PostType::get_instance();
				break;
			default:
				return null;
				break;
		}
	}
}
