<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_GZD_Email_Customer_On_Hold_Order' ) ) :

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
	class WC_GZD_Email_Customer_On_Hold_Order extends WC_Email_Customer_On_Hold_Order {

		public function __construct() {
			parent::__construct();

			// Remove Triggers for parent email.
			wc_gzd_remove_class_action( 'woocommerce_order_status_pending_to_on-hold_notification', 'WC_Email_Customer_On_Hold_Order', 'trigger', 10 );
			wc_gzd_remove_class_action( 'woocommerce_order_status_failed_to_on-hold_notification', 'WC_Email_Customer_On_Hold_Order', 'trigger', 10 );
			wc_gzd_remove_class_action( 'woocommerce_order_status_cancelled_to_on-hold_notification', 'WC_Email_Customer_On_Hold_Order', 'trigger', 10 );
		}

		public function trigger( $order_id, $order = false ) {
			/**
			 * Filter that allows re-enabling the on-hold order email which is by default
			 * replaced by the processing email used as order confirmation.
			 *
			 * @param bool $disable Whether to disable the on-hold email or not.
			 *
			 * @since 1.0.0
			 */
			if ( apply_filters( 'woocommerce_gzd_disable_on_hold_email', wc_gzd_send_instant_order_confirmation( ( $order ? $order : $order_id ) ) ) ) {
				return;
			}

			$requires_two_arguments = true;

			try {
				$method                 = new ReflectionMethod( get_parent_class( $this ), 'trigger' );
				$num                    = $method->getNumberOfParameters();
				$requires_two_arguments = ( 2 === $num );

			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}

			if ( $requires_two_arguments ) {
				parent::trigger( $order_id, $order );
			} else {
				parent::trigger( $order_id );
			}
		}
	}

endif;

return new WC_GZD_Email_Customer_On_Hold_Order();
