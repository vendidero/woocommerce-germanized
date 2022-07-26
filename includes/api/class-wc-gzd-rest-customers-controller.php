<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_GZD_REST_Customers_Controller
 *
 * @since 1.7.0
 * @author vendidero, Daniel Huesken
 */
class WC_GZD_REST_Customers_Controller {

	/**
	 * @var WC_GZD_Gateway_Direct_Debit
	 */
	private $direct_debit_gateway = null;

	/**
	 * ExtendOrdersController constructor.
	 */
	public function __construct() {
		$this->direct_debit_gateway = new WC_GZD_Gateway_Direct_Debit();

		add_filter( 'woocommerce_rest_prepare_customer', array( $this, 'prepare' ), 10, 3 );
		add_action( 'woocommerce_rest_insert_customer', array( $this, 'insert' ), 10, 3 );
		add_filter( 'woocommerce_rest_customer_schema', array( $this, 'schema' ) );
	}

	/**
	 * Filter customer data returned from the REST API.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_User $customer User object used to create response.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_prepare_customer
	 *
	 */
	public function prepare( $response, $user_data, $request ) {

		$customer               = new WC_Customer( $user_data->ID );
		$response_customer_data = $response->get_data();

		$response_customer_data['billing']['title']            = $customer->get_meta( 'billing_title' );
		$response_customer_data['billing']['title_formatted']  = wc_gzd_get_customer_title( $customer->get_meta( 'billing_title' ) );
		$response_customer_data['shipping']['title']           = $customer->get_meta( 'shipping_title' );
		$response_customer_data['shipping']['title_formatted'] = wc_gzd_get_customer_title( $customer->get_meta( 'shipping_title' ) );

		$holder = $customer->get_meta( 'direct_debit_holder' );
		$iban   = $customer->get_meta( 'direct_debit_iban' );
		$bic    = $customer->get_meta( 'direct_debit_bic' );

		if ( $this->direct_debit_gateway ) {
			$iban = $this->direct_debit_gateway->maybe_decrypt( $iban );
			$bic  = $this->direct_debit_gateway->maybe_decrypt( $bic );
		}

		$response_customer_data['direct_debit'] = array(
			'holder' => $holder,
			'iban'   => $iban,
			'bic'    => $bic,
		);

		if ( WC_GZD_Customer_Helper::instance()->is_double_opt_in_enabled() ) {
			$response_customer_data['is_activated'] = wc_gzd_is_customer_activated( $customer->get_id() );
		}

		$response->set_data( $response_customer_data );

		return $response;
	}

	/**
	 * Prepare a single customer for create or update.
	 *
	 * @param WP_User $customer Data used to create the customer.
	 * @param WP_REST_Request $request Request object.
	 * @param bool $creating True when creating item, false when updating.
	 *
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_insert_customer
	 *
	 */
	public function insert( $user_data, $request, $creating ) {
		$customer = new WC_Customer( $user_data->ID );

		if ( isset( $request['billing']['title'] ) ) {
			$customer->update_meta_data( 'billing_title', wc_clean( $request['billing']['title'] ) );
		}

		if ( isset( $request['shipping']['title'] ) ) {
			$customer->update_meta_data( 'shipping_title', wc_clean( $request['shipping']['title'] ) );
		}

		if ( isset( $request['direct_debit'] ) ) {

			if ( isset( $request['direct_debit']['holder'] ) ) {
				$customer->update_meta_data( 'direct_debit_holder', wc_clean( $request['direct_debit']['holder'] ) );
			}

			if ( isset( $request['direct_debit']['iban'] ) ) {
				$iban = wc_clean( $request['direct_debit']['iban'] );

				if ( $this->direct_debit_gateway ) {
					$iban = $this->direct_debit_gateway->maybe_encrypt( $iban );
				}

				$customer->update_meta_data( 'direct_debit_iban', $iban );
			}

			if ( isset( $request['direct_debit']['bic'] ) ) {
				$bic = wc_clean( $request['direct_debit']['bic'] );

				if ( $this->direct_debit_gateway ) {
					$bic = $this->direct_debit_gateway->maybe_encrypt( $bic );
				}

				$customer->update_meta_data( 'direct_debit_bic', $bic );
			}
		}

		$customer->save();
	}

	/**
	 * Extend schema.
	 *
	 * @param array $schema_properties Data used to create the customer.
	 *
	 * @return array
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_customer_schema
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
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);

		$schema_properties['shipping']['properties']['title'] = array(
			'description' => __( 'Title', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);

		$schema_properties['shipping']['properties']['title_formatted'] = array(
			'description' => __( 'Formatted title', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);

		if ( WC_GZD_Customer_Helper::instance()->is_double_opt_in_enabled() ) {
			$schema_properties['is_activated'] = array(
				'description' => __( 'Has been activated via DOI?', 'woocommerce-germanized' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			);
		}

		$schema_properties['direct_debit'] = array(
			'description' => __( 'Direct Debit', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'holder' => array(
					'description' => __( 'Account Holder', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'iban'   => array(
					'description' => __( 'IBAN', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'bic'    => array(
					'description' => __( 'BIC/SWIFT', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $schema_properties;
	}
}
