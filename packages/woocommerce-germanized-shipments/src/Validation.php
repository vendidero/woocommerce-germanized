<?php

namespace Vendidero\Germanized\Shipments;

use Exception;
use Vendidero\Germanized\Shipments\Interfaces\ShippingProvider;
use WC_Order;
use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

class Validation {

	private static $current_refund_parent_order = false;

	public static function init() {
		add_action( 'woocommerce_update_order_item', array( __CLASS__, 'update_order_item' ), 10, 2 );
		add_action( 'woocommerce_new_order_item', array( __CLASS__, 'create_order_item' ), 10, 3 );
		add_action( 'woocommerce_before_delete_order_item', array( __CLASS__, 'delete_order_item' ), 10, 1 );

		add_action( 'woocommerce_update_order', array( __CLASS__, 'update_order' ), 10, 1 );

		add_action(
			'woocommerce_new_order',
			function( $order_id ) {
				add_action(
					'woocommerce_after_order_object_save',
					function( $order ) use ( $order_id ) {
						if ( $order_id === $order->get_id() ) {
							self::new_order( $order );
						}
					},
					300,
					1
				);
			},
			10,
			1
		);

		add_action( 'woocommerce_delete_order', array( __CLASS__, 'delete_order' ), 10, 1 );

		foreach ( array( 'cancelled', 'failed', 'refunded' ) as $cancelled_status ) {
			add_action( "woocommerce_order_status_{$cancelled_status}", array( __CLASS__, 'maybe_cancel_shipments' ), 10, 2 );
		}

		add_action( 'before_delete_post', array( __CLASS__, 'before_delete_refund' ), 10, 1 );
		add_action( 'woocommerce_delete_order_refund', array( __CLASS__, 'delete_refund_order' ), 10, 1 );
		add_action( 'woocommerce_order_refund_object_updated_props', array( __CLASS__, 'refresh_refund_order' ), 10, 1 );

		// Check if order is shipped
		add_action( 'woocommerce_gzd_shipment_status_changed', array( __CLASS__, 'maybe_update_order_date_shipped' ), 10, 4 );

		add_action( 'woocommerce_gzd_shipping_provider_deactivated', array( __CLASS__, 'maybe_disable_default_shipping_provider' ), 10 );
	}

	/**
	 * In case a certain shipping provider is being deactivated make sure that the default
	 * shipping provider option is removed in case the option equals the deactivated provider.
	 *
	 * @param ShippingProvider $provider
	 */
	public static function maybe_disable_default_shipping_provider( $provider ) {
		$default_provider = wc_gzd_get_default_shipping_provider();

		if ( $default_provider === $provider->get_name() ) {
			update_option( 'woocommerce_gzd_shipments_default_shipping_provider', '' );
		}
	}

	/**
	 * @param $shipment_id
	 * @param $status_from
	 * @param $status_to
	 * @param Shipment $shipment
	 */
	public static function maybe_update_order_date_shipped( $shipment_id, $status_from, $status_to, $shipment ) {
		if ( 'simple' === $shipment->get_type() && ( $order = $shipment->get_order() ) ) {
			self::check_order_shipped( $order );
		}
	}

	public static function check_order_shipped( $order ) {
		if ( $shipment_order = wc_gzd_get_shipment_order( $order ) ) {
			if ( $shipment_order->is_shipped() ) {
				/**
				 * Action that fires as soon as an order has been shipped completely.
				 * That is the case when the order contains all relevant shipments and all the shipments are marked as shipped.
				 *
				 * @param string  $order_id The order id.
				 *
				 * @since 3.1.0
				 * @package Vendidero/Germanized/Shipments
				 */
				do_action( 'woocommerce_gzd_shipments_order_shipped', $shipment_order->get_order()->get_id() );

				$shipment_order->get_order()->update_meta_data( '_date_shipped', time() );
				$shipment_order->get_order()->save();
			} else {
				$shipment_order->get_order()->delete_meta_data( '_date_shipped' );
				$shipment_order->get_order()->save();
			}
		}
	}

	/**
	 * Delete editable shipments if an order is cancelled.
	 *
	 * @param $order_id
	 * @param WC_Order $order
	 */
	public static function maybe_cancel_shipments( $order_id, $order ) {
		$shipments = wc_gzd_get_shipments_by_order( $order );

		foreach ( $shipments as $shipment ) {
			if ( $shipment->is_editable() ) {
				$shipment->delete();
			}
		}
	}

	public static function before_delete_refund( $refund_id ) {
		if ( $refund = wc_get_order( $refund_id ) ) {

			if ( is_a( $refund, 'WC_Order_Refund' ) ) {
				self::$current_refund_parent_order = $refund->get_parent_id();
			}
		}
	}

	public static function delete_refund_order( $refund_id ) {
		if ( false !== self::$current_refund_parent_order ) {

			if ( $order_shipment = wc_gzd_get_shipment_order( self::$current_refund_parent_order ) ) {
				$order_shipment->validate_shipments();
			}

			self::$current_refund_parent_order = false;
		}
	}

	public static function refresh_refund_order( $refund ) {
		if ( $refund->get_parent_id() <= 0 ) {
			return;
		}

		if ( $order_shipment = wc_gzd_get_shipment_order( $refund->get_parent_id() ) ) {
			$order_shipment->validate_shipments();
		}
	}

	public static function delete_order( $order_id ) {
		if ( $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {

			foreach ( $order_shipment->get_shipments() as $shipment ) {

				if ( $shipment->is_editable() ) {
					$order_shipment->remove_shipment( $shipment->get_id() );
				}
			}

			$order_shipment->save();
		}
	}

	public static function new_order( $order ) {
		if ( $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
			$order_shipment->validate_shipments();
		}
	}

	public static function update_order( $order_id ) {
		if ( $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {
			$order_shipment->validate_shipments();
		}
	}

	public static function delete_order_item( $order_item_id ) {
		try {
			if ( $order_id = wc_get_order_id_by_order_item_id( $order_item_id ) ) {

				if ( $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {
					foreach ( $order_shipment->get_shipments() as $shipment ) {

						if ( $shipment->is_editable() ) {
							if ( $item = $shipment->get_item_by_order_item_id( $order_item_id ) ) {
								$shipment->remove_item( $item->get_id() );
							}
						}
					}

					$order_shipment->save();
				}
			}
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}
	}

	public static function create_order_item( $order_item_id, $order_item, $order_id ) {
		if ( $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {
			$order_shipment->validate_shipments();
		}
	}

	protected static function is_admin_save_order_request() {
		$is_admin_order_save_request = doing_action( 'save_post' );

		/**
		 * Detect admin order adjustments e.g. add item, remove item, save post etc. and
		 * prevent singular order item hooks from executing to prevent multiple shipment validation requests
		 * which will execute on order save hook as well.
		 */
		if ( ! $is_admin_order_save_request && wp_doing_ajax() && isset( $_REQUEST['action'] ) && isset( $_REQUEST['order_id'] ) && strpos( wc_clean( wp_unslash( $_REQUEST['action'] ) ), 'woocommerce_' ) !== false ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$is_admin_order_save_request = true;
		}

		return $is_admin_order_save_request;
	}

	/**
	 * @param $order_item_id
	 * @param WC_Order_Item $order_item
	 */
	public static function update_order_item( $order_item_id, $order_item ) {
		if ( ! self::is_admin_save_order_request() ) {
			if ( is_callable( array( $order_item, 'get_order_id' ) ) ) {

				if ( $order_shipment = wc_gzd_get_shipment_order( $order_item->get_order_id() ) ) {
					$order_shipment->validate_shipments();
				}
			}
		}
	}
}
