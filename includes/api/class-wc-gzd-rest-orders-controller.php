<?php
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

		add_filter( 'woocommerce_rest_prepare_shop_order', array( $this, 'prepare' ), 10, 3 );
		add_action( 'woocommerce_rest_insert_shop_order', array( $this, 'insert' ), 10, 3 );
		add_filter( 'woocommerce_rest_shop_order_schema', array( $this, 'schema' ) );
	}

	/**
	 * Filter order data returned from the REST API.
	 *
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_prepare_order
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param \WP_Post $post object used to create response.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function prepare( $response, $post, $request ) {

		$order = wc_get_order( $post );
		$response_order_data = $response->get_data();
		
		$response_order_data['billing']['title'] = wc_gzd_get_crud_data( $order, 'billing_title' );
		$response_order_data['shipping']['title'] = wc_gzd_get_crud_data( $order, 'shipping_title' );
		$response_order_data['parcel_delivery_opted_in'] = wc_gzd_get_crud_data( $order, 'parcel_delivery_opted_in' );
		
		$response->set_data( $response_order_data );

		return $response;
	}

	/**
	 * Prepare a single order for create or update.
	 *
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_insert_customer
	 *
	 * @param \WP_Post $post Data used to create the customer.
	 * @param \WP_REST_Request $request Request object.
	 * @param bool $creating True when creating item, false when updating.
	 */
	public function insert( $post, $request, $creating ) {

		if ( isset( $request['billing']['title'] ) ) {
			update_post_meta( $post->ID, '_billing_title', absint( $request['billing']['title'] ) );
		}

		if ( isset( $request['shipping']['title'] ) ) {
			update_post_meta( $post->ID, '_shipping_title', absint( $request['shipping']['title'] ) );
		}

	}

	/**
	 * Extend schema.
	 *
	 * @since 1.0.0
	 * @wp-hook woocommerce_rest_order_schema
	 *
	 * @param array $schema_properties Data used to create the order.
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

		$schema_properties['parcel_delivery_opted_in'] = array(
			'description' => __( 'Parcel Delivery Data Transfer', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'readonly'	  => true,
		);

		return $schema_properties;
	}

	/**
	 * Register
	 */
	public function register_fields() {

		register_rest_field(
			'shop_order',
			'direct_debit',
			array(
				'get_callback'    => array( $this, 'get_direct_debit' ),
				'update_callback' => array( $this, 'update_direct_debit' ),
				'schema'          => array(
					'description' => __( 'Direct Debit', 'woocommerce-germanized' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'holder'     => array(
							'description' => __( 'Account Holder', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' )
						),
						'iban'       => array(
							'description' => __( 'IBAN', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' )
						),
						'bic'        => array(
							'description' => __( 'BIC/SWIFT', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' )
						),
						'mandate_id' => array(
							'description' => __( 'Mandate Reference ID', 'woocommerce-germanized' ),
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

		$holder     = get_post_meta( $object['id'], '_direct_debit_holder', true );
		$iban       = get_post_meta( $object['id'], '_direct_debit_iban', true );
		$bic        = get_post_meta( $object['id'], '_direct_debit_bic', true );
		$mandate_id = get_post_meta( $object['id'], '_direct_debit_mandate_id', true );

		if ( $this->direct_debit_gateway ) {
			$iban = $this->direct_debit_gateway->maybe_decrypt( $iban );
			$bic  = $this->direct_debit_gateway->maybe_decrypt( $bic );
		}

		return array(
			'holder'     => $holder,
			'iban'       => $iban,
			'bic'        => $bic,
			'mandate_id' => $mandate_id
		);
	}

	/**
	 * Handler for updating custom field data
	 *
	 * @param mixed $value The value of the field
	 * @param WP_Post $object The object from the response
	 * @param string $field_name Name of field
	 *
	 * @return bool|int
	 */
	public function update_direct_debit( $value, $object, $field_name ) {

		if ( ! $value || ! is_array( $value ) ) {
			return false;
		}

		if ( isset( $value['holder'] ) ) {
			update_post_meta( $object->ID, '_direct_debit_holder', sanitize_text_field( $value['holder'] ) );
		}

		if ( isset( $value['iban'] ) ) {
			$iban = sanitize_text_field( $value['iban'] );
			if ( $this->direct_debit_gateway ) {
				$iban = $this->direct_debit_gateway->maybe_encrypt( $iban );
			}
			update_post_meta( $object->ID, '_direct_debit_iban', $iban );
		}

		if ( isset( $value['bic'] ) ) {
			$bic = sanitize_text_field( $value['bic'] );
			if ( $this->direct_debit_gateway ) {
				$bic = $this->direct_debit_gateway->maybe_encrypt( $bic );
			}
			update_post_meta( $object->ID, '_direct_debit_bic', $bic );
		}

		if ( isset( $value['mandate_id'] ) ) {
			update_post_meta( $object->ID, '_direct_debit_mandate_id', sanitize_text_field( $value['mandate_id'] ) );
		}

		return true;
	}

}
