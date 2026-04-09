<?php
/**
 * Plugin Name: EU Order Withdrawal Button for WooCommerce
 * Plugin URI: https://vendidero.com/
 * Description: EU-compliant order withdrawal button and form for WooCommerce.
 * Author: vendidero
 * Author URI: https://vendidero.com
 * Text Domain: eu-order-withdrawal-button-for-woocommerce
 * Version: 2.0.1
 * Requires at least: 5.4
 * Requires PHP: 7.4
 * License: GPLv3
 * Requires Plugins: woocommerce
 * WC requires at least: 3.9
 * WC tested up to: 10.6
 */
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'EU_OWB_WC_IS_STANDALONE_PLUGIN' ) ) {
	define( 'EU_OWB_WC_IS_STANDALONE_PLUGIN', true );
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

register_activation_hook( __FILE__, array( '\Vendidero\OrderWithdrawalButton\Package', 'install' ) );
register_deactivation_hook( __FILE__, array( '\Vendidero\OrderWithdrawalButton\Package', 'deactivate' ) );
add_action( 'plugins_loaded', array( '\Vendidero\OrderWithdrawalButton\Package', 'init' ) );
