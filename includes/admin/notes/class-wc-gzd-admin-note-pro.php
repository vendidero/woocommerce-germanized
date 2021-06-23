<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZD_Admin_Note_Pro extends WC_GZD_Admin_Note {

	public function get_name() {
		return 'pro';
	}

	public function is_pro() {
		return true;
	}

	public function get_days_until_show() {
		return 4;
	}

	public function get_title() {
		return __( 'For professionals: Upgrade to Pro-Version', 'woocommerce-germanized' );
	}

	public function get_content() {
		$content = '<p>' . __( 'Do you enjoy Germanized? Do you want to benefit from even more and better features? You may consider an uprade to Pro. Check out some of the main Pro features:', 'woocommerce-germanized' ) . '</p>
		    <ul>
		        <li>' . __( 'PDF invoices and packing slips', 'woocommerce-germanized' ) . '</li>
		        <li>' . __( 'Generator for terms & conditions and cancellation policy', 'woocommerce-germanized' ) . '</li>
		        <li>' . __( 'Multistep Checkout', 'woocommerce-germanized' ) . '</li>
		        <li><strong>' . __( 'Premium Ticket Support', 'woocommerce-germanized' ) . '</strong></li>
		    </ul>
	    ';

		return $content;
	}

	public function get_actions() {
		return array(
			array(
				'url'        => 'https://vendidero.de/woocommerce-germanized',
				'title'      => __( 'Learn more about Pro Version', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => true,
			),
		);
	}
}
