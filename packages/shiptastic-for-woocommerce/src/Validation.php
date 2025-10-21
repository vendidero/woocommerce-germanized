<?php

namespace Vendidero\Shiptastic;

use Exception;
use Vendidero\Shiptastic\Interfaces\ShippingProvider;
use WC_Order;
use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

class Validation {

	public static function init() {
		add_action( 'woocommerce_new_order_item', array( __CLASS__, 'create_order_item' ), 10, 3 );
		add_action( 'woocommerce_before_delete_order_item', array( __CLASS__, 'delete_order_item' ), 10, 1 );
		add_action( 'woocommerce_update_order_item', array( __CLASS__, 'update_order_item' ), 10, 2 );

		/**
		 * Use a tweak to detect and fire shipping status change events after the order has been saved.
		 */
		add_action(
			'woocommerce_before_order_object_save',
			function ( $order ) {
				if ( $order->get_id() > 0 ) {
					$old_shipping_status = '';
					$new_shipping_status = '';
					$old_return_status   = '';
					$new_return_status   = '';

					if ( $order_shipments = wc_stc_get_shipment_order( $order ) ) {
						$new_shipping_status     = $order_shipments->get_shipping_status( 'edit' );
						$current_shipping_status = $order_shipments->get_current_shipping_status();
						$new_return_status       = $order_shipments->get_return_status( 'edit' );
						$current_return_status   = $order_shipments->get_current_return_status();

						/**
						 * New shipping status detected - update order meta data.
						 */
						if ( $current_shipping_status !== $new_shipping_status ) {
							$order->update_meta_data( '_shipping_status', $current_shipping_status );
							$new_shipping_status = $current_shipping_status;
						}

						/**
						 * New return status detected - update order meta data.
						 */
						if ( $current_return_status !== $new_return_status ) {
							$order->update_meta_data( '_return_status', $current_return_status );
							$new_return_status = $current_return_status;
						}
					}

					if ( $old_order = wc_get_order( $order->get_id() ) ) {
						/**
						 * Need to use a tweak here to force a fresh read of old metadata due to caching.
						 */
						$old_order->read_meta_data( true );
						$old_shipping_status = $old_order->get_meta( '_shipping_status', true, 'edit' );
						$old_return_status   = $old_order->get_meta( '_return_status', true, 'edit' );
					}

					if ( $old_shipping_status !== $new_shipping_status || $old_return_status !== $new_return_status ) {
						add_action(
							'woocommerce_update_order',
							function ( $order_id ) use ( $order, $old_shipping_status, $new_shipping_status, $old_return_status, $new_return_status ) {
								if ( $order_id === $order->get_id() ) {
									/**
									 * Before triggering any custom actions, make sure to remove self to prevent infinite loops
									 * in case one hook (@see Automation::mark_order_completed()) saves the order from within the hook.
									 */
									remove_all_actions( 'woocommerce_update_order', 9998 );

									if ( $old_shipping_status !== $new_shipping_status ) {
										do_action( 'woocommerce_shiptastic_order_shipping_status_' . $new_shipping_status, $order->get_id(), $order );

										if ( 'shipped' === $new_shipping_status || ( 'delivered' === $new_shipping_status && in_array( $old_shipping_status, array( 'not-shipped', 'ready-for-shipping' ), true ) ) ) {
											/**
											 * Action that fires as soon as an order has been shipped completely.
											 * That is the case when the order contains all relevant shipments and all the shipments are marked as shipped.
											 *
											 * @param string  $order_id The order id.
											 *
											 * @package Vendidero/Shiptastic
											 */
											do_action( 'woocommerce_shiptastic_order_shipped', $order_id );
										}

										do_action( 'woocommerce_shiptastic_order_shipping_status_' . $old_shipping_status . '_to_' . $new_shipping_status, $order->get_id(), $order );
										do_action( 'woocommerce_shiptastic_order_shipping_status_changed', $order->get_id(), $old_shipping_status, $new_shipping_status, $order );
									}

									if ( $old_return_status !== $new_return_status ) {
										do_action( 'woocommerce_shiptastic_order_return_status_' . $new_return_status, $order->get_id(), $order );

										if ( 'returned' === $new_return_status ) {
											/**
											 * Action that fires as soon as an order has been returned completely.
											 *
											 * @param string  $order_id The order id.
											 *
											 * @package Vendidero/Shiptastic
											 */
											do_action( 'woocommerce_shiptastic_order_returned', $order_id );
										}

										do_action( 'woocommerce_shiptastic_order_return_status_' . $old_return_status . '_to_' . $new_return_status, $order->get_id(), $order );
										do_action( 'woocommerce_shiptastic_order_return_status_changed', $order->get_id(), $old_return_status, $new_return_status, $order );
									}
								}
							},
							9998,
							1
						);
					}
				}
			}
		);

		add_action(
			'woocommerce_before_order_object_save',
			function ( $order ) {
				$changes               = $order->get_changes();
				$screen                = is_admin() && function_exists( 'get_current_screen' ) ? get_current_screen() : false;
				$is_edit_order_request = $screen ? in_array( $screen->id, array( 'woocommerce_page_wc-orders' ), true ) : false;
				$skip_validation       = false;

				/**
				 * Try to detect edit-lock only save request and skip validation
				 */
				if ( $is_edit_order_request && ( empty( $changes ) || ( 1 === count( $changes ) && array_key_exists( 'date_modified', $changes ) ) ) ) {
					$skip_validation = true;
				}

				if ( ! $skip_validation ) {
					/**
					 * Prevent additional validation from happening while saving order items.
					 */
					add_action(
						'woocommerce_update_order_item',
						function ( $order_item_id, $order_item ) use ( $order ) {
							if ( is_a( $order_item, 'WC_Order_Item' ) ) {
								if ( $order_item->get_order_id() === $order->get_id() ) {
									remove_action( 'woocommerce_update_order_item', array( __CLASS__, 'update_order_item' ), 10 );
								}
							}
						},
						5,
						2
					);

					add_action(
						'woocommerce_update_order',
						function ( $order_id ) use ( $order ) {
							if ( $order_id === $order->get_id() ) {
								self::update_order( $order_id );
							}
						},
						10,
						1
					);
				}
			},
			10
		);

		add_action(
			'woocommerce_new_order',
			function ( $order_id ) {
				add_action(
					'woocommerce_after_order_object_save',
					function ( $order ) use ( $order_id ) {
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

		add_filter( 'woocommerce_pre_delete_order_refund', array( __CLASS__, 'before_delete_refund' ), 9999, 3 );
		add_action( 'woocommerce_shiptastic_deleted_refund_order', array( __CLASS__, 'delete_refund_order' ), 10, 2 );
		add_action( 'woocommerce_order_refund_object_updated_props', array( __CLASS__, 'refresh_refund_order' ), 10, 1 );

		add_action( 'woocommerce_order_partially_refunded', array( __CLASS__, 'on_refund_order' ), 5, 2 );
		add_action( 'woocommerce_order_fully_refunded', array( __CLASS__, 'on_refund_order' ), 5, 2 );

		// Check for order shipping status changes
		add_action(
			'woocommerce_shiptastic_shipment_before_status_change',
			function ( $shipment_id, $shipment ) {
				if ( $order = $shipment->get_order() ) {
					self::maybe_update_order_shipping_status( $order );
				}
			},
			5,
			2
		);
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'maybe_update_order_shipping_status' ), 10 );
		add_action( 'woocommerce_shiptastic_shipping_provider_deactivated', array( __CLASS__, 'maybe_disable_default_shipping_provider' ), 10 );
	}

	/**
	 * In case a certain shipping provider is being deactivated make sure that the default
	 * shipping provider option is removed in case the option equals the deactivated provider.
	 *
	 * @param ShippingProvider $provider
	 */
	public static function maybe_disable_default_shipping_provider( $provider ) {
		$default_provider = wc_stc_get_default_shipping_provider();

		if ( $default_provider === $provider->get_name() ) {
			update_option( 'woocommerce_shiptastic_default_shipping_provider', '' );
		}
	}

	/**
	 * @param WC_Order|Order $order
	 */
	public static function maybe_update_order_shipping_status( $order ) {
		if ( $shipment_order = wc_stc_get_shipment_order( $order ) ) {
			$shipping_status = $shipment_order->get_current_shipping_status();
			$return_status   = $shipment_order->get_current_return_status();

			$has_updated_shipping_status = $shipment_order->update_shipping_status( $shipping_status, false );
			$has_updated_return_status   = $shipment_order->update_return_status( $return_status, false );

			if ( $has_updated_return_status || $has_updated_shipping_status ) {
				$shipment_order->get_order()->save();
			}
		}
	}

	public static function check_order_shipped( $order ) {
		wc_deprecated_function( 'Vendidero\Shiptastic\Validation::check_order_shipped()', '4.4.0' );
	}

	/**
	 * Delete editable shipments if an order is cancelled.
	 *
	 * @param $order_id
	 * @param WC_Order $order
	 */
	public static function maybe_cancel_shipments( $order_id, $order ) {
		$shipments = wc_stc_get_shipments_by_order( $order );

		foreach ( $shipments as $shipment ) {
			$is_deletable = $shipment->is_editable();

			/**
			 * Do not auto-delete returns when refunding an order
			 */
			if ( 'refunded' === $order->get_status() && 'return' === $shipment->get_type() ) {
				$is_deletable = false;
			}

			if ( $is_deletable ) {
				$shipment->delete();
			}
		}
	}

	/**
	 * @param $do_delete
	 * @param \WC_Order_Refund $refund
	 * @param bool $force_delete
	 *
	 * @return mixed
	 */
	public static function before_delete_refund( $do_delete, $refund, $force_delete ) {
		/**
		 * Use this ugly hack to make sure we are able to hook into the order refund event as
		 * Woo does not offer hook-support for HPOS refund deletion yet.
		 */
		if ( null === $do_delete ) {
			if ( $data_store = $refund->get_data_store() ) {
				$refund_id = $refund->get_id();

				$data_store->delete( $refund, array( 'force_delete' => $force_delete ) );
				$refund->set_id( 0 );

				do_action( 'woocommerce_shiptastic_deleted_refund_order', $refund, $refund_id );
			}
		}

		return $do_delete;
	}

	/**
	 * @param \WC_Order_Refund $refund
	 * @param $refund_id
	 *
	 * @return void
	 */
	public static function delete_refund_order( $refund, $refund_id ) {
		if ( $refund->get_parent_id() <= 0 ) {
			return;
		}

		if ( $order_shipment = wc_stc_get_shipment_order( $refund->get_parent_id() ) ) {
			$order_shipment->validate_shipments();
			$order_shipment->sync_returns_with_refunds();

			self::maybe_update_order_shipping_status( $order_shipment );
		}
	}

	public static function refresh_refund_order( $refund ) {
		if ( $refund->get_parent_id() <= 0 ) {
			return;
		}

		if ( $order_shipment = wc_stc_get_shipment_order( $refund->get_parent_id() ) ) {
			$order_shipment->validate_shipments();
		}
	}

	/**
	 * @param $order_id
	 * @param $refund_id
	 */
	public static function on_refund_order( $order_id, $refund_id ) {
		if ( $order_shipment = wc_stc_get_shipment_order( $order_id ) ) {
			$order_shipment->sync_returns_with_refunds();
		}
	}

	public static function delete_order( $order_id ) {
		if ( $order_shipment = wc_stc_get_shipment_order( $order_id ) ) {
			foreach ( $order_shipment->get_shipments() as $shipment ) {
				if ( $shipment->is_editable() ) {
					$order_shipment->remove_shipment( $shipment->get_id() );
				}
			}

			$order_shipment->save();
		}
	}

	public static function new_order( $order ) {
		if ( $order_shipment = wc_stc_get_shipment_order( $order ) ) {
			$order_shipment->validate_shipments();
		}
	}

	public static function update_order( $order_id ) {
		if ( $order_shipment = wc_stc_get_shipment_order( $order_id ) ) {
			$order_shipment->validate_shipments();
		}
	}

	public static function delete_order_item( $order_item_id ) {
		try {
			if ( $order_id = wc_get_order_id_by_order_item_id( $order_item_id ) ) {
				if ( $order_shipment = wc_stc_get_shipment_order( $order_id ) ) {
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
		if ( $order_shipment = wc_stc_get_shipment_order( $order_id ) ) {
			$order_shipment->validate_shipments();
		}
	}

	protected static function is_admin_save_order_request() {
		$is_admin_order_save_request = doing_action( 'save_post' ) || doing_action( 'woocommerce_process_shop_order_meta' );

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
			if ( is_a( $order_item, 'WC_Order_Item' ) ) {
				if ( $order_shipment = wc_stc_get_shipment_order( $order_item->get_order_id() ) ) {
					$order_shipment->validate_shipments();
				}
			}
		}
	}
}
