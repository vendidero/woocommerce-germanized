<?php

namespace Vendidero\Germanized\Shipments\Admin;

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Ajax;
use Vendidero\Germanized\Shipments\Order;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Meta_Box_Order_Items Class.
 */
class MetaBox {

	/**
	 * @param Order $order
	 */
	public static function refresh_shipments( &$order ) {
		foreach ( $order->get_shipments() as $shipment ) {
			$id    = $shipment->get_id();
			$props = array();

			// Update items
			self::refresh_shipment_items( $order, $shipment );

			// Do only update props if they exist
			if ( isset( $_POST['shipment_weight'][ $id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$props['weight'] = wc_clean( wp_unslash( $_POST['shipment_weight'][ $id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			if ( isset( $_POST['shipment_length'][ $id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$props['length'] = wc_clean( wp_unslash( $_POST['shipment_length'][ $id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			if ( isset( $_POST['shipment_width'][ $id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$props['width'] = wc_clean( wp_unslash( $_POST['shipment_width'][ $id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			if ( isset( $_POST['shipment_height'][ $id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$props['height'] = wc_clean( wp_unslash( $_POST['shipment_height'][ $id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			if ( isset( $_POST['shipment_shipping_method'][ $id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$props['shipping_method'] = wc_clean( wp_unslash( $_POST['shipment_shipping_method'][ $id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			if ( isset( $_POST['shipment_tracking_id'][ $id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$props['tracking_id'] = wc_clean( wp_unslash( $_POST['shipment_tracking_id'][ $id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			if ( isset( $_POST['shipment_packaging_id'][ $id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$props['packaging_id'] = wc_clean( wp_unslash( $_POST['shipment_packaging_id'][ $id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			if ( isset( $_POST['shipment_shipping_provider'][ $id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$provider  = wc_clean( wp_unslash( $_POST['shipment_shipping_provider'][ $id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$providers = wc_gzd_get_shipping_providers();

				if ( empty( $provider ) || array_key_exists( $provider, $providers ) ) {
					$props['shipping_provider'] = $provider;
				}
			}

			$new_status = isset( $_POST['shipment_status'][ $id ] ) ? str_replace( 'gzd-', '', wc_clean( wp_unslash( $_POST['shipment_status'][ $id ] ) ) ) : 'draft'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			// Sync the shipment - make sure gets refresh on status switch (e.g. from shipped to processing)
			if ( $shipment->is_editable() || in_array( $new_status, wc_gzd_get_shipment_editable_statuses(), true ) ) {
				$shipment->sync( $props );
			}
		}
	}

	/**
	 * @param Order $order
	 * @param bool $shipment
	 */
	public static function refresh_shipment_items( &$order, &$shipment = false ) {
		$shipments = $shipment ? array( $shipment ) : $order->get_shipments();

		foreach ( $shipments as $shipment ) {
			$id = $shipment->get_id();

			if ( ! $shipment->is_editable() ) {
				continue;
			}

			// Update items
			foreach ( $shipment->get_items() as $item ) {
				$item_id = $item->get_id();
				$props   = array();

				// Set quantity to 1 by default
				if ( $shipment->is_editable() ) {
					$props['quantity'] = 1;
				}

				if ( isset( $_POST['shipment_item'][ $id ]['quantity'][ $item_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$props['quantity'] = absint( wp_unslash( $_POST['shipment_item'][ $id ]['quantity'][ $item_id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				}

				if ( isset( $_POST['shipment_item'][ $id ]['return_reason_code'][ $item_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$props['return_reason_code'] = wc_clean( wp_unslash( $_POST['shipment_item'][ $id ]['return_reason_code'][ $item_id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				}

				$item->sync( $props );
			}
		}
	}

	/**
	 * @param Order $order
	 */
	public static function refresh_status( &$order ) {
		foreach ( $order->get_shipments() as $shipment ) {
			$id     = $shipment->get_id();
			$status = isset( $_POST['shipment_status'][ $id ] ) ? wc_clean( wp_unslash( $_POST['shipment_status'][ $id ] ) ) : 'draft'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! wc_gzd_is_shipment_status( $status ) ) {
				$status = 'draft';
			}

			$shipment->set_status( $status );
		}
	}

	protected static function init_order_object( $post ) {
		if ( is_callable( array( '\Automattic\WooCommerce\Utilities\OrderUtil', 'init_theorder_object' ) ) ) {
			\Automattic\WooCommerce\Utilities\OrderUtil::init_theorder_object( $post );
		} else {
			global $post, $thepostid, $theorder;

			if ( ! is_int( $thepostid ) ) {
				$thepostid = $post->ID;
			}

			if ( ! is_object( $theorder ) ) {
				$theorder = wc_get_order( $thepostid );
			}
		}
	}

	/**
	 * Output the metabox.
	 *
	 * @param \WP_Post $post
	 */
	public static function output( $post ) {
		global $theorder;

		self::init_order_object( $post );

		$order           = $theorder;
		$order_shipment  = wc_gzd_get_shipment_order( $order );
		$active_shipment = isset( $_GET['shipment_id'] ) ? absint( $_GET['shipment_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		include Package::get_path() . '/includes/admin/views/html-order-shipments.php';
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 */
	public static function save( $order_id ) {
		// Get order object.
		$order_shipment = wc_gzd_get_shipment_order( $order_id );

		self::refresh_shipments( $order_shipment );

		$order_shipment->validate_shipments( array( 'save' => false ) );

		// Refresh status just before saving
		self::refresh_status( $order_shipment );

		$order_shipment->save();
	}
}
