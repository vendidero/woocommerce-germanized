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
		add_filter( 'woocommerce_rest_shop_order_schema', array( __CLASS__, 'order_schema' ) );

		add_filter( 'woocommerce_rest_product_schema', array( __CLASS__, 'product_schema' ) );
		add_filter( 'woocommerce_rest_product_variation_schema', array( __CLASS__, 'product_variation_schema' ) );

		add_filter( 'woocommerce_rest_pre_insert_product_object', array( __CLASS__, 'update_product' ), 10, 2 );

		add_filter( 'woocommerce_rest_prepare_product_object', array( __CLASS__, 'prepare_product' ), 10, 3 );
		add_filter( 'woocommerce_rest_prepare_product_variation_object', array( __CLASS__, 'prepare_product' ), 10, 3 );
	}

	public static function prepare_product( $response, $object, $request ) {
		if ( $product = wc_get_product( $object ) ) {
			$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

			$response->set_data( array_merge_recursive( $response->data, self::get_product_data( $product, $context ) ) );
		}

		return $response;
	}

	private static function get_product_data( $product, $context = 'view' ) {
		$data = array();

		if ( $shipments_product = wc_gzd_shipments_get_product( $product ) ) {
			$data['hs_code']             = $shipments_product->get_hs_code( $context );
			$data['customs_description'] = $shipments_product->get_customs_description( $context );
			$data['manufacture_country'] = $shipments_product->get_manufacture_country( $context );
			$data['shipping_dimensions'] = array(
				'length' => $shipments_product->get_shipping_length( $context ),
				'width'  => $shipments_product->get_shipping_width( $context ),
				'height' => $shipments_product->get_shipping_height( $context ),
			);
		}

		return $data;
	}

	/**
	 * @param \WC_Product $product
	 * @param $request
	 *
	 * @return \WC_Product $product
	 */
	public static function update_product( $product, $request ) {
		if ( $shipments_product = wc_gzd_shipments_get_product( $product ) ) {
			if ( isset( $request['customs_description'] ) ) {
				$shipments_product->set_customs_description( wc_clean( wp_unslash( $request['customs_description'] ) ) );
			}

			if ( isset( $request['hs_code'] ) ) {
				$shipments_product->set_hs_code( wc_clean( wp_unslash( $request['hs_code'] ) ) );
			}

			if ( isset( $request['manufacture_country'] ) ) {
				$shipments_product->set_manufacture_country( wc_clean( wp_unslash( $request['manufacture_country'] ) ) );
			}

			// Virtual.
			if ( isset( $request['virtual'] ) && true === $request['virtual'] ) {
				$shipments_product->set_shipping_length( '' );
				$shipments_product->set_shipping_width( '' );
				$shipments_product->set_shipping_height( '' );
			} else {
				// Height.
				if ( isset( $request['shipping_dimensions']['height'] ) ) {
					$shipments_product->set_shipping_height( $request['shipping_dimensions']['height'] );
				}

				// Width.
				if ( isset( $request['shipping_dimensions']['width'] ) ) {
					$shipments_product->set_shipping_width( $request['shipping_dimensions']['width'] );
				}

				// Length.
				if ( isset( $request['shipping_dimensions']['length'] ) ) {
					$shipments_product->set_shipping_length( $request['shipping_dimensions']['length'] );
				}
			}
		}

		return $product;
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
	 *
	 * @param array $schema_properties Data used to create the order.
	 *
	 * @return array
	 */
	public static function order_schema( $schema_properties ) {
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
	 * Extend product variation schema.
	 *
	 * @since 1.0.0
	 *
	 * @param array $schema_properties Data used to create the product.
	 *
	 * @return array
	 */
	public static function product_variation_schema( $schema_properties ) {
		$schema_properties['customs_description'] = array(
			'description' => _x( 'Customs description', 'shipments', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);

		$schema_properties['hs_code'] = array(
			'description' => _x( 'HS-Code', 'shipments', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);

		$schema_properties['manufacture_country'] = array(
			'description' => _x( 'Country of manufacture', 'shipments', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);

		$dimension_unit_label = Package::get_dimensions_unit_label( get_option( 'woocommerce_dimension_unit', 'cm' ) );

		$schema_properties['shipping_dimensions'] = array(
			'description' => _x( 'Product shipping dimensions.', 'shipments', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'length' => array(
					/* translators: %s: dimension unit */
					'description' => sprintf( _x( 'Shipping length (%s).', 'shipments', 'woocommerce-germanized' ), $dimension_unit_label ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'width'  => array(
					/* translators: %s: dimension unit */
					'description' => sprintf( _x( 'Shipping width (%s).', 'shipments', 'woocommerce-germanized' ), $dimension_unit_label ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'height' => array(
					/* translators: %s: dimension unit */
					'description' => sprintf( _x( 'Shipping height (%s).', 'shipments', 'woocommerce-germanized' ), $dimension_unit_label ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $schema_properties;
	}

	/**
	 * Extend product schema.
	 *
	 * @since 1.0.0
	 *
	 * @param array $schema_properties Data used to create the product.
	 *
	 * @return array
	 */
	public static function product_schema( $schema_properties ) {
		$schema_properties['customs_description'] = array(
			'description' => _x( 'Customs description', 'shipments', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);

		$schema_properties['hs_code'] = array(
			'description' => _x( 'HS-Code (Customs)', 'shipments', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);

		$schema_properties['manufacture_country'] = array(
			'description' => _x( 'Country of manufacture (Customs)', 'shipments', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);

		$dimension_unit_label = Package::get_dimensions_unit_label( get_option( 'woocommerce_dimension_unit', 'cm' ) );

		$schema_properties['shipping_dimensions'] = array(
			'description' => _x( 'Product shipping dimensions.', 'shipments', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'length' => array(
					/* translators: %s: dimension unit */
					'description' => sprintf( _x( 'Shipping length (%s).', 'shipments', 'woocommerce-germanized' ), $dimension_unit_label ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'width'  => array(
					/* translators: %s: dimension unit */
					'description' => sprintf( _x( 'Shipping width (%s).', 'shipments', 'woocommerce-germanized' ), $dimension_unit_label ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'height' => array(
					/* translators: %s: dimension unit */
					'description' => sprintf( _x( 'Shipping height (%s).', 'shipments', 'woocommerce-germanized' ), $dimension_unit_label ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
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
