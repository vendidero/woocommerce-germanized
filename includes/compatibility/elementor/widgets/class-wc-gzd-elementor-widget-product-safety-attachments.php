<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_GZD_Elementor_Widget_Product_Safety_Attachments extends WC_GZD_Elementor_Widget {

	public function get_title_raw() {
		return __( 'Safety documents', 'woocommerce-germanized' );
	}

	public function get_postfix() {
		return 'safety_attachments';
	}
}
