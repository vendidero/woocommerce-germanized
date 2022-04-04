<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_GZD_Elementor_Widget_Product_Deposit_Packaging_Type extends WC_GZD_Elementor_Widget {

	public function get_title_raw() {
		return __( 'Deposit Packaging Type', 'woocommerce-germanized' );
	}

	public function get_postfix() {
		return 'deposit_packaging_type';
	}
}
