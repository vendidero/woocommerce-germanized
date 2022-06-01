<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_GZD_REST_Orders_Controller
 *
 * @since 1.7.0
 * @author vendidero, Daniel Huesken
 */
class WC_GZD_REST_Orders_Controller {

	/**
	 * @var WC_GZD_Gateway_Direct_Debit
	 */
	private $direct_debit_gateway = null;

	/**
	 * ExtendOrdersController constructor.
	 */
	public function __construct() {
		$this->direct_debit_gateway = new WC_GZD_Gateway_Direct_Debit();

		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'prepare' ), 10, 3 );
		add_filter( 'woocommerce_rest_pre_insert_shop_order_object', array( $this, 'insert' ), 10, 3 );

		add_filter( 'woocommerce_rest_shop_order_schema', array( $this, 'schema' ) );
	}

	/**
	 * Filter order data returned from the REST API.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post $post object used to create response.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_prepare_order
	 *
	 */
	public function prepare( $response, $post, $request ) {

		$order               = wc_get_order( $post );
		$response_order_data = $response->get_data();

		$response_order_data['billing']['title']            = $order->get_meta( '_billing_title' );
		$response_order_data['billing']['title_formatted']  = wc_gzd_get_order_customer_title( $order );
		$response_order_data['shipping']['title']           = $order->get_meta( '_shipping_title' );
		$response_order_data['shipping']['title_formatted'] = wc_gzd_get_order_customer_title( $order, 'shipping' );

		$response_order_data['parcel_delivery_opted_in'] = $order->get_meta( '_parcel_delivery_opted_in' );

		$holder     = $order->get_meta( '_direct_debit_holder' );
		$iban       = $order->get_meta( '_direct_debit_iban' );
		$bic        = $order->get_meta( '_direct_debit_bic' );
		$mandate_id = $order->get_meta( '_direct_debit_mandate_id' );

		if ( $this->direct_debit_gateway ) {
			$iban = $this->direct_debit_gateway->maybe_decrypt( $iban );
			$bic  = $this->direct_debit_gateway->maybe_decrypt( $bic );
		}

		$response_order_data['direct_debit'] = array(
			'holder'     => $holder,
			'iban'       => $iban,
			'bic'        => $bic,
			'mandate_id' => $mandate_id,
		);

		$response->set_data( $response_order_data );

		return $response;
	}

	public function insert( $order, $request, $creating ) {
		$order = $this->save_update_order_data( $order, $request );

		return $order;
	}

	/**
	 * @param WC_Order $order
	 * @param $request
	 *
	 * @return mixed
	 */
	public function save_update_order_data( $order, $request ) {
		if ( isset( $request['billing']['title'] ) ) {
			$order->update_meta_data( '_billing_title', wc_clean( $request['billing']['title'] ) );
		}

		if ( isset( $request['shipping']['title'] ) ) {
			$order->update_meta_data( '_shipping_title', wc_clean( $request['shipping']['title'] ) );
		}

		if ( isset( $request['direct_debit'] ) ) {
			if ( isset( $request['direct_debit']['holder'] ) ) {
				$order->update_meta_data( '_direct_debit_holder', wc_clean( $request['direct_debit']['holder'] ) );
			}

			if ( isset( $request['direct_debit']['iban'] ) ) {
				$iban = wc_clean( $request['direct_debit']['iban'] );

				if ( $this->direct_debit_gateway ) {
					$iban = $this->direct_debit_gateway->maybe_encrypt( $iban );
				}

				$order->update_meta_data( '_direct_debit_iban', $iban );
			}

			if ( isset( $request['direct_debit']['bic'] ) ) {
				$bic = wc_clean( $request['direct_debit']['bic'] );

				if ( $this->direct_debit_gateway ) {
					$bic = $this->direct_debit_gateway->maybe_encrypt( $bic );
				}

				$order->update_meta_data( '_direct_debit_bic', $bic );
			}

			if ( isset( $request['direct_debit']['mandate_id'] ) ) {
				$order->update_meta_data( '_direct_debit_mandate_id', wc_clean( $request['direct_debit']['mandate_id'] ) );
			}
		}

		return $order;
	}

	/**
	 * Extend schema.
	 *
	 * @param array $schema_properties Data used to create the order.
	 *
	 * @return array
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_order_schema
	 *
	 */
	public function schema( $schema_properties ) {

		$schema_properties['billing']['properties']['title'] = array(
			'description' => __( 'Title', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);

		$schema_properties['billing']['properties']['title_formatted'] = array(
			'description' => __( 'Formatted title', 'woocommerce-germanized' ),
			'type'        => 'string',
			'readonly'    => true,
			'context'     => array( 'view', 'edit' ),
		);

		$schema_properties['shipping']['properties']['title'] = array(
			'description' => __( 'Title', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);

		$schema_properties['shipping']['properties']['title_formatted'] = array(
			'description' => __( 'Formatted title', 'woocommerce-germanized' ),
			'type'        => 'string',
			'readonly'    => true,
			'context'     => array( 'view', 'edit' ),
		);

		$schema_properties['parcel_delivery_opted_in'] = array(
			'description' => __( 'Parcel Delivery Data Transfer', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);

		$schema_properties['direct_debit'] = array(
			'description' => __( 'Direct Debit', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'holder'     => array(
					'description' => __( 'Account Holder', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'iban'       => array(
					'description' => __( 'IBAN', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'bic'        => array(
					'description' => __( 'BIC/SWIFT', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'mandate_id' => array(
					'description' => __( 'Mandate Reference ID', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $schema_properties;
	}
}
