<?php

defined( 'ABSPATH' ) || exit;

/**
 * Klarna Helper
 *
 * Specific configuration for Klarna.
 *
 * @class        WC_GZD_Compatibility_Klarna_Checkout_For_WooCommerce
 * @category     Class
 * @author       vendidero
 */
class WC_GZD_Compatibility_Klarna_Checkout_For_WooCommerce extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'Klarna Checkout for WooCommerce';
	}

	public static function get_path() {
		return 'klarna-checkout-for-woocommerce/klarna-checkout-for-woocommerce.php';
	}

	public function load() {
		add_action( 'woocommerce_gzd_run_legal_checkboxes_checkout', array( $this, 'maybe_disable_checkboxes' ), 5 );
		add_filter( 'woocommerce_gzd_enable_force_pay_order', array( $this, 'disable_force_pay_order' ), 10, 2 );
		add_filter( 'kco_checkout_timeout_duration', array( $this, 'increase_checkout_time' ), 10 );

		add_action( 'kco_wc_after_order_review', array( $this, 'add_checkboxes' ), 5 );
	}

	public function add_klarna_checkboxes() {
		return apply_filters( 'woocommerce_gzd_checkout_klarna_add_checkboxes', true );
	}

	public function add_checkboxes() {
		if ( $this->add_klarna_checkboxes() ) {
			WC_GZD_Legal_Checkbox_Manager::instance()->render( 'checkout' );
		}
	}

	public function increase_checkout_time( $time ) {
		if ( $time <= 10 ) {
			return 30;
		}

		return $time;
	}

	/**
	 * Prevent Germanized from adjusting the pay for order URL.
	 *
	 * @param $enable
	 * @param $order
	 *
	 * @return bool
	 */
	public function disable_force_pay_order( $enable, $order ) {

		if ( $order && 'kco' === $order->get_payment_method() ) {
			$enable = false;
		}

		return $enable;
	}

	/**
	 * Disable checkbox validation for Klarna orders.
	 */
	public function maybe_disable_checkboxes() {

		if ( ! WC()->checkout() ) {
			return;
		}

		$payment_method = WC()->checkout()->get_value( 'payment_method' );

		if ( 'kco' === $payment_method ) {

			if ( ! $this->add_klarna_checkboxes() ) {
				$checkboxes = WC_GZD_Legal_Checkbox_Manager::instance()->get_checkboxes( array( 'locations' => 'checkout' ) );

				foreach ( $checkboxes as $checkbox ) {
					$checkbox->set_is_mandatory( false );
				}
			}
		}
	}
}
