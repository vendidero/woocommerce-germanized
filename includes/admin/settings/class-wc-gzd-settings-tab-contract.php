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

	public function is_pro() {
		return true;
	}

	public function get_tab_settings( $current_section = '' ) {
		return array(
			array(
				'title' => '',
				'desc'  => '',
				'type'  => 'title',
				'id'    => 'manual_contract_options',
			),

			array(
				'title' => '',
				'id'    => 'woocommerce_gzdp_contract_after_confirmation',
				'img'   => WC_Germanized()->plugin_url() . '/assets/images/pro/settings-inline-contract.png?v=' . WC_germanized()->version,
				'href'  => 'https://vendidero.de/woocommerce-germanized/features#legal',
				'type'  => 'image',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'manual_contract_options',
			),
		);
	}

	public function is_enabled() {
		return false;
	}
}
