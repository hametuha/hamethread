<?php

namespace Hametuha\Thread\Screen;


use Hametuha\Hashboard\Pattern\Screen;

class HashboardScreen extends Screen {

	protected $icon = 'forum';

	public function description( $page = '' ) {
		return __( 'List of threads which belong to you.', 'hamethread' );
	}

	public function slug() {
		return 'threads';
	}

	public function label() {
		return __( 'Threads', 'hamethread' );
	}

	/**
	 * Render Screen
	 *
	 * @param string $page Ignored.
	 */
	public function render( $page = '' ) {
		?>
		<div id="hamethread-list"></div>
		<?php
	}


	public function head( $child = '' ) {
		// Load styles.
		wp_enqueue_style( 'hamethread-hashboard' );
	}

	public function footer( $child = '' ) {
		wp_enqueue_script( 'hamethread-hashboard' );
		// Load scripts.
	}
}
