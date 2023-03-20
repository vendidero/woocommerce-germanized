<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_GZD_Email_Customer_Processing_Order' ) ) :

	/**
	 * Customer Processing Order Email
	 *
	 * An email sent to the customer when a new order is received/paid for.
	 *
	 * @class        WC_Email_Customer_Processing_Order
	 * @version        2.0.0
	 * @package        WooCommerce/Classes/Emails
	 * @author        WooThemes
	 * @extends    WC_Email
	 */
	class WC_GZD_Email_Customer_Processing_Order extends WC_Email_Customer_Processing_Order {

		public function __construct() {
			parent::__construct();

			// Remove Triggers for parent email to prevent duplicates.
			wc_gzd_remove_class_action( 'woocommerce_order_status_on-hold_to_processing_notification', 'WC_Email_Customer_Processing_Order', 'trigger', 10 );
			wc_gzd_remove_class_action( 'woocommerce_order_status_pending_to_processing_notification', 'WC_Email_Customer_Processing_Order', 'trigger', 10 );
			wc_gzd_remove_class_action( 'woocommerce_order_status_failed_to_processing_notification', 'WC_Email_Customer_Processing_Order', 'trigger', 10 );
			wc_gzd_remove_class_action( 'woocommerce_order_status_cancelled_to_processing_notification', 'WC_Email_Customer_Processing_Order', 'trigger', 10 );

			$this->title       = __( 'Order Confirmation', 'woocommerce-germanized' );
			$this->description = wp_kses_post( sprintf( __( 'This is the <a href="%s" target="_blank">order confirmation</a> sent to customers containing order details after clicking the buy now button.', 'woocommerce-germanized' ), 'https://vendidero.de/dokument/automatische-bestellbestaetigung-nach-dem-kauf' ) );
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 * @since  3.1.0
		 */
		public function get_default_subject() {
			return __( 'Confirmation of your order {order_number}', 'woocommerce-germanized' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 * @since  3.1.0
		 */
		public function get_default_heading() {
			return __( 'Thank you for your order', 'woocommerce-germanized' );
		}
	}

endif;

return new WC_GZD_Email_Customer_Processing_Order();
