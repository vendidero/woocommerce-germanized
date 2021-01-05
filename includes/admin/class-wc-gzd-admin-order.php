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
		if ( wc_gzd_enable_additional_costs_split_tax_calculation() ) {
			add_action( 'woocommerce_order_item_shipping_after_calculate_taxes', array(
				$this,
				'adjust_item_taxes'
			), 10 );

			add_action( 'woocommerce_order_item_fee_after_calculate_taxes', array(
				$this,
				'adjust_item_taxes'
			), 10 );

			add_action( 'woocommerce_order_item_after_calculate_taxes', array(
				$this,
				'adjust_item_taxes'
			), 10 );
		}
	}

	/**
	 * @param WC_Order_Item $item
	 * @param $for
	 */
	public function adjust_item_taxes( $item ) {
		if ( ! wc_tax_enabled() || $item->get_total() <= 0 || ! in_array( $item->get_type(), array( 'fee', 'shipping' ) ) ) {
			return;
		}

		if ( $order = $item->get_order() ) {
			// Calculate tax shares
			$tax_share = $this->get_order_tax_share( $order, 'shipping' === $item->get_type() ? 'shipping' : 'fee' );

			// Do only adjust taxes if tax share contains more than one tax rate
			if ( $tax_share && ! empty( $tax_share ) && sizeof( $tax_share ) > 1 ) {
				$taxes    = array();
				$old_item = $order->get_item( $item->get_id() );

				// Lets grab a fresh copy (loaded from DB) to make sure we are not dependent on Woo's calculated taxes in $item.
				if ( $old_item ) {
					$item_total = $old_item->get_total();

					if ( wc_gzd_additional_costs_include_tax() ) {
						$item_total += $old_item->get_total_tax();
					}
				} else {
					$item_total = $item->get_total();

					if ( wc_gzd_additional_costs_include_tax() ) {
						$item_total += $item->get_total_tax();
					}
				}

				foreach ( $tax_share as $rate => $class ) {
					$tax_rates = WC_Tax::get_rates( $rate );
					$taxes     = $taxes + WC_Tax::calc_tax( ( $item_total * $class['share'] ), $tax_rates, wc_gzd_additional_costs_include_tax() );
				}

				$item->set_taxes( array( 'total' => $taxes ) );

				// The new net total equals old gross total minus new tax totals
				if ( wc_gzd_additional_costs_include_tax() ) {
					$item->set_total( $item_total - $item->get_total_tax() );
				}

				$order->update_meta_data( '_has_split_tax', 'yes' );
			} else {
				$order->delete_meta_data( '_has_split_tax' );
			}

			$order->update_meta_data( '_additional_costs_include_tax', wc_bool_to_string( wc_gzd_additional_costs_include_tax() ) );
			$order->save();
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