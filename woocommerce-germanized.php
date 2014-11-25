<?php
/**
 * Plugin Name: WooCommerce Germanized
 * Plugin URI: http://www.vendidero.de/woocommerce-germanized
 * Description: Extends WooCommerce to become a legally compliant store for the german market.
 * Version: 1.0.1
 * Author: Vendidero
 * Author URI: http://vendidero.de
 * Requires at least: 3.8
 * Tested up to: 4.0
 *
 * Text Domain: woocommerce-germanized
 * Domain Path: /i18n/languages/
 *
 * @author Vendidero
 */
if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

if ( ! class_exists( 'WooCommerce_Germanized' ) ) :

final class WooCommerce_Germanized {

	/**
	 * Current WooCommerce Germanized Version
	 *
	 * @var string
	 */
	public $version = '1.0.1';

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	/**
	 * Instance of WC_GZD_Units
	 *
	 * @var object
	 */
	public $units = null;

	/**
	 * WC_GZD_Trusted_Shops instance
	 *
	 * @var object
	 */
	public $trusted_shops = null;

	/**
	 * WC_GZD_Ekomi instance
	 *
	 * @var object
	 */
	public $ekomi = null;

	public $emails = null;

	/**
	 * Main WooCommerceGermanized Instance
	 *
	 * Ensures that only one instance of WooCommerceGermanized is loaded or can be loaded.
	 *
	 * @static
	 * @see WC_germanized()
	 * @return WooCommerceGermanized - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Global getter
	 *
	 * @param string  $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return self::$key;
	}

	/**
	 * adds some initialization hooks and inits WooCommerce Germanized
	 */
	public function __construct() {

		// Auto-load classes on demand
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}
		spl_autoload_register( array( $this, 'autoload' ) );

		// Define constants
		$this->define_constants();

		include_once 'includes/class-wc-gzd-install.php';

		// Hooks
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 12 );
		add_action( 'init', array( $this, 'init' ), 1 );
		add_action( 'init', array( 'WC_GZD_Shortcodes', 'init' ), 2 );
		add_action( 'widgets_init', array( $this, 'include_widgets' ), 25 );
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Loaded action
		do_action( 'woocommerce_germanized_loaded' );
	}

	public function deactivate() {
		if ( current_user_can( 'activate_plugins' ) )
			deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	/**
	 * Checks if WooCommerce is activated
	 *  
	 * @return boolean true if WooCommerce is activated
	 */
	public function is_woocommerce_activated() {
		if ( is_multisite() )
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		if ( is_multisite() && ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) )
			return false;
		else if ( ! is_multisite() && ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
			return false;
		return true;
	}

	/**
	 * Init WooCommerceGermanized when WordPress initializes.
	 */
	public function init() {
		if ( $this->is_woocommerce_activated() ) {
			// Before init action
			do_action( 'before_woocommerce_germanized_init' );
			// Include required files
			$this->includes();
			add_filter( 'woocommerce_locate_template', array( $this, 'filter_templates' ), 0, PHP_INT_MAX );
			add_filter( 'woocommerce_product_class', array( $this, 'filter_product_classes' ), 0, 4 );
			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings' ) );
			add_filter( 'woocommerce_enqueue_styles', array( $this, 'add_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_inline_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_styles' ) );
			add_action( 'wp_print_scripts', array( $this, 'localize_scripts' ), 5 );
			add_filter( 'woocommerce_email_classes', array( $this, 'add_emails' ) );
			add_filter( 'woocommerce_locate_core_template', array( $this, 'email_templates' ), 0, 3 );
			add_action( 'woocommerce_email_order_meta', array( $this, 'email_small_business_notice' ), 1 );
			// Payment Gateway BACS
			add_filter( 'woocommerce_payment_gateways', array( $this, 'payment_gateway_filter' ) );
			// Add better tax display to order totals
			add_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_totals' ), 0, 2 );
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_fee_cart' ), 0 );
			// Send order notice directly after new order is being added
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_initial_order_status' ), 0, 2 );

			$this->units          = new WC_GZD_Units();
			$this->trusted_shops  = new WC_GZD_Trusted_Shops();
			$this->ekomi    	  = new WC_GZD_Ekomi();
			$this->emails    	  = new WC_GZD_Emails();

			// Init action
			do_action( 'woocommerce_germanized_init' );
		} else {
			add_action( 'admin_init', array( $this, 'deactivate' ), 0 );
		}
	}

	/**
	 * Auto-load WC_Germanized classes on demand to reduce memory consumption.
	 *
	 * @param mixed   $class
	 * @return void
	 */
	public function autoload( $class ) {
		$path = $this->plugin_path() . '/includes/';
		$class = strtolower( $class );
		$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

		if ( strpos( $class, 'wc_gzd_shipping_' ) === 0 )
			$path = $this->plugin_path() . '/includes/shipping/' . trailingslashit( substr( str_replace( '_', '-', $class ), 16 ) );
		else if ( strpos( $class, 'wc_gzd_gateway_' ) === 0 )
			$path = $this->plugin_path() . '/includes/gateways/' . trailingslashit( substr( str_replace( '_', '-', $class ), 15 ) );

		if ( $path && is_readable( $path . $file ) ) {
			include_once $path . $file;
			return;
		}
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Get the language path
	 *
	 * @return string
	 */
	public function language_path() {
		return $this->plugin_path() . '/i18n/languages';
	}

	/**
	 * Define WC_Germanized Constants
	 */
	private function define_constants() {
		define( 'WC_GERMANIZED_PLUGIN_FILE', __FILE__ );
		define( 'WC_GERMANIZED_VERSION', $this->version );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	private function includes() {

		if ( is_admin() ) {
			include_once 'includes/admin/class-wc-gzd-admin.php';
			include_once 'includes/admin/class-wc-gzd-admin-welcome.php';
			include_once 'includes/admin/class-wc-gzd-admin-notices.php';
			include_once 'includes/admin/meta-boxes/class-wc-gzd-meta-box-product-data.php';
			include_once 'includes/admin/meta-boxes/class-wc-gzd-meta-box-product-data-variable.php';
		}

		if ( defined( 'DOING_AJAX' ) ) {
			$this->ajax_includes();
		}

		if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
			$this->frontend_includes();
		}

		// Post types
		include_once 'includes/class-wc-gzd-post-types.php';

		// Abstracts
		include_once 'includes/abstracts/abstract-wc-gzd-product.php';

		include_once 'includes/wc-gzd-cart-functions.php';
		include_once 'includes/class-wc-gzd-checkout.php';
	}

	/**
	 * Include required ajax files.
	 */
	public function ajax_includes() {
		include_once 'includes/class-wc-gzd-ajax.php';
	}

	/**
	 * Include required frontend files.
	 */
	public function frontend_includes() {
		include_once 'includes/wc-gzd-template-hooks.php';
	}

	/**
	 * Function used to Init WooCommerceGermanized Template Functions - This makes them pluggable by plugins and themes.
	 */
	public function include_template_functions() {
		if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
			include_once 'includes/wc-gzd-template-functions.php';
		}
	}

	/**
	 * Filter WooCommerce Templates to look into /templates before looking within theme folder
	 *
	 * @param string  $template
	 * @param string  $template_name
	 * @param string  $template_path
	 * @return string
	 */
	public function filter_templates( $template, $template_name, $template_path ) {

		if ( ! $template_path ) {
			$template_path = WC()->template_path();
		}

		if ( empty( $GLOBALS[ 'template_name' ] ) )
			$GLOBALS['template_name'] = array();
		$GLOBALS['template_name'][] = $template_name;
		// Check Theme
		$theme_template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				$template_name
			)
		);
		if ( ! $this->is_theme_template_compatible( $template_name, $theme_template ) ) 
			$theme_template = false;
		// Load Default
		if ( ! $theme_template ) {
			if ( file_exists( $this->plugin_path() . '/templates/' . $template_name ) )
				$template = $this->plugin_path() . '/templates/' . $template_name;
		} else
			$template = $theme_template;

		return apply_filters( 'woocommerce_germanized_filter_template', $template, $template_name, $template_path );
	}

	/**
	 * Checks if a template from a theme is woocommerce germanized compatible
	 *  
	 * @param  string  $template template's file name
	 * @param  string  $path     path to template file
	 * @return boolean          
	 */
	public function is_theme_template_compatible( $template, $path = '' ) {
		$templates_to_check = apply_filters( 'woocommerce_gzd_important_templates', array( 'checkout/form-pay.php', 'checkout/review-order.php' ) );
		if ( in_array( $template, $templates_to_check ) && ! empty( $path ) ) {
			// Check if theme may overwrite files
			$data = get_file_data( $path, array( 'wc_gzd_compatible' => 'wc_gzd_compatible' ) );
			if ( ! $data[ 'wc_gzd_compatible' ] )
				return false;
		}
		return true;
	}

	/**
	 * Filter payment gateway classes to load WC_GZD_Gateway_BACS.
	 *  
	 * @param  array $gateways 
	 * @return array filtered gateway array
	 */
	public function payment_gateway_filter( $gateways ) {
		if ( ! empty( $gateways ) ) {
			foreach ( $gateways as $key => $gateway ) {
				if ( $gateway == 'WC_Gateway_BACS' )
					$gateways[ $key ] = 'WC_GZD_Gateway_BACS';
				else if ( $gateway == 'WC_Gateway_COD' )
					$gateways[ $key ] = 'WC_GZD_Gateway_COD';
			}
		}
		return $gateways;
	}

	/**
	 * Load WooCommerce Germanized Product Classes instead of WooCommerce builtin Product Classes
	 *
	 * @param string  $classname
	 * @param string  $product_type
	 * @param string  $post_type
	 * @param integer $product_id
	 * @return string
	 */
	public function filter_product_classes( $classname, $product_type, $post_type, $product_id ) {
		$gzd_classname = str_replace( 'WC', 'WC_GZD', $classname );
		if ( class_exists( $gzd_classname ) )
			$classname = $gzd_classname;
		return $classname;
	}

	/**
	 * Load Localisation files for WooCommerce Germanized.
	 */
	public function load_plugin_textdomain() {
		$domain = 'woocommerce-germanized';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/i18n/languages/' );
	}

	/**
	 * Load a single translation by textdomain
	 *
	 * @param string  $path
	 * @param string  $textdomain
	 * @param string  $prefix
	 */
	public function load_translation( $path, $textdomain, $prefix ) {
		if ( is_readable( $path . $prefix . '-de_DE.mo' ) )
			load_textdomain( $textdomain, $path . $prefix . '-de_DE.mo' );
	}

	/**
	 * Include WooCommerce Germanized Widgets
	 */
	public function include_widgets() {
		if ( is_object( $this->trusted_shops) && $this->trusted_shops->is_rich_snippets_enabled() )
			include_once 'includes/widgets/class-wc-gzd-widget-trusted-shops-rich-snippets.php';
		if ( is_object( $this->trusted_shops) && $this->trusted_shops->is_review_widget_enabled() )
			include_once 'includes/widgets/class-wc-gzd-widget-trusted-shops-reviews.php';
	}

	/**
	 * Show action links on the plugin screen
	 *
	 * @param mixed   $links
	 * @return array
	 */
	public function action_links( $links ) {
		return array_merge( array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=germanized' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>',
			), $links );
	}

	/**
	 * Add custom styles to Admin
	 */
	public function add_admin_styles() {
		wp_register_style( 'woocommerce-gzd-admin', WC_germanized()->plugin_url() . '/assets/css/woocommerce-gzd-admin.css', false, WC_germanized()->version );
		wp_enqueue_style( 'woocommerce-gzd-admin' );
	}

	/**
	 * Add styles to frontend
	 *
	 * @param array   $styles
	 */
	public function add_styles( $styles ) {
		$styles['woocommerce-gzd-layout'] = array(
			'src'     => str_replace( array( 'http:', 'https:' ), '', WC_germanized()->plugin_url() ) . '/assets/css/woocommerce-gzd-layout.css',
			'deps'    => '',
			'version' => WC_GERMANIZED_VERSION,
			'media'   => 'all'
		);
		return $styles;
	}

	/**
	 * Adds woocommerce checkout table background highlight color as inline css
	 */
	public function add_inline_styles() {
		$color = ( get_option( 'woocommerce_gzd_display_checkout_table_color' ) ? get_option( 'woocommerce_gzd_display_checkout_table_color' ) : '#eee' );
        $custom_css = ".woocommerce-checkout .shop_table { background-color: $color; }";
        wp_add_inline_style( 'woocommerce-gzd-layout', $custom_css );
	}

	/**
	 * Add Scripts to frontend
	 */
	public function add_scripts() {
		global $post;
		$assets_path = str_replace( array( 'http:', 'https:' ), '', WC_germanized()->plugin_url() ) . '/assets/';
		$frontend_script_path = $assets_path . 'js/';
		if ( isset( $post ) && $post->ID == woocommerce_get_page_id( 'revocation' ) )
			wp_enqueue_script( 'wc-gzd-revocation', $frontend_script_path . 'revocation.js', array( 'jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n' ), WC_GERMANIZED_VERSION, true );
		if ( is_checkout() )
			wp_enqueue_script( 'wc-gzd-checkout', $frontend_script_path . 'checkout.js', array( 'jquery', 'wc-checkout' ), WC_GERMANIZED_VERSION, true );
		if ( is_singular( 'product' ) ) {
			$product = wc_get_product( $post->ID );
			if ( $product && $product->is_type( 'variable' ) ) {
				// Enqueue variation scripts
				wp_enqueue_script( 'wc-add-to-cart-variation' );
				wp_enqueue_script( 'wc-gzd-add-to-cart-variation', $frontend_script_path . 'add-to-cart-variation.js', array( 'jquery', 'woocommerce' ), WC_GERMANIZED_VERSION, true );
			}
		} 
	}

	/**
	 * Localize Script to enable AJAX
	 */
	public function localize_scripts() {
		global $wp;
		$assets_path = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';
		if ( wp_script_is( 'wc-gzd-revocation' ) ) {
			wp_localize_script( 'wc-gzd-revocation', 'wc_gzd_revocation_params', apply_filters( 'wc_gzd_revocation_params', array(
				'ajax_url'                  => WC()->ajax_url(),
				'ajax_loader_url'           => apply_filters( 'woocommerce_ajax_loader_url', $assets_path . 'images/ajax-loader@2x.gif' ),
			) ) );
		}
	}

	/**
	 * Add WooCommerce Germanized Settings Tab
	 *
	 * @param array   $integrations
	 * @return array
	 */
	public function add_settings( $integrations ) {
		include_once 'includes/admin/settings/class-wc-gzd-settings-germanized.php';
		$integrations[] = new WC_GZD_Settings_Germanized();
		return $integrations;
	}

	/**
	 * Add small business global Email Footer
	 */
	public function email_small_business_notice() {
		if ( get_option( 'woocommerce_gzd_small_enterprise' ) == 'yes' )
			woocommerce_get_template( 'global/small-business-info.php' );
	}

	/**
	 * Add Custom Email templates
	 *
	 * @param array   $mails
	 * @return array
	 */
	public function add_emails( $mails ) {
		$mails[] = include 'includes/emails/class-wc-gzd-email-customer-revocation.php';
		$mails[] = include 'includes/emails/class-wc-gzd-email-customer-ekomi.php';
		return $mails;
	}

	/**
	 * Filter Email template to include WooCommerce Germanized template files
	 *
	 * @param string  $core_file
	 * @param string  $template
	 * @param string  $template_base
	 * @return string
	 */
	public function email_templates( $core_file, $template, $template_base ) {
		if ( ! file_exists( $template_base . $template ) && file_exists( $this->plugin_path() . '/templates/' . $template ) )
			$core_file = $this->plugin_path() . '/templates/' . $template;
		return apply_filters( 'woocommerce_germanized_email_template_hook', $core_file, $template, $template_base );
	}

	/**
	 * Set initial order status to processing - even if paypal is being used (email affirmation has to be sent directly after order submit)
	 *  	
	 * @param  int 	  $order_id 	 the order id	
	 * @param  array  $post_data  meta data
	 */
	public function update_initial_order_status( $order_id, $post_data ) {
		$order = wc_get_order( $order_id );
		$order->update_status( 'processing' );
	}

	/**
	 * Update fee for cart if cod has been selected as payment method
	 */
	public function add_fee_cart() {
		if ( WC()->session->get('chosen_payment_method') == 'cod' ) {
			$cod = new WC_GZD_Gateway_COD();
			$cod->add_fee();
		}
	}

	/**
	 * Improve tax display within order totals
	 *  
	 * @param  array $order_totals 
	 * @param  object $order        
	 * @return array               
	 */
	public function order_item_totals( $order_totals, $order ) {
		$order_totals['order_total'] = array(
			'label' => __( 'Order Total:', 'woocommerce' ),
			'value'	=> $order->get_formatted_order_total()
		);
		// Tax for inclusive prices
		if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) && 'incl' == $order->tax_display_cart ) {
			
			$tax_array = array();
			if ( 'itemized' == get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( $order->get_tax_totals() as $code => $tax ) {
					$tax->rate = WC_Tax::get_rate_percent( $tax->rate_id );
					$tax_array[] = array( 'tax' => $tax, 'amount' => $tax->formatted_amount );
				}
			} else {
				$base_rate = array_values( WC_Tax::get_shop_base_rate() );
				$base_rate = (object) $base_rate[0];
				$base_rate->rate = $base_rate->rate . '%';
				$tax_array[] = array( 'tax' => $base_rate, 'amount' => wc_price( $order->get_total_tax() ) );
			}

			if ( ! empty( $tax_array ) ) {
				foreach ( $tax_array as $tax ) {
					$order_totals['tax_' . $tax['tax']->label] = array(
						'label' => '<span class="tax small tax-label">' . sprintf( __( 'incl. %s VAT', 'woocommerce-germanized' ), $tax[ 'tax' ]->rate ) . '</span>',
						'value' => '<span class="tax small tax-value">' . $tax[ 'amount' ] . '</span>'
					);
				}
			}
		}
		return $order_totals;
	}

}

endif;

/**
 * Returns the global instance of WooCommerce Germanized
 */
function WC_germanized() {
	return WooCommerce_Germanized::instance();
}

$GLOBALS['woocommerce_germanized'] = WC_germanized();

?>
