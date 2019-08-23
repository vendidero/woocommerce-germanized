<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Email settings.
 *
 * @class 		WC_GZD_Settings_Tab_Emails
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Tab_Emails extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Adjust email related settings e.g. attach your legal page content to certain email templates.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Emails', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'emails';
	}
}