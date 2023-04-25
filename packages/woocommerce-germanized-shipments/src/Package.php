<?php

namespace Vendidero\Germanized\Shipments;

use Exception;
use Vendidero\Germanized\Shipments\Packaging\ReportHelper;
use Vendidero\Germanized\Shipments\ShippingProvider\Method;
use WC_Shipping;
use WC_Shipping_Method;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {
	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '2.3.1';

	public static $upload_dir_suffix = '';

	protected static $method_settings = null;

	/**
	 * Init the package - load the REST API Server class.
	 */
	public static function init() {
		if ( ! self::has_dependencies() ) {
			return;
		}

		self::define_tables();
		self::maybe_set_upload_dir();
		self::init_hooks();
		self::includes();

		do_action( 'woocommerce_gzd_shipments_init' );
	}

	protected static function init_hooks() {
		add_filter( 'woocommerce_data_stores', array( __CLASS__, 'register_data_stores' ), 10, 1 );
		add_action( 'after_setup_theme', array( __CLASS__, 'include_template_functions' ), 11 );

		// Filter email templates
		add_filter( 'woocommerce_gzd_default_plugin_template', array( __CLASS__, 'filter_templates' ), 10, 3 );

		add_filter( 'woocommerce_get_query_vars', array( __CLASS__, 'register_endpoints' ), 10, 1 );

		if ( ! did_action( 'woocommerce_loaded' ) ) {
			add_action( 'woocommerce_loaded', array( __CLASS__, 'inject_endpoints' ), 10 );
		} else {
			self::inject_endpoints();
		}

		add_action( 'woocommerce_load_shipping_methods', array( __CLASS__, 'load_shipping_methods' ), 5, 1 );
		// Use a high priority here to make sure we are hooking even after plugins such as flexible shipping
		add_filter( 'woocommerce_shipping_methods', array( __CLASS__, 'set_method_filters' ), 5000, 1 );

		// Guest returns
		add_filter( 'wc_get_template', array( __CLASS__, 'add_return_shipment_guest_endpoints' ), 10, 2 );
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
		add_action( 'init', array( __CLASS__, 'check_version' ), 10 );

		add_action( 'woocommerce_gzd_wpml_compatibility_loaded', array( __CLASS__, 'load_wpml_compatibility' ), 10 );
	}

	public static function add_return_shipment_guest_endpoints( $template, $template_name ) {
		global $wp;

		if ( 'myaccount/form-login.php' === $template_name ) {
			try {
				$key      = ( isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$order_id = false;
				$callback = false;

				if ( isset( $wp->query_vars['add-return-shipment'] ) ) {
					$callback = 'woocommerce_gzd_shipments_template_add_return_shipment';
					$order_id = absint( $wp->query_vars['add-return-shipment'] );
				}

				if ( $callback && $order_id && ( $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) && ! empty( $key ) ) {

					// Order return key is invalid.
					if ( ! wc_gzd_customer_can_add_return_shipment( $order_id ) ) {
						throw new Exception( _x( 'Sorry, this order is invalid and cannot be returned.', 'shipments', 'woocommerce-germanized' ) );
					} else {
						call_user_func_array( $callback, array( 'order_id' => $order_id ) );
						$template = self::get_path() . '/templates/global/empty.php';
					}
				}
			} catch ( Exception $e ) {
				wc_add_notice( $e->getMessage(), 'error' );
			}
		}

		return $template;
	}

	public static function register_shortcodes() {
		add_shortcode( 'gzd_return_request_form', array( __CLASS__, 'return_request_form' ) );
	}

	public static function return_request_form( $args = array() ) {
		$defaults = array(
			'message' => '',
			'hidden'  => false,
		);

		$args    = wp_parse_args( $args, $defaults );
		$notices = function_exists( 'wc_print_notices' ) ? wc_print_notices( true ) : '';
		$html    = '';

		// Output notices in case notices have not been outputted yet.
		if ( ! empty( $notices ) ) {
			$html .= '<div class="woocommerce">' . $notices . '</div>';
		}

		$html .= wc_get_template_html( 'global/form-return-request.php', $args );

		return $html;
	}

	public static function load_wpml_compatibility( $compatibility ) {
		WPMLHelper::init( $compatibility );
	}

	public static function get_excluded_methods() {
		return apply_filters( 'woocommerce_gzd_shipments_get_methods_excluded_from_provider_settings', array( 'pr_dhl_paket', 'flexible_shipping_info' ) );
	}

	public static function set_method_filters( $methods ) {
		foreach ( $methods as $method => $class ) {
			if ( in_array( $method, self::get_excluded_methods(), true ) ) {
				continue;
			}

			add_filter( 'woocommerce_shipping_instance_form_fields_' . $method, array( __CLASS__, 'add_method_settings' ), 10, 1 );
			/**
			 * Use this filter as a backup to support plugins like Flexible Shipping which may override methods
			 */
			add_filter( 'woocommerce_settings_api_form_fields_' . $method, array( __CLASS__, 'add_method_settings' ), 10, 1 );
			add_filter( 'woocommerce_shipping_' . $method . '_instance_settings_values', array( __CLASS__, 'filter_method_settings' ), 10, 2 );
		}

		return $methods;
	}

	/**
	 * Indicates whether the BoxPack library for improved packing calculation is supported
	 *
	 * @return bool
	 */
	public static function is_packing_supported() {
		return version_compare( phpversion(), '7.1', '>=' ) && apply_filters( 'woocommerce_gzd_enable_rucksack_packaging', true );
	}

	public static function get_method_settings() {
		if ( is_null( self::$method_settings ) ) {
			self::$method_settings = Method::get_admin_settings();
		}

		return self::$method_settings;
	}

	public static function filter_method_settings( $p_settings, $method ) {
		$shipping_provider_settings = self::get_method_settings();
		$shipping_provider          = isset( $p_settings['shipping_provider'] ) ? $p_settings['shipping_provider'] : '';
		$shipping_method            = wc_gzd_get_shipping_provider_method( $method );

		/**
		 * Make sure the (maybe) new selected provider is used on updating the settings.
		 */
		$shipping_method->set_provider( $shipping_provider );

		foreach ( $p_settings as $setting => $value ) {
			if ( array_key_exists( $setting, $shipping_provider_settings ) ) {
				// Check if setting does neither belong to global setting nor shipping provider prefix
				if ( 'shipping_provider' !== $setting && ! $shipping_method->setting_belongs_to_provider( $setting ) ) {
					unset( $p_settings[ $setting ] );
				} elseif ( $shipping_method->get_fallback_setting_value( $setting ) === $value ) {
					unset( $p_settings[ $setting ] );
				} elseif ( '' === $value ) {
					unset( $p_settings[ $setting ] );
				}
			}
		}

		/**
		 * Filter that returns shipping method settings cleaned from global shipping provider method settings.
		 * This filter might be useful to remove some default setting values from
		 * shipping provider method settings e.g. DHL settings.
		 *
		 * @param array               $p_settings The settings
		 * @param WC_Shipping_Method $method The shipping method instance
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipping_provider_method_clean_settings', $p_settings, $method );
	}

	public static function add_method_settings( $p_settings ) {
		$wc = WC();

		/**
		 * Prevent undefined index notices during REST API calls.
		 *
		 * @see WC_REST_Shipping_Zone_Methods_V2_Controller::get_settings()
		 */
		if ( is_callable( array( $wc, 'is_rest_api_request' ) ) && $wc->is_rest_api_request() ) {
			return $p_settings;
		}

		$shipping_provider_settings = self::get_method_settings();

		return array_merge( $p_settings, $shipping_provider_settings );
	}

	public static function load_shipping_methods( $package ) {
		$shipping = WC_Shipping::instance();

		foreach ( $shipping->shipping_methods as $key => $method ) {
			if ( in_array( $key, self::get_excluded_methods(), true ) ) {
				continue;
			}

			$shipping_provider_method = new Method( $method );
		}
	}

	public static function is_hpos_enabled() {
		if ( ! is_callable( array( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) ) ) {
			return false;
		}

		return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	public static function inject_endpoints() {
		if ( function_exists( 'WC' ) && WC()->query ) {
			foreach ( self::get_endpoints() as $endpoint ) {
				if ( ! array_key_exists( $endpoint, WC()->query->query_vars ) ) {
					$option_name                         = str_replace( '-', '_', $endpoint );
					WC()->query->query_vars[ $endpoint ] = get_option( "woocommerce_gzd_shipments_{$option_name}_endpoint", $endpoint );
				}
			}
		}
	}

	public static function get_base_country() {
		$default_country  = wc_get_base_location()['country'];
		$shipment_country = wc_format_country_state_string( self::get_setting( 'shipper_address_country' ) )['country'];

		if ( empty( $shipment_country ) ) {
			$shipment_country = $default_country;
		}

		return apply_filters( 'woocommerce_gzd_shipment_base_country', $shipment_country );
	}

	public static function get_base_postcode() {
		$default_postcode  = WC()->countries->get_base_postcode();
		$shipment_postcode = self::get_setting( 'shipper_address_postcode' );

		if ( empty( $shipment_postcode ) ) {
			$shipment_postcode = $default_postcode;
		}

		return apply_filters( 'woocommerce_gzd_shipment_base_postcode', $shipment_postcode );
	}

	public static function base_country_belongs_to_eu_customs_area() {
		return self::country_belongs_to_eu_customs_area( self::get_base_country(), self::get_base_postcode() );
	}

	public static function country_belongs_to_eu_customs_area( $country, $postcode = '' ) {
		$country            = wc_strtoupper( $country );
		$eu_countries       = WC()->countries->get_european_union_countries();
		$belongs            = false;
		$postcode           = wc_normalize_postcode( $postcode );
		$postcode_wildcards = wc_get_wildcard_postcodes( $postcode, $country );

		if ( in_array( $country, $eu_countries, true ) ) {
			$belongs = true;
		}

		if ( $belongs ) {
			$exemptions = array(
				'DE' => array(
					'27498', // Helgoland
					'78266', // Büsingen am Hochrhein
				),
				'ES' => array(
					'35*', // Canary Islands
					'38*', // Canary Islands
					'51*', // Ceuta
					'52*', // Melilla
				),
				'GR' => array(
					'63086', // Mount Athos
					'63087', // Mount Athos
				),
				'IT' => array(
					'22060', // Livigno, Campione d’Italia
					'23030', // Lake Lugano
				),
				'FI' => array(
					'AX*', // Åland Islands
				),
				'CY' => array(
					'9*', // Northern Cyprus
					'5*', // Northern Cyprus
				),
			);

			if ( array_key_exists( $country, $exemptions ) ) {
				foreach ( $exemptions[ $country ] as $exempt_postcode ) {
					if ( in_array( $exempt_postcode, $postcode_wildcards, true ) ) {
						$belongs = false;
						break;
					}
				}
			}
		}

		return apply_filters( 'woocommerce_gzd_country_belongs_to_eu_customs_area', $belongs, $country, $postcode );
	}

	public static function is_shipping_international( $country, $args = array() ) {
		$args = self::parse_location_data( $args );
		/**
		 * In case the sender country belongs to EU customs area, a third country needs to lie outside of the EU customs area
		 */
		if ( self::country_belongs_to_eu_customs_area( $args['sender_country'], $args['sender_postcode'] ) ) {
			if ( ! self::country_belongs_to_eu_customs_area( $country, $args['postcode'] ) ) {
				return true;
			}

			return false;
		} else {
			if ( ! self::is_shipping_domestic( $country, $args ) ) {
				return true;
			}

			return false;
		}
	}

	public static function is_shipping_domestic( $country, $args = array() ) {
		$args        = self::parse_location_data( $args );
		$is_domestic = $country === $args['sender_country'];

		/**
		 * If the sender country belongs to EU customs area but the postcode (e.g. Helgoland in DE) not, do not consider domestic shipping
		 */
		if ( $is_domestic && self::country_belongs_to_eu_customs_area( $args['sender_country'], $args['sender_postcode'] ) ) {
			if ( ! self::country_belongs_to_eu_customs_area( $country, $args['postcode'] ) ) {
				$is_domestic = false;
			}
		}

		return $is_domestic;
	}

	private static function parse_location_data( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'postcode'        => '',
				'sender_country'  => self::get_base_country(),
				'sender_postcode' => self::get_base_postcode(),
			)
		);

		return $args;
	}

	/**
	 * Whether shipping is inner EU (from one EU country to another) shipment or not.
	 *
	 * @param $country
	 * @param array $args
	 *
	 * @return mixed|void
	 */
	public static function is_shipping_inner_eu_country( $country, $args = array() ) {
		$args = self::parse_location_data( $args );

		if ( self::is_shipping_domestic( $country, $args ) || ! self::country_belongs_to_eu_customs_area( $args['sender_country'], $args['sender_postcode'] ) ) {
			return false;
		}

		return self::country_belongs_to_eu_customs_area( $country, $args['postcode'] );
	}

	public static function get_endpoints() {
		return array(
			'view-shipment',
			'add-return-shipment',
			'view-shipments',
		);
	}

	public static function register_endpoints( $query_vars ) {
		foreach ( self::get_endpoints() as $endpoint ) {
			if ( ! array_key_exists( $endpoint, $query_vars ) ) {
				$option_name             = str_replace( '-', '_', $endpoint );
				$query_vars[ $endpoint ] = get_option( "woocommerce_gzd_shipments_{$option_name}_endpoint", $endpoint );
			}
		}

		return $query_vars;
	}

	public static function install() {
		self::init();
		Install::install();
	}

	public static function install_integration() {
		self::init();
		self::install();
	}

	public static function maybe_set_upload_dir() {
		// Create a dir suffix
		if ( ! get_option( 'woocommerce_gzd_shipments_upload_dir_suffix', false ) ) {
			self::$upload_dir_suffix = substr( self::generate_key(), 0, 10 );
			update_option( 'woocommerce_gzd_shipments_upload_dir_suffix', self::$upload_dir_suffix );
		} else {
			self::$upload_dir_suffix = get_option( 'woocommerce_gzd_shipments_upload_dir_suffix' );
		}
	}

	public static function is_feature_plugin() {
		return defined( 'WC_GZD_SHIPMENTS_IS_FEATURE_PLUGIN' ) && WC_GZD_SHIPMENTS_IS_FEATURE_PLUGIN;
	}

	public static function check_version() {
		if ( self::is_feature_plugin() && self::has_dependencies() && ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'woocommerce_gzd_shipments_version' ) !== self::get_version() ) ) {
			Install::install();

			do_action( 'woocommerce_gzd_shipments_updated' );
		}
	}

	/**
	 * Generate a unique key.
	 *
	 * @return string
	 */
	protected static function generate_key() {
		$key       = array( ABSPATH, time() );
		$constants = array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT', 'SECRET_KEY' );

		foreach ( $constants as $constant ) {
			if ( defined( $constant ) ) {
				$key[] = constant( $constant );
			}
		}

		shuffle( $key );

		return md5( serialize( $key ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
	}

	public static function log( $message, $type = 'info' ) {
		$enable_logging = defined( 'WP_DEBUG' ) && WP_DEBUG ? true : false;

		/**
		 * Filter that allows adjusting whether to enable or disable
		 * logging for the shipments package
		 *
		 * @param boolean $enable_logging True if logging should be enabled. False otherwise.
		 *
		 * @package Vendidero/Germanized/Shipments
		 */
		if ( ! apply_filters( 'woocommerce_gzd_shipments_enable_logging', $enable_logging ) ) {
			return false;
		}

		$logger = wc_get_logger();

		if ( ! $logger ) {
			return false;
		}

		if ( ! is_callable( array( $logger, $type ) ) ) {
			$type = 'info';
		}

		$logger->{$type}( $message, array( 'source' => 'wc-gzd-shipments' ) );
	}

	public static function get_upload_dir_suffix() {
		return self::$upload_dir_suffix;
	}

	public static function get_upload_dir() {
		self::set_upload_dir_filter();
		$upload_dir = wp_upload_dir();
		self::unset_upload_dir_filter();

		/**
		 * Filter to adjust the upload directory used to store shipment related files. By default
		 * files are stored in a custom directory under wp-content/uploads.
		 *
		 * @param array $upload_dir Array containing `wp_upload_dir` data.
		 *
		 * @since 3.0.1
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipments_upload_dir', $upload_dir );
	}

	public static function get_relative_upload_dir( $path ) {
		self::set_upload_dir_filter();
		$path = _wp_relative_upload_path( $path );
		self::unset_upload_dir_filter();

		/**
		 * Filter to retrieve the relative upload path used for storing shipment related files.
		 *
		 * @param array $path Relative path.
		 *
		 * @since 3.0.1
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipments_relative_upload_dir', $path );
	}

	public static function set_upload_dir_filter() {
		add_filter( 'upload_dir', array( __CLASS__, 'filter_upload_dir' ), 150, 1 );
	}

	public static function unset_upload_dir_filter() {
		remove_filter( 'upload_dir', array( __CLASS__, 'filter_upload_dir' ), 150 );
	}

	public static function get_file_by_path( $file ) {
		// If the file is relative, prepend upload dir.
		if ( $file && 0 !== strpos( $file, '/' ) && ( ( $uploads = self::get_upload_dir() ) && false === $uploads['error'] ) ) {
			$file = $uploads['basedir'] . "/$file";

			return $file;
		} else {
			return $file;
		}
	}

	public static function filter_upload_dir( $args ) {
		$upload_base = trailingslashit( $args['basedir'] );
		$upload_url  = trailingslashit( $args['baseurl'] );

		/**
		 * Filter to adjust the upload path used to store shipment related files. By default
		 * files are stored in a custom directory under wp-content/uploads.
		 *
		 * @param string $path Path to the upload directory.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		$args['basedir'] = apply_filters( 'woocommerce_gzd_shipments_upload_path', $upload_base . 'wc-gzd-shipments-' . self::get_upload_dir_suffix() );
		/**
		 * Filter to adjust the upload URL used to retrieve shipment related files. By default
		 * files are stored in a custom directory under wp-content/uploads.
		 *
		 * @param string $url URL to the upload directory.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		$args['baseurl'] = apply_filters( 'woocommerce_gzd_shipments_upload_url', $upload_url . 'wc-gzd-shipments-' . self::get_upload_dir_suffix() );

		$args['path'] = $args['basedir'] . $args['subdir'];
		$args['url']  = $args['baseurl'] . $args['subdir'];

		return $args;
	}

	public static function has_dependencies() {
		return class_exists( 'WooCommerce' ) && apply_filters( 'woocommerce_gzd_shipments_enabled', true );
	}

	private static function includes() {
		if ( is_admin() ) {
			Admin\Admin::init();
		}

		Ajax::init();
		Automation::init();
		Labels\Automation::init();
		Labels\DownloadHandler::init();
		Emails::init();
		Validation::init();
		Api::init();
		FormHandler::init();
		ReportHelper::init();

		if ( self::is_frontend_request() ) {
			include_once self::get_path() . '/includes/wc-gzd-shipment-template-hooks.php';
		}

		include_once self::get_path() . '/includes/wc-gzd-shipment-functions.php';
		include_once self::get_path() . '/includes/wc-gzd-label-functions.php';
		include_once self::get_path() . '/includes/wc-gzd-packaging-functions.php';
	}

	private static function is_frontend_request() {
		return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
	}

	/**
	 * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
	 */
	public static function include_template_functions() {
		include_once self::get_path() . '/includes/wc-gzd-shipments-template-functions.php';
	}

	public static function filter_templates( $path, $template_name ) {

		if ( file_exists( self::get_path() . '/templates/' . $template_name ) ) {
			$path = self::get_path() . '/templates/' . $template_name;
		}

		return $path;
	}

	/**
	 * Register custom tables within $wpdb object.
	 */
	private static function define_tables() {
		global $wpdb;

		// List of tables without prefixes.
		$tables = array(
			'gzd_shipment_itemmeta'     => 'woocommerce_gzd_shipment_itemmeta',
			'gzd_shipmentmeta'          => 'woocommerce_gzd_shipmentmeta',
			'gzd_shipments'             => 'woocommerce_gzd_shipments',
			'gzd_shipment_labelmeta'    => 'woocommerce_gzd_shipment_labelmeta',
			'gzd_shipment_labels'       => 'woocommerce_gzd_shipment_labels',
			'gzd_shipment_items'        => 'woocommerce_gzd_shipment_items',
			'gzd_shipping_provider'     => 'woocommerce_gzd_shipping_provider',
			'gzd_shipping_providermeta' => 'woocommerce_gzd_shipping_providermeta',
			'gzd_packaging'             => 'woocommerce_gzd_packaging',
			'gzd_packagingmeta'         => 'woocommerce_gzd_packagingmeta',
		);

		foreach ( $tables as $name => $table ) {
			$wpdb->$name    = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
	}

	public static function register_data_stores( $stores ) {
		$stores['shipment']          = 'Vendidero\Germanized\Shipments\DataStores\Shipment';
		$stores['shipment-label']    = 'Vendidero\Germanized\Shipments\DataStores\Label';
		$stores['packaging']         = 'Vendidero\Germanized\Shipments\DataStores\Packaging';
		$stores['shipment-item']     = 'Vendidero\Germanized\Shipments\DataStores\ShipmentItem';
		$stores['shipping-provider'] = 'Vendidero\Germanized\Shipments\DataStores\ShippingProvider';

		do_action( 'woocommerce_gzd_shipments_registered_data_stores' );

		return $stores;
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_url() {
		return plugins_url( '', __DIR__ );
	}

	public static function get_assets_url() {
		return self::get_url() . '/assets';
	}

	public static function get_setting( $name, $default = false ) {
		$option_name = "woocommerce_gzd_shipments_{$name}";

		return get_option( $option_name, $default );
	}

	public static function get_store_address_country() {
		$default = get_option( 'woocommerce_store_country' );

		return $default;
	}

	public static function get_store_address_street() {
		$store_address = wc_gzd_split_shipment_street( get_option( 'woocommerce_store_address' ) );

		return $store_address['street'];
	}

	public static function get_store_address_street_number() {
		$store_address = wc_gzd_split_shipment_street( get_option( 'woocommerce_store_address' ) );

		return $store_address['number'];
	}

	public static function is_valid_datetime( $maybe_datetime, $format = 'Y-m-d' ) {
		if ( ! is_a( $maybe_datetime, 'DateTime' && ! is_numeric( $maybe_datetime ) ) ) {
			if ( ! \DateTime::createFromFormat( $format, $maybe_datetime ) ) {
				return false;
			}
		}

		return true;
	}

	public static function is_valid_mysql_date( $mysql_date ) {
		return ( '0000-00-00 00:00:00' === $mysql_date || null === $mysql_date ) ? false : true;
	}
}
