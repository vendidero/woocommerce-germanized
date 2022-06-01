<?php

defined( 'ABSPATH' ) || exit;

/**
 * B2B Market
 *
 * Specific configuration for B2B Market plugin
 */
class WC_GZD_Compatibility_B2B_Market extends WC_GZD_Compatibility_Woocommerce_Role_Based_Pricing {

	public static function get_name() {
		return 'B2B Market';
	}

	public static function get_path() {
		return 'b2b-market/b2b-market.php';
	}

	public function set_unit_price_filter() {
		if ( apply_filters( 'woocommerce_gzd_enable_b2b_market_unit_price_compatibility', true ) ) {
			add_action( 'woocommerce_gzd_before_get_unit_price', array( $this, 'calculate_unit_price' ), 10, 1 );
			add_filter( 'woocommerce_gzd_variable_unit_price_html', array( $this, 'filter_variable_unit_price' ), 10, 2 );
		}
	}

	protected function get_prices_from_string( $price_html ) {
		$prices           = array();
		$price_count      = substr_count( $price_html, 'woocommerce-Price-currencySymbol' );
		$start_pos_offset = 0;
		$currency_pos     = get_option( 'woocommerce_currency_pos' );

		if ( strpos( $price_html, 'b2b-' ) === false ) {
			return $prices;
		}

		for ( $i = 0; $i < $price_count; $i++ ) {
			$needle = 'woocommerce-Price-currencySymbol';

			$start_pos         = strpos( $price_html, $needle, $start_pos_offset );
			$start_pos_offset += ( $start_pos + strlen( $needle ) );
			$price_test        = substr( $price_html, ( $start_pos - 30 ), 30 );

			if ( in_array( $currency_pos, array( 'left_space', 'left' ), true ) ) {
				$price_test = substr( $price_html, ( $start_pos + 30 ), 30 );
			}

			$sep       = wc_get_price_decimal_separator();
			$regex_sep = '.' === $sep ? '\.' : $sep;

			preg_match_all( '/\d+' . $regex_sep . '\d+/', $price_test, $matches );

			if ( ! empty( $matches[0] ) ) {
				$prices[] = array(
					'string' => $matches[0][0],
					'number' => floatval( str_replace( ',', '.', $matches[0][0] ) ),
				);
			}
		}

		return $prices;
	}

	/**
	 * @param string $price
	 * @param WC_GZD_Product $gzd_product
	 */
	public function filter_variable_unit_price( $price, $gzd_product ) {
		// Prevent infinite loops in case recalculation is called via the price_html filter
		if ( doing_action( 'woocommerce_get_price_html' ) ) {
			return $price;
		}

		$price_html = $gzd_product->get_wc_product()->get_price_html();
		$prices     = $this->get_prices_from_string( $price_html );

		if ( empty( $prices ) ) {
			return $price;
		}

		$new_unit_price_html = $price_html;

		foreach ( $prices as $price_data ) {
			$args = array(
				'regular_price' => $price_data['number'],
				'sale_price'    => $price_data['number'],
				'price'         => $price_data['number'],
			);

			$unit_prices         = wc_gzd_recalculate_unit_price( $args, $gzd_product );
			$formatted           = number_format( $unit_prices['unit'], wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
			$new_unit_price_html = str_replace( $price_data['string'], $formatted, $new_unit_price_html );
		}

		return $new_unit_price_html !== $price_html ? $new_unit_price_html : $price;
	}

	/**
	 * @param WC_GZD_Product $gzd_product
	 */
	public function calculate_unit_price( $gzd_product ) {
		// Prevent infinite loops in case recalculation is called via the price_html filter
		if ( doing_action( 'woocommerce_get_price_html' ) ) {
			return;
		}

		$product    = $gzd_product->get_wc_product();
		$price_html = $product->get_price_html();
		$prices     = $this->get_prices_from_string( $price_html );

		if ( empty( $prices ) ) {
			return;
		}

		$args = array(
			'regular_price' => wc_format_decimal( $prices[0]['number'], '' ),
			'sale_price'    => wc_format_decimal( isset( $prices[1] ) ? $prices[1]['number'] : $prices[0]['number'], '' ),
			'price'         => wc_format_decimal( isset( $prices[1] ) ? $prices[1]['number'] : $prices[0]['number'], '' ),
		);

		if ( wc_tax_enabled() ) {
			if ( $product->is_taxable() ) {
				/**
				 * Prices are net based - let's get the net price first and use that to recalculate the unit price
				 */
				if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
					$tax_rates      = WC_Tax::get_rates( $product->get_tax_class() );
					$base_tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );

					foreach ( $args as $key => $line_price ) {
						$remove_taxes = apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ? WC_Tax::calc_tax( $line_price, $base_tax_rates, true ) : WC_Tax::calc_tax( $line_price, $tax_rates, true );
						$return_price = $line_price - array_sum( $remove_taxes );

						$args[ $key ] = $return_price;
					}
				}
			}
		}

		$gzd_product->recalculate_unit_price( $args );
	}

	public function variable_unit_prices_hash( $price_hash ) {
		return $price_hash;
	}
}
