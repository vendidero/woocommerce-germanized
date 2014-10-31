<?php
/**
 * Display notices in admin.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_GZD_Admin_Notices' ) ) :

/**
 * Adds Notices after Install / Update to Admin
 *
 * @class 		WC_GZD_Admin_Notices
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Admin_Notices {

	public function __construct() {
		add_action( 'admin_print_styles', array( $this, 'add_notices' ) );
	}

	/**
	 * Add notices + styles if needed.
	 */
	public function add_notices() {
		if ( get_option( '_wc_gzd_needs_update' ) == 1 || get_option( '_wc_gzd_needs_pages' ) == 1 ) {
			wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', WC_PLUGIN_FILE ) );
			wp_enqueue_style( 'woocommerce-gzd-activation', plugins_url(  '/assets/css/woocommerce-gzd-activation.css', WC_GERMANIZED_PLUGIN_FILE ) );
			add_action( 'admin_notices', array( $this, 'install_notice' ) );
		}
	}

	/**
	 * Show the install notices
	 */
	public function install_notice() {
		// If we need to update, include a message with the update button
		if ( get_option( '_wc_gzd_needs_update' ) == 1 ) {
			include( 'views/html-notice-update.php' );
		}
		// If we have just installed, show a message with the install pages button
		elseif ( get_option( '_wc_gzd_needs_pages' ) == 1 ) {
			include( 'views/html-notice-install.php' );
		}
	}
}

endif;

return new WC_GZD_Admin_Notices();
