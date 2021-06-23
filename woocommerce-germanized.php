<?php
/**
 * Plugin Name: Germanized for WooCommerce
 * Plugin URI: https://www.vendidero.de/woocommerce-germanized
 * Description: Germanized for WooCommerce extends WooCommerce to become a legally compliant store in the german market.
 * Version: 3.5.0
 * Author: vendidero
 * Author URI: https://vendidero.de
 * Requires at least: 4.9
 * Tested up to: 5.8
 * WC requires at least: 3.9
 * WC tested up to: 5.5
 *
 * Text Domain: woocommerce-germanized
 * Domain Path: /i18n/languages/
 *
 * @author vendidero
 */

use Vendidero\Germanized\Autoloader;
use Vendidero\Germanized\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Load core packages and the autoloader.
 *
 * The new packages and autoloader require PHP 5.6+.
 */
if ( version_compare( PHP_VERSION, '5.6.0', '>=' ) ) {
	require __DIR__ . '/src/Autoloader.php';
	require __DIR__ . '/src/Packages.php';

	if ( ! Autoloader::init() ) {
		return;
	}

	Packages::init();
} else {
	function wc_gzd_admin_php_notice() {
		?>
		<div id="message" class="error">
			<p>
			<?php
			printf(
				/* translators: %s is the word upgrade with a link to a support page about upgrading */
				__( 'Germanized requires at least PHP 5.6 to work. Please %s your PHP version.', 'woocommerce-germanized' ),
				'<a href="https://wordpress.org/support/update-php/">' . esc_html__( 'upgrade', 'woocommerce-germanized' ) . '</a>'
			);
			?>
			</p>
		</div>
		<?php
	}

	add_action( 'admin_notices', 'wc_gzd_admin_php_notice', 20 );

	return;
}

if ( ! class_exists( 'WooCommerce_Germanized' ) ) :

	final class WooCommerce_Germanized {

		/**
		 * Current WooCommerce Germanized Version
		 *
		 * @var string
		 */
		public $version = '3.5.0';

		/**
		 * @var WooCommerce_Germanized $instance of the plugin
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
		 * @var WC_GZD_Emails|null
		 */
		public $emails = null;

		public $compatibilities = array();

		private $localized_scripts = array();

		/**
		 * @var WC_GZD_Product_Factory|null
		 */
		public $product_factory = null;

		/**
		 * Main WooCommerceGermanized Instance
		 *
		 * Ensures that only one instance of WooCommerceGermanized is loaded or can be loaded.
		 *
		 * @static
		 * @return WooCommerce_Germanized - Main instance
		 * @see WC_germanized()
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
		 *
		 * @return mixed
		 */
		public function __get( $key ) {
			return $this->$key;
		}

		/**
		 * adds some initialization hooks and inits WooCommerce Germanized
		 */
		public function __construct() {

			// Define constants
			$this->define_constants();

			// Auto-load classes on demand
			if ( function_exists( '__autoload' ) ) {
				spl_autoload_register( '__autoload' );
			}

			spl_autoload_register( array( $this, 'autoload' ) );

			/**
			 * Filter the Germanized dependency manager instance.
			 *
			 * @param WC_GZD_Dependencies $dependency The dependency manager instance.
			 *
			 * @since 1.0.0
			 *
			 */
			$dependencies = apply_filters( 'woocommerce_gzd_dependencies_instance', WC_GZD_Dependencies::instance( $this ) );

			if ( ! $dependencies->is_loadable() ) {
				return;
			}

			/**
			 * Before startup.
			 *
			 * This hook fires right before Germanized includes relevant files for startup.
			 *
			 * @since 1.0.0
			 */
			do_action( 'woocommerce_germanized_before_load' );

			$this->includes();

			// Hooks
			register_activation_hook( __FILE__, array( 'WC_GZD_Install', 'install' ) );
			register_deactivation_hook( __FILE__, array( 'WC_GZD_Install', 'deactivate' ) );

			/**
			 * Make sure the note hooks are available on install and during REST calls.
			 */
			add_action( 'woocommerce_note_updated', array( $this, 'on_update_admin_note' ) );
			add_filter( 'woocommerce_note_statuses', array( $this, 'add_note_statuses' ), 10 );

			/**
			 * Make sure to add emails globally.
			 */
			add_filter( 'woocommerce_email_classes', array( $this, 'add_emails' ), 1 );

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
			add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 12 );

			add_action( 'init', array( $this, 'init' ), 0 );
			add_action( 'init', array( 'WC_GZD_Shortcodes', 'init' ), 2 );

			add_action( 'plugins_loaded', array( $this, 'setup_compatibility' ), 0 );

			// Set template filter directly after load to ensure wc_get_template finds templates
			add_filter( 'woocommerce_locate_template', array( $this, 'filter_templates' ), 1500, 3 );

			$this->units           = new WC_GZD_Units();
			$this->price_labels    = new WC_GZD_Price_Labels();
			$this->product_factory = new WC_GZD_Product_Factory();

			/**
			 * After startup.
			 *
			 * This hook fires right after all relevant files for Germanized has been loaded.
			 *
			 * @since 1.0.0
			 */
			do_action( 'woocommerce_germanized_loaded' );

			if ( did_action( 'woocommerce_loaded' ) ) {
				$this->woocommerce_loaded_includes();
			} else {
				add_action( 'woocommerce_loaded', array( $this, 'woocommerce_loaded_includes' ) );
			}
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

			/**
			 * Initialize Germanized
			 *
			 * This hook fires as soon as Germanized initializes.
			 *
			 * @since 1.0.0
			 */
			do_action( 'before_woocommerce_germanized_init' );

			$this->load_plugin_textdomain();

			if ( get_option( 'woocommerce_gzd_display_checkout_fallback' ) == 'yes' ) {
				add_filter( 'woocommerce_germanized_filter_template', array( $this, 'set_checkout_fallback' ), 10, 3 );
			}

			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings' ) );

			if ( has_action( 'init', 'wc_corona_schedule_event' ) ) {
			    $this->check_corona_notice();
            }

			// Load after WooCommerce Frontend scripts
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ), 15 );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_inline_styles' ), 20 );
			add_action( 'wp_print_scripts', array( $this, 'localize_scripts' ), 5 );
			add_action( 'wp_print_footer_scripts', array( $this, 'localize_scripts' ), 5 );

			add_filter( 'woocommerce_locate_core_template', array( $this, 'email_templates' ), 0, 3 );

			// Payment gateways
			add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateways' ) );

			$this->emails = new WC_GZD_Emails();

			/**
			 * Initialized Germanized
			 *
			 * This hook fires after Germanized has been initialized e.g. textdomain has been loaded and relevant
			 * have been placed.
			 *
			 * @since 1.0.0
			 */
			do_action( 'woocommerce_germanized_init' );
		}

		protected function check_corona_notice() {
		    remove_action( 'init', 'wc_corona_schedule_event' );
		    add_action( 'admin_notices', array( $this, 'show_corona_notice' ), 20 );
        }

        public function show_corona_notice() {
		    $plugin_file = is_plugin_active( 'woocommerce-corona-taxes-master/woocommerce-corona-taxes.php' ) ? 'woocommerce-corona-taxes-master/woocommerce-corona-taxes.php' : 'woocommerce-corona-taxes/woocommerce-corona-taxes.php';
		    $plugin_data = function_exists( 'get_plugin_data' ) ? get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file ) : array();
		    $version     = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '1.0.0';

		    if ( empty( $version ) ) {
		        $version = '1.0.0';
            }

		    if ( version_compare( $version, '1.0.2', '>=' ) ) {
		        return;
            }

	        $deactivate_plugin_url = wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . urlencode( $plugin_file ), 'deactivate-plugin_' . $plugin_file );
		    ?>
            <div id="message" class="error">
                <p><?php printf( __( 'This version of the Corona Helper Plugin includes a bug which could lead to tax rates being added multiple times. Please <a href="%s">deactivate</a> the plugin and check our <a href="%s" target="_blank">blog post</a>.', 'woocommerce-germanized' ), $deactivate_plugin_url, 'https://vendidero.de/senkung-der-mehrwertsteuer-in-woocommerce-im-rahmen-der-corona-pandemie#update-vom-01-07-20' ); ?></p>
            </div>
            <?php
        }

		public function add_note_statuses( $statuses ) {
		    $statuses = array_merge( $statuses, array( 'disabled', 'deactivated' ) );

		    return $statuses;
        }

		/**
         * Add the option which indicates that a notices should be hidden from the admin user.
         *
		 * @param $note_id
		 */
		public function on_update_admin_note( $note_id ) {
			if ( $note = WC_GZD_Admin_Notices::instance()->get_woo_note( $note_id ) ) {
				if ( strpos( $note->get_name(), 'wc-gzd-admin-' ) !== false ) {
					$note_name = str_replace( 'wc-gzd-admin-', '', $note->get_name() );
					$note_name = str_replace( '-notice', '', $note_name );
					$note_name = str_replace( '-', '_', $note_name );

					if ( current_user_can( 'manage_woocommerce' ) ) {
						if ( 'disabled' === $note->get_status() ) {
							update_option( '_wc_gzd_hide_' . $note_name . '_notice', 'yes' );
						} elseif( 'deactivated' === $note->get_status() ) {
							update_option( '_wc_gzd_disable_' . $note_name . '_notice', 'yes' );
						}
                    }
				}
			}
        }

		/**
		 * Auto-load WC_Germanized classes on demand to reduce memory consumption.
		 *
		 * @param mixed $class
		 *
		 * @return void
		 */
		public function autoload( $class ) {

			$original_class = $class;
			$class          = strtolower( $class );

			$matcher = array(
				'wc_gzd_',
				'digitick\sepa',
				'defuse\crypto',
			);

			$is_match = ( str_replace( $matcher, '', $class ) != $class );

			if ( ! $is_match ) {
				return;
			}

			$path = $this->plugin_path() . '/includes/';
			$file = 'class-' . str_replace( '_', '-', $class ) . '.php';

			if ( strpos( $class, 'wc_gzd_admin' ) !== false ) {
				$path = $this->plugin_path() . '/includes/admin/';
			} elseif ( strpos( $class, 'wc_gzd_gateway_' ) !== false ) {
				$path = $this->plugin_path() . '/includes/gateways/' . substr( str_replace( '_', '-', $class ), 15 ) . '/';
			} elseif ( strpos( $class, 'wc_gzd_compatibility' ) !== false ) {
				$path = $this->plugin_path() . '/includes/compatibility/';
			} elseif ( strpos( $class, 'defuse\crypto' ) !== false ) {
				$path = $this->plugin_path() . '/includes/gateways/direct-debit/libraries/php-encryption/';
				$file = ucfirst( str_replace( 'Defuse/Crypto/', '', str_replace( '\\', '/', $original_class ) ) . '.php' );
			} elseif ( strpos( $class, 'digitick\sepa' ) !== false ) {
				$path = $this->plugin_path() . '/includes/gateways/direct-debit/libraries/php-sepa-xml/';
				$file = ucfirst( str_replace( 'Digitick/Sepa/', '', str_replace( '\\', '/', $original_class ) ) . '.php' );
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
			return untrailingslashit( plugins_url( '/', WC_GERMANIZED_PLUGIN_FILE ) );
		}

		/**
		 * Get the plugin path.
		 *
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( WC_GERMANIZED_PLUGIN_FILE ) );
		}

		/**
		 * Get WC Germanized template path
		 *
		 * @return string
		 */
		public function template_path() {
			/**
			 * Filter the default Germanized template path folder.
			 *
			 * ```php
			 * function ex_filter_template_path( $path ) {
			 *      return 'woocommerce-germanized-test/';
			 * }
			 * add_filter( 'woocommerce_gzd_template_path', 'ex_filter_template_path', 10, 1 );
			 * ```
			 *
			 * @param string $path The relative path within your theme directory.
			 *
			 * @since 1.0.0
			 *
			 */
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
			define( 'WC_GERMANIZED_ABSPATH', dirname( WC_GERMANIZED_PLUGIN_FILE ) . '/' );
			define( 'WC_GERMANIZED_VERSION', $this->version );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		private function includes() {

			include_once WC_GERMANIZED_ABSPATH . 'includes/wc-gzd-core-functions.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/wc-gzd-cart-functions.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/wc-gzd-order-functions.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/wc-gzd-legacy-functions.php';

			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-install.php';

			if ( is_admin() ) {

				include_once WC_GERMANIZED_ABSPATH . 'includes/admin/class-wc-gzd-admin.php';
				include_once WC_GERMANIZED_ABSPATH . 'includes/admin/class-wc-gzd-admin-welcome.php';
				include_once WC_GERMANIZED_ABSPATH . 'includes/admin/class-wc-gzd-admin-order.php';
				include_once WC_GERMANIZED_ABSPATH . 'includes/admin/class-wc-gzd-admin-notices.php';
				include_once WC_GERMANIZED_ABSPATH . 'includes/admin/class-wc-gzd-admin-customer.php';
				include_once WC_GERMANIZED_ABSPATH . 'includes/admin/class-wc-gzd-admin-legal-checkboxes.php';
				include_once WC_GERMANIZED_ABSPATH . 'includes/admin/settings/class-wc-gzd-settings-pointers.php';
				include_once WC_GERMANIZED_ABSPATH . 'includes/admin/class-wc-gzd-admin-product-categories.php';
				include_once WC_GERMANIZED_ABSPATH . 'includes/export/class-wc-gzd-product-export.php';
				include_once WC_GERMANIZED_ABSPATH . 'includes/import/class-wc-gzd-product-import.php';
			}

			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/meta-boxes/class-wc-gzd-meta-box-product-data.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/meta-boxes/class-wc-gzd-meta-box-product-data-variable.php';

			if ( $this->is_frontend() ) {
				if ( did_action( 'woocommerce_loaded' ) ) {
					/**
					 * If Pro version is enabled: Make sure we are not including frontend hooks before pro has been loaded.
					 * This is necessary to enable filters for hook priorities to work while adjusting theme-specific elements.
					 */
					if ( $this->is_pro() ) {
						if ( ! did_action( 'woocommerce_gzdp_loaded' ) ) {
							add_action( 'woocommerce_gzdp_loaded', array( $this, 'frontend_includes' ), 5 );
						} else {
							$this->frontend_includes();
						}
					} else {
						$this->frontend_includes();
					}
				} else {
					add_action( 'woocommerce_loaded', array( $this, 'frontend_includes' ), 5 );
				}
			}

			// Encryption helper
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-secret-box-helper.php';
			// Post types
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-post-types.php';
			// Gateway manipulation
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-payment-gateways.php';
			// Template priority
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-hook-priorities.php';
			// Customizer
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-shop-customizer.php';
			// Pricacy
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-privacy.php';

			// Abstracts
			include_once WC_GERMANIZED_ABSPATH . 'includes/abstracts/abstract-wc-gzd-product.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/abstracts/abstract-wc-gzd-taxonomy.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/abstracts/abstract-wc-gzd-compatibility.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/abstracts/abstract-wc-gzd-compatibility-woocommerce-role-based-pricing.php';

			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-product-factory.php';

			// API
			include_once WC_GERMANIZED_ABSPATH . 'includes/api/class-wc-gzd-rest-api.php';

			include_once WC_GERMANIZED_ABSPATH . 'includes/emails/class-wc-gzd-email-helper.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-ajax.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-checkout.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-order-helper.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-customer-helper.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-cache-helper.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-coupon-helper.php';

			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-virtual-vat-helper.php';
		}

		public function woocommerce_loaded_includes() {
			// Checkboxes
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-legal-checkbox.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-legal-checkbox-manager.php';

			// Product Attribute
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-product-attribute.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/class-wc-gzd-product-attribute-helper.php';
		}

		public function is_frontend() {
			return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! $this->is_rest_api_request();
		}

		public function is_rest_api_request() {
			if ( function_exists( 'WC' ) ) {
				$wc = WC();

				if ( is_callable( array( $wc, 'is_rest_api_request' ) ) ) {
					return $wc->is_rest_api_request();
				}
			}

			return false;
		}

		public function setup_compatibility() {
			/**
			 * Filter compatibility classes.
			 *
			 * This filter allows third party developers to register compatibility scripts
			 * for certain plugins or themes. Make sure to include your class accordingly before adding your script.
			 *
			 * @param array[string] $comp Array containing compatibility plugin slug => class name.
			 *
			 * @since 1.9.1
			 *
			 */
			$plugins = apply_filters( 'woocommerce_gzd_compatibilities',
				array(
					'wpml'                                        => 'WC_GZD_Compatibility_WPML',
					'wpml-string-translation'                     => 'WC_GZD_Compatibility_WPML_String_Translation',
					'polylang'                                    => 'WC_GZD_Compatibility_Polylang',
					'woo-poly-integration'                        => 'WC_GZD_Compatibility_Woo_Poly_Integration',
					'woocommerce-dynamic-pricing'                 => 'WC_GZD_Compatibility_WooCommerce_Dynamic_Pricing',
					'woocommerce-product-bundles'                 => 'WC_GZD_Compatibility_WooCommerce_Product_Bundles',
					'woocommerce-product-addons'                  => 'WC_GZD_Compatibility_WooCommerce_Product_Addons',
					'woocommerce-role-based-prices'               => 'WC_GZD_Compatibility_WooCommerce_Role_Based_Prices',
					'woocommerce-role-based-price'                => 'WC_GZD_Compatibility_WooCommerce_Role_Based_Price',
					'woo-discount-rules'                          => 'WC_GZD_Compatibility_Woo_Discount_Rules',
					'woocommerce-gateway-paypal-express-checkout' => 'WC_GZD_Compatibility_WooCommerce_Gateway_Paypal_Express_Checkout',
					'woocommerce-subscriptions'                   => 'WC_GZD_Compatibility_WooCommerce_Subscriptions',
					'woo-paypalplus'                              => 'WC_GZD_Compatibility_Woo_PaypalPlus',
					'elementor-pro'                               => 'WC_GZD_Compatibility_Elementor_Pro',
					'klarna-checkout-for-woocommerce'             => 'WC_GZD_Compatibility_Klarna_Checkout_For_WooCommerce',
					'flexible-checkout-fields'                    => 'WC_GZD_Compatibility_Flexible_Checkout_Fields',
					'woocommerce-all-products-for-subscriptions'  => 'WC_GZD_Compatibility_WooCommerce_All_Products_For_Subscriptions',
					'b2b-market'                                  => 'WC_GZD_Compatibility_B2B_Market',
					'paypal-express-checkout'                     => 'WC_GZD_Compatibility_PayPal_Express_Checkout',
					'woocommerce-memberships'                     => 'WC_GZD_Compatibility_WooCommerce_Memberships',
					'addify-role-based-pricing'                   => 'WC_GZD_Compatibility_Addify_Role_Based_Pricing'
				)
			);

			foreach ( $plugins as $comp => $classname ) {
				if ( class_exists( $classname ) && is_callable( array( $classname, 'is_applicable' ) ) ) {
					if ( $classname::is_applicable() ) {
						$this->compatibilities[ $comp ] = new $classname();
					}
				}
			}
		}

		public function get_compatibility( $name ) {
			return ( isset( $this->compatibilities[ $name ] ) ? $this->compatibilities[ $name ] : false );
		}

		/**
		 * Include required frontend files.
		 */
		public function frontend_includes() {
			include_once WC_GERMANIZED_ABSPATH . 'includes/wc-gzd-template-hooks.php';
		}

		/**
		 * Function used to Init WooCommerceGermanized Template Functions - This makes them pluggable by plugins and themes.
		 */
		public function include_template_functions() {
			include_once WC_GERMANIZED_ABSPATH . 'includes/wc-gzd-template-functions.php';
		}

		/**
		 * Filter WooCommerce Templates to look into /templates before looking within theme folder
		 *
		 * @param string $template
		 * @param string $template_name
		 * @param string $template_path
		 *
		 * @return string
		 */
		public function filter_templates( $template, $template_name, $template_path ) {
			$template_path = $this->template_path();

			// Tweak to make sure Germanized variation script loads when woocommerce_variable_add_to_cart() is called (just like Woo does)
			if ( 'single-product/add-to-cart/variable.php' === $template_name ) {
			    wp_enqueue_script( 'wc-gzd-add-to-cart-variation' );
            }

			// Check for Theme overrides
			$theme_template = locate_template( array(
				trailingslashit( $template_path ) . $template_name,
			) );

			/**
			 * Filters the template name.
			 *
			 * @param string $template_name The template name e.g. checkboxes/default.php
			 *
			 * @since 1.0.0
			 *
			 */
			$template_name = apply_filters( 'woocommerce_gzd_template_name', $template_name );

			/** This filter is documented in woocommerce-germanized.php */
			if ( ! $theme_template && file_exists( apply_filters( 'woocommerce_gzd_default_plugin_template', $this->plugin_path() . '/templates/' . $template_name, $template_name ) ) ) {
				/**
				 * Filter the default plugin template file.
				 *
				 * This file is being loaded as a default template if no theme template was found.
				 *
				 * @since 1.0.0
				 *
				 * @params string $path The absolute path to the template.
				 */
				$template = apply_filters( 'woocommerce_gzd_default_plugin_template', $this->plugin_path() . '/templates/' . $template_name, $template_name );
			} elseif ( $theme_template ) {
				$template = $theme_template;
			}

			/**
			 * Filters the actual loaded template.
			 *
			 * This filter allows filtering the located template path (whether theme or plugin).
			 *
			 * @since 1.0.0
			 *
			 * @params string $template The path to the template.
			 * @params string $template_name The template name e.g. checkboxes/default.php.
			 * @params string $template_path Germanized template path.
			 */
			return apply_filters( 'woocommerce_germanized_filter_template', $template, $template_name, $template_path );
		}

		/**
		 * Get templates which are legally critical
		 *
		 * @return array
		 */
		public function get_critical_templates() {
			/**
			 * Filters critical template which should be prevented from overriding.
			 *
			 * @param array $templates Array containing the template names.
			 *
			 * @since 1.0.0
			 *
			 */
			return apply_filters( 'woocommerce_gzd_important_templates', array() );
		}

		/**
		 * Sets review-order.php fallback (if activated) by filtering template name.
		 *
		 * @param string $template_name
		 *
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
		 * Load WooCommerce Germanized Product Classes instead of WooCommerce builtin Product Classes
		 *
		 * @param string $classname
		 * @param string $product_type
		 * @param string $post_type
		 * @param integer $product_id
		 *
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
		 *        - WP_LANG_DIR/woocommerce-germanized/woocommerce-germanized-LOCALE.mo
		 *        - WP_LANG_DIR/plugins/woocommerce-germanized-LOCALE.mo
		 */
		public function load_plugin_textdomain() {
			if ( function_exists( 'determine_locale' ) ) {
				$locale = determine_locale();
			} else {
				// @todo Remove when start supporting WP 5.0 or later.
				$locale = is_admin() ? get_user_locale() : get_locale();
			}

			$locale = apply_filters( 'plugin_locale', $locale, 'woocommerce-germanized' );

			unload_textdomain( 'woocommerce-germanized' );
			load_textdomain( 'woocommerce-germanized', trailingslashit( WP_LANG_DIR ) . 'woocommerce-germanized/woocommerce-germanized-' . $locale . '.mo' );
			load_plugin_textdomain( 'woocommerce-germanized', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages/' );
		}

		/**
		 * Show action links on the plugin screen
		 *
		 * @param mixed $links
		 *
		 * @return array
		 */
		public function action_links( $links ) {
			return array_merge( array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=germanized' ) . '">' . __( 'Settings', 'woocommerce-germanized' ) . '</a>',
			), $links );
		}

		/**
		 * Add Scripts to frontend
		 */
		public function add_scripts() {
			global $post;

			$suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$assets_path          = WC_germanized()->plugin_url() . '/assets/';
			$frontend_script_path = $assets_path . 'js/';

			wp_register_script( 'wc-gzd-revocation', $frontend_script_path . 'revocation' . $suffix . '.js', array(
				'jquery',
				'woocommerce',
				'wc-country-select',
				'wc-address-i18n'
			), WC_GERMANIZED_VERSION, true );

			wp_register_script( 'wc-gzd-checkout', $frontend_script_path . 'checkout' . $suffix . '.js', array(
				'jquery',
				'wc-checkout',
			), WC_GERMANIZED_VERSION, true );

			if ( function_exists( 'WC' ) ) {
				wp_register_script( 'accounting', WC()->plugin_url() . '/assets/js/accounting/accounting' . $suffix . '.js', array( 'jquery' ), '0.4.2' );
			}

			wp_register_script( 'wc-gzd-single-product', $frontend_script_path . 'single-product' . $suffix . '.js', array(
				'jquery',
				'woocommerce',
                'accounting',
				'wc-single-product',
			), WC_GERMANIZED_VERSION, true );

			wp_register_script( 'wc-gzd-add-to-cart-variation', $frontend_script_path . 'add-to-cart-variation' . $suffix . '.js', array(
				'jquery',
				'woocommerce',
				'wc-add-to-cart-variation'
			), WC_GERMANIZED_VERSION, true );

			wp_register_script( 'wc-gzd-force-pay-order', $frontend_script_path . 'force-pay-order' . $suffix . '.js', array(
				'jquery',
				'jquery-blockui'
			), WC_GERMANIZED_VERSION, true );

			if ( is_page() && is_object( $post ) && has_shortcode( $post->post_content, 'revocation_form' ) ) {
				wp_enqueue_script( 'wc-gzd-revocation' );
			}

			if ( is_checkout() ) {
				wp_enqueue_script( 'wc-gzd-checkout' );
			}

			if ( is_product() ) {
				$product = wc_get_product( $post->ID );

				if ( $product && $product->is_type( 'variable' ) ) {
					// Enqueue variation scripts
					wp_enqueue_script( 'wc-gzd-add-to-cart-variation' );
				}

				if ( apply_filters( 'woocommerce_gzd_refresh_unit_price_on_price_change', true ) ) {
					wp_enqueue_script( 'wc-gzd-single-product' );
				}
			}

			wp_register_style( 'woocommerce-gzd-layout', $assets_path . 'css/layout' . $suffix . '.css', array(), WC_GERMANIZED_VERSION );
			wp_enqueue_style( 'woocommerce-gzd-layout' );

			/**
			 * Register frontend scripts and styles.
			 *
			 * This hook executes right after Germanized has registered and enqueued relevant scripts and styles for the
			 * frontend.
			 *
			 * @param string $suffix The asset suffix e.g. .min in non-debugging mode.
			 * @param string $frontend_script_path The absolute URL to the plugins JS files.
			 * @param string $assets_path The absolute URL to the plugins asset files.
			 *
			 * @since 1.0.0
			 *
			 */
			do_action( 'woocommerce_gzd_registered_scripts', $suffix, $frontend_script_path, $assets_path );
		}

		/**
		 * Adds woocommerce checkout table background highlight color as inline css
		 */
		public function add_inline_styles() {
			$color      = ( get_option( 'woocommerce_gzd_display_checkout_table_color' ) ? get_option( 'woocommerce_gzd_display_checkout_table_color' ) : '#eee' );
			$custom_css = ".woocommerce-checkout .shop_table { background-color: $color; }";

			if ( 'yes' === get_option( 'woocommerce_gzd_display_hide_cart_tax_estimated' ) ) {
				$custom_css .= " p.woocommerce-shipping-destination { display: none; }";
			}

			wp_add_inline_style( 'woocommerce-gzd-layout', $custom_css );
		}

		public function get_variation_script_params() {
		    return apply_filters( 'woocommerce_gzd_add_to_cart_variation_params', array(
			    'wrapper'        => '.type-product',
			    'price_selector' => '.price',
		    ) );
		}

		/**
		 * Localize Script to enable AJAX
		 */
		public function localize_scripts() {
			global $wp;

			if ( wp_script_is( 'wc-gzd-revocation' ) && ! in_array( 'wc-gzd-revocation', $this->localized_scripts ) && function_exists( 'WC' ) ) {

				$this->localized_scripts[] = 'wc-gzd-revocation';
				$wc_assets_path            = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';

				/**
				 * Filters script localization paramaters for the `wc-gzd-revocation` script.
				 *
				 * @param array $params Key => value array containing parameter name and value.
				 *
				 * @since 1.0.0
				 */
				wp_localize_script( 'wc-gzd-revocation', 'wc_gzd_revocation_params', apply_filters( 'wc_gzd_revocation_params', array(
					'ajax_url'        => WC()->ajax_url(),
					'wc_ajax_url'     => WC_AJAX::get_endpoint( "%%endpoint%%" ),
					'ajax_loader_url' => apply_filters( 'woocommerce_ajax_loader_url', $wc_assets_path . 'images/wpspin-2x.gif' ),
				) ) );
			}

			if ( wp_script_is( 'wc-gzd-add-to-cart-variation' ) && ! in_array( 'wc-gzd-add-to-cart-variation', $this->localized_scripts ) ) {
				$this->localized_scripts[] = 'wc-gzd-add-to-cart-variation';

				/**
				 * Filters script localization paramaters for the `wc-gzd-add-to-cart-variation` script.
				 *
				 * @param array $params Key => value array containing parameter name and value.
				 *
				 * @since 1.0.0
				 *
				 */
				wp_localize_script( 'wc-gzd-add-to-cart-variation', 'wc_gzd_add_to_cart_variation_params', $this->get_variation_script_params() );
			}

			if ( wp_script_is( 'wc-gzd-single-product' ) && ! in_array( 'wc-gzd-single-product', $this->localized_scripts ) ) {
				global $post;

			    $this->localized_scripts[] = 'wc-gzd-single-product';

				$params = apply_filters( 'woocommerce_gzd_add_to_cart_variation_params', array(
					'wrapper'        => '.type-product',
					'price_selector' => 'p.price',
				) );

				$params = array_merge( $params, array(
					'ajax_url'                 => WC()->ajax_url(),
					'wc_ajax_url'              => WC_AJAX::get_endpoint( "%%endpoint%%" ),
                    'refresh_unit_price_nonce' => wp_create_nonce( 'wc-gzd-refresh-unit-price' ),
                    'product_id'               => $post ? $post->ID : '',
                    'price_decimal_sep'        => wc_get_price_decimal_separator(),
                    'price_thousand_sep'       => wc_get_price_thousand_separator()
                ) );

				/**
				 * Filters script localization paramaters for the `wc-gzd-single-product` script.
				 *
				 * @param array $params Key => value array containing parameter name and value.
				 *
				 * @since 3.3.0
				 */
				wp_localize_script( 'wc-gzd-single-product', 'wc_gzd_single_product_params', apply_filters( 'woocommerce_gzd_single_product_params', $params ) );
			}

			if ( wp_script_is( 'wc-gzd-force-pay-order' ) && ! in_array( 'wc-gzd-force-pay-order', $this->localized_scripts ) ) {
				global $wp;
				$order_id = absint( $wp->query_vars['order-pay'] );
				$order    = wc_get_order( $order_id );

				$this->localized_scripts[] = 'wc-gzd-force-pay-order';
				$auto_submit			   = true;

				/**
				 * Filters script localization paramaters for the `wc-gzd-force-pay-order` script.
				 *
				 * @param array $params Key => value array containing parameter name and value.
				 *
				 * @since 1.0.0
				 */
				wp_localize_script( 'wc-gzd-force-pay-order', 'wc_gzd_force_pay_order_params', apply_filters( 'wc_gzd_force_pay_order_params', array(
					'order_id'      => $order_id,
					'gateway'       => $order ? $order->get_payment_method() : '',
					'auto_submit'	=> $auto_submit,
					'block_message' => __( 'Pease wait while we are trying to redirect you to the payment provider.', 'woocommerce-germanized' ),
				) ) );
			}

			if ( wp_script_is( 'wc-gzd-checkout' ) && ! in_array( 'wc-gzd-checkout', $this->localized_scripts ) ) {

				$this->localized_scripts[] = 'wc-gzd-checkout';
				$html_id                   = 'legal';
				$hide_input                = false;
				$has_privacy_checkbox      = false;

				if ( $checkbox = wc_gzd_get_legal_checkbox( 'terms' ) ) {
					$html_id    = $checkbox->get_html_id();
					$hide_input = $checkbox->hide_input();
				}

				if ( $checkbox = wc_gzd_get_legal_checkbox( 'privacy' ) ) {
				    $has_privacy_checkbox = in_array( 'checkout', $checkbox->get_locations() );
				}

				/**
				 * Filters script localization paramaters for the `wc-gzd-checkout` script.
				 *
				 * @param array $params Key => value array containing parameter name and value.
				 *
				 * @since 1.0.0
				 *
				 */
				wp_localize_script( 'wc-gzd-checkout', 'wc_gzd_checkout_params', apply_filters( 'wc_gzd_checkout_params', array(
					'adjust_heading'       => true,
					'checkbox_id'          => $html_id,
					'checkbox_hidden'      => $hide_input,
                    'has_privacy_checkbox' => $has_privacy_checkbox,
				) ) );
			}

			/**
			 * Localized scripts.
			 *
			 * This hook fires after Germanized has localized it's scripts.
			 *
			 * @since 1.0.0
			 */
			do_action( 'woocommerce_gzd_localized_scripts' );
		}

		/**
		 * Add WooCommerce Germanized Settings Tab
		 *
		 * @param array $integrations
		 *
		 * @return array
		 */
		public function add_settings( $integrations ) {
			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/settings/abstract-wc-gzd-settings-tab.php';
			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/settings/class-wc-gzd-settings-germanized.php';

			$integrations[] = new WC_GZD_Settings_Germanized();

			return $integrations;
		}

		/**
		 * PHP 5.3 backwards compatibility for getting date diff in days
		 *
		 * @param string $from date from
		 * @param string $to date to
		 *
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
		 *
		 * @return array
		 */
		public function add_emails( $mails ) {

			$mails['WC_GZD_Email_Customer_Paid_For_Order']         = include WC_GERMANIZED_ABSPATH . 'includes/emails/class-wc-gzd-email-customer-paid-for-order.php';
			$mails['WC_GZD_Email_Customer_Cancelled_Order']        = include WC_GERMANIZED_ABSPATH . 'includes/emails/class-wc-gzd-email-customer-cancelled-order.php';
			$mails['WC_GZD_Email_Customer_New_Account_Activation'] = include WC_GERMANIZED_ABSPATH . 'includes/emails/class-wc-gzd-email-customer-new-account-activation.php';
			$mails['WC_GZD_Email_Customer_Revocation']             = include WC_GERMANIZED_ABSPATH . 'includes/emails/class-wc-gzd-email-customer-revocation.php';

			// Make sure the Processing Order Email is named Order Confirmation for better understanding
			if ( isset( $mails['WC_Email_Customer_Processing_Order'] ) ) {
				$mails['WC_Email_Customer_Processing_Order'] = include WC_GERMANIZED_ABSPATH . 'includes/emails/class-wc-gzd-email-customer-processing-order.php';
			}

			// Try to prevent the On Hold Email from being sent even though it is called directly via the trigger method
			if ( wc_gzd_send_instant_order_confirmation() ) {
				if ( isset( $mails['WC_Email_Customer_On_Hold_Order'] ) ) {
					$mails['WC_Email_Customer_On_Hold_Order'] = include WC_GERMANIZED_ABSPATH . 'includes/emails/class-wc-gzd-email-customer-on-hold-order.php';
				}
			}

			$mails['WC_GZD_Email_Customer_SEPA_Direct_Debit_Mandate'] = include WC_GERMANIZED_ABSPATH . 'includes/emails/class-wc-gzd-email-customer-sepa-direct-debit-mandate.php';

			return $mails;
		}

		/**
		 * Filter Email template to include WooCommerce Germanized template files
		 *
		 * @param string $core_file
		 * @param string $template
		 * @param string $template_base
		 *
		 * @return string
		 */
		public function email_templates( $core_file, $template, $template_base ) {

			if ( ! file_exists( $template_base . $template ) && file_exists( $this->plugin_path() . '/templates/' . $template ) ) {
				$core_file = $this->plugin_path() . '/templates/' . $template;
			}

			/**
			 * Filters email templates.
			 *
			 * @param string $core_file The core template file.
			 * @param string $template The template name.
			 * @param string $template_base The template base folder.
			 *
			 * @since 1.0.0
			 *
			 */
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
	}

endif;

/**
 * @return WooCommerce_Germanized $plugin instance
 */
function WC_germanized() {
	return WooCommerce_Germanized::instance();
}

$GLOBALS['woocommerce_germanized'] = WC_germanized();
