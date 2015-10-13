<?php

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

/**
 * WooCommerce Germanized Abstract Product
 *
 * The WC_GZD_Product Class is used to offer additional functionality for every product type.
 *
 * @class 		WC_GZD_Product
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Product_Variable extends WC_GZD_Product {

	/**
	 * Get the min or max variation regular price.
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string
	 */
	public function get_variation_regular_price( $min_or_max = 'min', $display = false ) {
		$prices = $this->get_variation_prices( $display );
		$price  = 'min' === $min_or_max ? current( $prices['regular_price'] ) : end( $prices['regular_price'] );
		return apply_filters( 'woocommerce_get_variation_regular_price', $price, $this, $min_or_max, $display );
	}

	/**
	 * Get the min or max variation sale price.
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string
	 */
	public function get_variation_sale_price( $min_or_max = 'min', $display = false ) {
		$prices = $this->get_variation_prices( $display );
		$price  = 'min' === $min_or_max ? current( $prices['sale_price'] ) : end( $prices['sale_price'] );
		return apply_filters( 'woocommerce_get_variation_sale_price', $price, $this, $min_or_max, $display );
	}

	/**
	 * Get the min or max variation (active) price.
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string
	 */
	public function get_variation_price( $min_or_max = 'min', $display = false ) {
		$prices = $this->get_variation_prices( $display );
		$price  = 'min' === $min_or_max ? current( $prices['price'] ) : end( $prices['price'] );
		return apply_filters( 'woocommerce_get_variation_price', $price, $this, $min_or_max, $display );
	}

	/**
	 * Get an array of all sale and regular unit prices from all variations. This is used for example when displaying the price range at variable product level or seeing if the variable product is on sale.
	 *
	 * Can be filtered by plugins which modify costs, but otherwise will include the raw meta costs unlike get_price() which runs costs through the woocommerce_get_price filter.
	 * This is to ensure modified prices are not cached, unless intended.
	 *
	 * @param  bool $display Are prices for display? If so, taxes will be calculated.
	 * @return array() Array of RAW prices, regular prices, and sale prices with keys set to variation ID.
	 */
	public function get_variation_unit_prices( $display = false ) {

		if ( ! $this->is_type( 'variable' ) )
			return false; 

		global $wp_filter;

		if ( ! empty( $this->unit_prices_array ) ) {
			return $this->unit_prices_array;
		}

		/**
		 * Create unique cache key based on the tax location (affects displayed/cached prices), product version and active price filters.
		 * Max transient length is 45, -10 for get_transient_version.
		 * @var string
		 */
		$hash = array( $this->id, $display, $display ? WC_Tax::get_rates() : array() );

		foreach ( $wp_filter as $key => $val ) {
			if ( in_array( $key, array( 'woocommerce_gzd_variation_unit_prices_price', 'woocommerce_gzd_variation_unit_prices_regular_price', 'woocommerce_gzd_variation_unit_prices_sale_price' ) ) ) {
				$hash[ $key ] = $val;
			}
		}

		/**
		 * DEVELOPERS should filter this hash if offering conditonal pricing to keep it unique.
		 */
		$hash               	 = apply_filters( 'woocommerce_gzd_get_variation_unit_prices_hash', $hash, $this, $display );
		$cache_key          	 = 'wc_gzd_var_unit_prices' . substr( md5( json_encode( $hash ) ), 0, 22 ) . WC_Cache_Helper::get_transient_version( 'product' );
		$this->unit_prices_array = get_transient( $cache_key );

		if ( empty( $this->unit_prices_array ) ) {
			$prices           = array();
			$regular_prices   = array();
			$sale_prices      = array();
			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
			$variation_ids    = $this->get_children( true );

			foreach ( $variation_ids as $variation_id ) {
				if ( $variation = $this->get_child( $variation_id ) ) {
					$price         = apply_filters( 'woocommerce_gzd_variation_unit_prices_price', $variation->unit_price, $variation, $this );
					$regular_price = apply_filters( 'woocommerce_gzd_variation_unit_prices_regular_price', $variation->unit_price_regular, $variation, $this );
					$sale_price    = apply_filters( 'woocommerce_gzd_variation_unit_prices_sale_price', $variation->unit_price_sale, $variation, $this );

					// If sale price does not equal price, the product is not yet on sale
					if ( $sale_price === $regular_price || $sale_price !== $price ) {
						$sale_price = $regular_price;
					}

					// If we are getting prices for display, we need to account for taxes
					if ( $display ) {
						if ( 'incl' === $tax_display_mode ) {
							$price         = '' === $price ? ''         : $variation->get_price_including_tax( 1, $price );
							$regular_price = '' === $regular_price ? '' : $variation->get_price_including_tax( 1, $regular_price );
							$sale_price    = '' === $sale_price ? ''    : $variation->get_price_including_tax( 1, $sale_price );
						} else {
							$price         = '' === $price ? ''         : $variation->get_price_excluding_tax( 1, $price );
							$regular_price = '' === $regular_price ? '' : $variation->get_price_excluding_tax( 1, $regular_price );
							$sale_price    = '' === $sale_price ? ''    : $variation->get_price_excluding_tax( 1, $sale_price );
						}
					}

					$prices[ $variation_id ]         = $price;
					$regular_prices[ $variation_id ] = $regular_price;
					$sale_prices[ $variation_id ]    = $sale_price;
				}
			}

			asort( $prices );
			asort( $regular_prices );
			asort( $sale_prices );

			$this->unit_prices_array = array(
				'price'         => $prices,
				'regular_price' => $regular_prices,
				'sale_price'    => $sale_prices
			);

			set_transient( $cache_key, $this->unit_prices_array, DAY_IN_SECONDS * 30 );
		}

		/**
		 * Give plugins one last chance to filter the variation prices array.
		 */
		return $this->unit_prices_array = apply_filters( 'woocommerce_gzd_variation_unit_prices', $this->unit_prices_array, $this, $display );
	}

}