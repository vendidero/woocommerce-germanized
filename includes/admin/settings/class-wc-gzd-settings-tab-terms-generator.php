<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Tax settings.
 *
 * @class        WC_GZD_Settings_Tab_Taxes
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_Terms_Generator extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Easily generate your custom terms & conditions through our API.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'TOS Generator', 'woocommerce-germanized' ) . ' <span class="wc-gzd-pro wc-gzd-pro-outlined">' . __( 'pro', 'woocommerce-germanized' ) . '</span>';
	}

	public function get_name() {
		return 'terms_generator';
	}

	public function is_pro() {
		return true;
	}

	public function get_tab_settings( $current_section = '' ) {
		return $this->get_pro_preview_settings(
			array(
				'title'        => __( 'Terms & conditions generator with Germanized Pro', 'woocommerce-germanized' ),
				'desc'         => __( 'Answer a few questions about your shop and let our API generate your terms & conditions.', 'woocommerce-germanized' ),
				'features'     => array(
					__( 'Terms & conditions tailored to your shop via a simple questionnaire', 'woocommerce-germanized' ),
					__( 'Insert the generated text directly into your terms page', 'woocommerce-germanized' ),
					__( 'Regenerate your terms at any time', 'woocommerce-germanized' ),
				),
				'href'         => 'https://vendidero.de/woocommerce-germanized/features#legal',
				'placeholders' => array(
					array(
						'title' => 'Shop',
						'type'  => 'title',
					),
					array(
						'title' => 'Shop name',
						'type'  => 'text',
						'value' => 'My Shop',
					),
					array(
						'title' => 'Legal form',
						'type'  => 'select',
						'value' => 'GmbH',
					),
					array(
						'title' => 'Customers',
						'type'  => 'select',
						'value' => 'Consumers and businesses',
					),
					array(
						'title' => 'Delivery countries',
						'type'  => 'select',
						'value' => 'Germany, Austria',
					),
					array(
						'title' => 'Payment methods',
						'type'  => 'checkbox',
						'value' => 'PayPal, Invoice, Direct debit',
					),
					array(
						'title' => 'Retention of title',
						'type'  => 'toggle',
						'value' => 'yes',
					),
					array(
						'title' => 'Terms page',
						'type'  => 'select',
						'value' => 'Terms and Conditions',
					),
				),
			)
		);
	}

	public function is_enabled() {
		return false;
	}
}
