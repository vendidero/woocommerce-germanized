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

	/**
	 * Single instance current class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	/**
	 * Ensures that only one instance of this class is loaded or can be loaded.
	 *
	 * @static
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	protected function __construct() {
		add_action( 'admin_init', array( $this, 'check_notice_hide' ) );
		add_action( 'after_switch_theme', array( $this, 'remove_theme_notice_hide' ) );
		add_action( 'admin_print_styles', array( $this, 'add_notices' ), 1 );
	}

	/**
	 * Add notices + styles if needed.
	 */
	public function add_notices() {

		if ( get_option( '_wc_gzd_needs_update' ) == 1 || get_option( '_wc_gzd_needs_pages' ) == 1 || get_option( '_wc_gzd_import_available' ) == 1 ) {
			
			wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', WC_PLUGIN_FILE ) );
			wp_enqueue_style( 'woocommerce-gzd-activation', plugins_url(  '/assets/css/woocommerce-gzd-activation.css', WC_GERMANIZED_PLUGIN_FILE ) );
			add_action( 'admin_notices', array( $this, 'install_notice' ) );
		
		}
		
		if ( ! get_option( '_wc_gzd_hide_theme_notice' ) ) {

			if ( ! WC_germanized()->is_pro() ) {

				if ( ! $this->is_theme_compatible() )
					add_action( 'admin_notices', array( $this, 'theme_incompatibility_notice' ) );
				elseif ( $this->is_theme_supported_by_pro() )
					add_action( 'admin_notices', array( $this, 'theme_supported_notice' ) );
				elseif ( ! $this->is_theme_ready() )
					add_action( 'admin_notices', array( $this, 'theme_not_ready_notice' ) );

			}
			
		}
		
		if ( ! get_option( '_wc_gzd_hide_review_notice' ) )
			add_action( 'admin_notices', array( $this, 'add_review_notice' ) );

		if ( ! get_option( '_wc_gzd_hide_pro_notice' ) && ! WC_germanized()->is_pro() )
			add_action( 'admin_notices', array( $this, 'add_pro_notice' ) );
		
		if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'wc-gzd-about' || get_option( '_wc_gzd_needs_pages' ) ) {
			remove_action( 'admin_notices', array( $this, 'theme_incompatibility_notice' ) );
			remove_action( 'admin_notices', array( $this, 'theme_not_ready_notice' ) );
			remove_action( 'admin_notices', array( $this, 'theme_supported_notice' ) );
		}
	}

	public function remove_theme_notice_hide() {
		delete_option( '_wc_gzd_hide_theme_notice' );
	}

	/**
	 * Show the install notices
	 */
	public function install_notice() {
		// If we need to update, include a message with the update button
		if ( get_option( '_wc_gzd_needs_update' ) == 1 ) {
			include( 'views/html-notice-update.php' );
		}
		// Check if other german market plugin was installed
		elseif ( get_option( '_wc_gzd_import_available' ) == 1 ) {
			include( 'views/html-notice-import.php' );
		}
		// If we have just installed, show a message with the install pages button
		elseif ( get_option( '_wc_gzd_needs_pages' ) == 1 ) {
			include( 'views/html-notice-install.php' );
		}
	}

	public function check_notice_hide() {
		
		$notices = array( 
			'wc-gzd-hide-theme-notice', 
			'wc-gzd-hide-review-notice',
			'wc-gzd-hide-pro-notice'
		);
		
		if ( ! empty( $notices ) ) {

			foreach ( $notices as $notice ) {
				
				if ( isset( $_GET[ 'notice' ] ) && $_GET[ 'notice' ] == $notice && isset( $_GET['nonce'] ) && check_admin_referer( $notice, 'nonce' ) ) {
					
					update_option( '_' . str_replace( '-', '_', $notice ) , true );
					$redirect_url = remove_query_arg( 'notice', remove_query_arg( 'nonce', $_SERVER['REQUEST_URI'] ) );
					wp_safe_redirect( $redirect_url );
					exit();
				
				}

			}

		}

	}

	public function theme_incompatibility_notice() {
		include( 'views/html-notice-theme-incompatibility.php' );
	}

	public function theme_not_ready_notice() {
		$current_theme = wp_get_theme();
		include( 'views/html-notice-theme-not-ready.php' );
	}

	public function theme_supported_notice() {
		$current_theme = wp_get_theme();
		include( 'views/html-notice-theme-supported.php' );
	}

	public function is_theme_ready() {
		$stylesheet = get_stylesheet_directory() . '/style.css';
		$data = get_file_data( $stylesheet, array( 'wc_gzd_compatible' => 'wc_gzd_compatible' ) );
		if ( ! $data[ 'wc_gzd_compatible' ] && ! current_theme_supports( 'woocommerce-germanized' ) )
			return false;
		return true;
	}

	public function is_theme_supported_by_pro() {
		
		$supporting = array(
			'enfold',
			'flatsome',
			'storefront',
			'virtue',
		);

		$current = wp_get_theme();

		if ( in_array( $current->get_template(), $supporting ) )
			return true;

		return false;
	}

	public function add_review_notice() {
		
		if ( get_option( 'woocommerce_gzd_activation_date' ) )
			$this->queue_notice( 7, 'html-notice-review.php' );

	}

	public function add_pro_notice() {
		
		if ( get_option( 'woocommerce_gzd_activation_date' ) )
			$this->queue_notice( 4, 'html-notice-pro.php' );
	}

	public function queue_notice( $days, $view ) {

		if ( get_option( 'woocommerce_gzd_activation_date' ) ) {
			
			$activation_date = ( get_option( 'woocommerce_gzd_activation_date' ) ? get_option( 'woocommerce_gzd_activation_date' ) : date( 'Y-m-d' ) );
			$diff = WC_germanized()->get_date_diff( $activation_date, date( 'Y-m-d' ) );

			if ( $diff[ 'd' ] >= absint( $days ) )
				include( 'views/' . $view );
		
		}

	}

	/**
	 * Checks if current theme is woocommerce germanized compatible
	 *  
	 * @return boolean
	 */
	public function is_theme_compatible() {
		
		$templates_to_check = WC_germanized()->get_critical_templates();
		
		if ( ! empty( $templates_to_check ) ) {
		
			foreach ( $templates_to_check as $template ) {
		
				$template_path = trailingslashit( 'woocommerce' ) . $template;
		
				$theme_template = locate_template( array(
					$template_path,
					$template
				) );
		
				if ( $theme_template )
					return false;
			}
		}

		return true;
	}
}

endif;

return WC_GZD_Admin_Notices::instance();
