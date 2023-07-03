<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZD_Admin_Note_Review extends WC_GZD_Admin_Note {

	public function is_disabled() {
		return true;
	}

	public function get_name() {
		return 'review';
	}

	public function get_title() {
		return __( 'Do you like Germanized?', 'woocommerce-germanized' );
	}

	public function get_content() {
		return __( 'If you like Germanized and our Plugin does a good job it would be great if you would write a review about WooCommerce Germanized on WordPress.org. Thank you for your support!', 'woocommerce-germanized' );
	}

	public function is_deactivatable() {
		return true;
	}

	public function get_deactivate_text() {
		return __( "I've added my review", 'woocommerce-germanized' );
	}
}
