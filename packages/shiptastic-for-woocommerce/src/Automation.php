<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Shiptastic;

use Vendidero\Shiptastic\Admin\Settings;
use Vendidero\Shiptastic\Packing\Helper;
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
			 * Workaround: Hook into the woocommerce_after_order_object_save instead after an order has been created.
			 */
			add_action(
				'woocommerce_new_order',
				function ( $order_id ) {
					self::$current_new_order_id = $order_id;
					add_action( 'woocommerce_after_order_object_save', array( __CLASS__, 'after_new_order' ), 150 );
				},
				10,
				1
			);

			add_filter( 'wcs_renewal_order_created', array( __CLASS__, 'maybe_create_subscription_shipments' ), 10 );
			add_action( 'woocommerce_shiptastic_order_auto_sync_callback', array( __CLASS__, 'auto_sync_callback' ) );
		}

		if ( 'yes' === Package::get_setting( 'auto_order_shipped_completed_enable' ) ) {
			add_action( 'woocommerce_shiptastic_order_shipped', array( __CLASS__, 'mark_order_completed' ), 10 );
		}

		if ( 'yes' === Package::get_setting( 'auto_order_completed_shipped_enable' ) ) {
			add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'maybe_mark_shipments_shipped' ), 150, 4 );
		}
	}

	public static function cancel_deferred_sync( $args ) {
		$queue = WC()->queue();

		/**
		 * Cancel outstanding events and queue new.
		 */
		$queue->cancel_all( 'woocommerce_shiptastic_order_auto_sync_callback', $args, 'woocommerce-shiptastic-order-sync' );
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
		 * @package Vendidero/Shiptastic
		 */
		if ( apply_filters( 'woocommerce_shiptastic_order_completed_status', 'completed', $order_id ) === $new_status ) {
			// Make sure that MetaBox is saved before we process automation
			if ( self::is_admin_edit_order_request() ) {
				add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'mark_shipments_shipped' ), 80 );
			} else {
				self::mark_shipments_shipped( $order_id );
			}
		}
	}

	public static function is_admin_edit_order_request() {
		$is_admin_edit_order_request = ( isset( $_POST['action'] ) && ( ( 'editpost' === $_POST['action'] && isset( $_POST['post_type'] ) && 'shop_order' === $_POST['post_type'] ) || 'edit_order' === $_POST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		/**
		 * Check whether the hook has already been firing.
		 */
		if ( did_action( 'woocommerce_process_shop_order_meta' ) && ! doing_action( 'woocommerce_process_shop_order_meta' ) ) {
			$is_admin_edit_order_request = false;
		}

		return $is_admin_edit_order_request;
	}

	public static function mark_shipments_shipped( $order_id ) {
		if ( $order = wc_get_order( $order_id ) ) {
			if ( $shipment_order = wc_stc_get_shipment_order( $order ) ) {
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
			 * Do not mark orders (paid via invoice) as completed after shipped as
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
			 * @package Vendidero/Shiptastic
			 */
			if ( ! apply_filters( 'woocommerce_shiptastic_shipment_order_mark_as_completed', $mark_as_completed, $order_id ) ) {
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
			 * @package Vendidero/Shiptastic
			 */
			$order->update_status( apply_filters( 'woocommerce_shiptastic_shipment_order_completed_status', 'completed', $order_id ), _x( 'Order is fully shipped.', 'shipments', 'woocommerce-germanized' ) );
		}
	}

	/**
	 * @param $order
	 *
	 * @return Shipment[]|\WP_Error
	 */
	public static function create_shipments( $order ) {
		$result = array();

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return $result;
		}

		$shipment_status = Package::get_setting( 'auto_default_status' );

		if ( empty( $shipment_status ) ) {
			$shipment_status = 'processing';
		}

		/**
		 * In case the order is already completed, e.g. while asynchronously creating shipments, make sure to update the shipment status.
		 */
		if ( 'yes' === Package::get_setting( 'auto_order_completed_shipped_enable' ) && apply_filters( 'woocommerce_shiptastic_order_completed_status', 'completed', $order->get_id() ) === $order->get_status() ) {
			$shipment_status = 'shipped';
		}

		/**
		 * Filter to disable automatically creating shipments for a specific order.
		 *
		 * @param string   $enable Whether to create or not create shipments.
		 * @param integer  $order_id The order id.
		 * @param WC_Order $order The order instance.
		 *
		 * @package Vendidero/Shiptastic
		 */
		if ( ! apply_filters( 'woocommerce_shiptastic_auto_create_shipments_for_order', true, $order->get_id(), $order ) ) {
			return $result;
		}

		if ( $order_shipment = wc_stc_get_shipment_order( $order ) ) {
			/**
			 * Sync existing shipments before creating new shipments
			 */
			foreach ( $order_shipment->get_simple_shipments() as $shipment ) {
				if ( $shipment->is_editable() ) {
					$shipment->sync();
					$shipment->save();
				}
			}

			do_action( 'woocommerce_shiptastic_before_auto_create_shipments_for_order', $order->get_id(), $shipment_status, $order );

			$result = $order_shipment->create_shipments( $shipment_status );

			do_action( 'woocommerce_shiptastic_after_auto_create_shipments_for_order', $order->get_id(), $shipment_status, $order, $result );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $result;
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

	public static function maybe_create_shipments( $order_id, $args = array() ) {
		$args = wp_parse_args(
			(array) $args,
			array(
				'allow_deferred_sync' => wc_shiptastic_allow_deferred_sync( 'shipments' ),
			)
		);

		$statuses   = self::get_auto_statuses();
		$has_status = empty( $statuses ) ? true : false;

		if ( ! $has_status ) {
			if ( $order_shipment = wc_stc_get_shipment_order( $order_id ) ) {
				$has_status = $order_shipment->get_order()->has_status( $statuses );
			}
		}

		if ( $has_status ) {
			// Make sure that MetaBox is saved before we process automation
			if ( self::is_admin_edit_order_request() ) {
				add_action(
					'woocommerce_process_shop_order_meta',
					function ( $order_id ) {
						self::create_shipments( $order_id );
					},
					70
				);
			} elseif ( $args['allow_deferred_sync'] ) {
					Package::log( 'Deferring order #' . $order_id . ' shipments sync' );

					$queue      = WC()->queue();
					$defer_args = array(
						'order_id' => $order_id,
					);

					/**
					 * Cancel outstanding events and queue new.
					 */
					self::cancel_deferred_sync( $defer_args );

					$queue->schedule_single(
						time() + 50,
						'woocommerce_shiptastic_order_auto_sync_callback',
						$defer_args,
						'woocommerce-shiptastic-order-sync'
					);
			} else {
				self::create_shipments( $order_id );
			}
		}
	}

	public static function auto_sync_callback( $order_id ) {
		/**
		 * Maybe cancel duplicate deferred syncs.
		 */
		self::cancel_deferred_sync( array( 'order_id' => $order_id ) );

		Package::log( 'Starting order #' . $order_id . ' shipments sync (deferred)' );

		self::create_shipments( $order_id );

		return true;
	}

	public static function maybe_create_subscription_shipments( $renewal_order ) {
		self::maybe_create_shipments( $renewal_order->get_id() );

		return $renewal_order;
	}
}
