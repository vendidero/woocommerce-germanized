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

		// v3
		if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
			add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'prepare' ), 10, 3 );
			add_filter( 'woocommerce_rest_pre_insert_shop_order_object', array( $this, 'insert_v3' ), 10, 3 );
		} else {
			add_filter( 'woocommerce_rest_prepare_shop_order', array( $this, 'prepare' ), 10, 3 );
			add_action( 'woocommerce_rest_insert_shop_order', array( $this, 'insert' ), 10, 3 );
		}

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
		$response_order_data['shipping']['parcelshop'] = (bool) wc_gzd_get_crud_data( $order, 'shipping_parcelshop' );
		$response_order_data['shipping']['parcelshop_post_number'] = wc_gzd_get_crud_data( $order, 'shipping_parcelshop_post_number' );
		$response_order_data['parcel_delivery_opted_in'] = wc_gzd_get_crud_data( $order, 'parcel_delivery_opted_in' );

		$holder         = wc_gzd_get_crud_data( $order, 'direct_debit_holder' );
		$iban           = wc_gzd_get_crud_data( $order, 'direct_debit_iban' );
		$bic            = wc_gzd_get_crud_data( $order, 'direct_debit_bic' );
		$mandate_id     = wc_gzd_get_crud_data( $order, 'direct_debit_mandate_id' );

		if ( $this->direct_debit_gateway ) {
			$iban = $this->direct_debit_gateway->maybe_decrypt( $iban );
			$bic  = $this->direct_debit_gateway->maybe_decrypt( $bic );
		}

		$response_order_data['direct_debit'] = array(
			'holder'        => $holder,
			'iban'          => $iban,
			'bic'           => $bic,
			'mandate_id'    => $mandate_id
		);

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
		$order = wc_get_order( $post->ID );
		$order = $this->save_update_order_data( $order, $request );
	}

	public function insert_v3( $order, $request, $creating ) {
		$order = $this->save_update_order_data( $order, $request );
		return $order;
	}

	public function save_update_order_data( $order, $request ) {
		if ( isset( $request['billing']['title'] ) ) {
			$order = wc_gzd_set_crud_meta_data( $order, '_billing_title', absint( $request['billing']['title'] ) );
		}

		if ( isset( $request['shipping']['title'] ) ) {
			$order = wc_gzd_set_crud_meta_data( $order, '_shipping_title', absint( $request['shipping']['title'] ) );
		}

		if ( isset( $request['shipping']['parcelshop'] ) ) {
			if ( (bool) $request['shipping']['parcelshop'] ) {
				$order = wc_gzd_set_crud_meta_data( $order, '_shipping_parcelshop', 1 );
			} else {
				$order = wc_gzd_unset_crud_meta_data( $order, '_shipping_parcelshop' );
			}
		}

		if ( isset( $request['shipping']['parcelshop_post_number'] ) ) {
			$order = wc_gzd_set_crud_meta_data( $order, '_shipping_parcelshop_post_number', wc_clean( $request['shipping']['parcelshop_post_number'] ) );
		}

		if ( isset( $request['direct_debit'] ) ) {
			if ( isset( $request['direct_debit']['holder'] ) ) {
				$order = wc_gzd_set_crud_meta_data( $order, '_direct_debit_holder', sanitize_text_field( $request['direct_debit']['holder'] ) );
			}

			if ( isset( $request['direct_debit']['iban'] ) ) {
				$iban = sanitize_text_field( $request['direct_debit']['iban'] );
				if ( $this->direct_debit_gateway ) {
					$iban = $this->direct_debit_gateway->maybe_encrypt( $iban );
				}
				$order = wc_gzd_set_crud_meta_data( $order, '_direct_debit_iban', $iban );
			}

			if ( isset( $request['direct_debit']['bic'] ) ) {
				$bic = sanitize_text_field( $request['direct_debit']['bic'] );
				if ( $this->direct_debit_gateway ) {
					$bic = $this->direct_debit_gateway->maybe_encrypt( $bic );
				}
				$order = wc_gzd_set_crud_meta_data( $order, '_direct_debit_bic', $bic );
			}

			if ( isset( $request['direct_debit']['mandate_id'] ) ) {
				$order = wc_gzd_set_crud_meta_data( $order, '_direct_debit_mandate_id', sanitize_text_field( $request['direct_debit']['mandate_id'] ) );
			}
		}

		return $order;
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

		$schema_properties['shipping']['properties']['parcelshop'] = array(
			'description' => __( 'Parcel Shop', 'woocommerce-germanized' ),
			'type'        => 'boolean',
			'context'     => array( 'view', 'edit' ),
		);

		$schema_properties['shipping']['properties']['parcelshop_post_number'] = array(
			'description' => __( 'Postnumber', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);

		$schema_properties['parcel_delivery_opted_in'] = array(
			'description' => __( 'Parcel Delivery Data Transfer', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'readonly'	  => true,
		);

		$schema_properties['direct_debit'] = array(
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
		);

		return $schema_properties;
	}
}
