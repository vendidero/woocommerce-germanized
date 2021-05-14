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

    public static function init() {
        if ( 'yes' === Package::get_setting( 'auto_enable' ) ) {
        	$statuses = (array) Package::get_setting( 'auto_statuses' );

        	if ( ! empty( $statuses ) ) {
        		foreach( $statuses as $status ) {
					$status = str_replace( 'wc-', '', $status );

			        add_action( 'woocommerce_order_status_' . $status, array( __CLASS__, 'maybe_create_shipments' ), 10, 1 );
		        }
	        } else {
		        add_action( 'woocommerce_new_order', array( __CLASS__, 'maybe_create_shipments' ), 10, 1 );
	        }
        }

        if ( 'yes' === Package::get_setting( 'auto_order_shipped_completed_enable' ) ) {
	        add_action( 'woocommerce_gzd_shipments_order_shipped', array( __CLASS__, 'mark_order_completed' ), 10 );
        }

	    if ( 'yes' === Package::get_setting( 'auto_order_completed_shipped_enable' ) ) {
		    add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'maybe_mark_shipments_shipped' ), 150, 4 );
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
	    return ( isset( $_POST['action'] ) && 'editpost' === $_POST['action'] && isset( $_POST['post_type'] ) && 'shop_order' === $_POST['post_type'] );
    }

    public static function mark_shipments_shipped( $order_id ) {

    	if ( $order = wc_get_order( $order_id ) ) {
		    if ( $shipment_order = wc_gzd_get_shipment_order( $order ) ) {
			    foreach( $shipment_order->get_simple_shipments() as $shipment ) {

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
			$mark_as_completed = ! in_array( $order->get_payment_method(), array( 'invoice' ) ) ? true : false;

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
			$order->update_status( apply_filters( 'woocommerce_gzd_shipment_order_completed_status', 'completed', $order_id ) , _x( 'Order is fully shipped.', 'shipments', 'woocommerce-germanized' ) );
		}
    }

    public static function create_shipments( $order_id, $enable_auto_filter = true ) {
	    $shipment_status = Package::get_setting( 'auto_default_status' );

	    if ( empty( $shipment_status ) ) {
	    	$shipment_status = 'processing';
	    }

	    /**
	     * Filter to disable automatically creating shipments for a specific order.
	     *
	     * @param string  $enable Whether to create or not create shipments.
	     * @param integer $order_id The order id.
	     *
	     * @since 3.1.0
	     * @package Vendidero/Germanized/Shipments
	     */
	    if ( $enable_auto_filter && ! apply_filters( 'woocommerce_gzd_auto_create_shipments_for_order', true, $order_id ) ) {
	    	return;
	    }

	    if ( $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {
	    	if ( ! apply_filters( 'woocommerce_gzd_auto_create_custom_shipments_for_order', false, $order_id ) ) {
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

	    	do_action( 'woocommerce_gzd_after_auto_create_shipments_for_order', $order_id, $shipment_status );
	    }
    }

    public static function maybe_create_shipments( $order_id ) {

    	// Make sure that MetaBox is saved before we process automation
    	if ( self::is_admin_edit_order_request() ) {
			add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'create_shipments' ), 70 );
	    } else {
    		self::create_shipments( $order_id );
	    }
    }
}
