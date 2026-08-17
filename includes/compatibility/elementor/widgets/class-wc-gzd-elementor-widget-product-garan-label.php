<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_GZD_Elementor_Widget_Product_Garan_Label extends WC_GZD_Elementor_Widget {

	public function get_title_raw() {
		return __( 'EU GARAN label', 'woocommerce-germanized' );
	}

	public function get_postfix() {
		return 'garan_label';
	}
}
