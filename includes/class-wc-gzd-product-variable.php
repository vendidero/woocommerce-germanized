<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * WooCommerce Germanized Product Variable
 *
 * The WC_GZD_Product_Variable Class is used to offer additional functionality for every variable product.
 *
 * @class        WC_GZD_Product
 * @version        1.0.0
 * @author        Vendidero
 */
class WC_GZD_Product_Variable extends WC_GZD_Product {

	protected $unit_prices_array = array();

	/**
	 * Get the min or max variation unit regular price.
	 *
	 * @param string $min_or_max - min or max
	 * @param boolean $display Whether the value is going to be displayed
	 *
	 * @return string
	 */
	public function get_variation_unit_regular_price( $min_or_max = 'min', $display = false ) {
		$prices = $this->get_variation_unit_prices( $display );
		$price  = 'min' === $min_or_max ? current( $prices['regular_price'] ) : end( $prices['regular_price'] );

		/**
		 * Filter to adjust the min or max variation regular unit price.
		 *
		 * @param string $price The price.
		 * @param WC_GZD_Product_Variable $product The product object.
		 * @param string $min_or_max Either `min` or `max`.
		 * @param bool $display Either for display purposes or not.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_get_variation_unit_regular_price', $price, $this, $min_or_max, $display );
	}

	/**
	 * Get the min or max variation unit sale price.
	 *
	 * @param string $min_or_max - min or max
	 * @param boolean $display Whether the value is going to be displayed
	 *
	 * @return string
	 */
	public function get_variation_unit_sale_price( $min_or_max = 'min', $display = false ) {
		$prices = $this->get_variation_unit_prices( $display );
		$price  = 'min' === $min_or_max ? current( $prices['sale_price'] ) : end( $prices['sale_price'] );

		/**
		 * Filter to adjust the min or max variation sale unit price.
		 *
		 * @param string $price The price.
		 * @param WC_GZD_Product_Variable $product The product object.
		 * @param string $min_or_max Either `min` or `max`.
		 * @param bool $display Either for display purposes or not.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_get_variation_unit_sale_price', $price, $this, $min_or_max, $display );
	}

	/**
	 * Get the min or max variation (active) unit price.
	 *
	 * @param string $min_or_max - min or max
	 * @param boolean $display Whether the value is going to be displayed
	 *
	 * @return string
	 */
	public function get_variation_unit_price( $min_or_max = 'min', $display = false ) {
		$prices = $this->get_variation_unit_prices( $display );
		$price  = 'min' === $min_or_max ? current( $prices['price'] ) : end( $prices['price'] );

		/**
		 * Filter to adjust the min or max variation unit price.
		 *
		 * @param string $price The price.
		 * @param WC_GZD_Product_Variable $product The product object.
		 * @param string $min_or_max Either `min` or `max`.
		 * @param bool $display Either for display purposes or not.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_get_variation_unit_price', $price, $this, $min_or_max, $display );
	}

	public function is_on_unit_sale() {
		$is_on_sale = false;
		$prices     = $this->get_variation_unit_prices();

		if ( $prices['regular_price'] !== $prices['sale_price'] && $prices['sale_price'] === $prices['price'] ) {
			$is_on_sale = true;
		}

		/** This filter is documented in includes/abstracts/abstract-wc-gzd-product.php */
		return apply_filters( 'woocommerce_gzd_product_is_on_unit_sale', $is_on_sale, $this );
	}

	public function has_unit() {
		$prices = $this->get_variation_unit_prices();

		if ( $this->get_unit() && is_array( $prices ) && $prices['regular_price'] && $this->get_unit_base() ) {
			return true;
		}

		return false;
	}

	public function has_unit_fields() {
		if ( $this->get_unit() && $this->get_unit_base() ) {
			return true;
		}

		return false;
	}

	public function get_price_html_from_to( $from, $to, $show_labels = true ) {
		$sale_label         = ( $show_labels ? $this->get_sale_price_label() : '' );
		$sale_regular_label = ( $show_labels ? $this->get_sale_price_regular_label() : '' );

		$price = ( ! empty( $sale_label ) ? '<span class="wc-gzd-sale-price-label">' . $sale_label . '</span>' : '' ) . ' <del>' . ( ( is_numeric( $from ) ) ? wc_price( $from ) : $from ) . '</del> ' . ( ! empty( $sale_regular_label ) ? '<span class="wc-gzd-sale-price-label wc-gzd-sale-price-regular-label">' . $sale_regular_label . '</span> ' : '' ) . '<ins>' . ( ( is_numeric( $to ) ) ? wc_price( $to ) : $to ) . '</ins>';

		/** This filter is documented in includes/abstracts/abstract-wc-gzd-product.php */
		return apply_filters( 'woocommerce_germanized_get_price_html_from_to', $price, $from, $to, $this );
	}

	/**
	 * Returns the price in html format.
	 *
	 * @access public
	 *
	 * @param string $price (default: '')
	 *
	 * @return string
	 */
	public function get_unit_price_html( $price = '', $tax_display = '' ) {

		if ( get_option( 'woocommerce_gzd_unit_price_enable_variable' ) === 'no' ) {
			return '';
		}

		$prices = $this->get_variation_unit_prices( true, $tax_display );

		if ( $this->has_unit() ) {
			$min_price     = current( $prices['price'] );
			$max_price     = end( $prices['price'] );
			$min_reg_price = current( $prices['regular_price'] );
			$max_reg_price = end( $prices['regular_price'] );

			if ( $min_price !== $max_price ) {
				$price = woocommerce_gzd_format_unit_price_range( $min_price, $max_price );
			} elseif ( $this->get_wc_product()->is_on_sale() && $min_reg_price === $max_reg_price ) {
				$price = wc_format_sale_price( wc_price( $max_reg_price ), wc_price( $min_price ) );
			} else {
				$price = wc_price( $min_price );
			}

			/**
			 * Filter to adjust variable product unit price.
			 * In case of Woo version > 3.0.0 this filter can contain the formatted sale price too.
			 *
			 * @param string $price The price.
			 * @param WC_GZD_Product_Variable $product The product object.
			 *
			 * @since 1.8.3
			 *
			 */
			$price = apply_filters( 'woocommerce_gzd_variable_unit_price_html', $price, $this );
			$price = wc_gzd_format_unit_price( $price, $this->get_unit_html(), $this->get_unit_base_html(), wc_gzd_format_product_units_decimal( $this->get_unit_product() ) );
		}

		/** This filter is documented in includes/abstract/abstract-wc-gzd-product.php */
		return apply_filters( 'woocommerce_gzd_unit_price_html', $price, $this );
	}

	/**
	 * Get an array of all sale and regular unit prices from all variations. This is used for example when displaying the price range at variable product level or seeing if the variable product is on sale.
	 *
	 * Can be filtered by plugins which modify costs, but otherwise will include the raw meta costs unlike get_price() which runs costs through the woocommerce_get_price filter.
	 * This is to ensure modified prices are not cached, unless intended.
	 *
	 * @param bool $display Are prices for display? If so, taxes will be calculated.
	 *
	 * @return array() Array of RAW prices, regular prices, and sale prices with keys set to variation ID.
	 */
	public function get_variation_unit_prices( $display = false, $tax_display = '' ) {

		if ( ! $this->child->is_type( 'variable' ) ) {
			return false;
		}

		// Product doesn't apply for unit pricing
		if ( ! $this->has_unit_fields() ) {
			return false;
		}

		global $wp_filter;

		$transient_name    = 'wc_gzd_var_unit_prices_' . $this->child->get_id();
		$transient_version = WC_Cache_Helper::get_transient_version( 'product' );
		$tax_display       = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_shop', 'excl' );

		/**
		 * Create unique cache key based on the tax location (affects displayed/cached prices), product version and active price filters.
		 * DEVELOPERS should filter this hash if offering conditonal pricing to keep it unique.
		 * @var string
		 */
		if ( $display && wc_tax_enabled() ) {
			$price_hash = array( $tax_display, WC_Tax::get_rates() );
		} else {
			$price_hash = array( false );
		}

		$filter_names = array(
			'woocommerce_gzd_variation_unit_prices_price',
			'woocommerce_gzd_variation_unit_prices_regular_price',
			'woocommerce_gzd_variation_unit_prices_sale_price',
		);

		foreach ( $filter_names as $filter_name ) {
			if ( ! empty( $wp_filter[ $filter_name ] ) ) {
				$price_hash[ $filter_name ] = array();

				foreach ( $wp_filter[ $filter_name ] as $priority => $callbacks ) {
					$price_hash[ $filter_name ][] = array_values( wp_list_pluck( $callbacks, 'function' ) );
				}
			}
		}

		// Check if prices array is stale.
		if ( ! isset( $this->unit_prices_array['version'] ) || $this->unit_prices_array['version'] !== $transient_version ) {
			$this->unit_prices_array = array(
				'version' => $transient_version,
			);
		}

		/**
		 * Filter to adjust variable unit prices hash.
		 * This hash is used to get a transient with cached variable unit prices.
		 *
		 * @param string $price_hash The hash.
		 * @param WC_GZD_Product_Variable $product The producht object.
		 * @param bool $display Whether prices are for displaying purposes or not.
		 *
		 * @since 1.0.0
		 *
		 */
		$price_hash = md5( wp_json_encode( apply_filters( 'woocommerce_gzd_get_variation_unit_prices_hash', $price_hash, $this, $display ) ) );

		// If the value has already been generated, we don't need to grab the values again.
		if ( empty( $this->unit_prices_array[ $price_hash ] ) ) {

			// Get value of transient
			$this->unit_prices_array = array_filter( (array) json_decode( strval( get_transient( $transient_name ) ), true ) );

			// If the product version has changed, reset cache
			if ( empty( $this->unit_prices_array['version'] ) || $this->unit_prices_array['version'] !== $transient_version ) {
				$this->unit_prices_array = array( 'version' => $transient_version );
			}

			// If the prices are not stored for this hash, generate them
			if ( empty( $this->unit_prices_array[ $price_hash ] ) ) {

				/**
				 * Use the (already sorted) variation prices of the parent product
				 * to make sure the right unit price matches min max price ranges.
				 */
				$variation_prices = $this->get_wc_product()->get_variation_prices( $display );
				$prices           = array();
				$regular_prices   = array();
				$sale_prices      = array();
				$unique_values    = array_unique( $variation_prices['price'] );
				/**
				 * Allow sorting unit prices by value in case the variable
				 * product contains only products of the same price
				 */
				$allow_sort   = count( $unique_values ) === 1;
				$is_min_price = woocommerce_gzd_price_range_format_is_min_price();
				$is_max_price = woocommerce_gzd_price_range_format_is_max_price();

				/**
				 * In case the current price range format includes a starting from price only
				 * we will need to make sure that we do only check unit prices for variations
				 * that match the minimum price.
				 */
				if ( $is_min_price && ! empty( $variation_prices['price'] ) ) {
					$min_price  = array_values( $variation_prices['price'] )[0];
					$allow_sort = true;

					foreach ( $variation_prices['price'] as $variation_id => $price ) {
						if ( $price > $min_price ) {
							unset( $variation_prices['price'][ $variation_id ] );
						}
					}
				} elseif ( $is_max_price && ! empty( $variation_prices['price'] ) ) {
					$max_price  = array_values( $variation_prices['price'] )[ count( $variation_prices['price'] ) - 1 ];
					$allow_sort = true;

					foreach ( $variation_prices['price'] as $variation_id => $price ) {
						if ( $price < $max_price ) {
							unset( $variation_prices['price'][ $variation_id ] );
						}
					}
				}

				foreach ( $variation_prices['price'] as $variation_id => $price ) {
					if ( $variation = wc_get_product( $variation_id ) ) {
						$gzd_variation = wc_gzd_get_product( $variation );

						/**
						 * Before retrieving variation unit price.
						 *
						 * Fires before a unit price for a certain variation is retrieved. May be useful for
						 * recalculation purposes.
						 *
						 * @param WC_GZD_Product the variation product object.
						 *
						 * @since 1.0.0
						 *
						 */
						do_action( 'woocommerce_gzd_before_get_variable_variation_unit_price', $gzd_variation );

						/**
						 * Filters the variation unit price.
						 *
						 * @param string $price The unit price.
						 * @param WC_Product_Variation $product The product object.
						 * @param WC_GZD_Product_Variable $parent The variable parent product object.
						 *
						 * @since 1.8.3
						 *
						 */
						$price = apply_filters( 'woocommerce_gzd_variation_unit_prices_price', $gzd_variation->get_unit_price(), $variation, $this );

						/**
						 * Filters the variation regular unit price.
						 *
						 * @param string $price The regular unit price.
						 * @param WC_Product_Variation $product The product object.
						 * @param WC_GZD_Product_Variable $parent The variable parent product object.
						 *
						 * @since 1.8.3
						 *
						 */
						$regular_price = apply_filters( 'woocommerce_gzd_variation_unit_prices_regular_price', $gzd_variation->get_unit_price_regular(), $variation, $this );

						/**
						 * Filters the variation sale unit price.
						 *
						 * @param string $price The sale unit price.
						 * @param WC_Product_Variation $product The product object.
						 * @param WC_GZD_Product_Variable $parent The variable parent product object.
						 *
						 * @since 1.8.3
						 *
						 */
						$sale_price = apply_filters( 'woocommerce_gzd_variation_unit_prices_sale_price', $gzd_variation->get_unit_price_sale(), $variation, $this );

						// If sale price does not equal price, the product is not yet on sale
						if ( $sale_price === $regular_price || $sale_price !== $price ) {
							$sale_price = $regular_price;
						}

						// If we are getting prices for display, we need to account for taxes
						if ( $display ) {
							if ( 'incl' === $tax_display ) {
								$price         = '' === $price ? '' : wc_get_price_including_tax(
									$variation,
									array(
										'qty'   => 1,
										'price' => $price,
									)
								);
								$regular_price = '' === $regular_price ? '' : wc_get_price_including_tax(
									$variation,
									array(
										'qty'   => 1,
										'price' => $regular_price,
									)
								);
								$sale_price    = '' === $sale_price ? '' : wc_get_price_including_tax(
									$variation,
									array(
										'qty'   => 1,
										'price' => $sale_price,
									)
								);
							} else {
								$price         = '' === $price ? '' : wc_get_price_excluding_tax(
									$variation,
									array(
										'qty'   => 1,
										'price' => $price,
									)
								);
								$regular_price = '' === $regular_price ? '' : wc_get_price_excluding_tax(
									$variation,
									array(
										'qty'   => 1,
										'price' => $regular_price,
									)
								);
								$sale_price    = '' === $sale_price ? '' : wc_get_price_excluding_tax(
									$variation,
									array(
										'qty'   => 1,
										'price' => $sale_price,
									)
								);
							}
						}

						$prices[ $variation_id ]         = $price;
						$regular_prices[ $variation_id ] = $regular_price;
						$sale_prices[ $variation_id ]    = $sale_price;
					}
				}

				if ( $allow_sort ) {
					asort( $prices );
					asort( $regular_prices );
					asort( $sale_prices );
				}

				$this->unit_prices_array[ $price_hash ] = array(
					'price'         => $prices,
					'regular_price' => $regular_prices,
					'sale_price'    => $sale_prices,
				);

				set_transient( $transient_name, wp_json_encode( $this->unit_prices_array ), DAY_IN_SECONDS * 30 );
			}

			/**
			 * Filter to adjust the unit prices for a certain variation right before returning.
			 * Last change to adjust unit prices before handing them over for further processing.
			 *
			 * @param array $unit_prices Array containing unit price data.
			 * @param WC_GZD_Product_Variable $product The product object.
			 * @param bool $display Whether output is for display purposes or not.
			 *
			 * @since 1.8.3
			 *
			 */
			$this->unit_prices_array[ $price_hash ] = apply_filters( 'woocommerce_gzd_variation_unit_prices', $this->unit_prices_array[ $price_hash ], $this, $display );
		}

		return $this->unit_prices_array[ $price_hash ];
	}
}
