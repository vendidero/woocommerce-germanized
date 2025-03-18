<?php

namespace Vendidero\Shiptastic\DHL;

use DateTime;
use DateTimeZone;
use Exception;
use Vendidero\Shiptastic\DHL\Api\LabelRest;
use Vendidero\Shiptastic\DHL\Api\LabelSoap;
use Vendidero\Shiptastic\DHL\Api\LocationFinder;
use Vendidero\Shiptastic\DHL\Api\Paket;
use Vendidero\Shiptastic\DHL\Api\ReturnRest;
use Vendidero\Shiptastic\DHL\ShippingProvider\DeutschePost;
use Vendidero\Shiptastic\DHL\ShippingProvider\DHL;
use Vendidero\Shiptastic\DHL\Api\Internetmarke;
use Vendidero\Shiptastic\Registry\Container;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShippingProvider\Helper;

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
	const VERSION = '3.6.1';

	// These are all considered domestic by DHL
	protected static $us_territories = array( 'US', 'GU', 'AS', 'PR', 'UM', 'VI' );

	protected static $holidays = array();

	protected static $api = null;

	protected static $im_api = null;

	protected static $iso = null;

	/**
	 * Init the package - load the REST API Server class.
	 */
	public static function init() {
		if ( self::has_dependencies() ) {
			add_filter( 'woocommerce_shiptastic_shipping_provider_class_names', array( __CLASS__, 'add_shipping_provider_class_name' ), 10, 1 );

			/**
			 * Make sure provider is loaded after main module.
			 */
			if ( ! did_action( 'woocommerce_shiptastic_init' ) ) {
				add_action( 'woocommerce_shiptastic_init', array( __CLASS__, 'on_init' ) );
			} else {
				self::on_init();
			}
		}
	}

	public static function on_init() {
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );
		add_action( 'init', array( __CLASS__, 'check_version' ), 10 );

		add_filter( 'woocommerce_shiptastic_shipment_is_shipping_domestic', array( __CLASS__, 'shipping_domestic' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipment_is_shipping_inner_eu', array( __CLASS__, 'shipping_inner_eu' ), 10, 2 );

		self::includes();
		self::define_tables();

		if ( self::is_enabled() ) {
			self::init_hooks();
		}
	}

	public static function check_version() {
		if ( self::is_standalone() && self::has_dependencies() && ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'woocommerce_shiptastic_dhl_version' ) !== self::get_version() ) ) {
			Install::install();
		}
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

		load_textdomain( 'dhl-for-shiptastic', trailingslashit( WP_LANG_DIR ) . 'dhl-for-shiptastic/dhl-for-shiptastic-' . $locale . '.mo' );
		load_plugin_textdomain( 'dhl-for-shiptastic', false, plugin_basename( self::get_path() ) . '/i18n/languages/' );
	}

	public static function force_load_german_language_variant( $file, $domain ) {
		if ( 'dhl-for-shiptastic' === $domain && function_exists( 'determine_locale' ) && class_exists( 'WP_Translation_Controller' ) ) {
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
		if ( 'dhl-for-shiptastic' === $domain ) {
			$locale = self::get_german_language_variant( $locale );
		}

		return $locale;
	}

	/**
	 * Exclude certain inner-DE shipments (e.g. to Helgoland) from being treated
	 * as international shipments.
	 *
	 * @param boolean $is_shipping_domestic
	 * @param Shipment $shipment
	 *
	 * @return boolean
	 */
	public static function shipping_domestic( $is_shipping_domestic, $shipment ) {
		if ( false === $is_shipping_domestic && in_array( $shipment->get_shipping_provider(), array( 'dhl', 'deutsche_post' ), true ) ) {
			/**
			 * Inner DE to Helgoland are not treated as crossborder
			 */
			if ( 'DE' === \Vendidero\Shiptastic\Package::get_base_country() && 'DE' === $shipment->get_country() ) {
				$is_shipping_domestic = true;
			}
		}

		return $is_shipping_domestic;
	}

	/**
	 * Exclude certain inner-DE shipments (e.g. to Helgoland) from being treated
	 * as international shipments.
	 *
	 * @param boolean $is_shipping_inner_eu
	 * @param Shipment $shipment
	 *
	 * @return boolean
	 */
	public static function shipping_inner_eu( $is_shipping_inner_eu, $shipment ) {
		if ( false === $is_shipping_inner_eu && in_array( $shipment->get_shipping_provider(), array( 'dhl', 'deutsche_post' ), true ) ) {
			/**
			 * Shipments to Helgoland are not treated as crossborder
			 */
			if ( \Vendidero\Shiptastic\Package::base_country_belongs_to_eu_customs_area() && 'DE' === $shipment->get_country() ) {
				$is_shipping_inner_eu = true;
			}
		}

		return $is_shipping_inner_eu;
	}

	public static function has_dependencies() {
		return ( class_exists( '\Vendidero\Shiptastic\Package' ) && \Vendidero\Shiptastic\Package::has_dependencies() && self::base_country_is_supported() && apply_filters( 'woocommerce_shiptastic_dhl_enabled', true ) );
	}

	public static function supports_soap() {
		return class_exists( 'SoapClient' ) ? true : false;
	}

	public static function base_country_is_supported() {
		return in_array( self::get_base_country(), self::get_supported_countries(), true );
	}

	public static function get_supported_countries() {
		return array( 'DE' );
	}

	public static function base_country_supports( $type = 'services' ) {
		$base_country = self::get_base_country();

		if ( 'services' === $type || 'returns' === $type || 'pickup' === $type ) {
			return 'DE' === $base_country;
		}

		return false;
	}

	public static function get_date_de_timezone( $format = 'Y-m-d' ) {
		try {
			$tz_obj         = new DateTimeZone( 'Europe/Berlin' );
			$current_date   = new DateTime( 'now', $tz_obj );
			$date_formatted = $current_date->format( $format );

			return $date_formatted;
		} catch ( Exception $e ) {
			return date( $format ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		}
	}

	public static function get_holidays( $country = 'DE' ) {
		if ( empty( self::$holidays ) ) {
			self::$holidays = include self::get_path() . '/i18n/holidays.php';
		}

		$holidays = self::$holidays;

		if ( ! empty( $country ) ) {
			$holidays = array_key_exists( $country, self::$holidays ) ? self::$holidays[ $country ] : array();
		}

		/**
		 * Filter to adjust dates regarded as holidays for a certain country.
		 *
		 * @param array  $holidays Array containing dates in Y-m-d format.
		 * @param string $country The country as ISO code.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Shiptastic/DHL
		 */
		return apply_filters( 'woocommerce_shiptastic_dhl_holidays', $holidays, $country );
	}

	/**
	 * Register custom tables within $wpdb object.
	 */
	private static function define_tables() {
		global $wpdb;

		// List of tables without prefixes.
		$tables = array(
			'stc_dhl_im_products'         => 'woocommerce_stc_dhl_im_products',
			'stc_dhl_im_product_services' => 'woocommerce_stc_dhl_im_product_services',
		);

		foreach ( $tables as $name => $table ) {
			$wpdb->$name    = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
	}

	public static function is_enabled() {
		return ( self::is_dhl_enabled() || self::is_deutsche_post_enabled() );
	}

	public static function is_dhl_enabled() {
		return Helper::instance()->is_shipping_provider_activated( 'dhl' );
	}

	public static function is_rest_api_request() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	private static function includes() {
		include_once self::get_path() . '/includes/wc-stc-dhl-core-functions.php';

		if ( self::is_enabled() ) {
			self::container()->get( Bootstrap::class );
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

	public static function init_hooks() {
		add_filter( 'woocommerce_shiptastic_shipment_label_types', array( __CLASS__, 'register_label_types' ), 10 );

		add_filter(
			'shiptastic_register_api_instance_dhl_paket_label_rest',
			function () {
				return new LabelRest();
			}
		);

		add_filter(
			'shiptastic_register_api_instance_dhl_paket_return_rest',
			function () {
				return new ReturnRest();
			}
		);

		add_filter(
			'shiptastic_register_api_instance_dhl_location_finder',
			function () {
				return new LocationFinder();
			}
		);

		add_filter(
			'shiptastic_register_api_instance_dhl_paket_label_soap',
			function () {
				return new LabelSoap();
			}
		);

		add_filter(
			'shiptastic_register_api_instance_dhl_paket_parcel_services',
			function () {
				return new \Vendidero\Shiptastic\DHL\Api\ParcelServices();
			}
		);
	}

	public static function register_label_types( $types ) {
		$types[] = 'inlay_return';

		return $types;
	}

	public static function get_default_bank_account_data( $data_key = '' ) {
		$bacs = get_option( 'woocommerce_bacs_accounts' );

		if ( ! empty( $bacs ) && is_array( $bacs ) ) {
			$data = $bacs[0];

			if ( isset( $data[ 'account_' . $data_key ] ) ) {
				return $data[ 'account_' . $data_key ];
			} elseif ( isset( $data[ $data_key ] ) ) {
				return $data[ $data_key ];
			}
		}

		return '';
	}

	/**
	 * @return false|DHL
	 */
	public static function get_dhl_shipping_provider() {
		$provider = wc_stc_get_shipping_provider( 'dhl' );

		if ( ! is_a( $provider, '\Vendidero\Shiptastic\DHL\ShippingProvider\DHL' ) ) {
			return false;
		}

		return $provider;
	}

	/**
	 * @return false|DeutschePost
	 */
	public static function get_deutsche_post_shipping_provider() {
		$provider = wc_stc_get_shipping_provider( 'deutsche_post' );

		if ( ! is_a( $provider, '\Vendidero\Shiptastic\DHL\ShippingProvider\DeutschePost' ) ) {
			return false;
		}

		return $provider;
	}

	public static function eur_to_cents( $price ) {
		return round( $price * 100 );
	}

	public static function cents_to_eur( $price ) {
		return $price > 0 ? $price / 100 : 0;
	}

	public static function add_shipping_provider_class_name( $class_names ) {
		$class_names['dhl']           = '\Vendidero\Shiptastic\DHL\ShippingProvider\DHL';
		$class_names['deutsche_post'] = '\Vendidero\Shiptastic\DHL\ShippingProvider\DeutschePost';

		return $class_names;
	}

	public static function install() {
		if ( self::has_dependencies() ) {
			self::init();
			Install::install();
		}
	}

	public static function install_integration() {
		self::install();
	}

	public static function is_integration() {
		return \Vendidero\Shiptastic\Package::is_integration() ? true : false;
	}

	public static function is_standalone() {
		return defined( 'WC_DHL_FOR_STC_IS_STANDALONE_PLUGIN' ) && WC_DHL_FOR_STC_IS_STANDALONE_PLUGIN;
	}

	public static function get_api() {
		if ( is_null( self::$api ) ) {
			self::$api = new Paket( self::is_debug_mode() );
		}

		return self::$api;
	}

	/**
	 * @return Internetmarke|null
	 */
	public static function get_internetmarke_api() {
		if ( is_null( self::$im_api ) ) {
			self::$im_api = new Internetmarke();
		}

		return self::$im_api;
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
	public static function get_i18n_path() {
		return apply_filters( 'woocommerce_shiptastic_dhl_get_i18n_path', self::get_path( 'i18n/languages' ) );
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_i18n_textdomain() {
		return apply_filters( 'woocommerce_shiptastic_dhl_get_i18n_textdomain', 'woocommerce-germanized' );
	}

	public static function get_template_path() {
		return \Vendidero\Shiptastic\Package::get_template_path();
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_url( $rel_path = '' ) {
		return trailingslashit( plugins_url( '', __DIR__ ) ) . $rel_path;
	}

	public static function get_assets_url() {
		return self::get_url() . '/assets';
	}

	public static function register_script( $handle, $path, $dep = array(), $ver = '', $in_footer = array( 'strategy' => 'defer' ) ) {
		global $wp_version;

		if ( version_compare( $wp_version, '6.3', '<' ) ) {
			$in_footer = true;
		}

		$ver = empty( $ver ) ? self::get_version() : $ver;

		wp_register_script(
			$handle,
			self::get_assets_build_url( $path ),
			$dep,
			$ver,
			$in_footer
		);
	}

	public static function get_assets_build_url( $script_or_style ) {
		$assets_url = self::get_url() . '/build';
		$is_debug   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$is_style   = '.css' === substr( $script_or_style, -4 );
		$is_static  = strstr( $script_or_style, 'static/' );

		if ( $is_debug && $is_static && ! $is_style ) {
			$assets_url = self::get_url() . '/assets/js';
		}

		return trailingslashit( $assets_url ) . $script_or_style;
	}

	public static function is_debug_mode() {
		$is_debug_mode = ( defined( 'WC_STC_DHL_DEBUG' ) && WC_STC_DHL_DEBUG );

		if ( ! $is_debug_mode && ( $provider = self::get_dhl_shipping_provider() ) ) {
			$is_debug_mode = $provider->is_sandbox();
		}

		return $is_debug_mode;
	}

	public static function enable_logging() {
		return ( defined( 'WC_STC_DHL_LOG_ENABLE' ) && WC_STC_DHL_LOG_ENABLE ) || self::is_debug_mode();
	}

	public static function get_geschaeftskunden_portal_url() {
		return 'https://geschaeftskunden.dhl.de';
	}

	public static function get_internetmarke_main_url() {
		return 'https://internetmarke.deutschepost.de/OneClickForAppV3?wsdl';
	}

	public static function get_internetmarke_products_url() {
		return 'https://prodws.deutschepost.de/ProdWSProvider_1_1/prodws?wsdl';
	}

	public static function get_internetmarke_refund_url() {
		return 'https://internetmarke.deutschepost.de/OneClickForRefund?wsdl';
	}

	public static function get_internetmarke_partner_id() {
		return 'AVHGE';
	}

	public static function get_internetmarke_token() {
		return 'XfQwh0tsdDF81IfpCKEPigk1US46rabN';
	}

	public static function get_internetmarke_key_phase() {
		return 1;
	}

	public static function get_internetmarke_product_username() {
		return 'vendidero';
	}

	public static function get_internetmarke_product_password() {
		return 'A&5%bk?dx8';
	}

	public static function get_internetmarke_product_mandant_id() {
		return 'VENDIDERO';
	}

	public static function is_deutsche_post_enabled() {
		return Helper::instance()->is_shipping_provider_activated( 'deutsche_post' );
	}

	public static function get_internetmarke_username() {
		if ( self::is_debug_mode() && defined( 'WC_STC_DHL_IM_SANDBOX_USER' ) ) {
			return WC_STC_DHL_IM_SANDBOX_USER;
		} else {
			return self::get_setting( 'deutsche_post_api_username' );
		}
	}

	public static function get_internetmarke_password() {
		if ( self::is_debug_mode() && defined( 'WC_STC_DHL_IM_SANDBOX_PASSWORD' ) ) {
			return WC_STC_DHL_IM_SANDBOX_PASSWORD;
		} else {
			return self::get_setting( 'deutsche_post_api_password' );
		}
	}

	/**
	 * CIG (SOAP) Authentication (basic auth) user. In Sandbox mode use Developer ID and password of entwickler.dhl.de
	 *
	 * @return mixed|string|void
	 */
	public static function get_cig_user( $is_sandbox = false ) {
		if ( $is_sandbox ) {
			$debug_user = defined( 'WC_STC_DHL_SANDBOX_USER' ) ? WC_STC_DHL_SANDBOX_USER : self::get_setting( 'api_sandbox_username' );
			$debug_user = strtolower( $debug_user );

			return $debug_user;
		} else {
			return 'woo_germanized_2';
		}
	}

	/**
	 * CIG (SOAP) Authentication (basic auth) password. In Sandbox mode use Developer ID and password of entwickler.dhl.de
	 *
	 * @return mixed|string|void
	 */
	public static function get_cig_password( $is_sandbox = false ) {
		if ( $is_sandbox ) {
			return defined( 'WC_STC_DHL_SANDBOX_PASSWORD' ) ? WC_STC_DHL_SANDBOX_PASSWORD : self::get_setting( 'api_sandbox_password' );
		} else {
			return '8KdXFjxwY0I1oOEo28Jk997tS5Rkky';
		}
	}

	/**
	 * GK Auth user
	 *
	 * @return mixed|string|void
	 */
	public static function get_gk_api_user( $is_sandbox = false ) {
		if ( $is_sandbox ) {
			$user = 'user-valid';
		} else {
			$user = self::get_setting( 'api_username' );
		}

		return strtolower( $user );
	}

	/**
	 * GK Auth password
	 *
	 * @return mixed|string|void
	 */
	public static function get_gk_api_signature( $is_sandbox = false ) {
		return $is_sandbox ? 'SandboxPasswort2023!' : self::get_setting( 'api_password' );
	}

	public static function use_legacy_soap_api() {
		$use_legacy_soap    = false;
		$has_custom_setting = false;

		if ( $dhl = wc_stc_get_shipping_provider( 'dhl' ) ) {
			if ( $dhl->get_setting( 'api_type' ) ) {
				$use_legacy_soap    = 'soap' === $dhl->get_setting( 'api_type' );
				$has_custom_setting = true;

				if ( defined( 'WC_STC_DHL_LEGACY_SOAP' ) ) {
					$use_legacy_soap = WC_STC_DHL_LEGACY_SOAP;
				}
			}
		}

		if ( ! $has_custom_setting ) {
			$use_legacy_soap = ( defined( 'WC_STC_DHL_LEGACY_SOAP' ) ? WC_STC_DHL_LEGACY_SOAP : ( 'yes' === get_option( 'woocommerce_shiptastic_dhl_enable_legacy_soap' ) ) );
		}

		if ( ! self::supports_soap() ) {
			$use_legacy_soap = false;
		}

		return apply_filters( 'woocommerce_shiptastic_dhl_use_legacy_soap_api', $use_legacy_soap );
	}

	public static function get_return_receivers() {
		$receiver = self::get_setting( 'retoure_receiver_ids' );

		if ( ! empty( $receiver ) ) {
			return (array) $receiver;
		} else {
			return array();
		}
	}

	public static function get_return_receiver_by_slug( $slug ) {
		$receivers = self::get_return_receivers();

		if ( array_key_exists( sanitize_key( $slug ), $receivers ) ) {
			return $receivers[ $slug ];
		}

		return false;
	}

	public static function get_return_receiver_by_country( $country ) {
		$receivers         = self::get_return_receivers();
		$country_receiver  = false;
		$fallback_receiver = false;

		foreach ( $receivers as $receiver ) {
			$receiver_country = empty( $receiver['country'] ) ? $receiver['id'] : $receiver['country'];

			if ( ! $fallback_receiver && empty( $receiver['country'] ) ) {
				$fallback_receiver = $receiver;
			}

			if ( $receiver_country === $country ) {
				$country_receiver = $receiver;
			}
		}

		if ( ! $country_receiver ) {
			$country_receiver = $fallback_receiver;
		}

		/**
		 * Returns the DHL retoure receiver id for a certain country.
		 *
		 * @param array  $receiver The receiver to be used for the retoure.
		 * @param string $country The country code of the retoure.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Shiptastic/DHL
		 */
		return apply_filters( 'woocommerce_shiptastic_dhl_retoure_receiver', $country_receiver, $country );
	}

	public static function get_dhl_com_api_key() {
		return 'uwi1SH5bHDdMTdcWXB5JIsDCvBOyIawn';
	}

	public static function get_core_wsdl_file( $file ) {
		$file = basename( $file );
		$file = str_replace( '?wsdl', '', $file );

		// Add .wsdl as default file extension in case missing (e.g. url)
		if ( '.wsdl' !== substr( $file, -5 ) && '.xsd' !== substr( $file, -4 ) ) {
			$file .= '.wsdl';
		}

		$file       = sanitize_file_name( $file );
		$local_file = trailingslashit( self::get_path() ) . 'assets/wsdl/' . $file;

		if ( file_exists( $local_file ) ) {
			return $local_file;
		}

		return null;
	}

	/**
	 * Retrieves a local, cached, WSDL file by a WSDL link.
	 * In case the file exists in core direction (assets/wsdl) prefer this file otherwise
	 * try to download and cache the WSDL file locally for 14 days.
	 *
	 * In case of enabled debug mode - use WSDL link instead of cached files.
	 *
	 * @param $wsdl_link
	 *
	 * @return false|mixed|string|void
	 */
	public static function get_wsdl_file( $wsdl_link ) {
		if ( self::is_debug_mode() ) {
			return $wsdl_link;
		}

		$main_file      = basename( $wsdl_link );
		$required_files = array( $main_file );

		// Some WSDLs may require multiple files
		if ( strpos( $wsdl_link, 'geschaeftskundenversand-api' ) !== false ) {
			$required_files = array(
				$main_file,
				str_replace( '.wsdl', '-schema-cis_base.xsd', $main_file ),
				str_replace( '.wsdl', '-schema-bcs_base.xsd', $main_file ),
			);
		}

		$file_link     = $wsdl_link;
		$transient     = 'wc_stc_dhl_wsdl_' . sanitize_key( $main_file );
		$new_file_name = $main_file;
		$files_exist   = true;
		$is_zip        = false;
		$is_core_file  = false;

		// Renew files every 14 days
		$transient_valid = DAY_IN_SECONDS * 14;

		if ( count( $required_files ) > 1 ) {
			$file_link     = str_replace( '.wsdl', '.zip', $file_link );
			$new_file_name = str_replace( '.wsdl', '.zip', $new_file_name );
			$is_zip        = true;
		}

		if ( $file_path = self::get_core_wsdl_file( $main_file ) ) {
			$files_exist  = true;
			$is_core_file = true;
		} else {
			/**
			 * Check if all required files exist locally
			 */
			foreach ( $required_files as $file ) {
				$inner_transient = 'wc_stc_dhl_wsdl_' . sanitize_key( $file );
				$file_path       = get_transient( $inner_transient );

				if ( $file_path ) {
					$file_path = \Vendidero\Shiptastic\Package::get_file_by_path( $file_path );
				}

				if ( ! $file_path || ! file_exists( $file_path ) ) {
					$files_exist = false;
				}
			}

			$file_path = get_transient( $transient );
		}

		/**
		 * This filter may be used to force loading an alternate (local) WSDL file
		 * for a certain API endpoint. By default we are trying to locally store necessary files
		 * to reduce API calls. Transients/files are renewed once per day.
		 *
		 * @param boolean|string $alternate_file In case an alternate file should be used this must be the absolute path.
		 * @param string         $wsdl_link The link to the original WSDL file.
		 *
		 * @since 3.1.2
		 * @package Vendidero/Shiptastic/DHL
		 */
		$alternate_file = apply_filters( 'woocommerce_shiptastic_dhl_alternate_wsdl_file', false, $wsdl_link );

		if ( ( $files_exist && $file_path ) || $alternate_file ) {
			if ( $is_core_file ) {
				$wsdl_link = $alternate_file ? $alternate_file : $file_path;
			} else {
				$wsdl_link = $alternate_file ? $alternate_file : \Vendidero\Shiptastic\Package::get_file_by_path( $file_path );
			}
		} else {
			if ( ! function_exists( 'download_url' ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';
			}

			if ( function_exists( 'download_url' ) && function_exists( 'unzip_file' ) ) {
				/**
				 * Some URLs like https://prodws.deutschepost.de:8443/ProdWSProvider_1_1/prodws?wsdl might
				 * be rejected due to not using standard SSL ports, e.g.: 8443. Allow them anyway.
				 */
				add_filter(
					'http_request_args',
					function ( $args, $url ) use ( $file_link ) {
						if ( $url === $file_link ) {
							$args['reject_unsafe_urls'] = false;
						}

						return $args;
					},
					10,
					2
				);

				$tmp_file = download_url( $file_link, 1500 );

				if ( ! is_wp_error( $tmp_file ) ) {
					$uploads    = \Vendidero\Shiptastic\Package::get_upload_dir();
					$new_file   = trailingslashit( $uploads['path'] ) . $new_file_name;
					$has_copied = @copy( $tmp_file, $new_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

					if ( $has_copied ) {
						if ( $is_zip ) {
							global $wp_filesystem;

							if ( ! $wp_filesystem ) {
								WP_Filesystem();
							}

							$unzipfile = unzip_file( $new_file, $uploads['path'] );

							if ( ! is_wp_error( $unzipfile ) ) {
								$files_exist   = true;
								$new_wsdl_link = false;

								foreach ( $required_files as $file ) {
									$transient = 'wc_stc_dhl_wsdl_' . sanitize_key( $file );
									$file_path = $uploads['path'] . "/$file";

									if ( file_exists( $file_path ) ) {
										set_transient( $transient, \Vendidero\Shiptastic\Package::get_relative_upload_dir( $file_path ), $transient_valid );

										if ( $file === $main_file ) {
											$new_wsdl_link = $file_path;
										}
									} else {
										$files_exist = false;
									}
								}

								if ( $files_exist && $new_wsdl_link ) {
									$wsdl_link = $new_wsdl_link;
								}
							}

							@unlink( $new_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
						} else {
							$transient = 'wc_stc_dhl_wsdl_' . sanitize_key( $main_file );
							$file_path = $uploads['path'] . "/$main_file";

							if ( file_exists( $file_path ) ) {
								set_transient( $transient, \Vendidero\Shiptastic\Package::get_relative_upload_dir( $file_path ), $transient_valid );
								$wsdl_link = $file_path;
							}
						}

						@unlink( $tmp_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
					}
				}
			}
		}

		return $wsdl_link;
	}

	public static function get_account_number( $is_sandbox = false ) {
		$account_number = '';

		if ( $is_sandbox ) {
			$account_number = '3333333333';
		} elseif ( $provider = self::get_dhl_shipping_provider() ) {
			$account_number = $provider->get_setting( 'account_number' );
		}

		return $account_number;
	}

	public static function get_participation_number( $product, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'services'   => array(),
				'is_sandbox' => false,
			)
		);

		$has_gogreen = in_array( 'GoGreen', $args['services'], true );

		if ( $args['is_sandbox'] ) {
			$participation = '01';

			if ( $has_gogreen ) {
				$participation = '02';
			}

			if ( 'V01PAK' === $product ) {
				$participation = '02';

				if ( $has_gogreen ) {
					$participation = '03';
				}
			} elseif ( 'V66WPI' === $product && $has_gogreen ) {
				$participation = '04';
			}
		} else {
			$participation = self::get_setting( 'participation_' . $product );

			if ( $has_gogreen ) {
				$participation_gogreen = self::get_setting( 'participation_gogreen_' . $product );

				if ( ! empty( $participation_gogreen ) ) {
					$participation = $participation_gogreen;
				}
			}
		}

		return $participation;
	}

	/**
	 * @param $name
	 *
	 * @return mixed|void
	 */
	public static function get_setting( $name, $shipment = false, $default_value = false ) {
		$is_dp = false;
		$value = $default_value;

		if ( substr( $name, 0, 4 ) === 'dhl_' ) {
			$name = substr( $name, 4 );
		} elseif ( substr( $name, 0, 14 ) === 'deutsche_post_' ) {
			$name  = substr( $name, 14 );
			$is_dp = true;
		}

		if ( ! $is_dp ) {
			if ( $provider = self::get_dhl_shipping_provider() ) {
				$value = $provider->get_setting( $name, $default_value );
			}
		} elseif ( $provider = self::get_deutsche_post_shipping_provider() ) {
			$value = $provider->get_setting( $name, $default_value );
		}

		return $value;
	}

	public static function log( $message, $type = 'info' ) {
		\Vendidero\Shiptastic\Package::log( $message, $type, 'dhl' );
	}

	public static function get_available_countries() {
		return array( 'DE' => _x( 'Germany', 'dhl', 'woocommerce-germanized' ) );
	}

	public static function get_base_country() {
		/**
		 * Filter to adjust the DHL base country.
		 *
		 * @param string $country The country as ISO code.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Shiptastic/DHL
		 */
		return apply_filters( 'woocommerce_shiptastic_dhl_base_country', \Vendidero\Shiptastic\Package::get_base_country() );
	}

	public static function get_us_territories() {
		return self::$us_territories;
	}

	/**
	 * Function return whether the sender and receiver country is the same territory
	 */
	public static function is_shipping_domestic( $country_receiver, $postcode = '' ) {
		$is_domestic = \Vendidero\Shiptastic\Package::is_shipping_domestic( $country_receiver, array( 'postcode' => $postcode ) );

		/**
		 * Shipments from DE to Helgoland are not treated as crossborder
		 */
		if ( ! $is_domestic && 'DE' === $country_receiver && \Vendidero\Shiptastic\Package::base_country_belongs_to_eu_customs_area() ) {
			$is_domestic = true;
		}

		return $is_domestic;
	}

	/**
	 * Check if it is an EU shipment
	 */
	public static function is_eu_shipment( $country_receiver, $postcode = '' ) {
		return \Vendidero\Shiptastic\Package::is_shipping_inner_eu_country( $country_receiver, array( 'postcode' => $postcode ) );
	}

	/**
	 * Function return whether the sender and receiver country is "crossborder" i.e. needs CUSTOMS declarations (outside EU)
	 */
	public static function is_crossborder_shipment( $country_receiver, $postcode = '' ) {
		$is_crossborder = \Vendidero\Shiptastic\Package::is_shipping_international( $country_receiver, array( 'postcode' => $postcode ) );

		/**
		 * Shipments from DE to Helgoland are not treated as crossborder
		 */
		if ( $is_crossborder && 'DE' === $country_receiver && \Vendidero\Shiptastic\Package::base_country_belongs_to_eu_customs_area() ) {
			$is_crossborder = false;
		}

		return $is_crossborder;
	}
}
