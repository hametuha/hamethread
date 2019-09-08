<?php

namespace Hametuha\Thread\Hooks;


use Hametuha\Pattern\Singleton;

/**
 * Admin settings.
 *
 * @package Hametuha\Thread\Hooks
 */
class AdminSetting extends Singleton {

	const OPTION_POST_TYPE = 'hamethread_best_answer_supported';

	/**
	 * Add setting section for hamail.
	 */
	public function init() {
		add_action( 'admin_init', [ $this, 'admin_setting' ] );
	}

	/**
	 * Add admin screen setting.
	 */
	public function admin_setting() {
		// Add main section.
		add_settings_section( 'hamethread-setting', __( 'Thread Setting', 'hamethread' ), function() {
			// Do nothing.
		}, 'discussion' );

		// Option for best answer.
		add_settings_field( self::OPTION_POST_TYPE, __( 'Best Answer', 'hamethread' ), function( $args ) {
			$key = $args['key'];
			$enabled = (array) get_option( $key, [] );
			?>
			<p>
			<?php foreach( get_post_types( [
				'public' => true,
			], OBJECT ) as $post_type ) :
				if ( ! post_type_supports( $post_type->name, 'comments' ) ) {
					continue;
				}
			?>
			<label style="display: inline-block; margin: 0 10px 10px 0;">
				<input name="<?php echo esc_attr( $key ) ?>[]" type="checkbox"
					   value="<?php echo esc_attr( $post_type->name ) ?>"
					<?php checked( in_array( $post_type->name, (array) get_option( $key, []) ) ) ?> />
				<?php echo esc_html( $post_type->label ) ?>
			</label>
			<?php endforeach; ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'Choose post types to enable best answer in.', 'hamethread' ) ?>
			</p>
			<?php
		}, 'discussion', 'hamethread-setting', [ 'key' => self::OPTION_POST_TYPE ] );
		register_setting( 'discussion', self::OPTION_POST_TYPE );
	}
}
