<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\Shipments;

use Vendidero\Germanized\Shipments\Admin\MetaBox;
use WC_Order;

defined( 'ABSPATH' ) || exit;

class Automation {

	protected static $current_new_order_id = null;

	public static function init() {
		if ( 'yes' === Package::get_setting( 'auto_enable' ) ) {
			foreach ( self::get_auto_statuses() as $status ) {
				add_action( 'woocommerce_order_status_' . $status, array( __CLASS__, 'maybe_create_shipments' ), 10, 1 );
			}

			/**
			 * Always listen to new order events and check whether to create new shipments
			 * E.g. Default order status exists in auto statuses or auto statuses are empty
			 *
			 * The issue with the woocommerce_new_order hook is that this hook is getting executed before order items
			 * has been stored. This will lead to items not being available.
			 *
			 * Workaround: Hook into the woocommerce_after_order_object_save instead after an order has been created as a workaround.
			 */
			add_action(
				'woocommerce_new_order',
				function( $order_id ) {
					self::$current_new_order_id = $order_id;
					add_action( 'woocommerce_after_order_object_save', array( __CLASS__, 'after_new_order' ), 150 );
				},
				10,
				1
			);

			add_filter( 'wcs_renewal_order_created', array( __CLASS__, 'maybe_create_subscription_shipments' ), 10 );
		}

		if ( 'yes' === Package::get_setting( 'auto_order_shipped_completed_enable' ) ) {
			add_action( 'woocommerce_gzd_shipments_order_shipped', array( __CLASS__, 'mark_order_completed' ), 10 );
		}

		if ( 'yes' === Package::get_setting( 'auto_order_completed_shipped_enable' ) ) {
			add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'maybe_mark_shipments_shipped' ), 150, 4 );
		}
	}

	/**
	 * Make sure that his callback is only executed once for new order requests.
	 *
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public static function after_new_order( $order ) {
		if ( self::$current_new_order_id === $order->get_id() ) {
			self::maybe_create_shipments( $order );

			remove_action( 'woocommerce_after_order_object_save', array( __CLASS__, 'after_new_order' ), 150 );
			self::$current_new_order_id = null;
		}
	}

	/**
	 * @param $order_id
	 * @param $old_status
	 * @param $new_status
	 * @param WC_Order $order
	 */
	public static function maybe_mark_shipments_shipped( $order_id, $old_status, $new_status, $order ) {

		/**
		 * Filter to decide which order status is used to determine if a order
		 * is completed or not to update contained shipment statuses to shipped.
		 * Does only take effect if the automation option has been set within the shipment settings.
		 *
		 * @param string $status The current order status.
		 * @param integer $order_id The order id.
		 *
		 * @since 3.0.5
		 * @package Vendidero/Germanized/Shipments
		 */
		if ( apply_filters( 'woocommerce_gzd_shipments_order_completed_status', 'completed', $order_id ) === $new_status ) {

			// Make sure that MetaBox is saved before we process automation
			if ( self::is_admin_edit_order_request() ) {
				add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'mark_shipments_shipped' ), 70 );
			} else {
				self::mark_shipments_shipped( $order_id );
			}
		}
	}

	private static function is_admin_edit_order_request() {
		return ( isset( $_POST['action'] ) && ( ( 'editpost' === $_POST['action'] && isset( $_POST['post_type'] ) && 'shop_order' === $_POST['post_type'] ) || 'edit_order' === $_POST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	public static function mark_shipments_shipped( $order_id ) {
		if ( $order = wc_get_order( $order_id ) ) {
			if ( $shipment_order = wc_gzd_get_shipment_order( $order ) ) {
				foreach ( $shipment_order->get_simple_shipments() as $shipment ) {

					if ( ! $shipment->is_shipped() ) {
						$shipment->update_status( 'shipped' );
					}
				}
			}
		}
	}

	/**
	 * Mark the order as completed if the order is fully shipped.
	 *
	 * @param $order_id
	 */
	public static function mark_order_completed( $order_id ) {
		if ( $order = wc_get_order( $order_id ) ) {

			/**
			 * By default do not mark orders (via invoice) as completed after shipped as
			 * the order will be shipped before the invoice was paid.
			 */
			$mark_as_completed = ! in_array( $order->get_payment_method(), array( 'invoice' ), true ) ? true : false;

			/**
			 * Filter that allows to conditionally disable automatic
			 * order completion after the shipments are marked as shipped.
			 *
			 * @param boolean $mark_as_completed Whether to mark the order as completed or not.
			 * @param integer $order_id The order id.
			 *
			 * @since 3.2.3
			 * @package Vendidero/Germanized/Shipments
			 */
			if ( ! apply_filters( 'woocommerce_gzd_shipment_order_mark_as_completed', $mark_as_completed, $order_id ) ) {
				return;
			}

			/**
			 * Filter to adjust the new status of an order after all it's required
			 * shipments have been marked as shipped. Does only take effect if the automation option has been set
			 * within the shipment settings.
			 *
			 * @param string  $status The order status to be used.
			 * @param integer $order_id The order id.
			 *
			 * @since 3.0.5
			 * @package Vendidero/Germanized/Shipments
			 */
			$order->update_status( apply_filters( 'woocommerce_gzd_shipment_order_completed_status', 'completed', $order_id ), _x( 'Order is fully shipped.', 'shipments', 'woocommerce-germanized' ) );
		}
	}

	public static function create_shipments( $order, $enable_auto_filter = true ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return;
		}

		$shipment_status = Package::get_setting( 'auto_default_status' );

		if ( empty( $shipment_status ) ) {
			$shipment_status = 'processing';
		}

		/**
		 * Filter to disable automatically creating shipments for a specific order.
		 *
		 * @param string   $enable Whether to create or not create shipments.
		 * @param integer  $order_id The order id.
		 * @param WC_Order $order The order instance.
		 *
		 * @since 3.1.0
		 * @package Vendidero/Germanized/Shipments
		 */
		if ( $enable_auto_filter && ! apply_filters( 'woocommerce_gzd_auto_create_shipments_for_order', true, $order->get_id(), $order ) ) {
			return;
		}

		if ( $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
			if ( ! apply_filters( 'woocommerce_gzd_auto_create_custom_shipments_for_order', false, $order->get_id(), $order ) ) {
				$shipments = $order_shipment->get_simple_shipments();

				foreach ( $shipments as $shipment ) {
					if ( $shipment->is_editable() ) {
						$shipment->sync();
						$shipment->sync_items();
						$shipment->save();
					}
				}

				if ( $order_shipment->needs_shipping() ) {
					$shipment = wc_gzd_create_shipment( $order_shipment, array( 'props' => array( 'status' => $shipment_status ) ) );

					if ( ! is_wp_error( $shipment ) ) {
						$order_shipment->add_shipment( $shipment );
					}
				}
			}

			do_action( 'woocommerce_gzd_after_auto_create_shipments_for_order', $order->get_id(), $shipment_status, $order );
		}
	}

	protected static function get_auto_statuses() {
		$statuses       = (array) Package::get_setting( 'auto_statuses' );
		$clean_statuses = array();

		if ( ! empty( $statuses ) ) {
			foreach ( $statuses as $status ) {
				$status = trim( str_replace( 'wc-', '', $status ) );

				if ( ! in_array( $status, $clean_statuses, true ) ) {
					$clean_statuses[] = $status;
				}
			}
		}

		return $clean_statuses;
	}

	public static function maybe_create_shipments( $order_id ) {
		$statuses   = self::get_auto_statuses();
		$has_status = empty( $statuses ) ? true : false;

		if ( ! $has_status ) {
			if ( $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {
				$has_status = $order_shipment->get_order()->has_status( $statuses );
			}
		}

		if ( $has_status ) {
			// Make sure that MetaBox is saved before we process automation
			if ( self::is_admin_edit_order_request() ) {
				add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'create_shipments' ), 70 );
			} else {
				self::create_shipments( $order_id );
			}
		}
	}

	public static function maybe_create_subscription_shipments( $renewal_order ) {
		self::create_shipments( $renewal_order->get_id() );

		return $renewal_order;
	}
}
