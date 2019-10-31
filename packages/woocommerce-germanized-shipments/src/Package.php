<?php

namespace Vendidero\Germanized\Shipments;

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
    const VERSION = '1.0.2';

    /**
     * Init the package - load the REST API Server class.
     */
    public static function init() {
	    if ( ! self::has_dependencies() ) {
		    return;
	    }

	    self::define_tables();
	    self::init_hooks();
        self::includes();
    }

    protected static function init_hooks() {
	    add_filter( 'woocommerce_data_stores', array( __CLASS__, 'register_data_stores' ), 10, 1 );
	    add_action( 'after_setup_theme', array( __CLASS__, 'include_template_functions' ), 11 );

	    // Filter email templates
	    add_filter( 'woocommerce_gzd_default_plugin_template', array( __CLASS__, 'filter_templates' ), 10, 3 );

	    add_filter( 'woocommerce_get_query_vars', array( __CLASS__, 'register_endpoints' ), 10, 1 );
    }

    public static function register_endpoints( $query_vars ) {
    	$query_vars['view-shipment'] = get_option( 'woocommerce_myaccount_view_shipment_endpoint', 'view-shipment' );

    	return $query_vars;
    }

	public static function install() {
    	self::includes();
		Install::install();
	}

	public static function install_integration() {
		self::install();
	}

	public static function has_dependencies() {
		return class_exists( 'WooCommerce' );
	}

    private static function includes() {

        if ( is_admin() ) {
	        Admin\Admin::init();
        }

        Ajax::init();
        Automation::init();
        Emails::init();
        Validation::init();
        Api::init();

        if ( self::is_frontend_request() ) {
        	include_once self::get_path() . '/includes/wc-gzd-shipment-template-hooks.php';
        }

        include_once self::get_path() . '/includes/wc-gzd-shipment-functions.php';
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
            'gzd_shipment_itemmeta' => 'woocommerce_gzd_shipment_itemmeta',
            'gzd_shipmentmeta'      => 'woocommerce_gzd_shipmentmeta',
            'gzd_shipments'         => 'woocommerce_gzd_shipments',
            'gzd_shipment_items'    => 'woocommerce_gzd_shipment_items',
        );

        foreach ( $tables as $name => $table ) {
            $wpdb->$name    = $wpdb->prefix . $table;
            $wpdb->tables[] = $table;
        }
    }

    public static function register_data_stores( $stores ) {
        $stores['shipment']      = 'Vendidero\Germanized\Shipments\DataStores\Shipment';
        $stores['shipment-item'] = 'Vendidero\Germanized\Shipments\DataStores\ShipmentItem';

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

	public static function get_setting( $name ) {
		$option_name = "woocommerce_gzd_shipments_{$name}";

		return get_option( $option_name );
	}
}
