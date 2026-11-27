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
class WC_GZD_Settings_Tab_Revocation_Generator extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Easily generate your revocation terms through our API.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Revocation Terms Generator', 'woocommerce-germanized' ) . ' <span class="wc-gzd-pro wc-gzd-pro-outlined">' . __( 'pro', 'woocommerce-germanized' ) . '</span>';
	}

	public function get_name() {
		return 'revocation_generator';
	}

	public function get_help_link() {
		return 'https://vendidero.de/doc/allgemein/agb-und-widerrufsbelehrung-mit-dem-generator-erstellen';
	}

	public function is_pro() {
		return true;
	}

	public function get_tab_settings( $current_section = '' ) {
		return $this->get_pro_preview_settings(
			array(
				'title'        => __( 'Revocation terms generator with Germanized Pro', 'woocommerce-germanized' ),
				'desc'         => __( 'Answer a few questions about your products and let our API generate your revocation terms.', 'woocommerce-germanized' ),
				'features'     => array(
					__( 'Revocation terms tailored to the products you sell', 'woocommerce-germanized' ),
					__( 'Includes the model withdrawal form', 'woocommerce-germanized' ),
					__( 'Insert the generated text directly into your revocation page', 'woocommerce-germanized' ),
				),
				'href'         => 'https://vendidero.de/woocommerce-germanized/features#legal',
				'placeholders' => array(
					array(
						'title' => 'Products',
						'type'  => 'title',
					),
					array(
						'title' => 'Product types',
						'type'  => 'checkbox',
						'value' => 'Goods, Digital content, Services',
					),
					array(
						'title' => 'Multiple deliveries',
						'type'  => 'toggle',
						'value' => 'no',
					),
					array(
						'title' => 'Return costs',
						'type'  => 'select',
						'value' => 'Customer bears return costs',
					),
					array(
						'title' => 'Non-parcel goods',
						'type'  => 'toggle',
						'value' => 'no',
					),
					array(
						'title' => 'Withdrawal form',
						'type'  => 'toggle',
						'value' => 'yes',
					),
					array(
						'title' => 'Revocation page',
						'type'  => 'select',
						'value' => 'Revocation',
					),
				),
			)
		);
	}

	public function is_enabled() {
		return false;
	}
}
