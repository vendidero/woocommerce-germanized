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
	 *
	 * @param WC_Payment_Gateways $payment_gateways
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
	 * @wp-hook woocommerce_rest_prepare_order
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
			update_user_meta( $customer->ID, '_billing_title', absint( $request['billing']['title'] ) );
		}

		if ( isset( $request['shipping']['title'] ) ) {
			update_user_meta( $customer->ID, '_shipping_title', absint( $request['shipping']['title'] ) );
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
			'description' => __( 'Title', 'woocommerce-germanized-pro' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' )
		);

		$schema_properties['shipping']['properties']['title'] = array(
			'description' => __( 'Title', 'woocommerce-germanized-pro' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' )
		);

		return $schema_properties;
	}

	/**
	 * Register
	 */
	public function register_fields() {

		register_rest_field(
			'customer',
			'direct_debit',
			array(
				'get_callback'    => array( $this, 'get_direct_debit' ),
				'update_callback' => array( $this, 'update_direct_debit' ),
				'schema'          => array(
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
				)
			)
		);

	}


	/**
	 * Handler for getting custom field data.
	 *
	 * @param array $object The object from the response
	 * @param string $field_name Name of field
	 * @param \WP_REST_Request $request Current request
	 *
	 * @return array
	 */
	public function get_direct_debit( $object, $field_name, $request ) {

		$holder = get_user_meta( $object['id'], 'direct_debit_holder', true );
		$iban   = get_user_meta( $object['id'], 'direct_debit_iban', true );
		$bic    = get_user_meta( $object['id'], 'direct_debit_bic', true );

		if ( $this->direct_debit_gateway ) {
			$iban = $this->direct_debit_gateway->maybe_decrypt( $iban );
			$bic  = $this->direct_debit_gateway->maybe_decrypt( $bic );
		}

		return array(
			'holder' => $holder,
			'iban'   => $iban,
			'bic'    => $bic
		);
	}

	/**
	 * Handler for updating custom field data.
	 *
	 * @param mixed $value The value of the field
	 * @param WP_User $object The object from the response
	 * @param string $field_name Name of field
	 *
	 * @return bool|int
	 */
	public function update_direct_debit( $value, $object, $field_name ) {

		if ( ! $value || ! is_array( $value ) ) {
			return false;
		}

		if ( isset( $value['holder'] ) ) {
			update_user_meta( $object->ID, 'direct_debit_holder', sanitize_text_field( $value['holder'] ) );
		}

		if ( isset( $value['iban'] ) ) {
			$iban = sanitize_text_field( $value['iban'] );
			if ( $this->direct_debit_gateway ) {
				$iban = $this->direct_debit_gateway->maybe_encrypt( $iban );
			}
			update_user_meta( $object->ID, 'direct_debit_iban', $iban );
		}

		if ( isset( $value['bic'] ) ) {
			$bic = sanitize_text_field( $value['bic'] );
			if ( $this->direct_debit_gateway ) {
				$bic = $this->direct_debit_gateway->maybe_encrypt( $bic );
			}
			update_user_meta( $object->ID, 'direct_debit_bic', $bic );
		}

		return true;
	}

}
