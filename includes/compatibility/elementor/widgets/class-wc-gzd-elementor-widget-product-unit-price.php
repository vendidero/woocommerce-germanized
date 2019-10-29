<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_GZD_Elementor_Widget_Product_Unit_Price extends WC_GZD_Elementor_Widget {

	public function get_title_raw() {
		return __( 'Unit Price', 'woocommerce-germanized' );
	}

	public function get_postfix() {
		return 'unit_price';
	}
}
