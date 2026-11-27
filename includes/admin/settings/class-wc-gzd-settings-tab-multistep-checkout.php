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
class WC_GZD_Settings_Tab_Multistep_Checkout extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Transform your checkout into a multistep checkout process.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Multistep checkout', 'woocommerce-germanized' ) . ' <span class="wc-gzd-pro wc-gzd-pro-outlined">' . __( 'pro', 'woocommerce-germanized' ) . '</span>';
	}

	public function get_name() {
		return 'multistep_checkout';
	}

	public function get_help_link() {
		return 'https://vendidero.de/doc/allgemein/mehrstufige-kasse-einrichten';
	}

	public function is_pro() {
		return true;
	}

	public function get_tab_settings( $current_section = '' ) {
		return $this->get_pro_preview_settings(
			array(
				'title'        => __( 'Multistep checkout with Germanized Pro', 'woocommerce-germanized' ),
				'desc'         => __( 'Split your checkout into clearly arranged steps and guide your customers through the order process.', 'woocommerce-germanized' ),
				'features'     => array(
					__( 'Separate steps for personal data, payment and order confirmation', 'woocommerce-germanized' ),
					__( 'Available for the classic checkout and the checkout block', 'woocommerce-germanized' ),
					__( 'Optional step navigation including a link back to the cart', 'woocommerce-germanized' ),
				),
				'href'         => 'https://vendidero.de/woocommerce-germanized/features#multistep-checkout',
				'placeholders' => array(
					array(
						'title' => 'Enable',
						'type'  => 'toggle',
						'value' => 'yes',
						'desc'  => 'Enable Multistep Checkout.',
					),
					array(
						'title' => 'Checkout Type',
						'type'  => 'select',
						'value' => 'Block-based',
					),
					array(
						'title' => 'Back to Cart',
						'type'  => 'toggle',
						'value' => 'yes',
						'desc'  => 'Add a cart link to step navigation.',
					),
					array(
						'title' => 'Step: Personal data',
						'type'  => 'text',
						'value' => 'Personal Data',
					),
					array(
						'title' => 'Step: Payment',
						'type'  => 'text',
						'value' => 'Payment',
					),
					array(
						'title' => 'Step: Confirmation',
						'type'  => 'text',
						'value' => 'Confirm Order',
					),
				),
			)
		);
	}

	public function is_enabled() {
		return false;
	}
}
