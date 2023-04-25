<?php
/**
 * Plugin Name: Germanized DHL for WooCommerce
 * Plugin URI: https://github.com/vendidero/woocommerce-germanized-dhl
 * Description: The Germanized DHL integration, installed as a feature plugin for development and testing purposes.
 * Author: vendidero
 * Author URI: https://vendidero.de
 * Version: 1.8.6
 * Requires PHP: 5.6
 * License: GPLv3
 *
 * @package Vendidero/Germanized/DHL
 * @internal This file is only used when running the DHL integration as a feature plugin.
 */

defined( 'ABSPATH' ) || exit;

if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
	return;
}

/**
 * Autoload packages.
 *
 * The package autoloader includes version information which prevents classes in this feature plugin
 * conflicting with Germanized core.
 *
 * We want to fail gracefully if `composer install` has not been executed yet, so we are checking for the autoloader.
 * If the autoloader is not present, let's log the failure and display a nice admin notice.
 */
$autoloader = __DIR__ . '/vendor/autoload_packages.php';

if ( is_readable( $autoloader ) ) {
	require $autoloader;
} else {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log(  // phpcs:ignore
			sprintf(
			/* translators: 1: composer command. 2: plugin directory */
				esc_html_x( 'Your installation of the Germanized DHL feature plugin is incomplete. Please run %1$s within the %2$s directory.', 'dhl', 'woocommerce-germanized' ),
				'`composer install`',
				'`' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '`'
			)
		);
	}
	/**
	 * Outputs an admin notice if composer install has not been ran.
	 */
	add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
					/* translators: 1: composer command. 2: plugin directory */
						esc_html_x( 'Your installation of the Germanized DHL feature plugin is incomplete. Please run %1$s within the %2$s directory.', 'dhl', 'woocommerce-germanized' ),
						'<code>composer install</code>',
						'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

register_activation_hook( __FILE__, array( '\Vendidero\Germanized\DHL\Package', 'install' ) );
add_action( 'plugins_loaded', array( '\Vendidero\Germanized\DHL\Package', 'init' ) );
