<?php
/**
 * Class WC_GZD_REST_Orders_Controller
 *
 * @since 1.7.0
 * @author vendidero, Daniel Hüsken
 */
class WC_GZD_REST_Orders_Controller {

	/**
	 * @var WC_GZD_Gateway_Direct_Debit
	 */
	private $direct_debit_gateway = null;

	/**
	 * ExtendOrdersController constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Payment_Gateways $payment_gateways
	 */
	public function __construct() {
		$this->direct_debit_gateway = new WC_GZD_Gateway_Direct_Debit();
	}

	/**
	 * Register
	 *
	 * @since 1.0.0
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
					'type'        => 'array',
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
	 * @since 1.0.0
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
	 * Handler for updating custom field data.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The value of the field
	 * @param \WP_Post $object The object from the response
	 * @param string $field_name Name of field
	 *
	 * @return bool|int
	 */
	public function update_direct_debit( $value, \WP_Post $object, $field_name ) {

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
