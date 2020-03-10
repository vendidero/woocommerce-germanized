<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZD_Admin_Note_Review extends WC_GZD_Admin_Note {

	public function is_disabled() {

		if ( get_option( '_wc_gzd_disable_review_notice' ) ) {
			return true;
		}

		return parent::is_disabled();
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

	public function get_days_until_show() {
		return 3;
	}

	public function get_deactivate_text() {
		return __( "I've added my review", 'woocommerce-germanized' );
	}

	public function get_actions() {
		return array(
			array(
				'url'        => 'https://wordpress.org/support/view/plugin-reviews/woocommerce-germanized?rate=5#postform',
				'title'      => __( 'Write review now', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => true,
			),
			array(
				'url'        => 'https://wordpress.org/support/plugin/woocommerce-germanized',
				'title'      => __( 'Found Bugs?', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => false,
			),
		);
	}
}
