<?php

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility script for https://wordpress.org/plugins/flexible-checkout-fields/
 *
 * @class        WC_GZD_Compatibility_Flexible_Checkout_Fields
 * @category     Class
 * @author       vendidero
 */
class WC_GZD_Compatibility_Flexible_Checkout_Fields extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'Flexible Checkout Fields';
	}

	public static function get_path() {
		return 'flexible-checkout-fields/flexible-checkout-fields.php';
	}

	public function load() {
		/**
		 * Use a very high priority here to ensure we are hooking after Flexible Checkout Fields.
		 */
		$priority = 999999;

		// Add Title to billing address format
		add_filter(
			'woocommerce_order_formatted_billing_address',
			array(
				WC_GZD_Order_Helper::instance(),
				'set_formatted_billing_address',
			),
			$priority,
			2
		);

		add_filter(
			'woocommerce_order_formatted_shipping_address',
			array(
				WC_GZD_Order_Helper::instance(),
				'set_formatted_shipping_address',
			),
			$priority,
			2
		);

		/**
		 * Prevent double-adding format.
		 */
		remove_filter( 'woocommerce_formatted_address_replacements', array( WC_GZD_Checkout::instance(), 'set_formatted_address' ), 0 );
		add_filter( 'woocommerce_formatted_address_replacements', array( WC_GZD_Checkout::instance(), 'set_formatted_address' ), $priority, 2 );

		/**
		 * Remove title from formatted customer address
		 */
		add_filter( 'flexible_checkout_fields_user_meta_display_value', array( $this, 'filter_customer_title' ), 10, 2 );
	}

	public function filter_customer_title( $meta_value, $field ) {

		if ( in_array( $field['name'], array( 'billing_title', 'shipping_title' ), true ) ) {
			return '';
		}

		return $meta_value;
	}
}
