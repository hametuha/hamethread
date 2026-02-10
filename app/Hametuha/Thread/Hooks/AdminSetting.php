<?php

namespace Hametuha\Thread\Hooks;


use Hametuha\Pattern\Singleton;
use Hametuha\Thread\Hooks\PostType;

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

	const OPTION_DESCRIPTION = 'hamethread_thread_description';

	const OPTION_ALLOW_PRIVATE = 'hamethread_allow_private';

	const OPTION_COMMENT_POST_TYPES = 'hamethread_comment_post_types';

	const OPTION_STRUCTURED_DATA_TYPE = 'hamethread_structured_data_type';

	const TERM_META_STRUCTURED_DATA_TYPE = '_hamethread_structured_data_type';

	/**
	 * Add setting section for hamail.
	 */
	public function init() {
		add_action( 'admin_init', [ $this, 'setting_section' ] );
		add_action( 'admin_init', [ $this, 'general_setting' ] );
		add_action( 'admin_init', [ $this, 'best_answer_setting' ] );
		add_action( 'admin_init', [ $this, 'auto_close_setting' ] );
		add_action( 'admin_init', [ $this, 'structured_data_setting' ] );
		add_action( 'admin_init', [ $this, 'term_structured_data_setting' ] );
		// Provide saved options as defaults for the customization filters.
		// Registered unconditionally so they also apply on the front-end.
		$this->register_filter_defaults();
	}

	/**
	 * Feed saved options into the customization filters as their default value.
	 *
	 * Site-specific filters added at the default priority (10) still override these.
	 */
	public function register_filter_defaults() {
		// Thread post type description.
		add_filter( 'hamethread_post_setting', function ( $args ) {
			$description = (string) get_option( self::OPTION_DESCRIPTION, '' );
			if ( '' !== $description ) {
				$args['description'] = $description;
			}
			return $args;
		}, 5 );
		// Allow users to start private threads.
		add_filter( 'hamethread_user_can_start_private_thread', function ( $allow ) {
			return $allow || (bool) get_option( self::OPTION_ALLOW_PRIVATE, false );
		}, 5 );
		// Post types that get HameThread-style comment threads.
		add_filter( 'hamethread_dynamic_comment_post_types', function ( $post_types ) {
			$saved = (array) get_option( self::OPTION_COMMENT_POST_TYPES, [] );
			return array_values( array_unique( array_merge( (array) $post_types, $saved ) ) );
		}, 5 );
	}

	/**
	 * General settings (description / private / comment post types).
	 */
	public function general_setting() {
		// Thread description.
		add_settings_field( self::OPTION_DESCRIPTION, __( 'Thread Description', 'hamethread' ), function ( $args ) {
			$key = $args['key'];
			?>
			<textarea name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" rows="3" class="large-text"><?php echo esc_textarea( (string) get_option( $key, '' ) ); ?></textarea>
			<p class="description">
				<?php esc_html_e( 'Description for the thread post type. Used as the default unless overridden by the hamethread_post_setting filter.', 'hamethread' ); ?>
			</p>
			<?php
		}, self::PAGE, self::SECTION, [ 'key' => self::OPTION_DESCRIPTION ] );
		register_setting( self::PAGE, self::OPTION_DESCRIPTION, [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
		] );
		// Allow private threads.
		add_settings_field( self::OPTION_ALLOW_PRIVATE, __( 'Private Threads', 'hamethread' ), function ( $args ) {
			$key = $args['key'];
			?>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( get_option( $key, '' ) ); ?> />
				<?php esc_html_e( 'Allow users to start private threads.', 'hamethread' ); ?>
			</label>
			<?php
		}, self::PAGE, self::SECTION, [ 'key' => self::OPTION_ALLOW_PRIVATE ] );
		register_setting( self::PAGE, self::OPTION_ALLOW_PRIVATE, [
			'type'              => 'boolean',
			'sanitize_callback' => function ( $value ) {
				return $value ? 1 : 0;
			},
		] );
		// Comment-enabled post types.
		add_settings_field( self::OPTION_COMMENT_POST_TYPES, __( 'Thread Comments', 'hamethread' ), function ( $args ) {
			$key     = $args['key'];
			$enabled = (array) get_option( $key, [] );
			?>
			<p>
			<?php
			foreach ( get_post_types( [
				'public' => true,
			], 'objects' ) as $post_type ) :
				if ( ! post_type_supports( $post_type->name, 'comments' ) ) {
					continue;
				}
				?>
			<label style="display: inline-block; margin: 0 10px 10px 0;">
				<input name="<?php echo esc_attr( $key ); ?>[]" type="checkbox"
						value="<?php echo esc_attr( $post_type->name ); ?>"
					<?php checked( in_array( $post_type->name, $enabled, true ) ); ?> />
				<?php echo esc_html( $post_type->label ); ?>
			</label>
			<?php endforeach; ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'Enable HameThread-style comment threads on these post types.', 'hamethread' ); ?>
			</p>
			<?php
		}, self::PAGE, self::SECTION, [ 'key' => self::OPTION_COMMENT_POST_TYPES ] );
		register_setting( self::PAGE, self::OPTION_COMMENT_POST_TYPES, [
			'type'              => 'array',
			'sanitize_callback' => function ( $value ) {
				return is_array( $value ) ? array_map( 'sanitize_key', $value ) : [];
			},
		] );
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
			], 'objects' ) as $post_type ) :
				if ( ! post_type_supports( $post_type->name, 'comments' ) ) {
					continue;
				}
				?>
			<label style="display: inline-block; margin: 0 10px 10px 0;">
				<input name="<?php echo esc_attr( $key ); ?>[]" type="checkbox"
						value="<?php echo esc_attr( $post_type->name ); ?>"
					<?php checked( in_array( $post_type->name, (array) get_option( $key, [] ), true ) ); ?> />
				<?php echo esc_html( $post_type->label ); ?>
			</label>
			<?php endforeach; ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'Choose post types to enable best answer in.', 'hamethread' ); ?>
			</p>
			<?php
		}, self::PAGE, self::SECTION, [ 'key' => self::OPTION_POST_TYPE ] );
		register_setting( self::PAGE, self::OPTION_POST_TYPE, [
			'type'              => 'array',
			'sanitize_callback' => function ( $value ) {
				return is_array( $value ) ? array_map( 'sanitize_key', $value ) : [];
			},
		] );
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
		register_setting( self::PAGE, self::OPTION_AUTO_CLOSE_DURATION, [
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		] );
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
		register_setting( self::PAGE, self::OPTION_AUTO_CLOSE_PROLONG, [
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		] );
	}

	/**
	 * Add structured data type setting.
	 */
	public function structured_data_setting() {
		add_settings_field( self::OPTION_STRUCTURED_DATA_TYPE, __( 'Structured Data Type', 'hamethread' ), function ( $args ) {
			$key     = $args['key'];
			$current = get_option( $key, 'qa' );
			foreach ( [
				'qa'         => __( 'QAPage — Suitable for Q&A style forums.', 'hamethread' ),
				'discussion' => __( 'DiscussionForumPosting — Suitable for general discussion forums.', 'hamethread' ),
			] as $value => $label ) {
				printf(
					'<p><label><input type="radio" name="%s" value="%s" %s/> %s</label></p>',
					esc_attr( $key ),
					esc_attr( $value ),
					checked( $value, $current, false ),
					esc_html( $label )
				);
			}
			?>
			<p class="description">
				<?php esc_html_e( 'Choose the structured data type for JSON-LD output on thread pages.', 'hamethread' ); ?>
			</p>
			<?php
		}, self::PAGE, self::SECTION, [ 'key' => self::OPTION_STRUCTURED_DATA_TYPE ] );
		register_setting( self::PAGE, self::OPTION_STRUCTURED_DATA_TYPE );
	}

	/**
	 * Add structured data type setting for topic terms.
	 */
	public function term_structured_data_setting() {
		$taxonomy = PostType::get_instance()->taxonomy;
		$meta_key = self::TERM_META_STRUCTURED_DATA_TYPE;
		$choices  = [
			''           => __( 'Default (use global setting)', 'hamethread' ),
			'qa'         => __( 'QAPage — Suitable for Q&A style forums.', 'hamethread' ),
			'discussion' => __( 'DiscussionForumPosting — Suitable for general discussion forums.', 'hamethread' ),
		];
		// Add form fields for new term.
		add_action( "{$taxonomy}_add_form_fields", function () use ( $meta_key, $choices ) {
			?>
			<div class="form-field">
				<label><?php esc_html_e( 'Structured Data Type', 'hamethread' ); ?></label>
				<?php foreach ( $choices as $value => $label ) : ?>
					<p>
						<label>
							<input type="radio" name="<?php echo esc_attr( $meta_key ); ?>"
								value="<?php echo esc_attr( $value ); ?>"
								<?php checked( $value, '' ); ?> />
							<?php echo esc_html( $label ); ?>
						</label>
					</p>
				<?php endforeach; ?>
				<p class="description">
					<?php esc_html_e( 'Override the global structured data type for threads in this topic.', 'hamethread' ); ?>
				</p>
			</div>
			<?php
		} );
		// Edit form fields for existing term.
		add_action( "{$taxonomy}_edit_form_fields", function ( $term ) use ( $meta_key, $choices ) {
			$current = get_term_meta( $term->term_id, $meta_key, true );
			?>
			<tr class="form-field">
				<th><label><?php esc_html_e( 'Structured Data Type', 'hamethread' ); ?></label></th>
				<td>
					<?php foreach ( $choices as $value => $label ) : ?>
						<p>
							<label>
								<input type="radio" name="<?php echo esc_attr( $meta_key ); ?>"
									value="<?php echo esc_attr( $value ); ?>"
									<?php checked( $value, $current ); ?> />
								<?php echo esc_html( $label ); ?>
							</label>
						</p>
					<?php endforeach; ?>
					<p class="description">
						<?php esc_html_e( 'Override the global structured data type for threads in this topic.', 'hamethread' ); ?>
					</p>
				</td>
			</tr>
			<?php
		} );
		// Save term meta on create.
		add_action( "created_{$taxonomy}", function ( $term_id ) use ( $meta_key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$value = isset( $_POST[ $meta_key ] ) ? sanitize_text_field( $_POST[ $meta_key ] ) : '';
			if ( '' === $value ) {
				delete_term_meta( $term_id, $meta_key );
			} else {
				update_term_meta( $term_id, $meta_key, $value );
			}
		} );
		// Save term meta on edit.
		add_action( "edited_{$taxonomy}", function ( $term_id ) use ( $meta_key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$value = isset( $_POST[ $meta_key ] ) ? sanitize_text_field( $_POST[ $meta_key ] ) : '';
			if ( '' === $value ) {
				delete_term_meta( $term_id, $meta_key );
			} else {
				update_term_meta( $term_id, $meta_key, $value );
			}
		} );
		// Register term meta.
		register_term_meta( $taxonomy, $meta_key, [
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => false,
		] );
	}
}
