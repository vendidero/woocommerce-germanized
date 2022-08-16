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
	}

	/**
	 * @param WP_REST_Response $response
	 * @param $post
	 * @param WP_REST_Request $request
	 *
	 * @return mixed
	 */
	public static function prepare_order_shipments( $response, $post, $request ) {
		$order                            = wc_get_order( $post );
		$response_order_data              = $response->get_data();
		$response_order_data['shipments'] = array();

		if ( $order ) {
			$order_shipment = wc_gzd_get_shipment_order( $order );
			$shipments      = $order_shipment->get_shipments();

			if ( ! empty( $shipments ) ) {
				foreach ( $shipments as $shipment ) {
					$response_order_data['shipments'][] = ShipmentsController::prepare_shipment( $shipment, 'view', $request['dp'] );
				}
			}
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
