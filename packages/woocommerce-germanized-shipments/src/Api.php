<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\Shipments;

use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

class Api {

	public static function init() {
		// add_filter( 'woocommerce_rest_api_get_rest_namespaces', array( __CLASS__, 'register_controllers' ) );

		add_filter( 'woocommerce_rest_shop_order_schema', array( __CLASS__, 'order_shipments_schema' ), 10 );
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( __CLASS__, 'prepare_order_shipments' ), 10, 3 );
	}

	protected static function get_shipment_statuses() {
		$statuses = array();

		foreach ( array_keys( wc_gzd_get_shipment_statuses() ) as $status ) {
			$statuses[] = str_replace( 'gzd-', '', $status );
		}

		return $statuses;
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
		$context                          = 'view';

		if ( $order ) {
			$order_shipment = wc_gzd_get_shipment_order( $order );
			$shipments      = $order_shipment->get_shipments();

			if ( ! empty( $shipments ) ) {

				foreach( $shipments as $shipment ) {

					$item_data = array();

					foreach( $shipment->get_items() as $item ) {
						$item_data[] = array(
							'id'            => $item->get_id(),
							'name'          => $item->get_name( $context ),
							'order_item_id' => $item->get_order_item_id( $context ),
							'product_id'    => $item->get_product_id( $context ),
							'quantity'      => $item->get_quantity( $context ),
						);
					}

					$shipment_data = array(
						'id'                    => $shipment->get_id(),
						'date_created'          => wc_rest_prepare_date_response( $shipment->get_date_created( $context ), false ),
						'date_created_gmt'      => wc_rest_prepare_date_response( $shipment->get_date_created( $context ) ),
						'date_sent'             => wc_rest_prepare_date_response( $shipment->get_date_sent( $context ), false ),
						'date_sent_gmt'         => wc_rest_prepare_date_response( $shipment->get_date_sent( $context ) ),
						'est_delivery_date'     => wc_rest_prepare_date_response( $shipment->get_est_delivery_date( $context ), false ),
						'est_delivery_date_gmt' => wc_rest_prepare_date_response( $shipment->get_est_delivery_date( $context ) ),
						'total'                 => wc_format_decimal( $shipment->get_total(), $request['dp'] ),
						'weight'                => $shipment->get_weight( $context ),
						'status'                => $shipment->get_status(),
						'tracking_id'           => $shipment->get_tracking_id(),
						'tracking_url'          => $shipment->get_tracking_url(),
						'shipping_provider'     => $shipment->get_shipping_provider(),
						'dimensions'            => array(
							'length' => $shipment->get_length( $context ),
							'width'  => $shipment->get_width( $context ),
							'height' => $shipment->get_height( $context ),
						),
						'address'               => $shipment->get_address( $context ),
						'items'                 => $item_data,
					);

					$response_order_data['shipments'][] = $shipment_data;
				}
			}
		}

		$response->set_data( $response_order_data );

		return $response;
	}

	public static function order_shipments_schema( $schema ) {
		$weight_unit    = get_option( 'woocommerce_weight_unit' );
		$dimension_unit = get_option( 'woocommerce_dimension_unit' );

		$schema['shipments'] = array(
			'description' => _x( 'List of shipments.', 'shipments', 'woocommerce-germanized' ),
			'type'        => 'array',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
			'items'       => array(
				'type'       => 'object',
				'properties' => array(
					'id'     => array(
						'description' => _x( 'Shipment ID.', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'status' => array(
						'description' => _x( 'Shipment status.', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'enum'        => self::get_shipment_statuses(),
						'readonly'    => true,
					),
					'tracking_id' => array(
						'description' => _x( 'Shipment tracking id.', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'tracking_url' => array(
						'description' => _x( 'Shipment tracking url.', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'shipping_provider' => array(
						'description' => _x( 'Shipment shipping provider.', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_created'         => array(
						'description' => _x( "The date the shipment was created, in the site's timezone.", 'shipments', 'woocommerce-germanized' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_created_gmt'     => array(
						'description' => _x( 'The date the shipment was created, as GMT.', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_sent'         => array(
						'description' => _x( "The date the shipment was sent, in the site's timezone.", 'shipments', 'woocommerce-germanized' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_sent_gmt'     => array(
						'description' => _x( 'The date the shipment was sent, as GMT.', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'est_delivery_date' => array(
						'description' => _x( "The estimated delivery date of the shipment, in the site's timezone.", 'shipments', 'woocommerce-germanized' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'est_delivery_date_gmt'     => array(
						'description' => _x( 'The estimated delivery date of the shipment, as GMT.', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'weight'                => array(
						/* translators: %s: weight unit */
						'description' => sprintf( _x( 'Shipment weight (%s).', 'shipments', 'woocommerce-germanized' ), $weight_unit ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'dimensions'            => array(
						'description' => _x( 'Shipment dimensions.', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties'  => array(
							'length' => array(
								/* translators: %s: dimension unit */
								'description' => sprintf( _x( 'Shipment length (%s).', 'shipments', 'woocommerce-germanized' ), $dimension_unit ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'width'  => array(
								/* translators: %s: dimension unit */
								'description' => sprintf( _x( 'Shipment width (%s).', 'shipments', 'woocommerce-germanized' ), $dimension_unit ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'height' => array(
								/* translators: %s: dimension unit */
								'description' => sprintf( _x( 'Shipment height (%s).', 'shipments', 'woocommerce-germanized' ), $dimension_unit ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
					'address'         => array(
						'description' => _x( 'Shipping address.', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties'  => array(
							'first_name' => array(
								'description' => _x( 'First name.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'last_name'  => array(
								'description' => _x( 'Last name.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'company'    => array(
								'description' => _x( 'Company name.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'address_1'  => array(
								'description' => _x( 'Address line 1', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'address_2'  => array(
								'description' => _x( 'Address line 2', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'city'       => array(
								'description' => _x( 'City name.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'state'      => array(
								'description' => _x( 'ISO code or name of the state, province or district.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'postcode'   => array(
								'description' => _x( 'Postal code.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'country'    => array(
								'description' => _x( 'Country code in ISO 3166-1 alpha-2 format.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
					'items'           => array(
						'description' => _x( 'Shipment items.', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'id'           => array(
									'description' => _x( 'Item ID.', 'shipments', 'woocommerce-germanized' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'name'         => array(
									'description' => _x( 'Item name.', 'shipments', 'woocommerce-germanized' ),
									'type'        => 'mixed',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'order_item_id'   => array(
									'description' => _x( 'Order Item ID.', 'shipments', 'woocommerce-germanized' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'product_id'   => array(
									'description' => _x( 'Product ID.', 'shipments', 'woocommerce-germanized' ),
									'type'        => 'mixed',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'quantity'     => array(
									'description' => _x( 'Quantity.', 'shipments', 'woocommerce-germanized' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
					),
				),
			),
		);

		return $schema;
	}

	public static function register_controllers( $controller ) {
		$controller['wc/v3']['shipments'] = 'Vendidero\Germanized\Shipments\Rest\Shipments.php';

		return $controller;
	}
}
