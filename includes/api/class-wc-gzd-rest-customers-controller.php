<?php
/**
 * Class WC_GZD_REST_Customers_Controller
 *
 * @since 1.7.0
 * @author vendidero, Daniel Hüsken
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
	 * @param \WP_User $object The object from the response
	 * @param string $field_name Name of field
	 *
	 * @return bool|int
	 */
	public function update_direct_debit( $value, \WP_User $object, $field_name ) {

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
