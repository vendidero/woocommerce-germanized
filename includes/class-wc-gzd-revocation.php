<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Contains Revocation Form Fields
 *
 * @class        WC_GZD_Revocation
 * @version        1.0.0
 * @author        Vendidero
 */
class WC_GZD_Revocation {

	/**
	 * Returns necessary form fields for revocation_form
	 *
	 * @return array
	 */
	public static function get_fields() {

		/**
		 * Filter to adjust form fields for the revocation form.
		 *
		 * @param array $fields The fields for the form.
		 *
		 * @since 1.0.0
		 */
		$fields = array(
			'content'           => array(
				'type'        => 'textarea',
				'label'       => _x( 'Withdrawal', 'revocation-form', 'woocommerce-germanized' ),
				'required'    => true,
				'description' => _x( 'Tip: Delete whatever entry does not apply, and provide, as needed', 'revocation-form', 'woocommerce-germanized' ),
				'default'     => _x( 'I/We hereby give notice that I/We withdraw from my/our contract of sale of the following goods/provision of the following service: ', 'revocation-form', 'woocommerce-germanized' ),
			),
			'received'          => array(
				'type'  => 'text',
				'label' => _x( 'Received', 'revocation-form', 'woocommerce-germanized' ),
			),
			'order_date'        => array(
				'type'  => 'text',
				'label' => _x( 'Order Date', 'revocation-form', 'woocommerce-germanized' ),
			),
			'sep'               => _x( 'Customer Data', 'revocation-form', 'woocommerce-germanized' ),
			'address_title'     => array(
				'type'     => 'select',
				'label'    => _x( 'Title', 'revocation-form', 'woocommerce-germanized' ),
				'required' => false,
				'options'  => array(
					__( 'Mr.', 'woocommerce-germanized' ) => __( 'Mr.', 'woocommerce-germanized' ),
					__( 'Ms.', 'woocommerce-germanized' ) => __( 'Ms.', 'woocommerce-germanized' ),
				),
			),
			'address_firstname' => array(
				'type'     => 'text',
				'label'    => _x( 'First Name', 'revocation-form', 'woocommerce-germanized' ),
				'required' => true,
			),
			'address_lastname'  => array(
				'type'     => 'text',
				'label'    => _x( 'Last Name', 'revocation-form', 'woocommerce-germanized' ),
				'required' => true,
			),
			'address_street'    => array(
				'type'     => 'text',
				'label'    => _x( 'Street', 'revocation-form', 'woocommerce-germanized' ),
				'required' => false,
			),
			'address_postal'    => array(
				'type'     => 'text',
				'label'    => _x( 'Postal Code', 'revocation-form', 'woocommerce-germanized' ),
				'required' => false,
			),
			'address_city'      => array(
				'type'     => 'text',
				'label'    => _x( 'City', 'revocation-form', 'woocommerce-germanized' ),
				'required' => false,
			),
			'address_country'   => array(
				'type'    => 'country',
				'label'   => _x( 'Country', 'revocation-form', 'woocommerce-germanized' ),
				'default' => 'DE',
			),
			'address_mail'      => array(
				'type'     => 'text',
				'validate' => array( 'email' ),
				'label'    => _x( 'Mail', 'revocation-form', 'woocommerce-germanized' ),
				'required' => true,
			),
		);

		if ( apply_filters( 'woocommerce_gzd_revocation_show_privacy_notice_checkbox', false ) ) {
			$fields = array_merge(
				$fields,
				array(
					'privacy_checkbox' => array(
						'type'     => 'checkbox',
						/**
						 * Filter to adjust the privacy field label for revocation form.
						 *
						 * @param string $html The label.
						 *
						 * @since 1.9.10
						 */
						'label'    => apply_filters( 'woocommerce_gzd_revocation_privacy_notice_label', sprintf( _x( 'Please accept our <a href="%s" target="_blank">Privacy Policy</a> so that we can process your inquiry.', 'revocation-form', 'woocommerce-germanized' ), esc_url( wc_gzd_get_privacy_policy_url() ) ) ),
						'required' => true,
					),
				)
			);
		}

		return apply_filters( 'woocommerce_gzd_revocation_fields', $fields );
	}
}
