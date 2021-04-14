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

        foreach( $order->get_shipments() as $shipment ) {

            $id    = $shipment->get_id();
            $props = array();

            // Update items
            self::refresh_shipment_items( $order, $shipment );

            // Do only update props if they exist
            if ( isset( $_POST['shipment_weight'][ $id ] ) ) {
                $props['weight'] = wc_clean( wp_unslash( $_POST['shipment_weight'][ $id ] ) );
            }

            if ( isset( $_POST['shipment_length'][ $id ] ) ) {
                $props['length'] = wc_clean( wp_unslash( $_POST['shipment_length'][ $id ] ) );
            }

            if ( isset( $_POST['shipment_width'][ $id ] ) ) {
                $props['width'] = wc_clean( wp_unslash( $_POST['shipment_width'][ $id ] ) );
            }

            if ( isset( $_POST['shipment_height'][ $id ] ) ) {
                $props['height'] = wc_clean( wp_unslash( $_POST['shipment_height'][ $id ] ) );
            }

	        if ( isset( $_POST['shipment_shipping_method'][ $id ] ) ) {
		        $props['shipping_method'] = wc_clean( wp_unslash( $_POST['shipment_shipping_method'][ $id ] ) );
	        }

	        if ( isset( $_POST['shipment_tracking_id'][ $id ] ) ) {
		        $props['tracking_id'] = wc_clean( wp_unslash( $_POST['shipment_tracking_id'][ $id ] ) );
	        }

	        if ( isset( $_POST['shipment_packaging_id'][ $id ] ) ) {
		        $props['packaging_id'] = wc_clean( wp_unslash( $_POST['shipment_packaging_id'][ $id ] ) );
	        }

	        if ( isset( $_POST['shipment_shipping_provider'][ $id ] ) ) {
	        	$provider  = wc_clean( wp_unslash( $_POST['shipment_shipping_provider'][ $id ] ) );
	        	$providers = wc_gzd_get_shipping_providers();

	        	if ( empty( $provider ) || array_key_exists( $provider, $providers ) ) {
			        $props['shipping_provider'] = $provider;
		        }
	        }

	        $new_status = isset( $_POST['shipment_status'][ $id ] ) ? str_replace( 'gzd-', '', wc_clean( wp_unslash( $_POST['shipment_status'][ $id ] ) ) ) : 'draft';

	        // Sync the shipment - make sure gets refresh on status switch (e.g. from shipped to processing)
            if ( $shipment->is_editable() || in_array( $new_status, wc_gzd_get_shipment_editable_statuses() ) ) {
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

        foreach( $shipments as $shipment ) {
            $id = $shipment->get_id();

            if ( ! $shipment->is_editable() ) {
                continue;
            }

            // Update items
            foreach( $shipment->get_items() as $item ) {
                $item_id = $item->get_id();
                $props   = array();

                // Set quantity to 1 by default
                if ( $shipment->is_editable() ) {
                    $props['quantity'] = 1;
                }

                if ( isset( $_POST['shipment_item'][ $id ]['quantity'][ $item_id ] ) ) {
                    $props['quantity'] = absint( wp_unslash( $_POST['shipment_item'][ $id ]['quantity'][ $item_id ] ) );
                }

	            if ( isset( $_POST['shipment_item'][ $id ]['return_reason_code'][ $item_id ] ) ) {
		            $props['return_reason_code'] = wc_clean( wp_unslash( $_POST['shipment_item'][ $id ]['return_reason_code'][ $item_id ] ) );
	            }

                $item->sync( $props );
            }
        }
    }

    /**
     * @param Order $order
     */
    public static function refresh_status( &$order ) {

        foreach( $order->get_shipments() as $shipment ) {

            $id     = $shipment->get_id();
            $status = isset( $_POST['shipment_status'][ $id ] ) ? wc_clean( wp_unslash( $_POST['shipment_status'][ $id ] ) ) : 'draft';

            if ( ! wc_gzd_is_shipment_status( $status ) ) {
                $status = 'draft';
            }

            $shipment->set_status( $status );
        }
    }

    /**
     * Output the metabox.
     *
     * @param WP_Post $post
     */
    public static function output( $post ) {
        global $post, $thepostid, $theorder;

        if ( ! is_int( $thepostid ) ) {
            $thepostid = $post->ID;
        }

        if ( ! is_object( $theorder ) ) {
            $theorder = wc_get_order( $thepostid );
        }

        $order           = $theorder;
        $order_shipment  = wc_gzd_get_shipment_order( $order );
        $active_shipment = isset( $_GET['shipment_id'] ) ? absint( $_GET['shipment_id'] ) : 0;

        include( Package::get_path() . '/includes/admin/views/html-order-shipments.php' );
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
