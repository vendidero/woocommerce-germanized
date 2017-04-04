<?php
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
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_prepare_customer
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param \WP_User $customer User object used to create response.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function prepare( $response, $customer, $request ) {

		$response_customer_data = $response->get_data();
		
		$response_customer_data['billing']['title'] = $customer->billing_title;
		$response_customer_data['shipping']['title'] = $customer->shipping_title;

		$response_customer_data['shipping']['parcelshop'] = $customer->shipping_parcelshop == '1';
		$response_customer_data['shipping']['parcelshop_post_number'] = $customer->shipping_parcelshop_post_number;

		$holder = $customer->direct_debit_holder;
		$iban   = $customer->direct_debit_iban;
		$bic    = $customer->direct_debit_bic;

		if ( $this->direct_debit_gateway ) {
			$iban = $this->direct_debit_gateway->maybe_decrypt( $iban );
			$bic  = $this->direct_debit_gateway->maybe_decrypt( $bic );
		}

		$response_customer_data['direct_debit'] = array(
			'holder' => $holder,
			'iban'   => $iban,
			'bic'    => $bic
		);

		$response->set_data( $response_customer_data );

		return $response;
	}

	/**
	 * Prepare a single customer for create or update.
	 *
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_insert_customer
	 *
	 * @param \WP_User $customer Data used to create the customer.
	 * @param \WP_REST_Request $request Request object.
	 * @param bool $creating True when creating item, false when updating.
	 */
	public function insert( $customer, $request, $creating ) {

		if ( isset( $request['billing']['title'] ) ) {
			update_user_meta( $customer->ID, 'billing_title', absint( $request['billing']['title'] ) );
		}

		if ( isset( $request['shipping']['title'] ) ) {
			update_user_meta( $customer->ID, 'shipping_title', absint( $request['shipping']['title'] ) );
		}

		if ( isset( $request['shipping']['parcelshop'] ) ) {
			if ( ! $request['shipping']['parcelshop'] || empty( $request['shipping']['parcelshop'] ) ) {
				delete_user_meta( $customer->ID, 'shipping_parcelshop' );
			} else {
				update_user_meta( $customer->ID, 'shipping_parcelshop', true );
			}
		}

		if ( isset( $request['shipping']['parcelshop_post_number'] ) ) {
			update_user_meta( $customer->ID, 'shipping_parcelshop_post_number', sanitize_text_field( $request['shipping']['parcelshop_post_number'] ) );
		}

		if ( isset( $request['direct_debit'] ) ) {
			if ( isset( $request['direct_debit']['holder'] ) ) {
				update_user_meta( $customer->ID, 'direct_debit_holder', sanitize_text_field( $request['direct_debit']['holder'] ) );
			}

			if ( isset( $request['direct_debit']['iban'] ) ) {
				$iban = sanitize_text_field( $request['direct_debit']['iban'] );
				if ( $this->direct_debit_gateway ) {
					$iban = $this->direct_debit_gateway->maybe_encrypt( $iban );
				}
				update_user_meta( $customer->ID, 'direct_debit_iban', $iban );
			}

			if ( isset( $request['direct_debit']['bic'] ) ) {
				$bic = sanitize_text_field( $request['direct_debit']['bic'] );
				if ( $this->direct_debit_gateway ) {
					$bic = $this->direct_debit_gateway->maybe_encrypt( $bic );
				}
				update_user_meta( $customer->ID, 'direct_debit_bic', $bic );
			}
		}
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
	public function schema( $schema_properties ) {

		$schema_properties['billing']['properties']['title'] = array(
			'description' => __( 'Title', 'woocommerce-germanized' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'enum'        => array( 1, 2 )
		);

		$schema_properties['shipping']['properties']['title'] = array(
			'description' => __( 'Title', 'woocommerce-germanized' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'enum'        => array( 1, 2 )
		);

		$schema_properties['shipping']['properties']['parcelshop'] = array(
			'description' => __( 'Send to DHL Parcel Shop?', 'woocommerce-germanized' ),
			'type'        => 'boolean',
			'context'     => array( 'view', 'edit' ),
		);

		$schema_properties['shipping']['properties']['parcelshop_post_number'] = array(
			'description' => __( 'Postnumber', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);

		$schema_properties['direct_debit'] = array(
			'description' => __( 'Direct Debit', 'woocommerce-germanized' ),
			'type'        => 'array',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'holder' => array(
					'description' => __( 'Account Holder', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'iban'   => array(
					'description' => __( 'IBAN', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'bic'    => array(
					'description' => __( 'BIC/SWIFT', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				)
			)
		);

		return $schema_properties;
	}

}
