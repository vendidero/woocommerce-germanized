<?php

namespace Vendidero\Shiptastic;

use Automattic\WooCommerce\Utilities\I18nUtil;
use Exception;
use Vendidero\Shiptastic\Registry\Container;
use Vendidero\Shiptastic\ShippingMethod\MethodHelper;

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
	const VERSION = '4.3.10';

	public static $upload_dir_suffix = '';

	protected static $iso = null;

	protected static $locale = array();

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
		self::load_compatibilities();

		do_action( 'woocommerce_shiptastic_init' );
	}

	protected static function init_hooks() {
		add_filter( 'woocommerce_data_stores', array( __CLASS__, 'register_data_stores' ), 10, 1 );
		add_action( 'after_setup_theme', array( __CLASS__, 'include_template_functions' ), 11 );
		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_feature_compatibility' ) );

		add_filter( 'woocommerce_locate_template', array( __CLASS__, 'filter_templates' ), 50, 3 );
		add_filter( 'woocommerce_get_query_vars', array( __CLASS__, 'register_endpoints' ), 10, 1 );

		if ( ! did_action( 'woocommerce_loaded' ) ) {
			add_action( 'woocommerce_loaded', array( __CLASS__, 'inject_endpoints' ), 10 );
		} else {
			self::inject_endpoints();
		}

		// Guest returns
		add_filter( 'wc_get_template', array( __CLASS__, 'add_return_shipment_guest_endpoints' ), 10, 2 );

		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
		add_action( 'init', array( __CLASS__, 'check_version' ), 10 );
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );
		add_action( 'init', array( __CLASS__, 'load_fallback_compatibility' ) );

		add_filter( 'woocommerce_shipping_method_add_rate_args', array( __CLASS__, 'manipulate_shipping_rates' ), 1000, 2 );
	}

	public static function load_plugin_textdomain() {
		if ( ! self::is_standalone() ) {
			return;
		}

		add_filter( 'plugin_locale', array( __CLASS__, 'support_german_language_variants' ), 10, 2 );
		add_filter( 'load_translation_file', array( __CLASS__, 'force_load_german_language_variant' ), 10, 2 );

		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			// @todo Remove when start supporting WP 5.0 or later.
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'woocommerce-germanized' );

		load_textdomain( 'shiptastic-for-woocommerce', trailingslashit( WP_LANG_DIR ) . 'shiptastic-for-woocommerce/shiptastic-for-woocommerce-' . $locale . '.mo' );
		load_plugin_textdomain( 'shiptastic-for-woocommerce', false, plugin_basename( self::get_path() ) . '/i18n/languages/' );
	}

	public static function force_load_german_language_variant( $file, $domain ) {
		if ( 'shiptastic-for-woocommerce' === $domain && function_exists( 'determine_locale' ) && class_exists( 'WP_Translation_Controller' ) ) {
			$locale     = determine_locale();
			$new_locale = self::get_german_language_variant( $locale );

			if ( $new_locale !== $locale ) {
				$i18n_controller = \WP_Translation_Controller::get_instance();
				$i18n_controller->load_file( $file, $domain, $locale ); // Force loading the determined file in the original locale.
			}
		}

		return $file;
	}

	protected static function get_german_language_variant( $locale ) {
		if ( apply_filters( 'woocommerce_shiptastic_force_de_language', in_array( $locale, array( 'de_CH', 'de_CH_informal', 'de_AT' ), true ) ) ) {
			$locale = apply_filters( 'woocommerce_shiptastic_german_language_variant_locale', 'de_DE' );
		}

		return $locale;
	}

	public static function support_german_language_variants( $locale, $domain ) {
		if ( 'shiptastic-for-woocommerce' === $domain ) {
			$locale = self::get_german_language_variant( $locale );
		}

		return $locale;
	}

	public static function get_locale_info( $country = '' ) {
		if ( function_exists( 'WC' ) && empty( self::$locale ) ) {
			self::$locale = include WC()->plugin_path() . '/i18n/locale-info.php';
		}

		if ( empty( $country ) ) {
			if ( function_exists( 'WC' ) && WC()->customer ) {
				$country = WC()->customer->get_shipping_country() ? WC()->customer->get_shipping_country() : WC()->customer->get_billing_country();
			} else {
				$country = self::get_base_country();
			}
		}

		$locale_info = array_key_exists( $country, self::$locale ) ? self::$locale[ $country ] : array();
		$locale_info = wp_parse_args(
			$locale_info,
			array(
				'weight_unit'    => '',
				'dimension_unit' => '',
				'direction'      => '',
				'default_locale' => '',
			)
		);

		return $locale_info;
	}

	/**
	 * Some label-related plugins, e.g. Swiss Post may have a built-in compatibility
	 * for the WooCommerce Shipment Tracking plugin. Let's mimic/add those basic API functions
	 * to make sure tracking-related info gets updates within shipments too.
	 *
	 * @return void
	 */
	public static function load_fallback_compatibility() {
		if ( ! function_exists( 'wc_st_add_tracking_number' ) ) {
			function wc_st_add_tracking_number( $order_id, $tracking_number, $provider, $date_shipped = null, $custom_url = false ) {
				$tracking_item = array(
					'tracking_provider'    => $provider,
					'custom_tracking_link' => $custom_url,
					'tracking_number'      => $tracking_number,
				);

				Compatibility\ShipmentTracking::transfer_tracking_to_shipment( $tracking_item, $order_id );
			}
		}

		if ( ! function_exists( 'wc_st_delete_tracking_number' ) ) {
			function wc_st_delete_tracking_number( $order_id, $tracking_number, $provider = false ) {
				$tracking_item = array(
					'tracking_number' => $tracking_number,
				);

				Compatibility\ShipmentTracking::remove_tracking_from_shipment( $tracking_item, $order_id );
			}
		}
	}

	/**
	 * Loads the dependency injection container for woocommerce blocks.
	 *
	 * @param boolean $reset Used to reset the container to a fresh instance.
	 *                       Note: this means all dependencies will be
	 *                       reconstructed.
	 */
	public static function container( $reset = false ) {
		static $container;
		if (
			! $container instanceof Container
			|| $reset
		) {
			$container = new Container();

			// register Bootstrap.
			$container->register(
				Bootstrap::class,
				function ( $container ) {
					return new Bootstrap(
						$container
					);
				}
			);
		}
		return $container;
	}

	public static function load_compatibilities() {
		$compatibilities = apply_filters(
			'woocommerce_shiptastic_compatibilities',
			array(
				'bundles'           => '\Vendidero\Shiptastic\Compatibility\Bundles',
				'shipment-tracking' => '\Vendidero\Shiptastic\Compatibility\ShipmentTracking',
				'wpml'              => '\Vendidero\Shiptastic\Compatibility\WPML',
				'translatepress'    => '\Vendidero\Shiptastic\Compatibility\TranslatePress',
			)
		);

		foreach ( $compatibilities as $compatibility ) {
			if ( is_a( $compatibility, '\Vendidero\Shiptastic\Interfaces\Compatibility', true ) ) {
				if ( $compatibility::is_active() ) {
					$compatibility::init();
				}
			}
		}
	}

	public static function manipulate_shipping_rates( $args, $method ) {
		if ( $method = wc_stc_get_shipping_provider_method( $method ) ) {
			$args['meta_data']['_shipping_provider'] = $method->get_shipping_provider();
		}

		return $args;
	}

	public static function add_return_shipment_guest_endpoints( $template, $template_name ) {
		global $wp;

		if ( 'myaccount/form-login.php' === $template_name ) {
			try {
				$key      = ( isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$order_id = false;
				$callback = false;

				if ( isset( $wp->query_vars['add-return-shipment'] ) ) {
					$callback = 'woocommerce_shiptastic_template_add_return_shipment';
					$order_id = absint( $wp->query_vars['add-return-shipment'] );
				}

				if ( $callback && $order_id && ( $order_shipment = wc_stc_get_shipment_order( $order_id ) ) && ! empty( $key ) ) {

					// Order return key is invalid.
					if ( ! wc_stc_customer_can_add_return_shipment( $order_id ) ) {
						throw new Exception( esc_html_x( 'Sorry, this order is invalid and cannot be returned.', 'shipments', 'woocommerce-germanized' ) );
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
		add_shortcode( 'shiptastic_return_request_form', array( __CLASS__, 'return_request_form' ) );
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

	public static function get_method_settings( $force_load_all = false ) {
		wc_deprecated_function( __FUNCTION__, '3.0.0', 'MethodHelper::get_method_settings()' );

		return MethodHelper::get_method_settings( $force_load_all );
	}

	public static function get_excluded_methods() {
		wc_deprecated_function( __FUNCTION__, '3.0.0', 'MethodHelper::get_excluded_methods()' );

		return array();
	}

	/**
	 * Indicates whether the BoxPack library for improved packing calculation is supported
	 *
	 * @return bool
	 */
	public static function is_packing_supported() {
		return version_compare( phpversion(), '7.4', '>=' ) && apply_filters( 'woocommerce_shiptastic_enable_rucksack_packaging', true );
	}

	public static function is_integration() {
		return apply_filters( 'woocommerce_shiptastic_is_integration', false );
	}

	public static function is_pro() {
		return apply_filters( 'woocommerce_shiptastic_is_pro', false );
	}

	/**
	 * @return int[]
	 */
	public static function get_shipping_classes() {
		$term_args = array(
			'taxonomy'     => 'product_shipping_class',
			'hide_empty'   => 0,
			'orderby'      => 'name',
			'hierarchical' => 0,
			'fields'       => 'id=>name',
		);

		$terms = get_terms( $term_args );

		if ( is_wp_error( $terms ) ) {
			return array();
		} else {
			return $terms;
		}
	}

	public static function is_hpos_enabled() {
		if ( ! is_callable( array( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) ) ) {
			return false;
		}

		return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	public static function get_current_payment_gateway() {
		$current_gateway    = WC()->session ? WC()->session->get( 'chosen_payment_method' ) : '';
		$has_block_checkout = has_block( 'woocommerce/checkout' ) || has_block( 'woocommerce/cart' ) || WC()->is_rest_api_request();

		if ( $has_block_checkout ) {
			$current_gateway = WC()->session ? WC()->session->get( 'wc_shiptastic_blocks_chosen_payment_method', '' ) : '';
		}

		return $current_gateway;
	}

	public static function inject_endpoints() {
		if ( function_exists( 'WC' ) && WC()->query ) {
			foreach ( self::get_endpoints() as $endpoint ) {
				if ( ! array_key_exists( $endpoint, WC()->query->query_vars ) ) {
					$option_name                         = str_replace( '-', '_', $endpoint );
					WC()->query->query_vars[ $endpoint ] = get_option( "woocommerce_shiptastic_{$option_name}_endpoint", $endpoint );
				}
			}
		}
	}

	public static function get_country_iso_alpha3( $country_code ) {
		$country_code = strtoupper( $country_code );
		$iso          = self::get_countries_iso_alpha3();

		if ( isset( $iso[ $country_code ] ) ) {
			return $iso[ $country_code ];
		}

		return $country_code;
	}

	protected static function get_countries_iso_alpha3() {
		if ( is_null( self::$iso ) ) {
			self::$iso = include self::get_path() . '/i18n/iso-3.php';
		}

		return (array) self::$iso;
	}

	public static function get_country_iso_alpha2( $country_code ) {
		$country_code = strtoupper( $country_code );
		$iso          = self::get_countries_iso_alpha3();

		if ( in_array( $country_code, $iso, true ) ) {
			return array_search( $country_code, $iso, true );
		}

		return $country_code;
	}

	public static function get_base_country() {
		$default_country  = wc_get_base_location()['country'];
		$shipment_country = wc_format_country_state_string( self::get_setting( 'shipper_address_country' ) )['country'];

		if ( empty( $shipment_country ) ) {
			$shipment_country = $default_country;
		}

		return apply_filters( 'woocommerce_shiptastic_shipment_base_country', $shipment_country );
	}

	public static function get_base_postcode() {
		$default_postcode  = WC()->countries->get_base_postcode();
		$shipment_postcode = self::get_setting( 'shipper_address_postcode' );

		if ( empty( $shipment_postcode ) ) {
			$shipment_postcode = $default_postcode;
		}

		return apply_filters( 'woocommerce_shiptastic_shipment_base_postcode', $shipment_postcode );
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

		return apply_filters( 'woocommerce_shiptastic_country_belongs_to_eu_customs_area', $belongs, $country, $postcode );
	}

	public static function base_country_supports_export_reference_number() {
		$base_country = self::get_base_country();

		return apply_filters( 'woocommerce_shiptastic_base_country_supports_export_reference_number', self::country_belongs_to_eu_customs_area( $base_country ) );
	}

	public static function get_shipping_zone( $country, $args = array() ) {
		$zone = 'int';

		if ( self::is_shipping_domestic( $country, $args ) ) {
			$zone = 'dom';
		} elseif ( self::is_shipping_inner_eu_country( $country, $args ) ) {
			$zone = 'eu';
		}

		return $zone;
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
				$query_vars[ $endpoint ] = get_option( "woocommerce_shiptastic_{$option_name}_endpoint", $endpoint );
			}
		}

		return $query_vars;
	}

	public static function deactivate() {
		Install::deactivate();
	}

	public static function install() {
		self::init();

		if ( ! self::has_dependencies() ) {
			return;
		}

		Install::install();
	}

	public static function install_integration() {
		self::install();
	}

	public static function maybe_set_upload_dir() {
		// Create a dir suffix
		if ( ! get_option( 'woocommerce_shiptastic_upload_dir_suffix', false ) ) {
			self::$upload_dir_suffix = substr( self::generate_key(), 0, 10 );
			update_option( 'woocommerce_shiptastic_upload_dir_suffix', self::$upload_dir_suffix );
		} else {
			self::$upload_dir_suffix = get_option( 'woocommerce_shiptastic_upload_dir_suffix' );
		}
	}

	public static function is_standalone() {
		return defined( 'WC_STC_IS_STANDALONE_PLUGIN' ) && WC_STC_IS_STANDALONE_PLUGIN;
	}

	public static function check_version() {
		if ( self::is_standalone() && self::has_dependencies() && ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'woocommerce_shiptastic_version' ) !== self::get_version() ) ) {
			Install::install();

			do_action( 'woocommerce_shiptastic_updated' );
		}
	}

	public static function get_dimensions_unit_label( $unit ) {
		return class_exists( 'Automattic\WooCommerce\Utilities\I18nUtil' ) ? I18nUtil::get_dimensions_unit_label( $unit ) : $unit;
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

	public static function is_debug_mode() {
		$is_debug_mode = defined( 'WP_DEBUG' ) && WP_DEBUG ? true : false;

		return apply_filters( 'woocommerce_shiptastic_is_debug_mode', $is_debug_mode );
	}

	public static function is_shipping_debug_mode() {
		return apply_filters( 'woocommerce_shiptastic_is_shipping_debug_mode', 'yes' === get_option( 'woocommerce_shipping_debug_mode', 'no' ) );
	}

	public static function is_constant_defined( $constant ) {
		return class_exists( 'Automattic\Jetpack\Constants' ) ? \Automattic\Jetpack\Constants::is_defined( $constant ) : defined( $constant );
	}

	public static function log( $message, $type = 'info', $source = '' ) {
		/**
		 * Filter that allows adjusting whether to enable or disable
		 * logging for the shipments package
		 *
		 * @param boolean $enable_logging True if logging should be enabled. False otherwise.
		 *
		 * @package Vendidero/Shiptastic
		 */
		if ( ! apply_filters( 'woocommerce_shiptastic_enable_logging', self::is_debug_mode() ) ) {
			return;
		}

		$logger = wc_get_logger();

		if ( ! $logger ) {
			return;
		}

		if ( ! is_callable( array( $logger, $type ) ) ) {
			$type = 'info';
		}

		$logger->{$type}( $message, array( 'source' => 'wc-shiptastic' . ( ! empty( $source ) ? '-' . $source : '' ) ) );
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
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_upload_dir', $upload_dir );
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
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_relative_upload_dir', $path );
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

	public static function get_upload_dir_name() {
		return apply_filters( 'woocommerce_shiptastic_upload_dir_name', 'wc-shiptastic-' . self::get_upload_dir_suffix() );
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
		 * @package Vendidero/Shiptastic
		 */
		$args['basedir'] = apply_filters( 'woocommerce_shiptastic_upload_path', $upload_base . self::get_upload_dir_name() );
		/**
		 * Filter to adjust the upload URL used to retrieve shipment related files. By default
		 * files are stored in a custom directory under wp-content/uploads.
		 *
		 * @param string $url URL to the upload directory.
		 *
		 * @package Vendidero/Shiptastic
		 */
		$args['baseurl'] = apply_filters( 'woocommerce_shiptastic_upload_url', $upload_url . self::get_upload_dir_name() );

		$args['path'] = $args['basedir'] . $args['subdir'];
		$args['url']  = $args['baseurl'] . $args['subdir'];

		return $args;
	}

	public static function has_dependencies() {
		return class_exists( 'WooCommerce' );
	}

	private static function includes() {
		self::container()->get( Bootstrap::class );

		if ( self::is_frontend_request() ) {
			include_once self::get_path() . '/includes/wc-stc-template-hooks.php';
		}

		include_once self::get_path() . '/includes/wc-stc-shipment-functions.php';
		include_once self::get_path() . '/includes/wc-stc-label-functions.php';
		include_once self::get_path() . '/includes/wc-stc-packaging-functions.php';
	}

	private static function is_frontend_request() {
		return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
	}

	/**
	 * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
	 */
	public static function include_template_functions() {
		include_once self::get_path() . '/includes/wc-stc-template-functions.php';
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_template_path() {
		return apply_filters( 'woocommerce_shiptastic_template_path', 'shiptastic/' );
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
	public static function filter_templates( $template, $template_name, $template_path ) {
		$default_template_path = apply_filters( 'shiptastic_default_template_path', self::get_path() . '/templates/' . $template_name, $template_name );

		if ( file_exists( $default_template_path ) ) {
			$template_path = self::get_template_path();

			// Check for Theme overrides
			$theme_template = locate_template(
				apply_filters(
					'woocommerce_shiptastic_locate_theme_template_locations',
					array(
						trailingslashit( $template_path ) . $template_name,
					),
					$template_name
				)
			);

			if ( ! $theme_template ) {
				$template = $default_template_path;
			} else {
				$template = $theme_template;
			}
		}

		return $template;
	}

	/**
	 * Register custom tables within $wpdb object.
	 */
	private static function define_tables() {
		global $wpdb;

		// List of tables without prefixes.
		$tables = array(
			'stc_shipment_itemmeta'     => 'woocommerce_stc_shipment_itemmeta',
			'stc_shipmentmeta'          => 'woocommerce_stc_shipmentmeta',
			'stc_shipments'             => 'woocommerce_stc_shipments',
			'stc_shipment_labelmeta'    => 'woocommerce_stc_shipment_labelmeta',
			'stc_shipment_labels'       => 'woocommerce_stc_shipment_labels',
			'stc_shipment_items'        => 'woocommerce_stc_shipment_items',
			'stc_shipping_provider'     => 'woocommerce_stc_shipping_provider',
			'stc_shipping_providermeta' => 'woocommerce_stc_shipping_providermeta',
			'stc_packaging'             => 'woocommerce_stc_packaging',
			'stc_packagingmeta'         => 'woocommerce_stc_packagingmeta',
		);

		foreach ( $tables as $name => $table ) {
			$wpdb->$name    = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
	}

	public static function declare_feature_compatibility() {
		if ( ! self::is_standalone() ) {
			return;
		}

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', trailingslashit( self::get_path() ) . 'shiptastic-for-woocommerce.php', true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', trailingslashit( self::get_path() ) . 'shiptastic-for-woocommerce.php', true );
		}
	}

	public static function register_data_stores( $stores ) {
		$stores['shipment']          = 'Vendidero\Shiptastic\DataStores\Shipment';
		$stores['shipment-label']    = 'Vendidero\Shiptastic\DataStores\Label';
		$stores['packaging']         = 'Vendidero\Shiptastic\DataStores\Packaging';
		$stores['shipment-item']     = 'Vendidero\Shiptastic\DataStores\ShipmentItem';
		$stores['shipping-provider'] = 'Vendidero\Shiptastic\DataStores\ShippingProvider';

		do_action( 'woocommerce_shiptastic_registered_data_stores' );

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
	public static function get_path( $rel_path = '' ) {
		return trailingslashit( dirname( __DIR__ ) ) . $rel_path;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_url( $rel_path = '' ) {
		return trailingslashit( plugins_url( '', __DIR__ ) ) . $rel_path;
	}

	public static function load_blocks() {
		$woo_version = defined( 'WC_VERSION' ) ? WC_VERSION : '1.0.0';

		return version_compare( $woo_version, '8.2.0', '>=' );
	}

	public static function register_script( $handle, $path, $dep = array(), $ver = '', $in_footer = array( 'strategy' => 'defer' ) ) {
		global $wp_version;

		if ( version_compare( $wp_version, '6.3', '<' ) ) {
			$in_footer = true;
		}

		$ver = empty( $ver ) ? self::get_version() : $ver;

		wp_register_script(
			$handle,
			self::get_assets_url( $path ),
			$dep,
			$ver,
			$in_footer
		);
	}

	public static function get_assets_url( $script_or_style ) {
		$assets_url = self::get_url() . '/build';
		$is_debug   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$is_style   = '.css' === substr( $script_or_style, -4 );
		$is_static  = strstr( $script_or_style, 'static/' );

		if ( $is_debug && $is_static && ! $is_style ) {
			$assets_url = self::get_url() . '/assets/js';
		}

		return trailingslashit( $assets_url ) . $script_or_style;
	}

	public static function get_setting( $name, $default_value = false ) {
		$option_name = "woocommerce_shiptastic_{$name}";

		return get_option( $option_name, $default_value );
	}

	public static function get_store_address_country() {
		$default = get_option( 'woocommerce_store_country' );

		return $default;
	}

	public static function get_store_address_street() {
		$store_address = wc_stc_split_shipment_street( get_option( 'woocommerce_store_address' ) );

		return $store_address['street'];
	}

	public static function get_store_address_street_number() {
		$store_address = wc_stc_split_shipment_street( get_option( 'woocommerce_store_address' ) );

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

	public static function extract_args_from_id( $id ) {
		$args = array(
			'shipping_provider_name' => '',
			'shipment_type'          => '',
			'zone'                   => '',
			'setting_group'          => '',
			'setting_name'           => '',
			'meta'                   => '',
		);

		$data = preg_split( '/-([a-z]-[a-zA-Z_0-9]+)-{0,1}/', $id, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		if ( false !== $data ) {
			foreach ( $data as $d ) {
				$arg = substr( $d, 0, 2 );
				$val = substr( $d, 2 );

				if ( 'p-' === $arg ) {
					$args['shipping_provider_name'] = $val;
				} elseif ( 's-' === $arg ) {
					$args['shipment_type'] = $val;
				} elseif ( 'z-' === $arg ) {
					$args['zone'] = $val;
				} elseif ( 'g-' === $arg ) {
					$args['setting_group'] = $val;
				} elseif ( 'n-' === $arg ) {
					$args['setting_name'] = $val;
				} elseif ( 'm-' === $arg ) {
					$args['meta'] = $val;
				}
			}
		}

		return $args;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_i18n_path() {
		return apply_filters( 'woocommerce_shiptastic_get_i18n_path', self::get_path( 'i18n/languages' ) );
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_i18n_textdomain() {
		return apply_filters( 'woocommerce_shiptastic_get_i18n_textdomain', 'woocommerce-germanized' );
	}
}
