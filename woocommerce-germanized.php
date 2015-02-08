<?php
/**
 * Plugin Name: WooCommerce Germanized
 * Plugin URI: http://www.vendidero.de/woocommerce-germanized
 * Description: Extends WooCommerce to become a legally compliant store for the german market.
 * Version: 1.1.2
 * Author: Vendidero
 * Author URI: http://vendidero.de
 * Requires at least: 3.8
 * Tested up to: 4.1
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
	public $version = '1.1.2';

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
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
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
		if ( function_exists( "__autoload" ) )
			spl_autoload_register( "__autoload" );
		spl_autoload_register( array( $this, 'autoload' ) );

		if ( ! $this->is_woocommerce_activated() ) {
			add_action( 'admin_init', array( $this, 'deactivate' ), 0 );
			return;
		}

		// Define constants
		$this->define_constants();

		$this->includes();

		// Hooks
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 12 );
		add_action( 'init', array( $this, 'init' ), 1 );
		add_action( 'init', array( 'WC_GZD_Shortcodes', 'init' ), 2 );
		add_action( 'widgets_init', array( $this, 'include_widgets' ), 25 );
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'woocommerce_init', array( $this, 'replace_woocommerce_cart' ), 0 );
		add_action( 'woocommerce_init', array( $this, 'replace_woocommerce_payment_gateways' ), 0 );

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
		if ( is_multisite() && ! ( is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) || in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) )
			return false;
		if ( ! is_multisite() && ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
			return false;
		return true;
	}

	/**
	 * Init WooCommerceGermanized when WordPress initializes.
	 */
	public function init() {
		// Before init action
		do_action( 'before_woocommerce_germanized_init' );

		add_filter( 'woocommerce_locate_template', array( $this, 'filter_templates' ), PHP_INT_MAX, 3 );
		add_filter( 'woocommerce_product_class', array( $this, 'filter_product_classes' ), PHP_INT_MAX, 4 );
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings' ) );
		add_filter( 'woocommerce_enqueue_styles', array( $this, 'add_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_inline_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_styles' ) );
		add_action( 'wp_print_scripts', array( $this, 'localize_scripts' ), 5 );
		add_filter( 'woocommerce_email_classes', array( $this, 'add_emails' ) );
		add_filter( 'woocommerce_locate_core_template', array( $this, 'email_templates' ), 0, 3 );
		add_action( 'woocommerce_email_order_meta', array( $this, 'email_small_business_notice' ), 1 );

		// Add better tax display to order totals
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_totals' ), 0, 2 );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_fee_cart' ), 0 );
		
		// Adjust virtual Product Price and tax class
		add_filter( 'woocommerce_get_price_including_tax', array( $this, 'set_virtual_product_price' ), PHP_INT_MAX, 3 );
		add_filter( 'get_post_metadata', array( $this, 'inject_gzd_product' ), 0, 4 );
		
		// Hide cart estimated text if chosen
		add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'hide_cart_estimated_text' ) );
		add_action( 'woocommerce_after_cart_totals', array( $this, 'remove_cart_tax_zero_filter' ) );

		// Add better WooCommerce shipping taxation
		add_filter( 'woocommerce_package_rates', array( $this, 'replace_shipping_rate_class' ), 0, 2 );

		// Send order notice directly after new order is being added - use these filters because order status has to be updated already
		add_filter( 'woocommerce_payment_successful_result', array( $this, 'send_order_confirmation_mails' ), 0, 2 );
		add_filter( 'woocommerce_checkout_no_payment_needed_redirect', array( $this, 'send_order_confirmation_mails' ), 0, 2 );

		// Remove processing + on-hold default order confirmation mails
		$mailer = WC()->mailer();
		$mails = $mailer->get_emails();
		remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $mails[ 'WC_Email_Customer_Processing_Order' ], 'trigger' ) );
		remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $mails[ 'WC_Email_Customer_Processing_Order' ], 'trigger' ) );
		remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $mails[ 'WC_Email_New_Order' ], 'trigger' ) );
		remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $mails[ 'WC_Email_New_Order' ], 'trigger' ) );
		remove_action( 'woocommerce_order_status_pending_to_completed_notification', array( $mails[ 'WC_Email_New_Order' ], 'trigger' ) );

		$this->units          = new WC_GZD_Units();
		$this->trusted_shops  = new WC_GZD_Trusted_Shops();
		$this->ekomi    	  = new WC_GZD_Ekomi();
		$this->emails    	  = new WC_GZD_Emails();

		// Init action
		do_action( 'woocommerce_germanized_init' );
	}

	/**
	 * Replaces default WC_Payment_Gateways to enable dependency injection
	 */
	public function replace_woocommerce_payment_gateways() {
		WC()->payment_gateways = WC_GZD_Payment_Gateways::instance();
	}

	/**
	 * Replace default WC_Shipping_Rate to enable exact taxation for shipping costs
	 *  
	 * @param  array $rates containing WC_Shipping_Rate objects
	 * @param  WC_Shipping_Rate $rate current object
	 * @return array 
	 */
	public function replace_shipping_rate_class( $rates, $rate ) {
		foreach ( $rates as $key => $rate )
			$rates[ $key ] = new WC_GZD_Shipping_Rate( $rate );
		return $rates;
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

		if ( strpos( $class, 'wc_gzd_gateway_' ) === 0 )
			$path = $this->plugin_path() . '/includes/gateways/' . trailingslashit( substr( str_replace( '_', '-', $class ), 15 ) );

		if ( $path && is_readable( $path . $file ) ) {
			include_once( $path . $file );
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
	 * Get WC Germanized template path
	 *  
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'woocommerce_gzd_template_path', 'woocommerce-germanized/' );
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

		include_once ( 'includes/wc-gzd-core-functions.php' );
		include_once ( 'includes/class-wc-gzd-install.php' );

		if ( is_admin() ) {
			include_once( 'includes/admin/class-wc-gzd-admin.php' );
			include_once( 'includes/admin/class-wc-gzd-admin-welcome.php' );
			include_once( 'includes/admin/class-wc-gzd-admin-notices.php' );
			include_once( 'includes/admin/meta-boxes/class-wc-gzd-meta-box-product-data.php' );
			include_once( 'includes/admin/meta-boxes/class-wc-gzd-meta-box-product-data-variable.php' );
		}

		if ( defined( 'DOING_AJAX' ) )
			$this->ajax_includes();

		if ( ! is_admin() || defined( 'DOING_AJAX' ) )
			add_action( 'init', array( $this, 'frontend_includes' ), 5 );

		// Post types
		include_once ( 'includes/class-wc-gzd-post-types.php' );

		// Abstracts
		include_once ( 'includes/abstracts/abstract-wc-gzd-product.php' );
		include_once ( 'includes/abstracts/abstract-wc-gzd-payment-gateway.php' );

		include_once ( 'includes/wc-gzd-cart-functions.php' );
		include_once ( 'includes/class-wc-gzd-checkout.php' );

	}

	/**
	 * Include required ajax files.
	 */
	public function ajax_includes() {
		include_once( 'includes/class-wc-gzd-ajax.php' );
	}

	/**
	 * Include required frontend files.
	 */
	public function frontend_includes() {
		include_once( 'includes/wc-gzd-template-hooks.php' );
	}

	/**
	 * Function used to Init WooCommerceGermanized Template Functions - This makes them pluggable by plugins and themes.
	 */
	public function include_template_functions() {
		if ( ! is_admin() || defined( 'DOING_AJAX' ) )
			include_once( 'includes/wc-gzd-template-functions.php' );
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
		$template_path = $this->template_path();

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

		// Load Default
		if ( ! $theme_template && file_exists( $this->plugin_path() . '/templates/' . $template_name ) )
			return $this->plugin_path() . '/templates/' . $template_name;
		else if ( $theme_template )
			$template = $theme_template;
		
		return apply_filters( 'woocommerce_germanized_filter_template', $template, $template_name, $template_path );
	}

	/**
	 * Get templates which are legally critical
	 *  
	 * @return array
	 */
	public function get_critical_templates() {
		return apply_filters( 'woocommerce_gzd_important_templates', array( 'checkout/form-pay.php', 'checkout/review-order.php' ) );
	}

	/**
	 * Inject WC_GZD_Product into WC_Product by filtering postmeta
	 *  
	 * @param  mixed $metadata 
	 * @param  int $object_id 
	 * @param  string $meta_key  
	 * @param  boolean $single    
	 * @return mixed
	 */
	public function inject_gzd_product( $metadata, $object_id, $meta_key, $single ) {
		if ( $meta_key == '_gzd_product' && in_array( get_post_type( $object_id ), array( 'product', 'product_variation' ) ) )
			return wc_gzd_get_product( $object_id );
		return $metadata;
	}

	/**
	 * Replace the default WC_Cart by WC_GZD_Cart for EU virtual VAT rules.
	 */
	public function replace_woocommerce_cart() {
		if ( get_option( 'woocommerce_gzd_enable_virtual_vat' ) == 'yes' && ( ! is_admin() || defined( 'DOING_AJAX' ) ) )
			WC()->cart = new WC_GZD_Cart();
	}

	/**
	 * Set default order button text instead of the button text defined by each payment gateway.
	 * Can be overriden by setting force_order_button_text within payment gateway class
	 */
	public function set_order_button_gateway_text() {
		$gateways = WC()->payment_gateways->get_available_payment_gateways();
		foreach( $gateways as $gateway ) {
			if ( ! isset( $gateway->force_order_button_text ) || ! $gateway->force_order_button_text )
				$gateway->order_button_text = __( get_option( 'woocommerce_gzd_order_submit_btn_text' ), 'woocommerce-germanized' );
		}
	}

	/**
	 * Calls a filter to temporarily set cart tax to zero. This is only done to hide the cart tax estimated text.
	 * Filter is being remove right after get_cart_tax - check has been finished within cart-totals.php
	 */
	public function hide_cart_estimated_text() {
		if ( get_option( 'woocommerce_gzd_display_hide_cart_tax_estimated' ) == 'yes' )
			add_filter( 'woocommerce_get_cart_tax', array( $this, 'set_cart_tax_zero' ) );
	}

	/**
	 * This will set the cart tax to zero
	 *  
	 * @param float $tax current's cart tax
	 * @return int
	 */
	public function set_cart_tax_zero( $tax ) {
		return 0;
	}

	/**
	 * Removes the zero cart tax filter after get_cart_tax has been finished
	 */
	public function remove_cart_tax_zero_filter() {
		if ( get_option( 'woocommerce_gzd_display_hide_cart_tax_estimated' ) == 'yes' )
			remove_filter( 'woocommerce_get_cart_tax', array( $this, 'set_cart_tax_zero' ) );
	}

	/**
	 * Gets payment gateways which are ready to accept fees.
	 *  
	 * @return array
	 */
	public function get_payment_gateways_feeable() {
		return apply_filters( 'wc_gzd_payment_gateways_feeable', array( 'bacs', 'paypal', 'cod' ) );
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
			include_once( 'includes/widgets/class-wc-gzd-widget-trusted-shops-rich-snippets.php' );
		if ( is_object( $this->trusted_shops) && $this->trusted_shops->is_review_widget_enabled() )
			include_once( 'includes/widgets/class-wc-gzd-widget-trusted-shops-reviews.php' );
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
		include_once( 'includes/admin/settings/class-wc-gzd-settings-germanized.php' );
		$integrations[] = new WC_GZD_Settings_Germanized();
		return $integrations;
	}

	/**
	 * Add small business global Email Footer
	 */
	public function email_small_business_notice() {
		if ( get_option( 'woocommerce_gzd_small_enterprise' ) == 'yes' )
			wc_get_template( 'global/small-business-info.php' );
	}

	/**
	 * PHP 5.3 backwards compatibility for getting date diff
	 *  
	 * @param  string $from date from
	 * @param  string $to   date to
	 * @return array  array containing year, month, date diff
	 */
	public function get_date_diff( $from, $to ) {
		$diff = abs( strtotime( $to ) - strtotime( $from ) );
		$years = floor( $diff / (365*60*60*24) );
		$months = floor( ( $diff - $years * 365*60*60*24 ) / ( 30*60*60*24 ) );
		$days = floor( ( $diff - $years * 365*60*60*24 - $months*30*60*60*24 ) / ( 60*60*24 ) );
		return array( 'y' => $years, 'm' => $months, 'd' => $days );
	}

	/**
	 * Add Custom Email templates
	 *
	 * @param array   $mails
	 * @return array
	 */
	public function add_emails( $mails ) {
		$mails[ 'WC_GZD_Email_Customer_Revocation' ] 	= include 'includes/emails/class-wc-gzd-email-customer-revocation.php';
		$mails[ 'WC_GZD_Email_Customer_Ekomi' ] 	 	= include 'includes/emails/class-wc-gzd-email-customer-ekomi.php';
		$mails[ 'WC_GZD_Email_Customer_Trusted_Shops' ] = include 'includes/emails/class-wc-gzd-email-customer-trusted-shops.php';
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
	 * Send order confirmation mail directly after order is being sent
	 *  	
	 * @param  mixed 	  $return 	
	 * @param  mixed  	  $order
	 */
	public function send_order_confirmation_mails( $return, $order ) {
		if ( ! is_object( $order ) )
			$order = wc_get_order( $order );
		// Send order processing mail
		$mailer = WC()->mailer();
		$mails = $mailer->get_emails();
		$mails[ 'WC_Email_Customer_Processing_Order' ]->trigger( $order->id );
		$mails[ 'WC_Email_New_Order' ]->trigger( $order->id );
		do_action( 'woocommerce_germanized_order_confirmation_sent', $order->id );
		return $return;
	}

	/**
	 * Stop WooCommerce from adding additional VAT to virtual products within Checkout.
	 *  
	 * @param float $price  
	 * @param int $qty    
	 * @param object $product
	 * @return adjusted price
	 */
	public function set_virtual_product_price( $price, $qty, $product ) {
		if ( ! $product->gzd_product->is_virtual_vat_exception() || ! isset( WC()->cart ) || ! WC()->cart->is_virtual_taxable() )
			return $price;
		if ( get_option('woocommerce_prices_include_tax') === 'yes' )
			return $product->get_price() * $qty;
		return $price;
	}

	/**
	 * Update fee for cart if feeable gateway has been selected as payment method
	 */
	public function add_fee_cart() {
		$gateways = WC()->payment_gateways()->get_available_payment_gateways();
		if ( ! ( $key = WC()->session->get('chosen_payment_method') ) || ! isset( $gateways[ $key ] ) )
			return;
		$gateway = $gateways[ $key ];
		if ( isset( $gateway->parent ) && is_callable( array( $gateway->parent, 'add_fee' ) ) )
			$gateway->parent->add_fee();
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
				$base_rate->rate = $base_rate->rate;
				$tax_array[] = array( 'tax' => $base_rate, 'amount' => wc_price( $order->get_total_tax() ) );
			}

			if ( ! empty( $tax_array ) ) {
				foreach ( $tax_array as $tax ) {
					$order_totals['tax_' . $tax['tax']->label] = array(
						'label' => '<span class="tax small tax-label">' . ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ? sprintf( __( 'incl. %s%% VAT', 'woocommerce-germanized' ), wc_gzd_format_tax_rate_percentage( $tax[ 'tax' ]->rate ) ) : __( 'incl. VAT', 'woocommerce-germanized' ) ) . '</span>',
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