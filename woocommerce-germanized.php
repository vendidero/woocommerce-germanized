<?php
/**
 * Plugin Name: WooCommerce Germanized
 * Plugin URI: https://www.vendidero.de/woocommerce-germanized
 * Description: Extends WooCommerce to become a legally compliant store for the german market.
 * Version: 1.9.6
 * Author: Vendidero
 * Author URI: https://vendidero.de
 * Requires at least: 3.8
 * Tested up to: 4.9
 * WC requires at least: 2.4
 * WC tested up to: 3.2
 * Requires at least WooCommerce: 2.4
 * Tested up to WooCommerce: 3.2
 *
 * Text Domain: woocommerce-germanized
 * Domain Path: /i18n/languages/
 *
 * @author Vendidero
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WooCommerce_Germanized' ) ) :

final class WooCommerce_Germanized {

	/**
	 * Current WooCommerce Germanized Version
	 *
	 * @var string
	 */
	public $version = '1.9.6';

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

	public $price_labels = null;

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

	public $compatibilities = array();

	private $localized_scripts = array();

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
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->$key;
	}

	/**
	 * adds some initialization hooks and inits WooCommerce Germanized
	 */
	public function __construct() {

		// Auto-load classes on demand
		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$dependencies = apply_filters( 'woocommerce_gzd_dependencies_instance', WC_GZD_Dependencies::instance( $this ) );

		if ( ! $dependencies->is_loadable() ) {
			return;
		}

		// Define constants
		$this->define_constants();

		$this->includes();

		// Hooks
		register_activation_hook( __FILE__, array( 'WC_GZD_Install', 'install' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 12 );

		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'WC_GZD_Shortcodes', 'init' ), 2 );
		add_action( 'plugins_loaded', array( $this, 'setup_compatibility' ), 0 );

		add_action( 'woocommerce_init', array( $this, 'replace_woocommerce_product_factory' ), PHP_INT_MAX );
		// Set template filter directly after load to ensure wc_get_template finds templates
		add_filter( 'woocommerce_locate_template', array( $this, 'filter_templates' ), PHP_INT_MAX, 3 );

		$this->units          = new WC_GZD_Units();
		$this->price_labels   = new WC_GZD_Price_Labels();

		// Loaded action
		do_action( 'woocommerce_germanized_loaded' );

	}

	/**
	 * Checks if is pro user
	 *
	 * @return boolean
	 */
	public function is_pro() {
		return WC_GZD_Dependencies::instance()->is_plugin_activated( 'woocommerce-germanized-pro/woocommerce-germanized-pro.php' );
	}

	/**
	 * Init WooCommerceGermanized when WordPress initializes.
	 */
	public function init() {

		// Before init action
		do_action( 'before_woocommerce_germanized_init' );

		$this->load_plugin_textdomain();

		if ( get_option( 'woocommerce_gzd_display_checkout_fallback' ) == 'yes' ) {
			add_filter( 'woocommerce_germanized_filter_template', array( $this, 'set_checkout_fallback' ), 10, 3 );
		}

		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings' ) );

		add_filter( 'woocommerce_enqueue_styles', array( $this, 'add_styles' ) );

		// Load after WooCommerce Frontend scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ), 15 );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_inline_styles' ) );
		add_action( 'wp_print_scripts', array( $this, 'localize_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( $this, 'localize_scripts' ), 5 );

		add_filter( 'woocommerce_email_classes', array( $this, 'add_emails' ) );
		add_filter( 'woocommerce_locate_core_template', array( $this, 'email_templates' ), 0, 3 );
		add_action( 'woocommerce_email_order_meta', array( $this, 'email_small_business_notice' ), 1 );

		// Add better tax display to order totals
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_totals' ), 0, 2 );

		// Unsure wether this could lead to future problems - tax classes with same name wont be merged anylonger
		// add_filter( 'woocommerce_rate_code', array( $this, 'prevent_tax_name_merge' ), PHP_INT_MAX, 2 );
		// Hide cart estimated text if chosen
		add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'hide_cart_estimated_text' ) );
		add_action( 'woocommerce_after_cart_totals', array( $this, 'remove_cart_tax_zero_filter' ) );

		// Add better WooCommerce shipping taxation
		add_filter( 'woocommerce_package_rates', array( $this, 'replace_shipping_rate_class' ), 0, 2 );

		// Payment gateways
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateways' ) );

		// Remove cart subtotal filter
		add_action( 'template_redirect', array( $this, 'remove_cart_unit_price_filter' ) );

		$this->emails    	  = new WC_GZD_Emails();

		// Init action
		do_action( 'woocommerce_germanized_init' );
	}

	/**
	 * Auto-load WC_Germanized classes on demand to reduce memory consumption.
	 *
	 * @param mixed $class
	 * @return void
	 */
	public function autoload( $class ) {

		$path = $this->plugin_path() . '/includes/';
		$original_class = $class;
		$class = strtolower( $class );
		$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

		if ( strpos( $class, 'wc_gzd_admin' ) !== false ) {
			$path = $this->plugin_path() . '/includes/admin/';
		} elseif ( strpos( $class, 'wc_gzd_gateway_' ) !== false ) {
			$path = $this->plugin_path() . '/includes/gateways/' . substr( str_replace( '_', '-', $class ), 15 ) . '/';
		} elseif ( strpos( $class, 'wc_gzd_trusted_shops' ) !== false ) {
			$path = $this->plugin_path() . '/includes/trusted-shops/';
		} elseif ( strpos( $class, 'wc_gzd_compatibility' ) !== false ) {
			$path = $this->plugin_path() . '/includes/compatibility/';
		} elseif ( strpos( $class, 'defuse\crypto' ) !== false ) {
			$path = $this->plugin_path() . '/includes/gateways/direct-debit/libraries/php-encryption/';
			$file = ucfirst( str_replace( 'Defuse/Crypto/', '', str_replace( '\\', '/', $original_class ) ) . '.php' );
		} elseif ( strpos( $class, 'digitick\sepa' ) !== false ) {
			$path = $this->plugin_path() . '/includes/gateways/direct-debit/libraries/php-sepa-xml/';
			$file = ucfirst( str_replace( 'Digitick/Sepa/', '', str_replace( '\\', '/', $original_class ) ) . '.php' );
		} elseif ( strpos( $class, 'ekomi\\' ) !== false ) {
			$path = $this->plugin_path() . '/includes/libraries/Ekomi/';
			$file = ucfirst( str_replace( 'Ekomi/', '', str_replace( '\\', '/', $original_class ) ) . '.php' );
		}

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

		include_once( 'includes/wc-gzd-core-functions.php' );
		include_once( 'includes/wc-gzd-legacy-functions.php' );
		include_once( 'includes/class-wc-gzd-install.php' );

		if ( is_admin() ) {

			include_once( 'includes/admin/class-wc-gzd-admin.php' );
			include_once( 'includes/admin/class-wc-gzd-admin-welcome.php' );
			include_once( 'includes/admin/class-wc-gzd-admin-notices.php' );
			include_once( 'includes/admin/class-wc-gzd-admin-customer.php' );
			include_once( 'includes/admin/class-wc-gzd-admin-importer.php' );

			include_once( 'includes/export/class-wc-gzd-product-export.php' );
			include_once( 'includes/import/class-wc-gzd-product-import.php' );
		}

		include_once( 'includes/admin/meta-boxes/class-wc-gzd-meta-box-product-data.php' );
		include_once( 'includes/admin/meta-boxes/class-wc-gzd-meta-box-product-data-variable.php' );

		if ( ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) ) {
			if ( did_action( 'woocommerce_loaded' ) ) {
				$this->frontend_includes();
			} else {
				add_action( 'woocommerce_loaded', array( $this, 'frontend_includes' ), 5 );
			}
		}

		// Post types
		include_once( 'includes/class-wc-gzd-post-types.php' );
		// Gateway manipulation
		include_once( 'includes/class-wc-gzd-payment-gateways.php' );
		// Template priority
		include_once( 'includes/class-wc-gzd-hook-priorities.php' );

		// Abstracts
		include_once( 'includes/abstracts/abstract-wc-gzd-product.php' );
		include_once( 'includes/abstracts/abstract-wc-gzd-taxonomy.php' );
		include_once( 'includes/abstracts/abstract-wc-gzd-compatibility.php' );

		// API
		include_once( 'includes/api/class-wc-gzd-rest-api.php' );

		include_once( 'includes/wc-gzd-cart-functions.php' );
		include_once( 'includes/wc-gzd-order-functions.php' );

		include_once( 'includes/class-wc-gzd-ajax.php' );
		include_once( 'includes/class-wc-gzd-checkout.php' );
		include_once( 'includes/class-wc-gzd-dhl-parcel-shops.php' );
		include_once( 'includes/class-wc-gzd-customer-helper.php' );

		// Only available for Woo 3.X
		if ( WC_GZD_Dependencies::instance( $this )->woocommerce_version_supports_crud() ) {
			include_once( 'includes/class-wc-gzd-coupon-helper.php' );
		}

		include_once( 'includes/class-wc-gzd-virtual-vat-helper.php' );

		$this->setup_trusted_shops();
		$this->ekomi = new WC_GZD_Ekomi();

	}

	public function setup_compatibility() {

		$plugins = apply_filters( 'woocommerce_gzd_compatibilities',
			array(
				'wpml',
				'polylang',
				'woo-poly-integration',
				'woocommerce-dynamic-pricing',
				'woocommerce-role-based-prices'
			)
		);

		foreach ( $plugins as $comp ) {

			$classname = str_replace( ' ', '_', 'WC_GZD_Compatibility_' . ucwords( str_replace( '-', ' ', $comp ) ) );
			if ( class_exists( $classname ) ) {
				$this->compatibilities[ $comp ] = new $classname();
			}
		}

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
		include_once( 'includes/wc-gzd-template-functions.php' );
	}

	/**
	 * Filter WooCommerce Templates to look into /templates before looking within theme folder
	 *
	 * @param string $template
	 * @param string $template_name
	 * @param string $template_path
	 * @return string
	 */
	public function filter_templates( $template, $template_name, $template_path ) {
		$template_path = $this->template_path();

		if ( ! isset( $GLOBALS['wc_gzd_template_name'] ) || empty( $GLOBALS['wc_gzd_template_name'] ) || ! is_array( $GLOBALS['wc_gzd_template_name'] ) ) {
			$GLOBALS['wc_gzd_template_name'] = array();
		}

		$GLOBALS['wc_gzd_template_name'][] = $template_name;

		// Check Theme
		$theme_template = locate_template(
			array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
			)
		);

		$template_name = apply_filters( 'woocommerce_gzd_template_name', $template_name );

		// Load Default
		if ( ! $theme_template && file_exists( apply_filters( 'woocommerce_gzd_default_plugin_template', $this->plugin_path() . '/templates/' . $template_name, $template_name ) ) ) {
			$template = apply_filters( 'woocommerce_gzd_default_plugin_template', $this->plugin_path() . '/templates/' . $template_name, $template_name );
		} elseif ( $theme_template ) {
			$template = $theme_template;
		}

		return apply_filters( 'woocommerce_germanized_filter_template', $template, $template_name, $template_path );
	}

	/**
	 * Get templates which are legally critical
	 *
	 * @return array
	 */
	public function get_critical_templates() {
		return apply_filters( 'woocommerce_gzd_important_templates', array() );
	}

	/**
	 * Sets review-order.php fallback (if activated) by filtering template name.
	 *
	 * @param string $template_name
	 * @return string
	 */
	public function set_checkout_fallback( $template, $template_name, $template_path ) {

		$path = WC()->plugin_path() . '/templates/';

		if ( strstr( $template_name, 'review-order.php' ) ) {
			return trailingslashit( $path ) . 'checkout/review-order.php';
		} elseif ( strstr( $template_name, 'form-checkout.php' ) ) {
			return trailingslashit( $path ) . 'checkout/form-checkout.php';
		}

		return $template;
	}

	/**
	 * Overload product factory to inject gzd_product
	 */
	public function replace_woocommerce_product_factory() {
		WC()->product_factory = new WC_GZD_Product_Factory();
	}

	/**
	 * Replace default WC_Shipping_Rate to enable exact taxation for shipping costs
	 *
	 * @param  array            $rates containing WC_Shipping_Rate objects
	 * @param  WC_Shipping_Rate $rate current object
	 * @return array
	 */
	public function replace_shipping_rate_class( $rates, $rate ) {

		if ( get_option( 'woocommerce_gzd_shipping_tax' ) !== 'yes' ) {
			return $rates;
		}

		foreach ( $rates as $key => $rate ) {

			// Check for instance to make sure calculation is not done for multiple times
			if ( ! $rate instanceof WC_GZD_Shipping_Rate ) {

				// Replace rate with germanized placeholder
				$rates[ $key ] = new WC_GZD_Shipping_Rate( $rate );

				// Copy meta data if available
				if ( is_callable( array( $rate, 'get_meta_data' ) ) ) {
					foreach( $rate->get_meta_data() as $meta_key => $meta_val ) {
						$rates[ $key ]->add_meta_data( $meta_key, $meta_val );
					}
				}
			}
		}

		return $rates;
	}

	/**
	 * Calls a filter to temporarily set cart tax to zero. This is only done to hide the cart tax estimated text.
	 * Filter is being remove right after get_cart_tax - check has been finished within cart-totals.php
	 */
	public function hide_cart_estimated_text() {
		if ( get_option( 'woocommerce_gzd_display_hide_cart_tax_estimated' ) == 'yes' ) {
			add_filter( 'woocommerce_get_cart_tax', array( $this, 'set_cart_tax_zero' ) );
		}
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
		if ( get_option( 'woocommerce_gzd_display_hide_cart_tax_estimated' ) == 'yes' ) {
			remove_filter( 'woocommerce_get_cart_tax', array( $this, 'set_cart_tax_zero' ) );
		}
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
		if ( class_exists( $gzd_classname ) ) {
			$classname = $gzd_classname;
		}
		return $classname;
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Frontend/global Locales found in:
	 * 		- WP_LANG_DIR/woocommerce-germanized/woocommerce-germanized-LOCALE.mo
	 * 	 	- WP_LANG_DIR/plugins/woocommerce-germanized-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'woocommerce-germanized' );

		load_textdomain( 'woocommerce-germanized', trailingslashit( WP_LANG_DIR ) . 'woocommerce-germanized/woocommerce-germanized-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-germanized', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages/' );
	}

	/**
	 * Show action links on the plugin screen
	 *
	 * @param mixed $links
	 * @return array
	 */
	public function action_links( $links ) {
		return array_merge( array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=germanized' ) . '">' . __( 'Settings', 'woocommerce-germanized' ) . '</a>',
		), $links );
	}

	/**
	 * Add styles to frontend
	 *
	 * @param array $styles
	 */
	public function add_styles( $styles ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$styles['woocommerce-gzd-layout'] = array(
		'src'     => str_replace( array( 'http:', 'https:' ), '', WC_germanized()->plugin_url() ) . '/assets/css/woocommerce-gzd-layout' . $suffix . '.css',
		'deps'    => '',
		'version' => WC_GERMANIZED_VERSION,
		'media'   => 'all',
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

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path = str_replace( array( 'http:', 'https:' ), '', WC_germanized()->plugin_url() ) . '/assets/';
		$frontend_script_path = $assets_path . 'js/';

		wp_register_script( 'wc-gzd-revocation', $frontend_script_path . 'revocation' . $suffix . '.js', array(
			'jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n'
		), WC_GERMANIZED_VERSION, true );

		wp_register_script( 'wc-gzd-checkout', $frontend_script_path . 'checkout' . $suffix . '.js', array(
			'jquery',
			'wc-checkout',
		), WC_GERMANIZED_VERSION, true );

		wp_register_script( 'wc-gzd-add-to-cart-variation', $frontend_script_path . 'add-to-cart-variation' . $suffix . '.js', array(
			'jquery', 'woocommerce', 'wc-add-to-cart-variation'
		), WC_GERMANIZED_VERSION, true );

		if ( is_page() && is_object( $post ) && has_shortcode( $post->post_content, 'revocation_form' ) ) {
			wp_enqueue_script( 'wc-gzd-revocation' );
		}

		if ( is_checkout() ) {
			wp_enqueue_script( 'wc-gzd-checkout' );
		}

		if ( is_singular( 'product' ) ) {
			$product = wc_get_product( $post->ID );

			if ( $product && $product->is_type( 'variable' ) ) {
				// Enqueue variation scripts
				wp_enqueue_script( 'wc-gzd-add-to-cart-variation' );
			}
		}

		do_action( 'woocommerce_gzd_registered_scripts', $suffix, $frontend_script_path, $assets_path );
	}

	/**
	 * Localize Script to enable AJAX
	 */
	public function localize_scripts() {
		global $wp;

		$assets_path = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';

		if ( wp_script_is( 'wc-gzd-revocation' ) && ! in_array( 'wc-gzd-revocation', $this->localized_scripts ) ) {

			$this->localized_scripts[] = 'wc-gzd-revocation';

			wp_localize_script( 'wc-gzd-revocation', 'wc_gzd_revocation_params', apply_filters( 'wc_gzd_revocation_params', array(
				'ajax_url'                  => WC()->ajax_url(),
				'wc_ajax_url'               => WC_AJAX::get_endpoint( "%%endpoint%%" ),
				'ajax_loader_url'           => apply_filters( 'woocommerce_ajax_loader_url', $assets_path . 'images/ajax-loader@2x.gif' ),
			) ) );
		}

		if ( wp_script_is( 'wc-gzd-add-to-cart-variation' ) && ! in_array( 'wc-gzd-add-to-cart-variation', $this->localized_scripts )  ) {

			$this->localized_scripts[] = 'wc-gzd-add-to-cart-variation';

			wp_localize_script( 'wc-gzd-add-to-cart-variation', 'wc_gzd_add_to_cart_variation_params', apply_filters( 'woocommerce_gzd_add_to_cart_variation_params', array(
				'wrapper'                   => '.type-product',
			) ) );
		}

		do_action( 'woocommerce_gzd_localized_scripts', $assets_path );
	}

	/**
	 * Add WooCommerce Germanized Settings Tab
	 *
	 * @param array $integrations
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
		if ( get_option( 'woocommerce_gzd_small_enterprise' ) == 'yes' ) {
			wc_get_template( 'global/small-business-info.php' );
		}
	}

	/**
	 * PHP 5.3 backwards compatibility for getting date diff in days
	 *
	 * @param  string $from date from
	 * @param  string $to   date to
	 * @return array
	 */
	public function get_date_diff( $from, $to ) {
		$diff = abs( strtotime( $to ) - strtotime( $from ) );
		return array(
			'd' => floor( $diff / ( 60 * 60 * 24 ) ),
		);
	}

	/**
	 * Add Custom Email templates
	 *
	 * @param array $mails
	 * @return array
	 */
	public function add_emails( $mails ) {
		$mails['WC_GZD_Email_Customer_Paid_For_Order'] 			= include 'includes/emails/class-wc-gzd-email-customer-paid-for-order.php';
		$mails['WC_GZD_Email_Customer_New_Account_Activation'] 	= include 'includes/emails/class-wc-gzd-email-customer-new-account-activation.php';
		$mails['WC_GZD_Email_Customer_Revocation'] 				= include 'includes/emails/class-wc-gzd-email-customer-revocation.php';
		$mails['WC_GZD_Email_Customer_Ekomi'] 	 				= include 'includes/emails/class-wc-gzd-email-customer-ekomi.php';
		$mails['WC_GZD_Email_Customer_Trusted_Shops'] 			= include 'includes/emails/class-wc-gzd-email-customer-trusted-shops.php';

		// Make sure the Processing Order Email is named Order Confirmation for better understanding
		if ( isset( $mails[ 'WC_Email_Customer_Processing_Order' ] ) ) {
			$mails[ 'WC_Email_Customer_Processing_Order' ]->title = __( 'Order Confirmation', 'woocommerce-germanized' );
		}
		
		return $mails;
	}

	/**
	 * Filter Email template to include WooCommerce Germanized template files
	 *
	 * @param string $core_file
	 * @param string $template
	 * @param string $template_base
	 * @return string
	 */
	public function email_templates( $core_file, $template, $template_base ) {

		if ( ! file_exists( $template_base . $template ) && file_exists( $this->plugin_path() . '/templates/' . $template ) ) {
			$core_file = $this->plugin_path() . '/templates/' . $template;
		}

		return apply_filters( 'woocommerce_germanized_email_template_hook', $core_file, $template, $template_base );
	}

	public function register_gateways( $gateways ) {

		// Do only load gateway for PHP >= 5.3 because of Namespaces
		if ( version_compare( phpversion(), '5.3', '>=' ) ) {
			$gateways[] = 'WC_GZD_Gateway_Direct_Debit';
		}

		$gateways[] = 'WC_GZD_Gateway_Invoice';

		return $gateways;

	}

	/**
	 * Improve tax display within order totals
	 *
	 * @param  array  $order_totals
	 * @param  object $order
	 * @return array
	 */
	public function order_item_totals( $order_totals, $order ) {

		// Set to formatted total without displaying tax info behind the price
		$order_totals['order_total']['value'] = $order->get_formatted_order_total();

		// Tax for inclusive prices
		if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) && 'incl' == get_option( 'woocommerce_tax_display_cart' ) ) {

			$tax_array = array();
			if ( 'itemized' == get_option( 'woocommerce_tax_total_display' ) ) {

				foreach ( $order->get_tax_totals() as $code => $tax ) {

					$tax->rate = WC_Tax::get_rate_percent( $tax->rate_id );

					if ( ! isset( $tax_array[ $tax->rate ] ) ) {
						$tax_array[ $tax->rate ] = array(
							'tax' => $tax,
							'amount' => $tax->amount,
							'contains' => array( $tax ),
						);
					} else {
						array_push( $tax_array[ $tax->rate ]['contains'], $tax );
						$tax_array[ $tax->rate ]['amount'] += $tax->amount;
					}
				}
			} else {

				$base_rate = WC_Tax::get_shop_base_rate();

				$rate = reset($base_rate);
				$rate_id = key($base_rate);

				$base_rate = (object) $rate;
				$base_rate->rate = $base_rate->rate;
				$base_rate->rate_id = $rate_id;

				$tax_array[] = array(
					'tax' => $base_rate,
					'contains' => array( $base_rate ),
					'amount' => $order->get_total_tax(),
				);
			}

			if ( ! empty( $tax_array ) ) {
				foreach ( $tax_array as $tax ) {

					$order_totals[ 'tax_' . WC_Tax::get_rate_code( $tax['tax']->rate_id ) ] = array(
						'label' => ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ? sprintf( __( 'incl. %s%% VAT', 'woocommerce-germanized' ), wc_gzd_format_tax_rate_percentage( $tax['tax']->rate ) ) : __( 'incl. VAT', 'woocommerce-germanized' ) ),
						'value' => wc_price( $tax['amount'] ),
					);
				}
			}
		}// End if().

		return $order_totals;
	}

	/**
	 * Remove cart unit price subtotal filter
	 */
	public function remove_cart_unit_price_filter() {
		if ( is_cart() ) {
			remove_filter( 'woocommerce_cart_item_subtotal', 'wc_gzd_cart_product_unit_price', 0, 2 );
		}
	}

	/**
	 * Prevent tax class merging. Could lead to future problems - not yet implemented
	 *
	 * @param  string $code    tax class code
	 * @param  int    $rate_id
	 * @return string          unique tax class code
	 */
	public function prevent_tax_name_merge( $code, $rate_id ) {
		return $code . '-' . $rate_id;
	}

	/**
	 * Initialize Trusted Shops Module
	 */
	private function setup_trusted_shops() {
		// Initialize Trusted Shops module
		$this->trusted_shops  = new WC_GZD_Trusted_Shops( $this, array(
			'prefix' 	  	  => 'GZD_',
			'et_params'       => array(
				'utm_campaign' => 'shopsoftware',
				'utm_content' => 'WOOCOMMERCEGERMANIZED',
			),
			'signup_params'	  => array(
				'utm_source' => 'woocommerce-germanized',
				'utm_campaign' => 'woocommerce-germanized',
			),
			'urls'		  	  => array(
				'integration' 		=> 'http://www.trustedshops.de/shopbetreiber/integration/shopsoftware-integration/woocommerce-germanized/',
				'signup' 			=> 'http://www.trustbadge.com/de/Preise/',
				'trustbadge_custom' => 'http://www.trustedshops.de/shopbetreiber/integration/trustbadge/trustbadge-custom/',
				'reviews' 			=> 'http://www.trustedshops.de/shopbetreiber/integration/product-reviews/',
			),
			)
		);
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
