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
class WC_GZD_Settings_Tab_Contract extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Adjust the time of closing contract with your customer.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Manual contract', 'woocommerce-germanized' ) . ' <span class="wc-gzd-pro wc-gzd-pro-outlined">' . __( 'pro', 'woocommerce-germanized' ) . '</span>';
	}

	public function get_name() {
		return 'contract';
	}

	public function get_help_link() {
		return 'https://vendidero.de/doc/allgemein/verspaeteter-vertragsschluss';
	}

	public function is_pro() {
		return true;
	}

	public function get_tab_settings( $current_section = '' ) {
		return $this->get_pro_preview_settings(
			array(
				'title'        => __( 'Manual contract conclusion with Germanized Pro', 'woocommerce-germanized' ),
				'desc'         => __( 'Review incoming orders within your backend before closing the contract with your customer.', 'woocommerce-germanized' ),
				'features'     => array(
					__( 'Manually review and confirm orders before closing the contract', 'woocommerce-germanized' ),
					__( 'The first email only confirms the receipt of the offer', 'woocommerce-germanized' ),
					__( 'Payment information is provided only after you have confirmed the order', 'woocommerce-germanized' ),
				),
				'href'         => 'https://vendidero.de/woocommerce-germanized/features#legal',
				'placeholders' => array(
					array(
						'title' => 'Conclusion of Contract',
						'type'  => 'toggle',
						'value' => 'yes',
						'desc'  => 'Manually review and confirm an order before closing contract.',
					),
				),
			)
		);
	}

	public function is_enabled() {
		return false;
	}
}
