<?php
/**
 * The pseudo login system.
 *
 * This is only for local development.
 */

if ( 'local' !== wp_get_environment_type() ) {
	return;
}

// Pseudo-login user. Defaults to 'admin' but can be overridden via wp-config,
// e.g. `wp config set HAMETUHA_LOGGED_IN_AS false --raw` to browse as a logged-out visitor.
if ( ! defined( 'HAMETUHA_LOGGED_IN_AS' ) ) {
	define( 'HAMETUHA_LOGGED_IN_AS', 'admin' );
}

// A falsy value disables the pseudo-login entirely (browse as anonymous).
if ( ! HAMETUHA_LOGGED_IN_AS ) {
	return;
}

/**
 * Treat every request as specified user.
 */
add_filter( 'determine_current_user', function( $user_id ) {
	// Get user.
	$user = get_user_by( 'login', HAMETUHA_LOGGED_IN_AS );
	if ( ! $user ) {
		return $user_id;
	}

	// 既に同じユーザーIDが設定されている場合は何もしない
	if ( $user_id && (int) $user_id === (int) $user->ID ) {
		return $user_id;
	}

	// 新しいユーザーでログイン
	wp_set_current_user( $user->ID, $user->user_login );
	wp_set_auth_cookie( $user->ID, true );
	return $user->ID;
}, 30 );

// Initialize Hashboard in local environment.
if ( class_exists( 'Hametuha\Hashboard' ) ) {
	\Hametuha\Hashboard::get_instance();
}
