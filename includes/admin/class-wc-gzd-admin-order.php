<?php
/**
 * Hook into item tax recalculation to ensure tax shares are calculated correctly.
 *
 * @author      Vendidero
 * @category    Admin
 * @package     WooCommerceGermanized/Admin
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

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
			add_action( 'woocommerce_order_item_shipping_after_calculate_taxes', array(
				$this,
				'adjust_item_taxes'
			), 10, 2 );
		}

		if ( 'yes' === get_option( 'woocommerce_gzd_fee_tax' ) ) {
			add_action( 'woocommerce_order_item_fee_after_calculate_taxes', array(
				$this,
				'adjust_item_taxes'
			), 10, 2 );
		}
	}

	public function adjust_item_taxes( $item, $for ) {

		if ( $item->get_total() <= 0 ) {
			return;
		}

		if ( $order = $item->get_order() ) {

			// Calculate tax shares
			$tax_share = $this->get_order_tax_share( $order, is_a( $item, 'WC_Order_Item_Shipping' ) ? 'shipping' : 'fee' );

			// Do only adjust taxes if tax share contains more than one tax rate
			if ( $tax_share && ! empty( $tax_share ) && sizeof( $tax_share ) > 1 ) {
				$taxes    = array();
				$old_item = $order->get_item( $item->get_id() );

				// Lets grab a fresh copy (loaded from DB) to make sure we are not dependent on Woo's calculated taxes in $item.
				if ( $old_item ) {
					$item_total = $old_item->get_total() + $old_item->get_total_tax();
				} else {
					$item_total = $item->get_total() + $item->get_totaL_tax();
				}

				foreach ( $tax_share as $rate => $class ) {
					$tax_rates = WC_Tax::get_rates( $rate );
					$taxes     = $taxes + WC_Tax::calc_tax( ( $item_total * $class['share'] ), $tax_rates, true );
					// Apply the same total tax rounding as we do in WC_GZD_Shipping_Rate::set_shared_taxes
					$taxes     = array_map( 'wc_round_tax_total', $taxes );
				}

				$item->set_taxes( array( 'total' => $taxes ) );

				// The new net total equals old gross total minus new tax totals
				$item->set_total( $item_total - $item->get_total_tax() );
			}
		}
	}

	/**
	 * @param WC_Order $order
	 * @param string $type
	 *
	 * @return array
	 */
	public function get_order_tax_share( $order, $type = 'shipping' ) {
		return wc_gzd_get_cart_tax_share( $type, $order->get_items() );
	}
}

WC_GZD_Admin_Order::instance();