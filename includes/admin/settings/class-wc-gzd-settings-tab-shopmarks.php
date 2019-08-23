<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Shopmark settings.
 *
 * @class 		WC_GZD_Settings_Tab_Shopmarks
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Tab_Shopmarks extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Adjust shopmark related settings and adjust which labels shall be attached to your product data.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Shopmarks', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'shopmarks';
	}
}