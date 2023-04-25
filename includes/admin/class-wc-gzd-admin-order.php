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
		if ( wc_gzd_enable_additional_costs_split_tax_calculation() || wc_gzd_calculate_additional_costs_taxes_based_on_main_service() ) {
			add_action(
				'woocommerce_order_item_shipping_after_calculate_taxes',
				array(
					$this,
					'adjust_item_taxes',
				),
				10,
				2
			);

			add_action(
				'woocommerce_order_item_fee_after_calculate_taxes',
				array(
					$this,
					'adjust_item_taxes',
				),
				10,
				2
			);

			add_action(
				'woocommerce_order_item_after_calculate_taxes',
				array(
					$this,
					'adjust_item_taxes',
				),
				10,
				2
			);

			add_action(
				'woocommerce_order_before_calculate_totals',
				array(
					$this,
					'set_shipping_total_filter',
				),
				500,
				2
			);

			add_action(
				'woocommerce_order_after_calculate_totals',
				array(
					$this,
					'remove_shipping_total_filter',
				),
				500
			);

			add_action(
				'woocommerce_create_refund',
				array(
					$this,
					'fix_refund_precision',
				),
				1,
				2
			);
		}
	}

	/**
	 * WooCommerce does by default not show full precision amounts for shipping and fees in admin panel
	 * that's why the refund (tax) amount entered by the shop owner might differ from the actual amount (with full precision)
	 * in the corresponding parent item. In case the rounded amounts equal, use the higher-precision amounts from the parent item instead.
	 *
	 * @param WC_Order_Refund $refund
	 * @param $args
	 *
	 * @return void
	 */
	public function fix_refund_precision( $refund, $args ) {
		if ( count( $args['line_items'] ) > 0 ) {
			if ( $order = wc_get_order( $refund->get_parent_id() ) ) {
				$refund_needs_save = false;
				$items             = $order->get_items( array( 'fee', 'shipping' ) );
				$refund_items      = $refund->get_items( array( 'fee', 'shipping' ) );

				foreach ( $refund_items as $refunded_item ) {
					$item_id = absint( $refunded_item->get_meta( '_refunded_item_id' ) );

					if ( isset( $items[ $item_id ] ) ) {
						$item                 = $items[ $item_id ];
						$needs_save           = false;
						$item_total_rounded   = wc_format_decimal( $item->get_total(), '' );
						$refund_total_rounded = wc_format_decimal( $refunded_item->get_total() * -1, '' );

						$item_tax_rounded   = wc_format_decimal( $item->get_total_tax(), '' );
						$refund_tax_rounded = wc_format_decimal( $refunded_item->get_total_tax() * -1, '' );

						if ( $item_total_rounded === $refund_total_rounded ) {
							$needs_save = true;
							$refunded_item->set_total( wc_format_refund_total( $item->get_total() ) );
						}

						if ( $item_tax_rounded === $refund_tax_rounded ) {
							$total_taxes    = array_map( 'floatval', $item->get_taxes()['total'] );
							$subtotal_taxes = isset( $item->get_taxes()['subtotal'] ) ? array_map( 'floatval', $item->get_taxes()['subtotal'] ) : $total_taxes;

							$needs_save = true;
							$refunded_item->set_taxes(
								array(
									'total'    => array_map( 'wc_format_refund_total', $total_taxes ),
									'subtotal' => array_map( 'wc_format_refund_total', $subtotal_taxes ),
								)
							);
						}

						if ( $needs_save ) {
							$refund_needs_save = true;

							$refunded_item->save();
						}
					}
				}

				if ( $refund_needs_save ) {
					$refund->update_taxes();
					$refund->calculate_totals( false );
				}
			}
		}
	}

	/**
	 * When (re-) calculation order totals Woo does round shipping total to current price decimals.
	 * That is not the case within cart/checkout and leads to rounding issues. This filter forces recalculating
	 * the exact shipping total instead of using the already calculated shipping total amount while calculating order totals.
	 *
	 * @see WC_Abstract_Order::calculate_totals()
	 *
	 * @param $and_taxes
	 * @param WC_Order $order
	 */
	public function set_shipping_total_filter( $and_taxes, $order ) {
		add_filter( 'woocommerce_order_get_shipping_total', array( $this, 'force_shipping_total_exact' ), 10, 2 );
	}

	/**
	 * Remove the filter after order totals have been calculated successfully.
	 */
	public function remove_shipping_total_filter() {
		remove_filter( 'woocommerce_order_get_shipping_total', array( $this, 'force_shipping_total_exact' ), 10 );
	}

	/**
	 * @param $total
	 * @param WC_Order $order
	 */
	public function force_shipping_total_exact( $total, $order ) {
		$total = 0;

		foreach ( $order->get_shipping_methods() as $method ) {
			$total += floatval( $method->get_total() );
		}

		return $total;
	}

	/**
	 * @param WC_Order_Item $item
	 * @param array $calculate_tax_for
	 */
	public function adjust_item_taxes( $item, $calculate_tax_for = array() ) {
		if ( ! wc_tax_enabled() || ! in_array( $item->get_type(), array( 'fee', 'shipping' ), true ) ) {
			return;
		}

		if ( apply_filters( 'woocommerce_gzd_skip_order_item_split_tax_calculation', false, $item ) ) {
			return;
		}

		if ( $order = $item->get_order() ) {
			$item->delete_meta_data( '_split_taxes' );
			$item->delete_meta_data( '_tax_shares' );

			$order->delete_meta_data( '_has_split_tax' );
			$order->delete_meta_data( '_additional_costs_taxed_based_on_main_service' );
			$order->delete_meta_data( '_additional_costs_taxed_based_on_main_service_tax_class' );
			$order->delete_meta_data( '_additional_costs_taxed_based_on_main_service_by' );

			$calculate_tax_for = empty( $calculate_tax_for ) ? $this->get_order_taxable_location( $order ) : $calculate_tax_for;
			$tax_type          = 'shipping' === $item->get_type() ? 'shipping' : 'fee';

			if ( wc_gzd_enable_additional_costs_split_tax_calculation() ) {
				// Calculate tax shares
				$tax_share = apply_filters( "woocommerce_gzd_{$tax_type}_order_tax_shares", $this->get_order_tax_share( $order, $tax_type ), $item );

				// Do only adjust taxes if tax share contains more than one tax rate
				if ( $tax_share && ! empty( $tax_share ) ) {
					$taxes           = array();
					$item_total      = $this->get_item_total( $item, $order->get_item( $item->get_id() ) );
					$taxable_amounts = array();

					foreach ( $tax_share as $tax_class => $class ) {
						if ( isset( $calculate_tax_for['country'] ) ) {
							$calculate_tax_for['tax_class'] = $tax_class;
							$tax_rates                      = \WC_Tax::find_rates( $calculate_tax_for );
						} else {
							$tax_rates = \WC_Tax::get_rates_from_location( $tax_class, $calculate_tax_for );
						}

						$taxable_amount  = $item_total * $class['share'];
						$tax_class_taxes = WC_Tax::calc_tax( $taxable_amount, $tax_rates, wc_gzd_additional_costs_include_tax() );
						$net_base        = wc_gzd_additional_costs_include_tax() ? ( $taxable_amount - array_sum( $tax_class_taxes ) ) : $taxable_amount;

						$taxable_amounts[ $tax_class ] = array(
							'taxable_amount' => $taxable_amount,
							'tax_share'      => $class['share'],
							'tax_rates'      => array_keys( $tax_rates ),
							'net_amount'     => $net_base,
							'includes_tax'   => wc_gzd_additional_costs_include_tax(),
						);

						$taxes = $taxes + $tax_class_taxes;
					}

					$item->set_taxes( array( 'total' => $taxes ) );
					$item->update_meta_data( '_split_taxes', $taxable_amounts );
					$item->update_meta_data( '_tax_shares', $tax_share );

					// The new net total equals old gross total minus new tax totals
					if ( wc_gzd_additional_costs_include_tax() ) {
						$item->set_total( $item_total - $item->get_total_tax() );
					}

					$order->update_meta_data( '_has_split_tax', 'yes' );
				} else {
					$item->delete_meta_data( '_split_taxes' );
					$item->delete_meta_data( '_tax_shares' );

					$order->delete_meta_data( '_has_split_tax' );
				}
			} elseif ( wc_gzd_calculate_additional_costs_taxes_based_on_main_service() ) {
				$taxes          = array();
				$main_tax_class = WC_GZD_Order_Helper::instance()->get_order_main_service_tax_class( $order, $tax_type );

				if ( false !== $main_tax_class ) {
					$item_total = $this->get_item_total( $item, $order->get_item( $item->get_id() ) );

					if ( isset( $calculate_tax_for['country'] ) ) {
						$calculate_tax_for['tax_class'] = $main_tax_class;
						$tax_rates                      = \WC_Tax::find_rates( $calculate_tax_for );
					} else {
						$tax_rates = \WC_Tax::get_rates_from_location( $main_tax_class, $calculate_tax_for );
					}

					$taxable_amount  = $item_total;
					$tax_class_taxes = WC_Tax::calc_tax( $taxable_amount, $tax_rates, wc_gzd_additional_costs_include_tax() );
					$taxes           = $taxes + $tax_class_taxes;

					$item->set_taxes( array( 'total' => $taxes ) );

					if ( is_callable( array( $item, 'set_tax_class' ) ) ) {
						$item->set_tax_class( $main_tax_class );
					}

					// The new net total equals old gross total minus new tax totals
					if ( wc_gzd_additional_costs_include_tax() ) {
						$item->set_total( $item_total - $item->get_total_tax() );
					}

					$order->update_meta_data( '_additional_costs_taxed_based_on_main_service', 'yes' );
					$order->update_meta_data( '_additional_costs_taxed_based_on_main_service_by', wc_gzd_additional_costs_taxes_detect_main_service_by() );
					$order->update_meta_data( '_additional_costs_taxed_based_on_main_service_tax_class', $main_tax_class );
				}
			}

			$order->update_meta_data( '_additional_costs_include_tax', wc_bool_to_string( wc_gzd_additional_costs_include_tax() ) );

			/**
			 * Need to manually call the order save method to make sure
			 * meta data is persisted as $item->get_order() constructs a fresh order instance which will be lost
			 * during global save event.
			 */
			$order->save();
		}
	}

	/**
	 * @param WC_Order_Item $item
	 * @param WC_Order_Item|false $old_item
	 *
	 * @return float
	 */
	protected function get_item_total( $item, $old_item = false ) {
		// Let's grab a fresh copy (loaded from DB) to make sure we are not dependent on Woo's calculated taxes in $item.
		if ( $old_item ) {
			$item_total = $old_item->get_total();

			if ( wc_gzd_additional_costs_include_tax() ) {
				$item_total += $old_item->get_total_tax();
			}
		} else {
			$item_total     = $item->get_total();
			$is_adding_item = wp_doing_ajax() && isset( $_POST['action'] ) && in_array( wp_unslash( $_POST['action'] ), array( 'woocommerce_add_order_fee', 'woocommerce_add_order_shipping' ), true ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			/**
			 * When adding a fee through the admin panel, Woo by default calculates taxes
			 * based on the fee's tax class (which by default is standard). Ignore the tax data on first call.
			 */
			if ( ! $is_adding_item && wc_gzd_additional_costs_include_tax() ) {
				$item_total += $item->get_total_tax();
			}
		}

		return $item_total;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public function get_order_taxable_location( $order ) {
		return \Vendidero\EUTaxHelper\Helper::get_order_taxable_location( $order );
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
