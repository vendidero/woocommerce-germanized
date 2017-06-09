<?php

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

		// Tax Calculation
		add_filter( 'woocommerce_after_calculate_totals', array( $this, 'recalculate_tax_totals' ), 10, 1 );
	}

	public function recalculate_tax_totals( $cart ) {

		if ( WC()->customer->get_is_vat_exempt() )
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

		$cart->taxes                  = $tax_totals;

		// Remove discounted taxes (taxes are not being discounted for vouchers)
		$cart->discount_cart          = wc_cart_round_discount( $cart->discount_cart + $cart->discount_cart_tax, $cart->dp );
		$cart->discount_cart_tax      = 0;

		// Total up/round taxes
		if ( $cart->round_at_subtotal ) {
			$cart->tax_total          = WC_Tax::get_tax_total( $tax_totals );
			$cart->taxes              = array_map( array( 'WC_Tax', 'round' ), $cart->taxes );
		} else {
			$cart->tax_total          = array_sum( $tax_totals );
		}
	}

	public function convert_coupon_to_voucher( $coupon ) {
		$coupon = wc_gzd_set_crud_meta_data( $coupon, 'is_voucher', 'yes' );

		if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
			$coupon->set_individual_use( true );
			$coupon->save();
		} else {
			wc_gzd_set_crud_meta_data( $coupon, 'individual_use', 'yes' );
		}
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

			if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
				$coupon->save();
			}
		}
	}

}

WC_GZD_Coupon_Helper::instance();
