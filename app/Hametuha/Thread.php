<?php

namespace Hametuha;


use Hametuha\Thread\Pattern\Singleton;

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
		foreach ( [ 'Hooks' ] as $dir ) {
			$dir_path = __DIR__ . '/' . $dir;
			if ( ! is_dir( $dir_path ) ) {
				continue;
			}
			foreach ( scandir( $dir_path ) as $file ) {
				if ( ! preg_match( '#^([^._].*)\.php$#u', $file, $match ) ) {
					continue;
				}
				$class_name = "Hametuha\\Thread\\{$dir}\\{$match[1]}";
				if ( class_exists( $class_name ) ) {
					call_user_func( [ "{$class_name}::get"] );
				}
			}
		}
	}
}
