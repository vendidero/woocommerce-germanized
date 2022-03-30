<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_GZD_Elementor_Widget_Product_Nutrients extends WC_GZD_Elementor_Widget {

	public function get_title_raw() {
		return __( 'Nutrients', 'woocommerce-germanized' );
	}

	public function get_postfix() {
		return 'food_nutrients';
	}
}
