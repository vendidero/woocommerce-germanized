<?php
/**
 * Plugin Name: Shiptastic for WooCommerce
 * Plugin URI: https://vendidero.com/doc/shiptastic/home-shiptastic
 * Description: Create and manage shipments for orders and use shipping rules to build extensive shipping scenarios.
 * Author: vendidero
 * Author URI: https://vendidero.com
 * Text Domain: shiptastic-for-woocommerce
 * Version: 4.3.11
 * Requires at least: 5.4
 * Requires PHP: 5.6
 * License: GPLv3
 * Requires Plugins: woocommerce
 * WC requires at least: 3.9
 * WC tested up to: 9.7
 */
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WC_STC_IS_STANDALONE_PLUGIN' ) ) {
	define( 'WC_STC_IS_STANDALONE_PLUGIN', true );
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

register_activation_hook( __FILE__, array( '\Vendidero\Shiptastic\Package', 'install' ) );
register_deactivation_hook( __FILE__, array( '\Vendidero\Shiptastic\Package', 'deactivate' ) );
add_action( 'plugins_loaded', array( '\Vendidero\Shiptastic\Package', 'init' ) );
