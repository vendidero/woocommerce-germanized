<?php

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

		// Tax Calculation
		add_filter( 'woocommerce_after_calculate_totals', array( $this, 'recalculate_tax_totals' ), 10, 1 );
		// Order Total Recalculation
		add_action( 'woocommerce_before_order_object_save', array( $this, 'maybe_recalculate_tax_totals' ), 150, 1 );
		// Add Hook before recalculating line taxes
		add_action( 'wp_ajax_woocommerce_calc_line_taxes', array( $this, 'before_recalculate_totals' ), 0 );
	}

	/**
	 * Checks whether an order has an voucher as coupon or not.
	 * @param $order
	 *
	 * @return bool
	 */
	public function order_has_voucher( $order ) {
		$order = is_numeric( $order ) ? $order = wc_get_order( $order ) : $order;

		$has_vouchers = false;

		if ( $coupons = $order->get_items( 'coupon' ) ) {
			foreach ( $coupons as $coupon ) {
				if ( wc_gzd_get_crud_data( $coupon, 'is_voucher', true ) === 'yes' ) {
					$has_vouchers = true;
				}
			}
		}

		return $has_vouchers;
	}

	/**
	 * Adjust WooCommerce order recalculation to make it compatible with vouchers.
	 * Maybe some day we'll be able to hook into calculate_taxes or calculate_totals so that is not necessary anymore.
	 */
	public function before_recalculate_totals() {

		check_ajax_referer( 'calc-totals', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$order_id           = absint( $_POST['order_id'] );

		// Grab the order and recalc taxes
		$order = wc_get_order( $order_id );

		// Do not replace recalculation if the order has no voucher
		if ( ! $this->order_has_voucher( $order ) )
			return;

		// Disable WC order total recalculation
		remove_action( 'wp_ajax_woocommerce_calc_line_taxes', array( WC_AJAX, 'calc_line_taxes' ), 10 );

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

		if ( array_key_exists( 'discount_total', $order->get_changes() ) ) {
			$order->set_discount_total( $order->get_discount_total() +  $order->get_cart_tax() );
			$order->set_discount_tax( 0 );
		}

		// Look for changes made after recalculating totals
		if ( array_key_exists( 'total', $order->get_changes() ) ) {
			$order->set_total( $order->get_total() - $order->get_cart_tax() );
		}

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
	 * @param $item
	 * @param $code
	 * @param $coupon
	 * @param $order
	 */
	public function coupon_item_save( $item, $code, $coupon, $order ) {
		if ( is_a( $coupon, 'WC_Coupon' ) ) {
			if ( wc_gzd_get_crud_data( $coupon, 'is_voucher', true ) === 'yes' )
				$item = wc_gzd_set_crud_meta_data( $item, 'is_voucher', 'yes' );
		}
	}

	public function maybe_recalculate_tax_totals( $order ) {

		if ( ! is_admin() || is_checkout() ) {
			return;
		}

		if ( ! $this->order_has_voucher( $order ) )
			return;

		// Look for changes made after recalculating totals
		if ( array_key_exists( 'total', $order->get_changes() ) ) {
			$order->set_total( $order->get_total() - $order->get_cart_tax() );
		}

		if ( array_key_exists( 'discount_total', $order->get_changes() ) ) {
			$order->set_discount_total( $order->get_discount_total() + $order->get_discount_tax() );
			$order->set_discount_tax( 0 );
		}
	}

	public function recalculate_tax_totals( $cart ) {

		if ( WC()->customer->is_vat_exempt() )
			return;

		// Check for discounts and whether the coupon is a voucher
		$coupons                = $cart->get_coupons();
		$has_vouchers           = false;

		foreach( $coupons as $coupon ) {
			if ( wc_gzd_get_crud_data( $coupon, 'is_voucher', true ) === 'yes' ) {
				$has_vouchers = true;
			}
		}

		if ( ! $has_vouchers )
			return;

		$cart_contents          = $cart->get_cart();
		$tax_rates              = array();
		$tax_totals             = array();
		
		/**
		 * Calculate totals for items.
		 */
		foreach ( $cart_contents as $cart_item_key => $values ) {

			$product = $values['data'];

			if ( ! $product->is_taxable() )
				continue;

			// Get item tax rates
			if ( empty( $tax_rates[ $product->get_tax_class() ] ) ) {
				$tax_rates[ $product->get_tax_class() ] = WC_Tax::get_rates( $product->get_tax_class() );

				// Setup total tax amounts per rate
				foreach( $tax_rates[ $product->get_tax_class() ] as $key => $rate ) {
					if ( ! isset( $tax_totals[ $key ] ) )
						$tax_totals[ $key ] = 0;
				}
			}

			$item_tax_rates     = $tax_rates[ $product->get_tax_class() ];

			$cart->cart_contents[ $cart_item_key ][ 'line_total' ] = ( ( $values[ 'line_total' ] + $values[ 'line_tax' ] ) - $values[ 'line_subtotal_tax' ] );
			$cart->cart_contents[ $cart_item_key ][ 'line_tax' ] = $values[ 'line_subtotal_tax' ];
			$cart->cart_contents[ $cart_item_key ][ 'line_tax_data' ][ 'total' ] = $values[ 'line_tax_data' ][ 'subtotal' ];

			foreach( $item_tax_rates as $key => $rate ) {
				$tax_totals[ $key ] = $tax_totals[ $key ] + $values['line_subtotal_tax'];
			}
		}

		if ( is_callable( array( $cart, 'set_discount_total' ) ) && is_callable( array( $cart, 'set_cart_contents_taxes' ) ) ) {

			$cart->set_cart_contents_taxes( $tax_totals );

			$cart->set_discount_total( wc_cart_round_discount( ( $cart->get_discount_total() + $cart->get_discount_tax() ), $cart->dp ) );
			$cart->set_discount_tax( 0 );

			// Total up/round taxes
			if ( $cart->round_at_subtotal ) {
				$cart->set_total_tax( WC_Tax::get_tax_total( $tax_totals ) );
				$cart->set_cart_contents_taxes( array_map( array( 'WC_Tax', 'round' ), $cart->get_cart_contents_taxes() ) );
			} else {
				$cart->set_total_tax( array_sum( $tax_totals ) );
			}

		} else {

			$cart->taxes = $tax_totals;

			// Remove discounted taxes (taxes are not being discounted for vouchers)
			$cart->discount_cart          = wc_cart_round_discount( ( $cart->discount_cart + $cart->discount_cart_tax ), $cart->dp );
			$cart->discount_cart_tax      = 0;

			// Total up/round taxes
			if ( $cart->round_at_subtotal ) {
				$cart->tax_total          = WC_Tax::get_tax_total( $tax_totals );
				$cart->taxes              = array_map( array( 'WC_Tax', 'round' ), $cart->taxes );
			} else {
				$cart->tax_total          = array_sum( $tax_totals );
			}
		}
	}

	public function convert_coupon_to_voucher( $coupon ) {
		$coupon = wc_gzd_set_crud_meta_data( $coupon, 'is_voucher', 'yes' );
		$coupon->set_individual_use( true );
		$coupon->save();
	}

	public function coupon_options( $id, $coupon ) {

		woocommerce_wp_checkbox( array(
			'id'          => 'is_voucher',
			'label'       => __( 'Is voucher?', 'woocommerce-germanized' ),
			'description' => sprintf( __( 'Whether or not this coupon is a voucher which has been sold to a customer without VAT and needs to be taxed as soon as the customer redeems the voucher. Find more information <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'https://www.haufe.de/finance/steuern-finanzen/umsatzsteuer-wie-gutscheine-zu-behandeln-sind_190_76132.html' ),
		) );

	}

	public function coupon_save( $id, $coupon ) {
		if ( isset( $_POST[ 'is_voucher' ] ) ) {
			$this->convert_coupon_to_voucher( $coupon );
		} else {
			$coupon = wc_gzd_set_crud_meta_data( $coupon, 'is_voucher', 'no' );
			$coupon->save();
		}
	}

}

WC_GZD_Coupon_Helper::instance();
