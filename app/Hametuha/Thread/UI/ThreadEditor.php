<?php

namespace Hametuha\Thread\UI;


use Hametuha\Thread\Model\ThreadModel;
use Hametuha\Thread\Pattern\AbstractUI;

/**
 * Thread Editor
 *
 * @package hamethread
 * @property string $controller_position
 */
class ThreadEditor extends AbstractUI {

	/**
	 * Constructor
	 */
	protected function init() {
		parent::init();
		add_filter( 'the_content', [ $this, 'display_controllers' ] );
	}

	/**
	 * Detect if controllers should be display.
	 *
	 * @param null|int|\WP_Post $post
	 * @return bool
	 */
	protected function should_display_controllers( $post = null ) {
		$post = get_post( $post );
		return (bool) apply_filters( 'hamethread_should_display_controllers', true, $post );
	}

	/**
	 * Filter to display controllers.
	 *
	 * @param string $content
	 * @return string
	 */
	public function display_controllers( $content = '' ) {
		if ( ! $this->should_display_controllers() || ! is_singular( $this->post_object->post_type ) || ! $this->post_object->is_supported( get_post_type() ) ) {
			return $content;
		}
		$controller = $this->get_editor_controllers();
		$position   = $this->controller_position;
		switch ( $position ) {
			case 'bottom':
				$content .= $controller;
				break;
			case 'top':
				$content = $controller . $content;
				break;
			default:
				// Do nothing.
				break;
		}
		return $content;
	}

	/**
	 * Get controllers
	 *
	 * @param null|int|\WP_Post $post
	 * @return string
	 */
	public function get_editor_controllers( $post = null ) {
		$post = get_post( $post );
		wp_enqueue_style( 'hamethread' );
		wp_enqueue_script( 'hamethread-thread' );
		return hamethread_template( 'button-thread-controller', $post->post_type, false, [
			'post'        => $post,
			'can_edit'    => ThreadModel::can_edit( get_current_user_id(), $post ),
			'can_archive' => ThreadModel::can_archive( get_current_user_id(), $post ),
		] );
	}

	/**
	 * Get controller position.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'controller_position':
				return apply_filters( 'hamethread_controller_position', 'top' );
				break;
			default:
				return parent::__get( $name );
				break;
		}
	}
}
