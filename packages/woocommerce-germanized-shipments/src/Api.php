<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\Shipments;

use Vendidero\Germanized\Shipments\Rest\ShipmentsController;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

class Api {

	public static function init() {
		add_filter( 'woocommerce_rest_api_get_rest_namespaces', array( __CLASS__, 'register_controllers' ) );

		add_filter( 'woocommerce_rest_shop_order_schema', array( __CLASS__, 'order_shipments_schema' ), 10 );
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( __CLASS__, 'prepare_order_shipments' ), 10, 3 );
		add_filter( 'woocommerce_rest_shop_order_schema', array( __CLASS__, 'schema' ) );
	}

	public static function remove_status_prefix( $status ) {
		if ( 'gzd-' === substr( $status, 0, 4 ) ) {
			$status = substr( $status, 4 );
		}

		return $status;
	}

	/**
	 * Extend schema.
	 *
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_customer_schema
	 *
	 * @param array $schema_properties Data used to create the customer.
	 *
	 * @return array
	 */
	public static function schema( $schema_properties ) {
		$statuses = array_map( array( __CLASS__, 'remove_status_prefix' ), array_keys( wc_gzd_get_shipment_order_shipping_statuses() ) );

		$schema_properties['shipping_status'] = array(
			'description' => _x( 'Shipping status', 'shipments', 'woocommerce-germanized' ),
			'type'        => 'string',
			'enum'        => $statuses,
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);

		return $schema_properties;
	}

	/**
	 * @param WP_REST_Response $response
	 * @param $post
	 * @param WP_REST_Request $request
	 *
	 * @return mixed
	 */
	public static function prepare_order_shipments( $response, $post, $request ) {
		$order                                  = wc_get_order( $post );
		$response_order_data                    = $response->get_data();
		$response_order_data['shipments']       = array();
		$response_order_data['shipping_status'] = 'no-shipping-needed';

		if ( $order ) {
			$order_shipment = wc_gzd_get_shipment_order( $order );
			$shipments      = $order_shipment->get_shipments();

			if ( ! empty( $shipments ) ) {
				foreach ( $shipments as $shipment ) {
					$response_order_data['shipments'][] = ShipmentsController::prepare_shipment( $shipment, 'view', $request['dp'] );
				}
			}

			$response_order_data['shipping_status'] = $order_shipment->get_shipping_status();
		}

		$response->set_data( $response_order_data );

		return $response;
	}

	public static function order_shipments_schema( $schema ) {
		$schema['shipments'] = array(
			'description' => _x( 'List of shipments.', 'shipments', 'woocommerce-germanized' ),
			'type'        => 'array',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
			'items'       => ShipmentsController::get_single_item_schema(),
		);

		return $schema;
	}

	protected static function get_shipment_statuses() {
		$statuses = array();

		foreach ( array_keys( wc_gzd_get_shipment_statuses() ) as $status ) {
			$statuses[] = str_replace( 'gzd-', '', $status );
		}

		return $statuses;
	}

	public static function register_controllers( $controller ) {
		$controller['wc/v3']['shipments'] = ShipmentsController::class;

		return $controller;
	}
}
