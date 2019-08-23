<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Button Solution settings.
 *
 * @class 		WC_GZD_Settings_Tab_Emails
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Tab_Button_Solution extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Adjust settings relevant to apply to the button solution.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Button Solution', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'button_solution';
	}
}