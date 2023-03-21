<?php

use Vendidero\Germanized\Utilities\NumberUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_GZD_Coupon_Helper
 */
class WC_GZD_Coupon_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized' ), '1.0' );
	}

	public function __construct() {
		add_action( 'woocommerce_coupon_options', array( $this, 'coupon_options' ), 10, 2 );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'coupon_save' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_coupon_item', array( $this, 'coupon_item_save' ), 10, 4 );

		add_action( 'woocommerce_applied_coupon', array( $this, 'on_apply_voucher' ), 10, 1 );

		$this->register_coupon_validation_filters();

		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'vouchers_as_fees' ), 10000 );
		add_action( 'woocommerce_checkout_create_order_fee_item', array( $this, 'fee_item_save' ), 10, 4 );

		add_filter( 'woocommerce_gzd_force_fee_tax_calculation', array( $this, 'exclude_vouchers_from_forced_tax' ), 10, 2 );
		add_filter( 'woocommerce_cart_totals_get_fees_from_cart_taxes', array( $this, 'remove_taxes_for_vouchers' ), 10, 3 );

		add_action( 'woocommerce_order_item_fee_after_calculate_taxes', array( $this, 'remove_order_item_fee_taxes' ), 10 );
		add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'limit_order_voucher_discounts_callback' ), 15, 2 );

		add_action( 'woocommerce_before_order_item_object_save', array( $this, 'on_order_item_coupon_save' ), 5 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'voucher_fragments' ), 10, 1 );

		/**
		 * Some plugins, e.g. Gift card plugins may reduce the coupon amount based on the discount total of
		 * the corresponding WC_Order_Item_Coupon. Temporarily register a filter while adding new items to adjust
		 * the actual discount amount. This filter cannot be used globally as it would distort the order discount amount.
		 */
		add_action( 'woocommerce_new_order_item', array( $this, 'register_coupon_item_discount_total_filter' ), 0, 3 );
		add_action( 'woocommerce_new_order_item', array( $this, 'unregister_coupon_item_discount_total_filter' ), 1500, 3 );

		/**
		 * Do only add the discount total filter for the admin edit order view to make
		 * sure calculating totals does not produce wrong results.
		 */
		add_action(
			'woocommerce_admin_order_items_after_line_items',
			function() {
				add_filter( 'woocommerce_order_item_get_discount', array( $this, 'voucher_discount' ), 10, 2 );
			}
		);

		add_action(
			'woocommerce_admin_order_totals_after_discount',
			function() {
				remove_filter( 'woocommerce_order_item_get_discount', array( $this, 'voucher_discount' ), 10 );
			}
		);

		add_action( 'woocommerce_order_before_calculate_totals', array( $this, 'observe_order_voucher_removal' ), 10, 2 );
		/**
		 * Hack to make sure that execute voucher recalculation only when
		 * coupons are added/removed/recalculated as Woo does not have a specific event for this.
		 */
		add_filter( 'woocommerce_order_recalculate_coupons_coupon_object', array( $this, 'on_recalculate_order_coupons' ), 10, 1 );

		/**
		 * Legacy support for vouchers which may affect subtotal vs. total in shipment customs data.
		 */
		add_filter( 'woocommerce_gzd_shipments_order_has_voucher', array( $this, 'legacy_shipments_order_has_voucher' ), 10, 2 );
		add_action( 'wp_ajax_woocommerce_calc_line_taxes', array( $this, 'legacy_before_recalculate_totals' ), 0 );
	}

	public function register_coupon_item_discount_total_filter( $item_id, $item, $order_id ) {
		$added_filter = false;

		if ( is_a( $item, 'WC_Order_Item_Coupon' ) ) {
			if ( $this->order_item_coupon_is_voucher( $item ) ) {
				if ( $fee = $this->get_order_item_fee_by_coupon( $item, wc_get_order( $order_id ) ) ) {
					add_filter( 'woocommerce_order_item_get_discount', array( $this, 'item_discount_filter' ), 500, 2 );
					$added_filter = true;
				}
			}
		}

		if ( ! $added_filter ) {
			remove_filter( 'woocommerce_order_item_get_discount', array( $this, 'item_discount_filter' ), 500 );
		}
	}

	public function unregister_coupon_item_discount_total_filter( $item_id, $item, $order_id ) {
		remove_filter( 'woocommerce_order_item_get_discount', array( $this, 'item_discount_filter' ), 500 );
	}

	/**
	 * @param $value
	 * @param WC_Order_Item_Coupon $item
	 *
	 * @return mixed
	 */
	public function item_discount_filter( $value, $item ) {
		if ( 0.0 === (float) wc_format_decimal( $value ) ) {
			if ( $order = $item->get_order() ) {
				if ( $fee = $this->get_order_item_fee_by_coupon( $item, $order ) ) {
					$value = wc_format_decimal( floatval( $fee->get_total() ) * -1 );
				}
			}
		}

		return $value;
	}

	/**
	 * On saving a coupon order item make sure to check whether it's
	 * a voucher and transform the item to a voucher.
	 *
	 * @param WC_Order_Item $item
	 *
	 * @return void
	 */
	public function on_order_item_coupon_save( $item ) {
		if ( is_a( $item, 'WC_Order_Item_Coupon' ) && ( $order = $item->get_order() ) && ! $this->order_item_coupon_is_voucher( $item ) ) {
			$coupon_object = $this->get_voucher_by_coupon_order_item( $item );

			if ( $coupon_object && $this->coupon_is_voucher( $coupon_object ) && $this->order_supports_fee_vouchers( $order ) ) {
				$this->convert_order_item_coupon_to_voucher( $item, $coupon_object, $this->get_tax_display_mode( $order ) );
			}
		}
	}

	/**
	 * This event may be fired once per coupon stored within the order.
	 * Do only register the woocommerce_order_after_calculate_totals event once.
	 *
	 * @param $coupon
	 *
	 * @return mixed
	 */
	public function on_recalculate_order_coupons( $coupon ) {
		remove_filter( 'woocommerce_order_recalculate_coupons_coupon_object', array( $this, 'on_recalculate_order_coupons' ), 10 );

		if ( ! has_action( 'woocommerce_order_after_calculate_totals', array( $this, 'observer_order_voucher_refresh' ) ) ) {
			add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'observer_order_voucher_refresh' ), 10, 2 );
		}

		return $coupon;
	}

	protected function register_coupon_validation_filters() {
		add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'is_valid_for_product_filter' ), 1000, 3 );
		add_filter( 'woocommerce_coupon_is_valid_for_cart', array( $this, 'is_valid' ), 1000, 2 );
		add_filter( 'woocommerce_coupon_get_free_shipping', array( $this, 'is_valid_free_shipping_filter' ), 1000, 2 );
	}

	protected function unregister_coupon_validation_filters() {
		remove_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'is_valid_for_product_filter' ), 1000 );
		remove_filter( 'woocommerce_coupon_is_valid_for_cart', array( $this, 'is_valid' ), 1000 );
		remove_filter( 'woocommerce_coupon_get_free_shipping', array( $this, 'is_valid_free_shipping_filter' ), 1000 );
	}

	public function is_valid_free_shipping_filter( $free_shipping, $coupon ) {
		if ( $this->coupon_is_voucher( $coupon ) ) {
			return false;
		}

		return $free_shipping;
	}

	public function is_valid_for_product_filter( $is_valid, $product, $coupon ) {
		/**
		 * During coupon validation in WC_Discounts::validate_coupon_excluded_items()
		 * at least one product must be eligible for product based coupons, otherwise
		 * the coupon may not be applied.
		 */
		if ( $this->coupon_is_voucher( $coupon ) && $coupon->is_type( wc_get_product_coupon_types() ) ) {
			$stack = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 5 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace

			foreach ( $stack as $backtrace ) {
				if ( ! isset( $backtrace['class'], $backtrace['function'] ) ) {
					continue;
				}

				if ( 'WC_Discounts' === $backtrace['class'] && 'validate_coupon_excluded_items' === $backtrace['function'] ) {
					return true;
				}
			}
		}

		return $this->is_valid( $is_valid, $coupon );
	}

	/**
	 * @param WC_Order_Item_Coupon $item
	 * @param WC_Coupon $coupon
	 * @param string $tax_display_mode
	 *
	 * @return void
	 */
	protected function convert_order_item_coupon_to_voucher( $item, $coupon, $tax_display_mode = 'incl' ) {
		$item->update_meta_data( 'is_voucher', 'yes' );
		$item->update_meta_data( 'is_stored_as_fee', 'yes' );
		$item->update_meta_data( 'voucher_includes_shipping_costs', wc_bool_to_string( $this->voucher_includes_shipping_costs( $coupon ) ) );

		/**
		 * Store the current tax_display_mode used to calculate coupon totals
		 * as the coupon tax amount may differ depending on which mode is being used.
		 */
		$item->update_meta_data( 'tax_display_mode', $tax_display_mode );
	}

	public function observe_order_voucher_removal( $and_taxes, $order ) {
		if ( 'shop_order_refund' === $order->get_type() ) {
			return;
		}

		$this->maybe_remove_order_vouchers( $order );
	}

	/**
	 * @param boolean $and_taxes
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function observer_order_voucher_refresh( $and_taxes, $order ) {
		$updated = $this->refresh_order_vouchers( $order );

		/**
		 * Need to recalculate order totals again to include the newly created and/or updated voucher fees.
		 * Prevent infinite loops by removing the action.
		 */
		if ( $updated ) {
			remove_action( 'woocommerce_order_after_calculate_totals', array( $this, 'observer_order_voucher_refresh' ), 10 );
			$order->calculate_totals( $and_taxes );
		}
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return boolean
	 */
	protected function maybe_remove_order_vouchers( $order ) {
		$has_updated = false;

		foreach ( $order->get_fees() as $item_id => $fee ) {
			if ( $this->fee_is_voucher( $fee ) ) {
				// Check if the corresponding coupon has been removed
				if ( ! $this->get_order_item_coupon_by_fee( $fee, $order ) ) {
					$order->remove_item( $item_id );
					$has_updated = true;
				}
			}
		}

		return $has_updated;
	}

	/**
	 * As Woo does not offer a hook on coupon removal we'll need to observe the
	 * calculate totals event and remove the fee in case the coupon is missing.
	 *
	 * @param WC_Order $order
	 * @param boolean $force_refresh
	 *
	 * @return boolean
	 */
	protected function refresh_order_vouchers( $order, $force_refresh = true ) {
		$has_updated = false;

		foreach ( $order->get_coupons() as $item_id => $coupon ) {
			if ( $this->order_item_coupon_is_voucher( $coupon ) ) {
				// Check if a voucher has been added which misses a fee
				$fee = $this->get_order_item_fee_by_coupon( $coupon, $order );

				if ( ! $fee ) {
					$this->add_voucher_to_order( $coupon, $order );
					$has_updated = true;
				} elseif ( $force_refresh ) {
					$this->add_voucher_to_order( $coupon, $order, $fee );
					$has_updated = true;
				}
			}
		}

		return $has_updated;
	}

	/**
	 * @param WC_Order_Item_Coupon $coupon
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function add_voucher_to_order( $coupon, $order, $fee = false ) {
		if ( is_a( $fee, 'WC_Order_Item_Fee' ) ) {
			$is_new = false;
		} else {
			$is_new = true;
			$fee    = new WC_Order_Item_Fee();
		}

		$coupon_data = $this->get_fee_data_from_coupon( $coupon, $order );

		// Do not allow setting id
		$fee->set_props( array_diff_key( $coupon_data, array( 'id' => '' ) ) );
		$fee->update_meta_data( '_is_voucher', 'yes' );
		$fee->update_meta_data( '_code', $coupon_data['code'] );
		$fee->update_meta_data( '_voucher_amount', wc_format_decimal( floatval( $coupon_data['amount'] ) * -1 ) );
		$fee->update_meta_data( '_voucher_discount_type', $coupon_data['voucher_discount_type'] );
		$fee->update_meta_data( '_voucher_id', $coupon_data['id'] );

		$fee->set_tax_status( 'none' );

		// Add a placeholder negative amount to trigger the recalculation in WC_GZD_Discount_Helper::allow_order_fee_total_incl_tax()
		$fee->set_total( wc_format_decimal( $coupon_data['amount'] ) );
		$fee->set_total_tax( 0 );

		if ( $is_new ) {
			$order->add_item( $fee );
		}
	}

	/**
	 * @param WC_Order_Item_Coupon $coupon
	 * @param WC_Order|false $order
	 *
	 * @return WC_Order_Item_Fee|false
	 */
	public function get_order_item_fee_by_coupon( $coupon, $order = false ) {
		$fee   = false;
		$order = $order ? $order : $coupon->get_order();

		if ( $order ) {
			foreach ( $order->get_fees() as $order_item_fee ) {
				if ( $this->fee_is_voucher( $order_item_fee ) ) {
					if ( $order_item_fee->get_meta( '_code' ) === $coupon->get_code() ) {
						$fee = $order_item_fee;
						break;
					}
				}
			}
		}

		return $fee;
	}

	/**
	 * @param WC_Order_Item_Fee $fee
	 * @param WC_Order|false $order
	 *
	 * @return WC_Order_Item_Coupon|false
	 */
	public function get_order_item_coupon_by_fee( $fee, $order = false ) {
		$coupon = false;
		$order  = $order ? $order : $fee->get_order();

		if ( $order ) {
			foreach ( $order->get_coupons() as $coupon_order_item ) {
				if ( $this->order_item_coupon_is_voucher( $coupon_order_item ) ) {
					if ( $fee->get_meta( '_code' ) === $coupon_order_item->get_code() ) {
						$coupon = $coupon_order_item;
						break;
					}
				}
			}
		}

		return $coupon;
	}

	public function voucher_discount( $discount, $item ) {
		if ( is_a( $item, 'WC_Order_Item_Coupon' ) ) {
			if ( $this->order_item_coupon_is_voucher( $item ) && empty( $discount ) ) {
				if ( $fee = $this->get_order_item_fee_by_coupon( $item ) ) {
					return floatval( $fee->get_total() ) * -1;
				}
			}
		}

		return $discount;
	}

	public function get_voucher_data_from_cart() {
		$voucher_data = array();

		foreach ( WC()->cart->get_fees() as $fee ) {
			if ( self::instance()->fee_is_voucher( $fee ) ) {
				$voucher_data[ $fee->id ] = array(
					'name'         => esc_attr( $fee->name ),
					'coupon_name'  => esc_attr( wc_cart_totals_coupon_label( $fee->code, false ) ),
					'coupon_class' => 'coupon-' . esc_attr( sanitize_title( $fee->code ) ),
				);
			}
		}

		return $voucher_data;
	}

	public function voucher_fragments( $fragments ) {
		$fragments['.gzd-vouchers'] = $this->get_voucher_data_from_cart();

		return $fragments;
	}

	protected function get_order_fee_total( $order ) {
		return array_reduce(
			$order->get_fees(),
			function( $carry, $item ) {
				return $carry + ( $item->get_total() + $item->get_total_tax() );
			}
		);
	}

	public function limit_order_voucher_discounts_callback( $and_taxes, $order ) {
		$this->limit_order_voucher_discounts( $order );
	}

	/**
	 * Woo calculates max discounts for fees based on net amounts. By doing so
	 * negative fees will never be able to reach 0 order total in case of prices excluding taxes.
	 *
	 * Do also make sure that (sequential) vouchers do not exceed order total.
	 *
	 * @see WC_Order::calculate_totals()
	 *
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	public function limit_order_voucher_discounts( $order ) {
		$fees_total           = 0;
		$voucher_item_updated = false;
		$fees_total_before    = 0;
		$shipping_total       = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();
		$voucher_fee_total    = 0;

		$order_total                 = $order->get_total();
		$order_total_before_vouchers = $order_total;

		foreach ( $order->get_fees() as $item ) {
			if ( ! apply_filters( 'woocommerce_gzd_voucher_order_allow_fee_reduction', true, $item ) ) {
				$order_total_before_vouchers -= ( (float) $item->get_total() + (float) $item->get_total_tax() );
			}

			if ( $this->fee_is_voucher( $item ) ) {
				$order_total_before_vouchers -= ( (float) $item->get_total() + (float) $item->get_total_tax() );
			}

			$fees_total_before += ( (float) $item->get_total() + (float) $item->get_total_tax() );
		}

		foreach ( $order->get_fees() as $item ) {
			$fee_total = (float) $item->get_total() + (float) $item->get_total_tax();

			if ( $this->fee_is_voucher( $item ) && $fee_total < 0 ) {
				$coupon            = $this->get_order_item_coupon_by_fee( $item, $order );
				$max_voucher_total = '' !== $item->get_meta( '_voucher_amount' ) ? ( wc_format_decimal( $item->get_meta( '_voucher_amount' ) ) ) : ( (float) $item->get_total() * -1 );
				$max_discount      = max( 0, NumberUtil::round( ( $order_total_before_vouchers - ( ( $coupon && ! $this->voucher_includes_shipping_costs( $coupon ) ) ? $shipping_total : 0 ) + $voucher_fee_total ), wc_get_price_decimals() ) );
				$discount          = min( $max_voucher_total, $max_discount ) * -1;

				if ( $discount < 0 || $max_voucher_total > $max_discount ) {
					$fee_total = $discount;

					if ( $item->get_total() != $fee_total ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
						$voucher_item_updated = true;
						$item->set_total( $fee_total );
					}
				}

				$voucher_fee_total += $fee_total;
			}

			$fees_total += $fee_total;
		}

		if ( $voucher_item_updated ) {
			$fees_diff = NumberUtil::round( $fees_total_before - $fees_total, wc_get_price_decimals() );

			if ( $fees_diff > 0 ) {
				$order->set_total( $order->get_total() - $fees_diff );
				$order->save();
			}

			return true;
		}

		return false;
	}

	public function fee_is_voucher( $fee ) {
		if ( is_a( $fee, 'WC_Order_Item_Fee' ) ) {
			return 'yes' === $fee->get_meta( '_is_voucher' );
		} else {
			$fee_id = isset( $fee->object ) ? $fee->object->id : $fee->id;

			return strstr( $fee_id, 'voucher_' );
		}
	}

	/**
	 * Should always round at subtotal?
	 *
	 * @since 3.9.0
	 * @return bool
	 */
	protected static function round_at_subtotal() {
		return 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );
	}

	/**
	 * Apply rounding to an array of taxes before summing. Rounds to store DP setting, ignoring precision.
	 *
	 * @since  3.2.6
	 * @param  float $value    Tax value.
	 * @param  bool  $in_cents Whether precision of value is in cents.
	 * @return float
	 */
	protected static function round_line_tax( $value, $in_cents = true ) {
		if ( ! self::round_at_subtotal() ) {
			$value = wc_round_tax_total( $value, $in_cents ? 0 : null );
		}
		return $value;
	}

	/**
	 * Woo seems to ignore the non-taxable status for negative fees @see WC_Order_Item_Fee::calculate_taxes()
	 *
	 * @param WC_Order_Item_Fee $fee
	 *
	 * @return void
	 */
	public function remove_order_item_fee_taxes( $fee ) {
		if ( 'yes' === $fee->get_meta( '_is_voucher' ) ) {
			$fee->set_taxes( false );
		}
	}

	/**
	 * @param WC_Coupon|WC_Order_Item_Coupon $coupon
	 *
	 * @return boolean
	 */
	public function voucher_includes_shipping_costs( $coupon ) {
		$allow_free_shipping = false;

		if ( is_a( $coupon, 'WC_Coupon' ) ) {
			$allow_free_shipping = $coupon->get_free_shipping( 'edit' );
		} elseif ( is_a( $coupon, 'WC_Order_Item_Coupon' ) ) {
			$allow_free_shipping = 'yes' === $coupon->get_meta( 'voucher_includes_shipping_costs' );
		}

		return apply_filters( 'woocommerce_gzd_voucher_includes_shipping_costs', $allow_free_shipping, $coupon );
	}

	/**
	 * Woo seems to ignore the non-taxable status for negative fees @see WC_Cart_Totals::get_fees_from_cart()
	 *
	 * @param $taxes
	 * @param $fee
	 * @param WC_Cart_Totals $cart_totals
	 *
	 * @return array|mixed
	 */
	public function remove_taxes_for_vouchers( $taxes, $fee, $cart_totals ) {
		if ( $this->fee_is_voucher( $fee ) ) {
			$fee_running_total = 0;

			/**
			 * Need to (re) calculate the running fee total to allow adjusting voucher total amounts.
			 */
			foreach ( WC()->cart->get_fees() as $fee_key => $fee_object ) {
				$tmp_fee = (object) array(
					'object'    => null,
					'tax_class' => '',
					'taxable'   => false,
					'total_tax' => 0,
					'taxes'     => array(),
				);

				$tmp_fee->object    = $fee_object;
				$tmp_fee->tax_class = $tmp_fee->object->tax_class;
				$tmp_fee->taxable   = $tmp_fee->object->taxable;
				$tmp_fee->total     = wc_add_number_precision_deep( $tmp_fee->object->amount );

				// Negative fees should not make the order total go negative.
				if ( 0 > $tmp_fee->total ) {
					if ( $this->fee_is_voucher( $tmp_fee ) ) {
						$allow_free_shipping = false;

						if ( $tmp_fee->object->coupon && $this->voucher_includes_shipping_costs( $tmp_fee->object->coupon ) ) {
							$allow_free_shipping = true;
						}

						// Max voucher total may include taxes and maybe shipping
						$max_discount = NumberUtil::round( $cart_totals->get_total( 'items_total', true ) + $fee_running_total + ( $allow_free_shipping ? $cart_totals->get_total( 'shipping_total', true ) : 0 ) + $cart_totals->get_total( 'items_total_tax', true ) + ( $allow_free_shipping ? $cart_totals->get_total( 'shipping_tax_total', true ) : 0 ) ) * -1;
					} else {
						$max_discount = NumberUtil::round( $cart_totals->get_total( 'items_total', true ) + $fee_running_total + $cart_totals->get_total( 'shipping_total', true ) ) * -1;
					}

					/**
					 * We've reached the current voucher object
					 */
					if ( $fee_object === $fee->object ) {
						// Make sure the max voucher amount does not exceed the coupon amount
						$max_voucher_amount = max( wc_add_number_precision_deep( $fee->object->amount ), $max_discount );
						$fee->total         = $max_voucher_amount;

						break;
					}

					if ( $tmp_fee->total < $max_discount ) {
						$tmp_fee->total = $max_discount;
					}
				}

				if ( apply_filters( 'woocommerce_gzd_voucher_cart_allow_fee_reduction', true, $tmp_fee ) ) {
					$fee_running_total += $tmp_fee->total;
				}

				if ( $fee_object === $fee->object ) {
					break;
				}
			}

			$taxes = array();
		}

		return $taxes;
	}

	/**
	 * @param boolean $force_tax
	 * @param $fee
	 *
	 * @return boolean
	 */
	public function exclude_vouchers_from_forced_tax( $force_tax, $fee ) {
		if ( $this->fee_is_voucher( $fee ) ) {
			$force_tax = false;
		}

		return $force_tax;
	}

	/**
	 * @param WC_Coupon $coupon
	 *
	 * @return void
	 */
	protected function register_coupon_as_fee( $coupon ) {
		$id         = 'voucher_' . $coupon->get_code();
		$fee_exists = false;

		foreach ( WC()->cart->get_fees() as $fee ) {
			if ( $fee->id === $id ) {
				$fee_exists = true;
				break;
			}
		}

		if ( ! $fee_exists ) {
			WC()->cart->fees_api()->add_fee( $this->get_fee_data_from_coupon( $coupon, WC()->cart ) );
		}
	}

	/**
	 * @param WC_Order_Item_Coupon|WC_Coupon $item
	 *
	 * @return WC_Coupon
	 */
	protected function get_voucher_by_coupon_order_item( $item ) {
		if ( is_a( $item, 'WC_Coupon' ) ) {
			return $item;
		}

		$coupon_code = $item->get_code();
		$coupon_id   = wc_get_coupon_id_by_code( $coupon_code );

		// If we have a coupon ID (loaded via wc_get_coupon_id_by_code) we can simply load the new coupon object using the ID.
		if ( $coupon_id ) {
			$coupon_object = new WC_Coupon( $coupon_id );
		} else {
			// If we do not have a coupon ID (was it virtual? has it been deleted?) we must create a temporary coupon using what data we have stored during checkout.
			$coupon_object = new WC_Coupon();
			$coupon_object->set_props( (array) $item->get_meta( 'coupon_data', true ) );
			$coupon_object->set_code( $coupon_code );
			$coupon_object->set_virtual( true );
		}

		return $coupon_object;
	}

	/**
	 * @param WC_Coupon|WC_Order_Item_Coupon $coupon
	 *
	 * @return array
	 */
	protected function get_fee_data_from_coupon( $coupon, $object ) {
		if ( is_a( $coupon, 'WC_Order_Item_Coupon' ) ) {
			$coupon = $this->get_voucher_by_coupon_order_item( $coupon );
		}

		if ( ! $coupon ) {
			return array();
		}

		$this->unregister_coupon_validation_filters();
		$discounts = new WC_GZD_Voucher_Discounts( $object, $coupon );
		$discounts->apply_coupon( $coupon, false );
		$total_discounts = $discounts->get_discounts_by_coupon();
		$this->register_coupon_validation_filters();

		$amount = isset( $total_discounts[ $coupon->get_code() ] ) ? $total_discounts[ $coupon->get_code() ] : $coupon->get_amount();
		$id     = 'voucher_' . $coupon->get_code();

		return array(
			'name'                  => apply_filters( 'woocommerce_gzd_voucher_name', sprintf( __( 'Voucher: %1$s', 'woocommerce-germanized' ), $coupon->get_code() ), $coupon->get_code() ),
			'amount'                => floatval( $amount ) * - 1,
			'taxable'               => false,
			'id'                    => $id,
			'tax_class'             => '',
			'code'                  => $coupon->get_code(),
			'voucher_amount'        => $coupon->get_amount(),
			'voucher_discount_type' => $coupon->get_discount_type(),
			'coupon'                => $coupon,
		);
	}

	public function vouchers_as_fees() {
		foreach ( WC()->cart->get_applied_coupons() as $key => $coupon_code ) {
			if ( $coupon = $this->get_voucher_by_code( $coupon_code ) ) {
				$this->register_coupon_as_fee( $coupon );
			}
		}
	}

	/**
	 * @param boolean $is_valid
	 * @param WC_Coupon $coupon
	 *
	 * @return boolean
	 */
	public function is_valid( $is_valid, $coupon ) {
		if ( $this->coupon_is_voucher( $coupon ) ) {
			return false;
		}

		return $is_valid;
	}

	public function on_apply_voucher( $coupon_code ) {
		if ( $coupon = $this->get_voucher_by_code( $coupon_code ) ) {
			$this->register_coupon_as_fee( $coupon );
		}
	}

	public function legacy_shipments_order_has_voucher( $has_voucher, $order ) {
		return ! $this->order_supports_fee_vouchers( $order ) && $this->order_has_voucher( $order, true );
	}

	/**
	 * Checks whether an order has a voucher as coupon or not.
	 *
	 * @param WC_Order|integer $order
	 * @param bool $allow_legacy
	 *
	 * @return bool
	 */
	public function order_has_voucher( $order, $allow_legacy = false ) {
		$order        = is_numeric( $order ) ? wc_get_order( $order ) : $order;
		$has_vouchers = false;

		if ( $coupons = $order->get_items( 'coupon' ) ) {
			foreach ( $coupons as $coupon ) {
				if ( $this->order_item_coupon_is_voucher( $coupon, $allow_legacy ) ) {
					$has_vouchers = true;
					break;
				}
			}
		}

		return $has_vouchers;
	}

	/**
	 * @param WC_Order|integer $order
	 *
	 * @return boolean
	 */
	public function order_supports_fee_vouchers( $order ) {
		return version_compare( WC_GZD_Order_Helper::instance()->get_order_version( $order ), '3.9.0', '>=' );
	}

	/**
	 * Checks whether an order has a voucher as coupon or not.
	 *
	 * @param $order
	 *
	 * @return string
	 */
	public function order_voucher_total( $order, $inc_tax = true ) {
		$order = is_numeric( $order ) ? $order = wc_get_order( $order ) : $order;
		$total = 0;

		if ( ! $this->order_supports_fee_vouchers( $order ) ) {
			foreach ( $order->get_items( 'coupon' ) as $coupon ) {
				if ( $this->order_item_coupon_is_voucher( $coupon, true ) ) {
					$total += $coupon->get_discount();

					if ( $inc_tax ) {
						$total += $coupon->get_discount_tax();
					}
				}
			}
		} else {
			foreach ( $order->get_items( 'fee' ) as $fee ) {
				if ( $this->fee_is_voucher( $fee ) ) {
					$total += floatval( $fee->get_total() ) * -1;
				}
			}
		}

		return wc_format_decimal( $total );
	}

	/**
	 * Legacy support for non-fee vouchers.
	 *
	 * Adjust WooCommerce order recalculation to make it compatible with vouchers.
	 * Maybe some day we'll be able to hook into calculate_taxes or calculate_totals so that is not necessary anymore.
	 */
	public function legacy_before_recalculate_totals() {
		check_ajax_referer( 'calc-totals', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( - 1 );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;

		if ( empty( $order_id ) ) {
			return;
		}

		// Grab the order and recalc taxes
		$order = wc_get_order( $order_id );

		// Do not replace recalculation if the order has no voucher or does support fee vouchers
		if ( ! $this->order_has_voucher( $order, true ) || $this->order_supports_fee_vouchers( $order ) ) {
			return;
		}

		// Disable WC order total recalculation
		remove_action( 'wp_ajax_woocommerce_calc_line_taxes', array( 'WC_AJAX', 'calc_line_taxes' ), 10 );

		$calculate_tax_args = array(
			'country'  => isset( $_POST['country'] ) ? strtoupper( wc_clean( wp_unslash( $_POST['country'] ) ) ) : '',
			'state'    => isset( $_POST['state'] ) ? strtoupper( wc_clean( wp_unslash( $_POST['state'] ) ) ) : '',
			'postcode' => isset( $_POST['postcode'] ) ? strtoupper( wc_clean( wp_unslash( $_POST['postcode'] ) ) ) : '',
			'city'     => isset( $_POST['city'] ) ? strtoupper( wc_clean( wp_unslash( $_POST['city'] ) ) ) : '',
		);

		// Parse the jQuery serialized items
		$items = array();
		parse_str( ( isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array() ), $items ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,

		// Save order items first
		wc_save_order_items( $order_id, $items );

		// Add item hook to make sure taxes are being (re)calculated correctly
		add_filter( 'woocommerce_order_item_get_total', array( $this, 'legacy_adjust_item_total' ), 10, 2 );
		$order->calculate_taxes( $calculate_tax_args );
		remove_filter( 'woocommerce_order_item_get_total', array( $this, 'legacy_adjust_item_total' ), 10 );

		$order->calculate_totals( false );

		include WC()->plugin_path() . '/includes/admin/meta-boxes/views/html-order-items.php';
		wp_die();
	}

	public function legacy_adjust_item_total( $value, $item ) {
		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $item->get_subtotal();
		}

		return $value;
	}

	/**
	 * Sets voucher coupon data if available.
	 *
	 * @param WC_Order_Item_Coupon $item
	 * @param string $code
	 * @param WC_Coupon $coupon
	 * @param WC_Order $order
	 */
	public function coupon_item_save( $item, $code, $coupon, $order ) {
		if ( is_a( $coupon, 'WC_Coupon' ) ) {
			if ( $this->coupon_is_voucher( $coupon ) ) {
				$this->convert_order_item_coupon_to_voucher( $item, $coupon, $this->get_tax_display_mode( $order ) );
			}
		}
	}

	/**
	 * @param string $code
	 *
	 * @return WC_Coupon|boolean
	 */
	protected function get_voucher_by_code( $code ) {
		$voucher = new WC_Coupon( $code );

		if ( ! $this->coupon_is_voucher( $voucher ) ) {
			return false;
		}

		return apply_filters( 'woocommerce_gzd_voucher', $voucher, $code );
	}

	/**
	 * Sets voucher coupon data if available.
	 *
	 * @param WC_Order_Item_Fee $item
	 * @param $fee_key
	 * @param object $fee
	 * @param WC_Order $order
	 */
	public function fee_item_save( $item, $fee_key, $fee, $order ) {
		if ( $this->fee_is_voucher( $fee ) ) {
			$item->update_meta_data( '_is_voucher', 'yes' );
			$item->update_meta_data( '_code', wc_clean( $fee->code ) );
			$item->update_meta_data( '_voucher_amount', wc_format_decimal( $fee->voucher_amount ) );
			$item->update_meta_data( '_voucher_discount_type', wc_clean( $fee->voucher_discount_type ) );

			$item->set_tax_status( 'none' );
			$item->set_tax_class( '' );
		}
	}

	/**
	 * @param WC_Order $order
	 */
	protected function get_tax_display_mode( $order ) {
		$is_vat_exempt = wc_string_to_bool( $order->get_meta( 'is_vat_exempt', true ) );

		if ( ! $is_vat_exempt ) {
			$is_vat_exempt = apply_filters( 'woocommerce_order_is_vat_exempt', $is_vat_exempt, $order );
		}

		if ( $is_vat_exempt ) {
			$tax_display_mode = 'excl';
		} else {
			$tax_display_mode = get_option( 'woocommerce_tax_display_cart' );
		}

		return apply_filters( 'woocommerce_gzd_order_coupon_tax_display_mode', $tax_display_mode, $order );
	}

	/**
	 * @param WC_Coupon $coupon
	 *
	 * @return bool
	 */
	protected function coupon_is_voucher( $coupon ) {
		if ( is_string( $coupon ) ) {
			$coupon = new WC_Coupon( $coupon );
		}

		if ( ! is_a( $coupon, 'WC_Coupon' ) ) {
			return false;
		}

		return apply_filters( 'woocommerce_gzd_coupon_is_voucher', ( 'yes' === $coupon->get_meta( 'is_voucher', true ) ), $coupon );
	}

	/**
	 * @param WC_Order_Item_Coupon $coupon
	 *
	 * @return bool
	 */
	protected function order_item_coupon_is_voucher( $coupon, $allow_legacy = false ) {
		$is_voucher = 'yes' === $coupon->get_meta( 'is_voucher', true ) && 'yes' === $coupon->get_meta( 'is_stored_as_fee', true );

		if ( $allow_legacy ) {
			$is_voucher = 'yes' === $coupon->get_meta( 'is_voucher', true );
		}

		return apply_filters( 'woocommerce_gzd_order_item_coupon_is_voucher', $is_voucher, $coupon, $allow_legacy );
	}

	/**
	 * @param WC_Coupon $coupon
	 */
	public function convert_coupon_to_voucher( $coupon ) {
		$coupon->update_meta_data( 'is_voucher', 'yes' );
		$coupon->save();
	}

	public function coupon_options( $id, $coupon ) {
		woocommerce_wp_checkbox(
			array(
				'id'          => 'is_voucher',
				'label'       => __( 'Is voucher?', 'woocommerce-germanized' ),
				'description' => sprintf( __( 'Whether or not this coupon is a voucher which has been sold to a customer without VAT and needs to be taxed as soon as the customer redeems the voucher. Find more information <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'https://www.haufe.de/finance/steuern-finanzen/umsatzsteuer-wie-gutscheine-zu-behandeln-sind_190_76132.html' ),
			)
		);
	}

	/**
	 * @param $id
	 * @param WC_Coupon $coupon
	 */
	public function coupon_save( $id, $coupon ) {
		// Reassign coupon to prevent saving bug https://github.com/woocommerce/woocommerce/issues/24570
		$coupon = new WC_Coupon( $id );

		if ( ! $coupon ) {
			return;
		}

		if ( isset( $_POST['is_voucher'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$this->convert_coupon_to_voucher( $coupon );
		} else {
			$coupon->update_meta_data( 'is_voucher', 'no' );
			$coupon->save();
		}
	}
}

WC_GZD_Coupon_Helper::instance();
