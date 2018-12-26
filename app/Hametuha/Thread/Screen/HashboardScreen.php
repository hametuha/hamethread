<?php

namespace Hametuha\Thread\Dashboard;


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
	
	public function head() {
		// Load styles.
	}
	
	public function footer() {
		// Load scripts.
	}
}
