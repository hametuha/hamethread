<?php
namespace Hametuha\Thread\Hooks;


use Hametuha\Pattern\Singleton;

/**
 * Support Hamethread
 *
 * @package hamethread
 */
class SupportHashboard extends Singleton {
	
	/**
	 * Constructor
	 */
	protected function init() {
		if ( ! class_exists( 'Hametuha\\Hashboard' ) ) {
			return;
		}
		add_filter( 'hashboard_screens', [ $this, 'add_screen' ] );
	}
	
	/**
	 * Add screens
	 *
	 * @param array $screens
	 * @return array
	 */
	public function add_screen( $screens ) {
		$new_screen = [];
		foreach ( $screens as $slug => $class_name ) {
			if ( 'profile' === $slug ) {
				$new_screen[ 'threads' ] = \Hametuha\Thread\Screen\HashboardScreen::class;
			}
			$new_screen[ $slug ] = $class_name;
		}
		return $new_screen;
	}
}
