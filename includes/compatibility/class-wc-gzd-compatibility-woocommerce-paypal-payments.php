<?php

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce PayPal Payments Helper
 *
 * @see https://wordpress.org/plugins/woocommerce-paypal-payments/
 *
 * @class    WC_GZD_Compatibility_WooCommerce_PayPal_Payments
 * @category Class
 * @author   vendidero
 */
class WC_GZD_Compatibility_WooCommerce_PayPal_Payments extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'WooCommerce PayPal Payments';
	}

	public static function get_path() {
		return 'woocommerce-paypal-payments/woocommerce-paypal-payments.php';
	}

	public function load() {
		add_filter( 'woocommerce_paypal_payments_checkout_button_renderer_hook', array( $this, 'move_paypal_payment_button' ), 10 );

		add_action(
			'woocommerce_gzd_after_checkout_order_submit',
			function () {
				do_action( 'woocommerce_gzd_render_paypal_payments_smart_button' );
			}
		);

		add_action(
			'woocommerce_review_order_after_payment',
			function () {
				if ( wc_gzd_checkout_adjustments_disabled() || ! woocommerce_gzd_checkout_custom_submit_button_is_shown() ) {
					do_action( 'woocommerce_gzd_render_paypal_payments_smart_button' );
				}
			}
		);

		add_filter(
			'woocommerce_paypal_payments_tracking_data_before_update',
			function ( $shipment_data ) {
				if ( isset( $shipment_data['carrier'] ) ) {
					if ( strstr( $shipment_data['carrier'], 'dpd' ) ) {
						$shipment_data['carrier'] = 'DPD';
					} elseif ( strstr( $shipment_data['carrier'], 'gls' ) ) {
						$shipment_data['carrier'] = 'GLS';
					} elseif ( strstr( $shipment_data['carrier'], 'hermes' ) ) {
						$shipment_data['carrier'] = 'HERMES';
					} elseif ( strstr( $shipment_data['carrier'], 'ups' ) ) {
						$shipment_data['carrier'] = 'UPS';
					}
				}

				return $shipment_data;
			},
			10,
			1
		);

		/**
		 * When PayPal > Settings > Pay Now Experience is enabled (skip order review page), PayPal Payments
		 * manually creates a WC_Order (withount setting created_via) via ppc-approve-order endpoint.
		 * This order will include shipping net prices - make sure Germanized does not handle those prices as gross instead.
		 */
		add_filter(
			'woocommerce_paypal_payments_approve_order_request_started',
			function () {
				add_filter(
					'woocommerce_gzd_order_item_additional_cost_is_net',
					function ( $cost_is_net, $old_item, $item ) {
						$item_total     = wc_format_decimal( floatval( $old_item->get_total() ) );
						$new_item_total = wc_format_decimal( floatval( $item->get_total() ) );
						$item_tax_total = floatval( $old_item->get_total_tax() );

						return 0.0 === $item_tax_total && $item_total === $new_item_total;
					},
					10,
					3
				);
			}
		);
	}

	public function after_plugins_loaded() {
		/**
		 * Do not send the order confirmation email instantly as we'll need to wait for the IPN callback to finish
		 * to allow showing custom PayPal banking data.
		 */
		add_filter(
			'woocommerce_gzd_instant_order_confirmation',
			function ( $send_confirmation, $order = null ) {
				if ( is_a( $order, 'WC_Order' ) && 'ppcp-pay-upon-invoice-gateway' === $order->get_payment_method() ) {
					$send_confirmation = false;
				}

				return $send_confirmation;
			},
			10,
			2
		);

		/**
		 * Disable the paid for order mail for the PP invoice gateway.
		 */
		add_filter(
			'woocommerce_gzd_disable_gateways_paid_order_email',
			function ( $gateways_disabled ) {
				$gateways_disabled[] = 'ppcp-pay-upon-invoice-gateway';

				return $gateways_disabled;
			}
		);
	}

	public function move_paypal_payment_button( $filter ) {
		return 'woocommerce_gzd_render_paypal_payments_smart_button';
	}
}
