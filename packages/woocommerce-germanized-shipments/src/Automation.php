<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\Shipments;

use Vendidero\Germanized\Shipments\Admin\MetaBox;

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
    }

    public static function create_shipments( $order_id ) {
	    $shipment_status = Package::get_setting( 'auto_default_status' );

	    if ( empty( $shipment_status ) ) {
	    	$shipment_status = 'processing';
	    }

	    if ( $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {
		    $shipments = $order_shipment->get_simple_shipments();

		    foreach( $shipments as $shipment ) {
			    if ( $shipment->is_editable() ) {
			    	$shipment->sync();
			    	$shipment->sync_items();
				    $shipment->set_status( $shipment_status );
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
    }

    public static function maybe_create_shipments( $order_id ) {

    	// Make sure that MetaBox is saved before we process automation
    	if ( isset( $_POST['action'] ) && 'editpost' === $_POST['action'] && isset( $_POST['post_type'] ) && 'shop_order' === $_POST['post_type'] ) {
			add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'create_shipments' ), 70 );
	    } else {
    		self::create_shipments( $order_id );
	    }
    }
}
