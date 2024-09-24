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
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 35 );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_editable_checkboxes' ), 45 );

		if ( wc_gzd_enable_additional_costs_split_tax_calculation() || wc_gzd_calculate_additional_costs_taxes_based_on_main_service() ) {
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

	public function get_order_screen_id() {
		return function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	}

	protected function init_order_object( $post ) {
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

	public function save_editable_checkboxes( $order_id ) {
		$checkboxes = WC_GZD_Legal_Checkbox_Manager::instance()->get_editable_checkboxes( 'order' );
		$visible    = isset( $_POST['_checkboxes_visible'] ) ? (array) wc_clean( wp_unslash( $_POST['_checkboxes_visible'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( $order = wc_get_order( $order_id ) ) {
			$updated = false;
			if ( ! empty( $checkboxes ) ) {
				foreach ( $checkboxes as $checkbox_id ) {
					if ( ! in_array( $checkbox_id, $visible, true ) ) {
						continue;
					}

					$is_checked = isset( $_POST[ "_checkbox_{$checkbox_id}" ] ) ? wc_string_to_bool( wc_clean( wp_unslash( $_POST[ "_checkbox_{$checkbox_id}" ] ) ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

					if ( 'parcel_delivery' === $checkbox_id ) {
						$order->update_meta_data( '_parcel_delivery_opted_in', wc_bool_to_string( $is_checked ) );
					} elseif ( 'photovoltaic_systems' === $checkbox_id ) {
						$order->update_meta_data( '_photovoltaic_systems_opted_in', wc_bool_to_string( $is_checked ) );
					} else {
						$order->update_meta_data( "_checkbox_{$checkbox_id}", wc_bool_to_string( $is_checked ) );
					}

					$updated = true;
				}
			}

			if ( $updated ) {
				$order->save();
			}
		}
	}

	public function add_meta_boxes() {
		$order_type_screen_ids = array_merge( wc_get_order_types( 'order-meta-boxes' ), array( $this->get_order_screen_id() ) );

		// Orders.
		foreach ( $order_type_screen_ids as $type ) {
			add_meta_box(
				'woocommerce-gzd-order-checkboxes',
				__( 'Checkboxes', 'woocommerce-germanized' ),
				function ( $post ) {
					global $theorder;
					$this->init_order_object( $post );
					$order = $theorder;

					$this->render_checkboxes_meta_box( $order );
				},
				$type,
				'side',
				'default'
			);
		}
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	protected function render_checkboxes_meta_box( $order ) {
		WC_GZD_Legal_Checkbox_Manager::instance()->render_checkbox_log( $order, 'order' );
	}

	/**
	 * @param WC_Order_Item $item
	 *
	 * @return void
	 */
	public function remove_additional_costs_meta( $item ) {
		wc_deprecated_function( 'WC_GZD_Admin_Order::remove_additional_costs_meta', '3.15.6', 'WC_GZD_Order_Helper::remove_additional_costs_item_meta' );

		WC_GZD_Order_Helper::instance()->remove_additional_costs_item_meta( $item );
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
	 * @param WC_Order_Item $item
	 * @param array $calculate_tax_for
	 */
	public function adjust_item_taxes( $item, $calculate_tax_for = array() ) {
		wc_deprecated_function( 'WC_GZD_Admin_Order::adjust_item_taxes', '3.15.6', 'WC_GZD_Order_Helper::adjust_additional_costs_item_taxes' );

		WC_GZD_Order_Helper::instance()->adjust_additional_costs_item_taxes( $item, $calculate_tax_for );
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
		wc_deprecated_function( 'WC_GZD_Admin_Order::get_order_tax_share', '3.15.6', 'WC_GZD_Order_Helper::get_order_tax_share' );

		return wc_gzd_get_cart_tax_share( $type, $order->get_items() );
	}
}

WC_GZD_Admin_Order::instance();
