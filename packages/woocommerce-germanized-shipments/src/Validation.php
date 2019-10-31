<?php

namespace Vendidero\Germanized\Shipments;
use Exception;

defined( 'ABSPATH' ) || exit;

class Validation {

    private static $current_refund_parent_order = false;

    public static function init() {
        add_action( 'woocommerce_update_order_item', array( __CLASS__, 'update_order_item' ), 10, 3 );
        add_action( 'woocommerce_new_order_item', array( __CLASS__, 'create_order_item' ), 10, 3 );
        add_action( 'woocommerce_before_delete_order_item', array( __CLASS__, 'delete_order_item' ), 10, 1 );

        add_action( 'woocommerce_update_order', array( __CLASS__, 'update_order' ), 10, 1 );
        add_action( 'woocommerce_new_order', array( __CLASS__, 'new_order' ), 10, 1 );
        add_action( 'woocommerce_delete_order', array( __CLASS__, 'delete_order' ), 10, 1 );

        add_action( 'before_delete_post', array( __CLASS__, 'before_delete_refund' ), 10, 1 );
        add_action( 'woocommerce_delete_order_refund', array( __CLASS__, 'delete_refund_order' ), 10, 1 );
        add_action( 'woocommerce_order_refund_object_updated_props', array( __CLASS__, 'refresh_refund_order' ), 10, 1 );
    }

    public static function before_delete_refund( $refund_id ) {
        if ( $refund = wc_get_order( $refund_id ) ) {

            if ( is_a( $refund, 'WC_Order_Refund' ) ) {
                self::$current_refund_parent_order = $refund->get_parent_id();
            }
        }
    }

    public static function delete_refund_order( $refund_id ) {
        if ( self::$current_refund_parent_order !== false ) {

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

            foreach( $order_shipment->get_shipments() as $shipment ) {

                if ( $shipment->is_editable() ) {
                    $order_shipment->remove_shipment( $shipment->get_id() );
                }
            }

            $order_shipment->save();
        }
    }

    public static function new_order( $order_id ) {
        if ( $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {
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
                    foreach( $order_shipment->get_shipments() as $shipment ) {

                        if ( $shipment->is_editable() ) {
                            if ( $item = $shipment->get_item_by_order_item_id( $order_item_id ) ) {
                                $shipment->remove_item( $item->get_id() );
                            }
                        }
                    }

                    $order_shipment->save();
                }
            }
        } catch( Exception $e ) {}
    }

    public static function create_order_item( $order_item_id, $order_item, $order_id ) {
        if ( $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {
            $order_shipment->validate_shipments();
        }
    }

    public static function update_order_item( $order_item_id, $order_item, $order_id ) {
        if ( $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {
            $order_shipment->validate_shipments();
        }
    }
}
