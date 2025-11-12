<?php

namespace Hametuha\Thread\Hooks;


use Hametuha\Pattern\Singleton;

/**
 * Admin settings.
 *
 * @package Hametuha\Thread\Hooks
 */
class AdminSetting extends Singleton {

	const SECTION = 'hamethread';

	const PAGE = 'discussion';

	const OPTION_POST_TYPE = 'hamethread_best_answer_supported';

	const OPTION_AUTO_CLOSE_DURATION = 'hamethread_auto_close_duration';

	const OPTION_AUTO_CLOSE_PROLONG = 'hamethread_auto_close_prolong';

	/**
	 * Add setting section for hamail.
	 */
	public function init() {
		add_action( 'admin_init', [ $this, 'setting_section' ] );
		add_action( 'admin_init', [ $this, 'best_answer_setting' ] );
		add_action( 'admin_init', [ $this, 'auto_close_setting' ] );
	}

	/**
	 * Add admin screen setting.
	 */
	public function setting_section() {
		// Add main section.
		add_settings_section( self::SECTION, __( 'Thread Setting', 'hamethread' ), function () {
			// Do nothing.
		}, self::PAGE );
	}


	/**
	 * Add best answer.
	 */
	public function best_answer_setting() {
		// Option for best answer.
		add_settings_field( self::OPTION_POST_TYPE, __( 'Best Answer', 'hamethread' ), function ( $args ) {
			$key     = $args['key'];
			$enabled = (array) get_option( $key, [] );
			?>
			<p>
			<?php
			foreach ( get_post_types( [
				'public' => true,
			], OBJECT ) as $post_type ) :
				if ( ! post_type_supports( $post_type->name, 'comments' ) ) {
					continue;
				}
				?>
			<label style="display: inline-block; margin: 0 10px 10px 0;">
				<input name="<?php echo esc_attr( $key ); ?>[]" type="checkbox"
						value="<?php echo esc_attr( $post_type->name ); ?>"
					<?php checked( in_array( $post_type->name, (array) get_option( $key, [] ) ) ); ?> />
				<?php echo esc_html( $post_type->label ); ?>
			</label>
			<?php endforeach; ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'Choose post types to enable best answer in.', 'hamethread' ); ?>
			</p>
			<?php
		}, self::PAGE, self::SECTION, [ 'key' => self::OPTION_POST_TYPE ] );
		register_setting( self::PAGE, self::OPTION_POST_TYPE );
	}

	/**
	 * Add auto close setting.
	 */
	public function auto_close_setting() {
		// Option for auto close duration.
		add_settings_field( self::OPTION_AUTO_CLOSE_DURATION, __( 'Auto Close Duration', 'hamethread' ), function ( $args ) {
			$key      = $args['key'];
			$duration = (int) get_option( $key, 0 );
			?>
			<input type="number" step="1" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>"
					value="<?php echo esc_attr( $duration ); ?>" />
			<p class="description">
				<?php esc_html_e( 'Auto close duration in daiy. The thread will be automatically closed if this thread is more than 0.', 'hamethread' ); ?>
			</p>
			<?php
		}, self::PAGE, self::SECTION, [ 'key' => self::OPTION_AUTO_CLOSE_DURATION ] );
		register_setting( self::PAGE, self::OPTION_AUTO_CLOSE_DURATION );
		// Option for auto close prolong.
		add_settings_field( self::OPTION_AUTO_CLOSE_PROLONG, __( 'Prolongation', 'hamethread' ), function ( $args ) {
			$key          = $args['key'];
			$prolongation = (int) get_option( $key, 0 );
			foreach ( [
				__( 'Close anyway.', 'hamethread' ),
				__( 'Count down just after thread creation and prolong prolong with new comment.', 'hamethread' ),
				__( 'Count down just after first comment submission and prolong with new comment.', 'hamethread' ),
			] as $value => $label ) {
				printf(
					'<p><label><input type="radio" name="%s" value="%s" %s/> %s</label></p>',
					esc_attr( $key ),
					esc_attr( $value ),
					checked( $value, $prolongation, false ),
					esc_html( $label )
				);
			}
		}, self::PAGE, self::SECTION, [ 'key' => self::OPTION_AUTO_CLOSE_PROLONG ] );
		register_setting( self::PAGE, self::OPTION_AUTO_CLOSE_PROLONG );
	}
}
