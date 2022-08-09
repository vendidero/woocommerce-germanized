<?php
/**
 * REST Support for Germanized
 *
 * @author vendidero
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_GZD_REST_API {

	protected static $_instance = null;

	public static $endpoints = array();

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'init' ), 10 );
	}

	public function init() {
		$this->rest_api_includes();
		$this->register_rest_routes();
	}

	public function rest_api_includes() {
		// REST API v1 controllers.
		include_once dirname( __FILE__ ) . '/v1/class-wc-gzd-rest-product-delivery-times-v1-controller.php';
		include_once dirname( __FILE__ ) . '/v1/class-wc-gzd-rest-product-price-labels-v1-controller.php';
		include_once dirname( __FILE__ ) . '/v1/class-wc-gzd-rest-product-units-v1-controller.php';

		// REST API v1 controllers.
		include_once dirname( __FILE__ ) . '/v2/class-wc-gzd-rest-product-delivery-times-v2-controller.php';
		include_once dirname( __FILE__ ) . '/v2/class-wc-gzd-rest-product-price-labels-v2-controller.php';
		include_once dirname( __FILE__ ) . '/v2/class-wc-gzd-rest-product-units-v2-controller.php';

		// REST API controllers.
		include_once dirname( __FILE__ ) . '/class-wc-gzd-rest-customers-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-rest-orders-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-rest-product-delivery-times-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-rest-product-price-labels-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-rest-product-units-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-rest-products-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-rest-nutrients-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-rest-allergenic-controller.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-rest-product-deposit-types-controller.php';
	}

	public function register_rest_routes() {
		/**
		 * Filter to add new REST controller to Germanized.
		 *
		 * @param array $controllers The controller classes.
		 *
		 * @since 1.8.5
		 *
		 */
		$controllers = apply_filters(
			'woocommerce_gzd_rest_controller',
			array(
				'WC_GZD_REST_Product_Delivery_Times_V1_Controller',
				'WC_GZD_REST_Product_Delivery_Times_Controller',
				'WC_GZD_REST_Product_Price_Labels_V1_Controller',
				'WC_GZD_REST_Product_Price_Labels_Controller',
				'WC_GZD_REST_Product_Deposit_Types_Controller',
				'WC_GZD_REST_Product_Units_V1_Controller',
				'WC_GZD_REST_Product_Units_Controller',
				'WC_GZD_REST_Customers_Controller',
				'WC_GZD_REST_Orders_Controller',
				'WC_GZD_REST_Products_Controller',
				'WC_GZD_REST_Nutrients_Controller',
				'WC_GZD_REST_Allergenic_Controller',
			)
		);

		foreach ( $controllers as $controller ) {
			self::$endpoints[ $controller ] = new $controller();

			if ( method_exists( self::$endpoints[ $controller ], 'register_routes' ) ) {
				self::$endpoints[ $controller ]->register_routes();
			}

			if ( method_exists( self::$endpoints[ $controller ], 'register_fields' ) ) {
				self::$endpoints[ $controller ]->register_fields();
			}
		}
	}
}

WC_GZD_REST_API::instance();
