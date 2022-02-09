<?php

use Automattic\WooCommerce\Utilities\NumberUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_GZD_Coupon_Helper
 */
class WC_GZD_Coupon_Helper {

	protected static $_instance = null;

	protected $vouchers_hidden = array();

	protected $added_discount_tax_left = false;

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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
	}

	public function __construct() {
		add_action( 'woocommerce_coupon_options', array( $this, 'coupon_options' ), 10, 2 );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'coupon_save' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_coupon_item', array( $this, 'coupon_item_save' ), 10, 4 );

		add_action( 'woocommerce_applied_coupon', array( $this, 'on_apply_voucher' ), 10, 1 );

		add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'is_valid' ), 1000, 2 );
		add_filter( 'woocommerce_coupon_is_valid_for_cart', array( $this, 'is_valid' ), 1000, 2 );

		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'vouchers_as_fees' ), 10000 );
		add_action( 'woocommerce_checkout_create_order_fee_item', array( $this, 'fee_item_save' ), 10, 4 );

		add_filter( 'woocommerce_gzd_force_fee_tax_calculation', array( $this, 'exclude_vouchers_from_forced_tax' ), 10, 2 );
		add_filter( 'woocommerce_cart_totals_get_fees_from_cart_taxes', array( $this, 'remove_taxes_for_vouchers' ), 10, 3 );

		// add_action( 'woocommerce_before_cart_totals', array( $this, 'hide_vouchers' ) );
		// add_action( 'woocommerce_after_cart_totals', array( $this, 'show_vouchers' ) );

		// add_action( 'woocommerce_review_order_after_cart_contents', array( $this, 'hide_vouchers' ) );
		// add_action( 'woocommerce_review_order_before_order_total', array( $this, 'show_vouchers' ) );
	}

	public function fee_is_voucher( $fee ) {
		$fee_id = isset( $fee->object ) ? $fee->object->id : $fee->id;

		return strstr( $fee_id, 'voucher_' );
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
			$max_discount_tax_left = NumberUtil::round( $cart_totals->get_total( 'items_total_tax', true ) + $cart_totals->get_total( 'shipping_tax_total', true ) ) * -1;

			if ( ! $this->added_discount_tax_left && wc_add_number_precision_deep( $fee->object->amount ) < $fee->total ) {
				$fee->total += $max_discount_tax_left;

				$this->added_discount_tax_left = true;
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

	public function vouchers_as_fees() {
		$this->added_discount_tax_left = false;

		foreach( WC()->cart->get_applied_coupons() as $key => $coupon_code ) {
			$coupon = new WC_Coupon( $coupon_code );

			if ( $this->coupon_is_voucher( $coupon ) ) {
				WC()->cart->fees_api()->add_fee(
					array(
						'name'      => apply_filters( 'woocommerce_gzd_voucher_name', sprintf( __( 'Voucher: %1$s', 'woocommerce-germanized' ), $coupon_code ), $coupon_code ),
						'amount'    => floatval( $coupon->get_amount() ) * -1,
						'taxable'   => false,
						'id'        => 'voucher_' . $coupon_code,
						'tax_class' => '',
						'code'      => $coupon_code
					)
				);
			}
		}
	}

	public function hide_vouchers() {
		$this->vouchers_hidden = array();

		foreach( WC()->cart->get_applied_coupons() as $key => $coupon_code ) {
			$coupon = new WC_Coupon( $coupon_code );

			if ( $this->coupon_is_voucher( $coupon ) ) {
				$this->vouchers_hidden[] = $coupon_code;

				unset( WC()->cart->applied_coupons[ $key ] );
			}
		}
	}

	public function show_vouchers() {
		foreach( $this->vouchers_hidden as $coupon_code ) {
			if ( ! in_array( $coupon_code, WC()->cart->get_applied_coupons() ) ) {
				WC()->cart->applied_coupons[] = $coupon_code;
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
		$coupon = new WC_Coupon( $coupon_code );

		if ( $coupon->get_id() > 0 && $this->coupon_is_voucher( $coupon ) ) {

		}
	}

	public function shipments_order_has_voucher( $has_voucher, $order ) {
		return $this->order_has_voucher( $order );
	}

	/**
	 * @param boolean $is_valid
	 * @param WC_Coupon $coupon
	 * @param WC_Discounts $discounts
	 *
	 * @throws Exception
	 */
	public function disallow_coupon_type_merging( $is_valid, $coupon, $discounts ) {
		$object       = $discounts->get_object();
		$has_vouchers = false;
		$has_coupons  = false;

		if ( is_a( $object, 'WC_Cart' ) ) {
			$has_vouchers = $this->cart_has_voucher( $object );
			$has_coupons  = sizeof( $object->get_coupons() ) > 0;
		} elseif( is_a( $object, 'WC_Order' ) ) {
			$has_vouchers = $this->order_has_voucher( $object );
			$has_coupons  = sizeof( $object->get_coupons() ) > 0;
		}

		if ( $has_vouchers && ! $this->coupon_is_voucher( $coupon ) ) {
			throw new Exception( __( 'The cart contains one or more vouchers. Vouchers cannot be mixed with normal coupons.', 'woocommerce-germanized' ) );
		} elseif ( $has_coupons && ! $has_vouchers && $this->coupon_is_voucher( $coupon ) ) {
			throw new Exception( __( 'The cart contains one or more coupons. Vouchers cannot be mixed with normal coupons. Please remove the coupon before adding your voucher.', 'woocommerce-germanized' ) );
		}

		return $is_valid;
	}

	/**
	 * Checks whether an order has an voucher as coupon or not.
	 *
	 * @param $order
	 *
	 * @return bool
	 */
	public function order_has_voucher( $order ) {
		$order = is_numeric( $order ) ? $order = wc_get_order( $order ) : $order;

		$has_vouchers = false;

		if ( $coupons = $order->get_items( 'coupon' ) ) {
			foreach ( $coupons as $coupon ) {
				if ( 'yes' === $coupon->get_meta( 'is_voucher', true ) ) {
					$has_vouchers = true;
				}
			}
		}

		return $has_vouchers;
	}

	/**
	 * Checks whether an order has an voucher as coupon or not.
	 *
	 * @param $order
	 *
	 * @return bool
	 */
	public function order_voucher_total( $order, $inc_tax = true ) {
		$order = is_numeric( $order ) ? $order = wc_get_order( $order ) : $order;

		$total = 0;

		if ( $coupons = $order->get_items( 'coupon' ) ) {
			foreach ( $coupons as $coupon ) {
				if ( $this->coupon_is_voucher( $coupon ) ) {
					$total += $coupon->get_discount();

					if ( $inc_tax ) {
						$total += $coupon->get_discount_tax();
					}
				}
			}
		}

		return wc_format_decimal( $total );
	}

	/**
	 * Adjust WooCommerce order recalculation to make it compatible with vouchers.
	 * Maybe some day we'll be able to hook into calculate_taxes or calculate_totals so that is not necessary anymore.
	 */
	public function before_recalculate_totals() {

		check_ajax_referer( 'calc-totals', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( - 1 );
		}

		$order_id = absint( $_POST['order_id'] );

		// Grab the order and recalc taxes
		$order = wc_get_order( $order_id );

		// Do not replace recalculation if the order has no voucher
		if ( ! $this->order_has_voucher( $order ) ) {
			return;
		}

		// Disable WC order total recalculation
		remove_action( 'wp_ajax_woocommerce_calc_line_taxes', array( 'WC_AJAX', 'calc_line_taxes' ), 10 );

		$calculate_tax_args = array(
			'country'  => strtoupper( wc_clean( $_POST['country'] ) ),
			'state'    => strtoupper( wc_clean( $_POST['state'] ) ),
			'postcode' => strtoupper( wc_clean( $_POST['postcode'] ) ),
			'city'     => strtoupper( wc_clean( $_POST['city'] ) ),
		);

		// Parse the jQuery serialized items
		$items = array();
		parse_str( $_POST['items'], $items );

		// Save order items first
		wc_save_order_items( $order_id, $items );

		// Add item hook to make sure taxes are being (re)calculated correctly
		add_filter( 'woocommerce_order_item_get_total', array( $this, 'adjust_item_total' ), 10, 2 );
		$order->calculate_taxes( $calculate_tax_args );
		remove_filter( 'woocommerce_order_item_get_total', array( $this, 'adjust_item_total' ), 10 );

		$order->calculate_totals( false );

		include( WC()->plugin_path() . '/includes/admin/meta-boxes/views/html-order-items.php' );
		wp_die();
	}

	public function adjust_item_total( $value, $item ) {
		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $item->get_subtotal();
		}

		return $value;
	}

	/**
	 * Sets voucher coupon data if available.
	 *
	 * @param WC_Order_Item $item
	 * @param $code
	 * @param WC_Order_Item_Coupon $coupon
	 * @param WC_Order $order
	 */
	public function coupon_item_save( $item, $code, $coupon, $order ) {
		if ( is_a( $coupon, 'WC_Coupon' ) ) {
			if ( 'yes' === $coupon->get_meta( 'is_voucher', true ) ) {
				$item->update_meta_data( 'is_voucher', 'yes' );
				/**
				 * Store the current tax_display_mode used to calculate coupon totals
				 * as the coupon tax amount may differ depending on which mode is being used.
				 */
				$item->update_meta_data( 'tax_display_mode', $this->get_tax_display_mode( $order ) );
			}
		}
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
	 * @param WC_Coupon|WC_Order_Item_Coupon $coupon
	 *
	 * @return bool
	 */
	protected function coupon_is_voucher( $coupon ) {
		if ( is_string( $coupon ) ) {
			$coupon = new WC_Coupon( $coupon );
		}

		return apply_filters( 'woocommerce_gzd_coupon_is_voucher', ( 'yes' === $coupon->get_meta( 'is_voucher', true ) ), $coupon );
	}

	protected function cart_has_voucher( $cart = null ) {
		if ( is_null( $cart ) ) {
			$cart = WC()->cart;
		}

		if ( is_null( $cart ) ) {
			return false;
		}

		// Check for discounts and whether the coupon is a voucher
		$coupons      = $cart->get_coupons();
		$has_vouchers = false;

		foreach ( $coupons as $coupon ) {
			if ( $this->coupon_is_voucher( $coupon ) ) {
				$has_vouchers = true;
				break;
			}
		}

		return $has_vouchers;
	}

	/**
	 * @param WC_Cart $cart
	 */
	public function recalculate_tax_totals( $cart ) {

		if ( WC()->customer->is_vat_exempt() ) {
			return;
		}

		if ( ! $this->cart_has_voucher( $cart ) ) {
			return;
		}

		$cart_contents = $cart->get_cart();
		$tax_rates     = array();
		$tax_totals    = array();

		/**
		 * Calculate totals for items.
		 */
		foreach ( $cart_contents as $cart_item_key => $values ) {
			$product = $values['data'];

			if ( ! $product->is_taxable() ) {
				continue;
			}

			// Get item tax rates
			if ( empty( $tax_rates[ $product->get_tax_class() ] ) ) {
				$tax_rates[ $product->get_tax_class() ] = WC_Tax::get_rates( $product->get_tax_class() );

				// Setup total tax amounts per rate
				foreach ( $tax_rates[ $product->get_tax_class() ] as $key => $rate ) {
					if ( ! isset( $tax_totals[ $key ] ) ) {
						$tax_totals[ $key ] = 0;
					}
				}
			}

			$item_tax_rates = $tax_rates[ $product->get_tax_class() ];

			if ( wc_prices_include_tax() ) {
				$cart->cart_contents[ $cart_item_key ]['line_total'] = ( ( $values['line_total'] + $values['line_tax'] ) - $values['line_subtotal_tax'] );
			} else {
				$cart->cart_contents[ $cart_item_key ]['line_total'] = $values['line_total'];
			}

			$cart->cart_contents[ $cart_item_key ]['line_tax']               = $values['line_subtotal_tax'];
			$cart->cart_contents[ $cart_item_key ]['line_tax_data']['total'] = $values['line_tax_data']['subtotal'];

			foreach ( $item_tax_rates as $key => $rate ) {
				$tax_totals[ $key ] = $tax_totals[ $key ] + $values['line_subtotal_tax'];
			}
		}

		if ( is_callable( array( $cart, 'set_discount_total' ) ) && is_callable( array(
				$cart,
				'set_cart_contents_taxes'
			) ) ) {

			$discount_tax = $cart->get_discount_tax();

			$cart->set_cart_contents_taxes( $tax_totals );

			$tax_total = 0;

			foreach ( $tax_totals as $total ) {
				if ( 'yes' !== get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
					$tax_total += wc_round_tax_total( $total );
				} else {
					$tax_total += $total;
				}
			}

			$cart->set_cart_contents_tax( $tax_total );

			if ( wc_prices_include_tax() || $cart->display_prices_including_tax() ) {
				$cart->set_discount_total( wc_cart_round_discount( ( $cart->get_discount_total() + $cart->get_discount_tax() ), $cart->dp ) );

				if ( is_callable( array( $cart, 'set_coupon_discount_tax_totals' ) ) ) {
					$tax_totals = $cart->get_coupon_discount_tax_totals();
					$totals     = $cart->get_coupon_discount_totals();

					/**
					 * Add tax amount to discount total
					 */
					foreach( $totals as $key => $total ) {
						$totals[ $key ] = $totals[ $key ] + ( isset( $tax_totals[ $key ] ) ? $tax_totals[ $key ] : 0 );
					}

					/**
					 * Remove tax amounts
					 */
					foreach( $tax_totals as $key => $total ) {
						$tax_totals[ $key ] = 0;
					}

					$cart->set_coupon_discount_totals( $totals );
					$cart->set_coupon_discount_tax_totals( $tax_totals );
				}
			} elseif ( ! wc_prices_include_tax() ) {
				$cart->set_discount_total( wc_cart_round_discount( $cart->get_discount_total(), $cart->dp ) );

				if ( $cart->get_total( 'edit' ) > 0 ) {
					/**
					 * Remove discount tax amounts
					 */
					if ( is_callable( array( $cart, 'set_coupon_discount_tax_totals' ) ) ) {
						$totals = $cart->get_coupon_discount_tax_totals();

						foreach( $totals as $key => $total ) {
							$totals[ $key ] = 0;
						}

						$cart->set_coupon_discount_tax_totals( $totals );
					}
				}
			}

			$cart->set_discount_tax( 0 );

			// Total up/round taxes
			if ( $cart->round_at_subtotal ) {
				$cart->set_total_tax( WC_Tax::get_tax_total( $tax_totals ) );
				$cart->set_cart_contents_taxes( array_map( array(
					'WC_Tax',
					'round'
				), $cart->get_cart_contents_taxes() ) );
			} else {
				$cart->set_total_tax( array_sum( $tax_totals ) );
			}
		} else {
			$cart->taxes = $tax_totals;

			// Remove discounted taxes (taxes are not being discounted for vouchers)
			$cart->discount_cart     = wc_cart_round_discount( ( $cart->discount_cart + $cart->discount_cart_tax ), $cart->dp );
			$cart->discount_cart_tax = 0;

			// Total up/round taxes
			if ( $cart->round_at_subtotal ) {
				$cart->tax_total = WC_Tax::get_tax_total( $tax_totals );
				$cart->taxes     = array_map( array( 'WC_Tax', 'round' ), $cart->taxes );
			} else {
				$cart->tax_total = array_sum( $tax_totals );
			}
		}
	}

	/**
	 * @param WC_Coupon $coupon
	 */
	public function convert_coupon_to_voucher( $coupon ) {
		$coupon->update_meta_data( 'is_voucher', 'yes' );
		$coupon->save();
	}

	public function coupon_options( $id, $coupon ) {

		woocommerce_wp_checkbox( array(
			'id'          => 'is_voucher',
			'label'       => __( 'Is voucher?', 'woocommerce-germanized' ),
			'description' => sprintf( __( 'Whether or not this coupon is a voucher which has been sold to a customer without VAT and needs to be taxed as soon as the customer redeems the voucher. Find more information <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'https://www.haufe.de/finance/steuern-finanzen/umsatzsteuer-wie-gutscheine-zu-behandeln-sind_190_76132.html' ),
		) );

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

		if ( isset( $_POST['is_voucher'] ) ) {
			$this->convert_coupon_to_voucher( $coupon );
		} else {
			 $coupon->update_meta_data( 'is_voucher', 'no' );
			 $coupon->save();
		}
	}
}

WC_GZD_Coupon_Helper::instance();
