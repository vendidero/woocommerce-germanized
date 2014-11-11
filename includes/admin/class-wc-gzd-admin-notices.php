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
		add_action( 'admin_print_styles', array( $this, 'check_theme_notice_hide' ), 0 );
		add_action( 'admin_print_styles', array( $this, 'add_notices' ), 1 );
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
		if ( ! $this->is_theme_compatible() && ! get_option( '_wc_gzd_hide_theme_notice' ) )
			add_action( 'admin_notices', array( $this, 'theme_incompatibility_notice' ) );
		else if ( ! $this->is_theme_ready() && ! get_option( '_wc_gzd_hide_theme_notice' ) )
			add_action( 'admin_notices', array( $this, 'theme_not_ready_notice' ) );
		// delete_option( '_wc_gzd_hide_theme_notice' );
		if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'wc-gzd-about' || get_option( '_wc_gzd_needs_pages' ) ) {
			remove_action( 'admin_notices', array( $this, 'theme_incompatibility_notice' ) );
			remove_action( 'admin_notices', array( $this, 'theme_not_ready_notice' ) );
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

	public function check_theme_notice_hide() {
		if ( isset( $_GET[ 'activated' ] ) )
			delete_option( '_wc_gzd_hide_theme_notice' );
		if ( isset( $_GET[ 'wc-gzd-hide-theme-notice' ] ) )
			update_option( '_wc_gzd_hide_theme_notice', true );
	}

	public function theme_incompatibility_notice() {
		include( 'views/html-notice-theme-incompatibility.php' );
	}

	public function theme_not_ready_notice() {
		include( 'views/html-notice-theme-not-ready.php' );
	}

	public function is_theme_ready() {
		$stylesheet = get_stylesheet_directory() . '/style.css';
		$data = get_file_data( $stylesheet, array( 'wc_gzd_compatible' => 'wc_gzd_compatible' ) );
		if ( ! $data[ 'wc_gzd_compatible' ] )
			return false;
		return true;
	}

	/**
	 * Checks if current theme is woocommerce germanized compatible
	 *  
	 * @return boolean
	 */
	public function is_theme_compatible() {
		$templates_to_check = apply_filters( 'woocommerce_gzd_important_templates', array( 'checkout/form-pay.php', 'checkout/review-order.php' ) );
		if ( ! empty( $templates_to_check ) ) {
			foreach ( $templates_to_check as $template ) {
				$template_path = trailingslashit( 'woocommerce' ) . $template;
				$theme_template = locate_template( array(
					$template_path,
					$template
				) );
				if ( $theme_template && ! WC_germanized()->is_theme_template_compatible( $template, $theme_template ) )
					return false;
			}
		}
		return true;
	}
}

endif;

return new WC_GZD_Admin_Notices();
