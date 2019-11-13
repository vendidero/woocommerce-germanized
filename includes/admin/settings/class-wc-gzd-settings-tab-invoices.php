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
class WC_GZD_Settings_Tab_Invoices extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Configure PDF invoices and packing slips.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Invoices & Packing Slips', 'woocommerce-germanized' ) . ' <span class="wc-gzd-pro wc-gzd-pro-outlined">' . __( 'pro', 'woocommerce-germanized' ) . '</span>';
	}

	public function get_name() {
		return 'invoices';
	}

	public function is_pro() {
		return true;
	}

	public function get_tab_settings( $current_section = '' ) {
		return array(
			array( 'title' => '', 'type' => 'title', 'id' => 'invoice_options', 'desc' => '' ),

			array(
				'title' => '',
				'id'    => 'woocommerce_gzdp_invoice_enable',
				'img'   => WC_Germanized()->plugin_url() . '/assets/images/pro/settings-invoices.png?v=' . WC_germanized()->version,
				'href'  => 'https://vendidero.de/woocommerce-germanized/features#accounting',
				'type'  => 'image',
			),

			array( 'type' => 'sectionend', 'id' => 'invoice_options' ),
		);
	}

	public function is_enabled() {
		return false;
	}
}