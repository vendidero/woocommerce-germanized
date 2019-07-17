<?php
/**
 * Hook into item tax recalculation to ensure tax shares are calculated correctly.
 *
 * @author      Vendidero
 * @category    Admin
 * @package     WooCommerceGermanized/Admin
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

class WC_GZD_Admin_Order {

    protected static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        if ( 'yes' === get_option( 'woocommerce_gzd_shipping_tax' ) ) {
            add_action( 'woocommerce_order_item_shipping_after_calculate_taxes', array( $this, 'adjust_item_taxes' ), 10, 2 );
        }

        if ( 'yes' === get_option( 'woocommerce_gzd_fee_tax' ) ) {
            add_action( 'woocommerce_order_item_fee_after_calculate_taxes', array( $this, 'adjust_item_taxes' ), 10, 2 );
        }
    }

    public function adjust_item_taxes( $item, $for ) {

        if ( $item->get_total() <= 0 ) {
            return;
        }

        if ( $order = $item->get_order() ) {

            // Calculate tax shares
            $tax_share = $this->get_order_tax_share( $order, is_a( $item, 'WC_Order_Item_Shipping' ) ? 'shipping' : 'fee' );

            if ( $tax_share && ! empty( $tax_share ) ) {
                $taxes      = array();
                $old_item   = $order->get_item( $item->get_id() );

                // Lets grab a fresh copy (loaded from DB) to make sure we are not dependent on Woo's calculated taxes in $item.
                if ( $old_item ) {
                    $item_total = $old_item->get_total() + $old_item->get_total_tax();
                } else {
                    $item_total = $item->get_total() + $item->get_totaL_tax();
                }

                foreach ( $tax_share as $rate => $class ) {
                    $tax_rates = WC_Tax::get_rates( $rate );
                    $taxes     = $taxes + WC_Tax::calc_tax( ( $item_total * $class['share'] ), $tax_rates, true );
                }

                $item->set_taxes( array( 'total' => $taxes ) );

                // The new net total equals old gross total minus new tax totals
                $item->set_total( $item_total - $item->get_total_tax() );
            }
        }
    }

    public function get_order_tax_share( $order, $type = 'shipping' ) {
        $tax_shares  = array();
        $item_totals = 0;

        foreach ( $order->get_items() as $key => $item ) {

            $_product          = $item->get_product();
            $no_shipping       = false;

            if ( ! $_product ) {
                continue;
            }

            if ( 'shipping' === $type ) {
                if ( $_product->is_virtual() || wc_gzd_get_gzd_product( $_product )->is_virtual_vat_exception() ) {
                    $no_shipping = true;
                }

                $tax_status = wc_gzd_get_crud_data( $_product, 'tax_status' );
                $tax_class  = $_product->get_tax_class();

                if ( 'none' === $tax_status || 'zero-rate' === $tax_class ) {
                    $no_shipping = true;
                }
            }

            /**
             * Filter to disable tax share calculation for a certain order item.
             *
             * @since 2.3.0
             *
             * @param bool          $no_shipping Set to false to disable tax share calculation for this item.
             * @param WC_Order_Item $item The order item.
             * @param string        $key The item key.
             * @param string        $type The tax share type e.g. shipping or fees.
             */
            if ( apply_filters( 'woocommerce_gzd_order_item_not_supporting_tax_share', $no_shipping, $item, $key, $type ) ) {
                continue;
            }

            $class = $_product->get_tax_class();

            if ( ! isset( $tax_shares[ $class ] ) ) {
                $tax_shares[ $class ] = array();
                $tax_shares[ $class ]['total'] = 0;
            }

            $item_total = ( $item->get_total() + $item->get_total_tax() );

            $tax_shares[ $class ]['total'] += $item_total;

            $item_totals += $item_total;
        }

        if ( ! empty( $tax_shares ) ) {
            $default = ( $item_totals == 0 ? 1 / sizeof( $tax_shares ) : 0 );

            foreach ( $tax_shares as $key => $class ) {
                $tax_shares[ $key ]['share'] = ( $item_totals > 0 ? $class['total'] / $item_totals : $default );
            }
        }

        return $tax_shares;
    }
}

WC_GZD_Admin_Order::instance();