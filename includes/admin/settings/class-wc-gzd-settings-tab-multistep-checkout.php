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

	public function is_pro() {
		return true;
	}

	public function get_tab_settings( $current_section = '' ) {
		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'multistep_checkout_options',
				'desc'  => '',
			),

			array(
				'title' => '',
				'id'    => 'woocommerce_gzdp_multistep_checkout_enable',
				'img'   => WC_Germanized()->plugin_url() . '/assets/images/pro/settings-checkout.png?v=' . WC_germanized()->version,
				'href'  => 'https://vendidero.de/woocommerce-germanized/features#multistep-checkout',
				'type'  => 'image',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'multistep_checkout_options',
			),
		);
	}

	public function is_enabled() {
		return false;
	}
}
