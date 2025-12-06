<?php
/**
 * This file is loaded in development environment.
 */
if ( 'local' !== wp_get_environment_type() ) {
	return;
}

if ( ! function_exists( 'auth_redirect' ) ) {
	function auth_redirect() {
		// ログイン済みなら認証OK
		if ( get_current_user_id() ) {
			return;
		}

		// 未ログインならログインページへリダイレクト
		nocache_headers();

		$redirect = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$login_url = wp_login_url( $redirect, true );

		wp_redirect( $login_url );
		exit;
	}
}
