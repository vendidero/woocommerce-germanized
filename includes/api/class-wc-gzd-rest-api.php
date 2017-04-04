<?php
/**
 * REST Support for Germanized
 *
 * @author vendidero
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_GZD_REST_API {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'init' ), 20 );
	}

	public function init() {
		if ( version_compare( wc_gzd_get_dependencies()->get_plugin_version( 'woocommerce' ), '2.6', '<' ) )
			return;

		$this->rest_api_includes();
		$this->register_rest_routes();
	}

	public function rest_api_includes() {

		// REST API v1 controllers.
		include_once( dirname( __FILE__ ) . '/v1/class-wc-gzd-rest-product-delivery-times-controller.php' );
		include_once( dirname( __FILE__ ) . '/v1/class-wc-gzd-rest-product-price-labels-controller.php' );
		include_once( dirname( __FILE__ ) . '/v1/class-wc-gzd-rest-product-units-controller.php' );

		// REST API controllers.
		include_once( dirname( __FILE__ ) . '/class-wc-gzd-rest-customers-controller.php' );
		include_once( dirname( __FILE__ ) . '/class-wc-gzd-rest-orders-controller.php' );
		include_once( dirname( __FILE__ ) . '/class-wc-gzd-rest-product-delivery-times-controller.php' );
		include_once( dirname( __FILE__ ) . '/class-wc-gzd-rest-product-price-labels-controller.php' );
		include_once( dirname( __FILE__ ) . '/class-wc-gzd-rest-product-units-controller.php' );
		include_once( dirname( __FILE__ ) . '/class-wc-gzd-rest-products-controller.php' );
	}

	public function register_rest_routes() {
		
		$controllers = apply_filters( 'woocommerce_gzd_rest_controller', array(
			'WC_GZD_REST_Product_Delivery_Times_V1_Controller',
			'WC_GZD_REST_Product_Delivery_Times_Controller',
			'WC_GZD_REST_Product_Price_Labels_V1_Controller',
			'WC_GZD_REST_Product_Price_Labels_Controller',
			'WC_GZD_REST_Product_Units_V1_Controller',
			'WC_GZD_REST_Product_Units_Controller',
			'WC_GZD_REST_Customers_Controller',
			'WC_GZD_REST_Orders_Controller',
			'WC_GZD_REST_Products_Controller',
		) );

		foreach ( $controllers as $controller ) {
			WC()->api->$controller = new $controller();

			if ( method_exists( WC()->api->$controller, 'register_routes' ) )
				WC()->api->$controller->register_routes();
			
			if ( method_exists( WC()->api->$controller, 'register_fields' ) )
				WC()->api->$controller->register_fields();
		}
	}

}

WC_GZD_REST_API::instance();
