<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Tax settings.
 *
 * @class 		WC_GZD_Settings_Tab_Taxes
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Tab_Taxes extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Find tax related settings like shipping costs taxation here.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Taxes', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'taxes';
	}
}