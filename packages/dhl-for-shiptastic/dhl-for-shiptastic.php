<?php
/**
 * Plugin Name: DHL for Shiptastic
 * Plugin URI: https://github.com/vendidero/dhl-for-shiptastic
 * Description: Create DHL and Deutsche Post labels for Shiptastic.
 * Author: vendidero
 * Author URI: https://vendidero.de
 * Version: 3.6.1
 * Requires PHP: 5.6
 * License: GPLv3
 * Requires Plugins: shiptastic-for-woocommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WC_DHL_FOR_STC_IS_STANDALONE_PLUGIN' ) ) {
	define( 'WC_DHL_FOR_STC_IS_STANDALONE_PLUGIN', true );
}

if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
	return;
}

$autoloader = __DIR__ . '/vendor/autoload_packages.php';

if ( is_readable( $autoloader ) ) {
	require $autoloader;
} else {
	return;
}

register_activation_hook( __FILE__, array( '\Vendidero\Shiptastic\DHL\Package', 'install' ) );
add_action( 'plugins_loaded', array( '\Vendidero\Shiptastic\DHL\Package', 'init' ) );
