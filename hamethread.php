<?php
/**
 * Plugin Name:       HameThread
 * Plugin URI:        https://wordpress.org/extend/plugins/hamethread
 * Description:       Forum plugin by Hametuha.
 * Version:           1.2.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:          Hametuha INC.
 * Author URI:      https://hametuha.co.jp
 * Text Domain:     hamethread
 * Domain Path:     /languages
 * License:         GPL3 or Later
 * @package         hamethread
 */

// Do not load directory.
defined( 'ABSPATH' ) || die();

// Check version and load plugin if possible.
add_action( 'plugins_loaded', 'hamethread_init' );

function hamethread_init() {
	// i18n.
	load_plugin_textdomain( 'hamethread', false, basename( dirname( __FILE__ ) ) . '/languages' );
	if ( version_compare( phpversion(), '7.4.0', '>=' ) ) {
		// Load functions.
		require __DIR__ . '/functions.php';
		$dir = __DIR__ . '/includes';
		if ( is_dir( $dir ) ) {
			foreach ( scandir( $dir ) as $file ) {
				if ( ! preg_match( '/[^._].*\.php$/u', $file ) ) {
					continue;
				}
				require $dir . '/' . $file;
			}
		}
		// Load composer.
		require __DIR__ . '/vendor/autoload.php';
		\Hametuha\Thread::get_instance();
	} else {
		add_action( 'admin_notices', 'hamethread_version_error' );
	}
}

/**
 * Display version error
 *
 * @internal
 */
function hamethread_version_error() {
	// translators: %1$s required PHP version, %2$s current PHP version.
	printf( '<div class="error"><p>%s</p></div>', sprintf( esc_html__( 'HameThread requires PHP %1$s, but your PHP version is %2$s. Please consider upgrade.', 'hamethread' ), '7.4', phpversion() ) );
}

/**
 * Get asset url
 *
 * @return string
 */
function hamethread_asset_url( $path = '' ) {
	$url = plugin_dir_url( __FILE__ ) . 'assets';
	if ( $path ) {
		$url = str_replace( 'assets/', $url . '/', $path );
	}
	return $url;
}

/**
 * Get plugin version.
 */
function hamethread_version() {
	static $version = null;
	if ( is_null( $version ) ) {
		$file_info = get_file_data( __FILE__, [
			'version' => 'Version'
		] );
		$version = trim( $file_info['version'] );
	}
	return $version;
}

/**
 * Flush rewrite rules
 *
 * @internal
 */
function hamethread_flush_rewrites() {
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'hamethread_flush_rewrites' );
register_deactivation_hook( __FILE__, 'hamethread_flush_rewrites' );
