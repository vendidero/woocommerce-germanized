<?php
/**
 * WooCommerce GZD cart
 *
 * Extends WooCommerce default cart to implement vat exception for digital products (if customer is not from shop base country)
 *
 * @class 		WC_GZD_Cart
 * @see 		WC_Cart
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Cart extends WC_Cart {

	/**
	 * Reset cart totals to the defaults. Useful before running calculations.
	 *
	 * @param  bool  	$unset_session If true, the session data will be forced unset.
	 * @access private
	 */
	private function reset( $unset_session = false ) {
		foreach ( $this->cart_session_data as $key => $default ) {
			$this->$key = $default;
			if ( $unset_session ) {
				unset( WC()->session->$key );
			}
		}
		do_action( 'woocommerce_cart_reset', $this, $unset_session );
	}

	/**
	 * Calculate totals for cart. Implements vat exception for digital products.
	 */
	public function calculate_totals() {

		$this->reset();
		$this->coupons = $this->get_coupons();

		do_action( 'woocommerce_before_calculate_totals', $this );

		if ( sizeof( $this->get_cart() ) == 0 ) {
			$this->set_session();
			return;
		}

		$tax_rates      = array();
		$shop_tax_rates = array();

		/**
		 * Calculate subtotals for items. This is done first so that discount logic can use the values.
		 */
		foreach ( $this->get_cart() as $cart_item_key => $values ) {

			$_product = $values['data'];

			// Count items + weight
			$this->cart_contents_weight += $_product->get_weight() * $values['quantity'];
			$this->cart_contents_count  += $values['quantity'];

			// Prices
			$line_price = $_product->get_price() * $values['quantity'];

			$line_subtotal = 0;
			$line_subtotal_tax = 0;

			/**
			 * No tax to calculate
			 */
			if ( ! $_product->is_taxable() ) {

				// Subtotal is the undiscounted price
				$this->subtotal += $line_price;
				$this->subtotal_ex_tax += $line_price;

			/**
			 * Prices include tax
			 *
			 * To prevent rounding issues we need to work with the inclusive price where possible
			 * otherwise we'll see errors such as when working with a 9.99 inc price, 20% VAT which would
			 * be 8.325 leading to totals being 1p off
			 *
			 * Pre tax coupons come off the price the customer thinks they are paying - tax is calculated
			 * afterwards.
			 *
			 * e.g. $100 bike with $10 coupon = customer pays $90 and tax worked backwards from that
			 */
			} elseif ( $this->prices_include_tax ) {

				// Get base tax rates
				if ( empty( $shop_tax_rates[ $_product->tax_class ] ) ) {
					$shop_tax_rates[ $_product->tax_class ] = WC_Tax::get_base_tax_rates( $_product->tax_class );
				}

				// Get item tax rates
				if ( empty( $tax_rates[ $_product->get_tax_class() ] ) ) {
					$tax_rates[ $_product->get_tax_class() ] = WC_Tax::get_rates( $_product->get_tax_class() );
				}

				$base_tax_rates = $shop_tax_rates[ $_product->tax_class ];
				$item_tax_rates = $tax_rates[ $_product->get_tax_class() ];

				/**
				 * ADJUST TAX - Calculations when base tax is not equal to the item tax
				 */
				if ( $item_tax_rates !== $base_tax_rates ) {

					// Work out a new base price without the shop's base tax
					$taxes                 = WC_Tax::calc_tax( $line_price, $base_tax_rates, true, true );

					// Digital VAT exception
					if ( $this->is_virtual_taxable() && $_product->gzd_product->is_virtual_vat_exception() )
						$taxes 		   		= WC_Tax::calc_tax( $line_price, $item_tax_rates, true, true );

					// Now we have a new item price (excluding TAX)
					$line_subtotal         = $line_price - array_sum( $taxes );

					// Now add modified taxes
					$tax_result            = WC_Tax::calc_tax( $line_subtotal, $item_tax_rates );
					$line_subtotal_tax     = array_sum( $tax_result );

				/**
				 * Regular tax calculation (customer inside base and the tax class is unmodified
				 */
				} else {

					// Calc tax normally
					$taxes                 = WC_Tax::calc_tax( $line_price, $item_tax_rates, true );
					$line_subtotal_tax     = array_sum( $taxes );
					$line_subtotal         = $line_price - array_sum( $taxes );
				}

			/**
			 * Prices exclude tax
			 *
			 * This calculation is simpler - work with the base, untaxed price.
			 */
			} else {

				// Get item tax rates
				if ( empty( $tax_rates[ $_product->get_tax_class() ] ) ) {
					$tax_rates[ $_product->get_tax_class() ] = WC_Tax::get_rates( $_product->get_tax_class() );
				}

				$item_tax_rates        = $tax_rates[ $_product->get_tax_class() ];

				// Base tax for line before discount - we will store this in the order data
				$taxes                 = WC_Tax::calc_tax( $line_price, $item_tax_rates );
				$line_subtotal_tax     = array_sum( $taxes );
				$line_subtotal         = $line_price;
			}

			// Add to main subtotal
			$this->subtotal        += $line_subtotal + $line_subtotal_tax;
			$this->subtotal_ex_tax += $line_subtotal;
		}

		/**
		 * Calculate totals for items
		 */
		foreach ( $this->get_cart() as $cart_item_key => $values ) {

			$_product = $values['data'];

			// Prices
			$base_price = $_product->get_price();
			$line_price = $_product->get_price() * $values['quantity'];

			// Tax data
			$taxes = array();
			$discounted_taxes = array();

			/**
			 * No tax to calculate
			 */
			if ( ! $_product->is_taxable() ) {

				// Discounted Price (price with any pre-tax discounts applied)
				$discounted_price      = $this->get_discounted_price( $values, $base_price, true );
				$line_subtotal_tax     = 0;
				$line_subtotal         = $line_price;
				$line_tax              = 0;
				$line_total            = WC_Tax::round( $discounted_price * $values['quantity'] );

			/**
			 * Prices include tax
			 */
			} elseif ( $this->prices_include_tax ) {

				$base_tax_rates = $shop_tax_rates[ $_product->tax_class ];
				$item_tax_rates = $tax_rates[ $_product->get_tax_class() ];

				/**
				 * ADJUST TAX - Calculations when base tax is not equal to the item tax
				 */
				if ( $item_tax_rates !== $base_tax_rates ) {

					// Work out a new base price without the shop's base tax
					$taxes             = WC_Tax::calc_tax( $line_price, $base_tax_rates, true, true );

					// Digital tax exception
					if ( $this->is_virtual_taxable() && $_product->gzd_product->is_virtual_vat_exception() )
						$taxes 		   = WC_Tax::calc_tax( $line_price, $item_tax_rates, true, true );

					// Now we have a new item price (excluding TAX)
					$line_subtotal     = round( $line_price - array_sum( $taxes ), WC_ROUNDING_PRECISION );

					$taxes             = WC_Tax::calc_tax( $line_subtotal, $item_tax_rates );
					$line_subtotal_tax = array_sum( $taxes );

					// Adjusted price (this is the price including the new tax rate)
					$adjusted_price    = ( $line_subtotal + $line_subtotal_tax ) / $values['quantity'];

					// Apply discounts
					$discounted_price  = $this->get_discounted_price( $values, $adjusted_price, true );
					$discounted_taxes  = WC_Tax::calc_tax( $discounted_price * $values['quantity'], $item_tax_rates, true );
					$line_tax          = array_sum( $discounted_taxes );
					$line_total        = ( $discounted_price * $values['quantity'] ) - $line_tax;

				/**
				 * Regular tax calculation (customer inside base and the tax class is unmodified
				 */
				} else {

					// Work out a new base price without the item tax
					$taxes             = WC_Tax::calc_tax( $line_price, $item_tax_rates, true );

					// Now we have a new item price (excluding TAX)
					$line_subtotal     = $line_price - array_sum( $taxes );
					$line_subtotal_tax = array_sum( $taxes );

					// Calc prices and tax (discounted)
					$discounted_price = $this->get_discounted_price( $values, $base_price, true );
					$discounted_taxes = WC_Tax::calc_tax( $discounted_price * $values['quantity'], $item_tax_rates, true );
					$line_tax         = array_sum( $discounted_taxes );
					$line_total       = ( $discounted_price * $values['quantity'] ) - $line_tax;
				}

				// Tax rows - merge the totals we just got
				foreach ( array_keys( $this->taxes + $discounted_taxes ) as $key ) {
					$this->taxes[ $key ] = ( isset( $discounted_taxes[ $key ] ) ? $discounted_taxes[ $key ] : 0 ) + ( isset( $this->taxes[ $key ] ) ? $this->taxes[ $key ] : 0 );
				}

			/**
			 * Prices exclude tax
			 */
			} else {

				$item_tax_rates        = $tax_rates[ $_product->get_tax_class() ];

				// Work out a new base price without the shop's base tax
				$taxes                 = WC_Tax::calc_tax( $line_price, $item_tax_rates );

				// Now we have the item price (excluding TAX)
				$line_subtotal         = $line_price;
				$line_subtotal_tax     = array_sum( $taxes );

				// Now calc product rates
				$discounted_price      = $this->get_discounted_price( $values, $base_price, true );
				$discounted_taxes      = WC_Tax::calc_tax( $discounted_price * $values['quantity'], $item_tax_rates );
				$discounted_tax_amount = array_sum( $discounted_taxes );
				$line_tax              = $discounted_tax_amount;
				$line_total            = $discounted_price * $values['quantity'];

				// Tax rows - merge the totals we just got
				foreach ( array_keys( $this->taxes + $discounted_taxes ) as $key ) {
					$this->taxes[ $key ] = ( isset( $discounted_taxes[ $key ] ) ? $discounted_taxes[ $key ] : 0 ) + ( isset( $this->taxes[ $key ] ) ? $this->taxes[ $key ] : 0 );
				}
			}

			// Cart contents total is based on discounted prices and is used for the final total calculation
			$this->cart_contents_total += $line_total;

			// Store costs + taxes for lines
			$this->cart_contents[ $cart_item_key ]['line_total']        = $line_total;
			$this->cart_contents[ $cart_item_key ]['line_tax']          = $line_tax;
			$this->cart_contents[ $cart_item_key ]['line_subtotal']     = $line_subtotal;
			$this->cart_contents[ $cart_item_key ]['line_subtotal_tax'] = $line_subtotal_tax;

			// Store rates ID and costs - Since 2.2
			$this->cart_contents[ $cart_item_key ]['line_tax_data']     = array( 'total' => $discounted_taxes, 'subtotal' => $taxes );
		}

		// Only calculate the grand total + shipping if on the cart/checkout
		if ( is_checkout() || is_cart() || defined('WOOCOMMERCE_CHECKOUT') || defined('WOOCOMMERCE_CART') ) {

			// Calculate the Shipping
			$this->calculate_shipping();

			// Trigger the fees API where developers can add fees to the cart
			$this->calculate_fees();

			// Total up/round taxes and shipping taxes
			if ( $this->round_at_subtotal ) {
				$this->tax_total          = WC_Tax::get_tax_total( $this->taxes );
				$this->shipping_tax_total = WC_Tax::get_tax_total( $this->shipping_taxes );
				$this->taxes              = array_map( array( 'WC_Tax', 'round' ), $this->taxes );
				$this->shipping_taxes     = array_map( array( 'WC_Tax', 'round' ), $this->shipping_taxes );
			} else {
				$this->tax_total          = array_sum( $this->taxes );
				$this->shipping_tax_total = array_sum( $this->shipping_taxes );
			}

			// VAT exemption done at this point - so all totals are correct before exemption
			if ( WC()->customer->is_vat_exempt() ) {
				$this->remove_taxes();
			}

			// Allow plugins to hook and alter totals before final total is calculated
			do_action( 'woocommerce_calculate_totals', $this );

			// Grand Total - Discounted product prices, discounted tax, shipping cost + tax
			$this->total = max( 0, apply_filters( 'woocommerce_calculated_total', round( $this->cart_contents_total + $this->tax_total + $this->shipping_tax_total + $this->shipping_total + $this->fee_total, $this->dp ), $this ) );

		} else {

			// Set tax total to sum of all tax rows
			$this->tax_total = WC_Tax::get_tax_total( $this->taxes );

			// VAT exemption done at this point - so all totals are correct before exemption
			if ( WC()->customer->is_vat_exempt() ) {
				$this->remove_taxes();
			}

		}

		do_action( 'woocommerce_after_calculate_totals', $this );
		//print_r($this->taxes);
		$this->set_session();
	}

	/**
	 * Decides whether current cart (and customer) apply for a digital vat exception (checks whether customer is from EU and not from base country)
	 *  
	 * @return boolean
	 */
	public function is_virtual_taxable() {
		if ( get_option( 'woocommerce_gzd_enable_virtual_vat' ) != 'yes' )
			return false;
		if ( ( ! empty( WC()->customer ) ) && ! WC()->customer->is_vat_exempt() ) {
			$taxable_address = WC()->customer->get_taxable_address();
			$base_country =  WC()->countries->get_base_country();
			if ( isset( $taxable_address[0] ) && $taxable_address[0] != $base_country && in_array( $taxable_address[0], WC()->countries->get_european_union_countries() ) )
				return true;
		}
		return false;
	}

}

?>