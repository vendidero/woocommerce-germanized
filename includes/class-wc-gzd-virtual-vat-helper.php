<?php
/**
 * Virtual VAT Helper
 *
 *
 * @class 		WC_GZD_Virtual_VAT_Helper
 * @category	Class
 * @author 		vendidero
 */
class WC_GZD_Virtual_VAT_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	public function __construct() {
		add_action( 'init', array( $this, 'init' ), 2 );
	}

	public function init() {
		// Adjust virtual Product Price and tax class
		add_filter( 'woocommerce_cart_product_price', array( $this, 'set_virtual_cart_price' ), 20, 2 );
		add_filter( 'woocommerce_cart_product_subtotal', array( $this, 'set_virtual_cart_subtotal' ), 20, 4 );
		add_filter( 'woocommerce_get_price_including_tax', array( $this, 'set_virtual_price_for_vat_exempts' ), 10, 3 );
	}

	/**
	 * Adjust line subtotal for virtual products
	 */
	public function set_virtual_cart_subtotal( $product_subtotal, $_product, $quantity, $cart ) {

		if ( ! $_product->gzd_product->is_virtual_vat_exception() || ! $cart->is_virtual_taxable() )
			return $product_subtotal;

		$product_subtotal = $this->set_virtual_cart_product_price( $product_subtotal, $_product, $quantity );

		return wc_price( $product_subtotal );
		
	}

	public function set_virtual_cart_price( $price, $_product ) {

		if ( ! $_product->gzd_product->is_virtual_vat_exception() || ! WC()->cart->is_virtual_taxable() )
			return $price;

		$price = $this->set_virtual_cart_product_price( $price, $_product );

		return wc_price( $price );

	}

	public function set_virtual_cart_product_price( $price, $_product, $quantity = 1 ) {

		if ( WC()->cart->prices_include_tax ) {

			/**
			 * Calculate new net price based on item tax rates
			 */
			if ( WC()->customer && WC()->customer->is_vat_exempt() ) {

				$tax_rates      = array();
				$shop_tax_rates = array();

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

				$taxes = WC_Tax::calc_tax( $_product->get_price() * $quantity, $item_tax_rates, true, true );
				$price = ( $_product->get_price() * $quantity ) - array_sum( $taxes );

			} else {

				$price = $_product->get_price() * $quantity;

			}

		}

		return $price;

	}

	/**
	 * Adjust prices (including tax) for virtual products
	 */
	public function set_virtual_price_for_vat_exempts( $price, $qty, $product ) {

		if ( $product->gzd_product && $product->gzd_product->is_virtual_vat_exception() && $product->is_taxable() ) {

			if ( get_option( 'woocommerce_prices_include_tax' ) === 'yes' ) {

				if ( ! empty( WC()->customer ) && WC()->customer->is_vat_exempt() ) {

					$tax_rates      = WC_Tax::get_rates( $product->get_tax_class() );
					$base_tax_rates = WC_Tax::get_base_tax_rates( $product->tax_class );

					// Add default rates to get original gross price
					$base_taxes         = WC_Tax::calc_tax( $price, $base_tax_rates );
					$base_tax_amount    = array_sum( $base_taxes );
					$price              = round( $price + $base_tax_amount, wc_get_price_decimals() );

					// Substract product tax rates (e.g. 20 percent if customer is from AT)
					$product_tax_rates 	= WC_Tax::get_rates( $product->get_tax_class() );
					$product_taxes  	= WC_Tax::calc_tax( $price, $product_tax_rates, true, true );
					$product_tax_amount = array_sum( $product_taxes );
					$price              = round( $price - $product_tax_amount, wc_get_price_decimals() );

				}

			}

		}

		return $price;

	}

}

return WC_GZD_Virtual_VAT_Helper::instance();