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

	protected $product_units_array = array();

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
		if ( $this->has_unit() ) {
			/**
			 * Before retrieving unit price HTML.
			 *
			 * Fires before the HTML output for the unit price is generated.
			 *
			 * @param WC_GZD_Product $this The product object.
			 *
			 * @since 1.0.0
			 *
			 */
			do_action( 'woocommerce_gzd_before_get_unit_price_html', $this );

			$has_from_to   = false;
			$prices        = $this->get_variation_unit_prices( true, $tax_display );
			$min_price     = current( $prices['price'] );
			$max_price     = end( $prices['price'] );
			$min_reg_price = current( $prices['regular_price'] );
			$max_reg_price = end( $prices['regular_price'] );

			if ( $min_price !== $max_price ) {
				$price       = woocommerce_gzd_format_unit_price_range( $min_price, $max_price );
				$has_from_to = true;
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

			/**
			 * Filter to adjust whether to hide from-to unit prices or not.
			 *
			 * @param bool $hide_unit_price Whether to hide the unit price or not.
			 * @param string $price The price.
			 * @param WC_GZD_Product_Variable $product The product object.
			 *
			 * @since 3.18.8
			 */
			if ( apply_filters( 'woocommerce_gzd_variable_disable_unit_price_from_to', ( get_option( 'woocommerce_gzd_unit_price_enable_variable' ) === 'no' && $has_from_to ), $price, $this ) ) {
				$price = '';
			}
		}

		/** This filter is documented in includes/abstract/abstract-wc-gzd-product.php */
		return apply_filters( 'woocommerce_gzd_unit_price_html', $price, $this, $tax_display );
	}

	protected function get_current_price_from() {
		$prices = $this->get_variation_prices( true );

		if ( empty( $prices['price'] ) ) {
			$price = apply_filters( 'woocommerce_variable_empty_price_html', '', $this );
		} else {
			$price = current( $prices['price'] );
		}

		return $price;
	}

	protected function get_current_price_to() {
		$prices = $this->get_variation_prices( true );

		if ( empty( $prices['price'] ) ) {
			$price = apply_filters( 'woocommerce_variable_empty_price_html', '', $this );
		} else {
			$price = end( $prices['price'] );
		}

		return $price;
	}

	public function recalculate_unit_price( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'price_from'    => (float) $this->get_current_price_from(),
				'price_to'      => (float) $this->get_current_price_to(),
				'sale_price'    => null,
				'regular_price' => null,
			)
		);

		if ( isset( $args['tax_mode'] ) && 'incl' === $args['tax_mode'] ) {
			$args['price_from'] = wc_get_price_including_tax( $this->get_wc_product(), array( 'price' => $args['price_from'] ) );
			$args['price_to']   = wc_get_price_including_tax( $this->get_wc_product(), array( 'price' => $args['price_to'] ) );
		} elseif ( isset( $args['tax_mode'] ) && 'excl' === $args['tax_mode'] ) {
			$args['price_from'] = wc_get_price_excluding_tax( $this->get_wc_product(), array( 'price' => $args['price_from'] ) );
			$args['price_to']   = wc_get_price_excluding_tax( $this->get_wc_product(), array( 'price' => $args['price_to'] ) );
		}

		$has_price_range = $args['price_from'] !== $args['price_to'];

		/**
		 * Support passing parsed from/to prices as regular/sale price, e.g. during
		 * price observations.
		 */
		if ( ! is_null( $args['regular_price'] ) ) {
			$args['price_from'] = $args['regular_price'];
			$args['price_to']   = $args['price'];
		}

		if ( ! is_null( $args['sale_price'] ) ) {
			$args['price_to'] = $args['sale_price'];
		}

		$prices = $this->get_variation_unit_prices( true );

		if ( $this->has_unit() ) {
			$variation_ids     = array_keys( $prices['price'] );
			$variation_id_from = current( $variation_ids );
			$variation_id_to   = end( $variation_ids );

			if ( $from_variation = wc_gzd_get_gzd_product( $variation_id_from ) ) {
				$price_args = array(
					'price' => $args['price_from'],
				);

				if ( ! $has_price_range ) {
					$price_args = $args;
				}

				$new_from_price = wc_gzd_recalculate_unit_price(
					array_merge(
						$price_args,
						array(
							'base'     => $from_variation->get_unit_base(),
							'products' => $from_variation->get_unit_product(),
						)
					)
				);

				$this->set_unit_prices(
					$variation_id_from,
					array(
						'price'         => $new_from_price['unit'],
						'sale_price'    => $new_from_price['sale'],
						'regular_price' => $new_from_price['regular'],
					),
					true
				);
			}

			if ( $variation_id_from !== $variation_id_to ) {
				if ( $to_variation = wc_gzd_get_gzd_product( $variation_id_to ) ) {
					$price_args = array(
						'price' => $args['price_to'],
					);

					if ( ! $has_price_range ) {
						$price_args = $args;
					}

					$new_to_price = wc_gzd_recalculate_unit_price(
						array_merge(
							$price_args,
							array(
								'base'     => $to_variation->get_unit_base(),
								'products' => $to_variation->get_unit_product(),
							)
						)
					);

					$this->set_unit_prices(
						$variation_id_to,
						array(
							'price'         => $new_to_price['unit'],
							'sale_price'    => $new_to_price['sale'],
							'regular_price' => $new_to_price['regular'],
						),
						true
					);
				}
			}
		}
	}

	public function has_unit_product() {
		if ( $this->show_unit_product_ranges() ) {
			return $this->get_unit() && ! empty( $this->get_variation_product_units() );
		}

		return parent::has_unit_product();
	}

	protected function show_unit_product_ranges() {
		return apply_filters( 'woocommerce_gzd_product_variable_show_unit_product_ranges', version_compare( $this->get_gzd_version(), '3.17.0', '>=' ), $this );
	}

	public function get_unit_product_html() {
		if ( ! $this->show_unit_product_ranges() ) {
			return parent::get_unit_product_html();
		}

		/**
		 * Filter that allows disabling product units output for a specific product.
		 *
		 * @param bool $disable Whether to disable or not.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.0.0
		 */
		if ( apply_filters( 'woocommerce_gzd_hide_product_units_text', false, $this ) ) {

			/**
			 * Filter that allows adjusting the disabled product units output.
			 *
			 * @param string $notice The output.
			 * @param WC_GZD_Product $product The product object.
			 *
			 * @since 1.0.0
			 *
			 */
			return apply_filters( 'woocommerce_germanized_disabled_product_units_text', '', $this );
		}

		$html = '';
		$text = get_option( 'woocommerce_gzd_product_units_text' );

		if ( $this->has_unit_product() ) {
			$product_units = $this->get_variation_product_units();
			$min_unit      = wc_gzd_format_product_units_decimal( current( $product_units ) );
			$max_unit      = wc_gzd_format_product_units_decimal( end( $product_units ) );

			$replacements = array(
				'{product_units}' => $min_unit,
				'{unit}'          => $this->get_unit_html(),
				'{unit_price}'    => $this->get_unit_price_html(),
			);

			if ( $min_unit !== $max_unit ) {
				$unit_html          = $this->get_unit_html();
				$variable_format    = apply_filters( 'woocommerce_gzd_product_single_product_unit_format', '{product_units} {unit}', $this );
				$formatted_min_unit = wc_gzd_replace_label_shortcodes(
					$variable_format,
					array(
						'{product_units}' => $min_unit,
						'{unit}'          => $unit_html,
					)
				);
				$formatted_max_unit = wc_gzd_replace_label_shortcodes(
					$variable_format,
					array(
						'{product_units}' => $max_unit,
						'{unit}'          => $unit_html,
					)
				);
				$range              = wc_gzd_replace_label_shortcodes(
					apply_filters( 'woocommerce_gzd_product_unit_range_format', '{min_units} &ndash; {max_units}', $formatted_min_unit, $formatted_max_unit ),
					array(
						'{min_units}' => $formatted_min_unit,
						'{max_units}' => $formatted_max_unit,
					)
				);

				$replacements['{product_units}'] = $range;
				$replacements['{unit}']          = '';
			}

			$html = wc_gzd_replace_label_shortcodes( $text, $replacements );
		}

		/**
		 * Filter to adjust the product units HTML output.
		 *
		 * @param string $html The HTML output.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_product_units_html', $html, $this );
	}

	public function get_variation_product_units() {
		// Product doesn't apply for unit pricing
		if ( ! $this->child->is_type( 'variable' ) || ! $this->has_unit_fields() ) {
			return array();
		}

		$transient_name    = 'wc_gzd_var_product_units_' . $this->child->get_id();
		$transient_version = WC_Cache_Helper::get_transient_version( 'product' );

		if ( ! isset( $this->product_units_array['version'] ) || $this->product_units_array['version'] !== $transient_version ) {
			$this->product_units_array = array(
				'version'       => $transient_version,
				'product_units' => array(),
			);
		}

		// If the value has already been generated, we don't need to grab the values again.
		if ( empty( $this->product_units_array['product_units'] ) ) {
			$this->product_units_array = array_filter( (array) json_decode( strval( get_transient( $transient_name ) ), true ) );

			// If the product version has changed since the transient was last saved, reset the transient cache.
			if ( ! isset( $this->product_units_array['version'] ) || $transient_version !== $this->product_units_array['version'] ) {
				$this->product_units_array = array( 'version' => $transient_version );
			}

			if ( ! isset( $this->product_units_array['product_units'] ) ) {
				$variation_ids = $this->get_wc_product()->get_visible_children();
				$product_units = array();

				if ( is_callable( '_prime_post_caches' ) ) {
					_prime_post_caches( $variation_ids );
				}

				foreach ( $variation_ids as $variation_id ) {
					if ( $variation = wc_gzd_get_gzd_product( $variation_id ) ) {
						$product_unit = $variation->get_unit_product();

						if ( ! empty( $product_unit ) ) {
							$product_units[ $variation_id ] = (float) wc_format_decimal( $product_unit );
						}
					}
				}

				asort( $product_units );

				$this->product_units_array['product_units'] = $product_units;

				set_transient( $transient_name, wp_json_encode( $this->product_units_array ), DAY_IN_SECONDS * 30 );
			}
		}

		return $this->product_units_array['product_units'];
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
		// Product doesn't apply for unit pricing
		if ( ! $this->child->is_type( 'variable' ) || ! $this->has_unit_fields() ) {
			return array(
				'price'         => array(),
				'regular_price' => array(),
				'sale_price'    => array(),
			);
		}

		$transient_name    = 'wc_gzd_var_unit_prices_' . $this->child->get_id();
		$transient_version = WC_Cache_Helper::get_transient_version( 'product' );
		$tax_display       = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_shop', 'excl' );
		$price_hash        = $this->get_current_unit_price_hash( $display );

		// Check if prices array is stale.
		if ( ! isset( $this->unit_prices_array['version'] ) || $this->unit_prices_array['version'] !== $transient_version ) {
			$this->unit_prices_array = array(
				'version' => $transient_version,
			);
		}

		// If the value has already been generated, we don't need to grab the values again.
		if ( empty( $this->unit_prices_array[ $price_hash ] ) ) {
			// Get value of transient
			$this->unit_prices_array = array_filter( (array) json_decode( strval( get_transient( $transient_name ) ), true ) );

			// If the product version has changed since the transient was last saved, reset the transient cache.
			if ( ! isset( $this->unit_prices_array['version'] ) || $transient_version !== $this->unit_prices_array['version'] ) {
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

	protected function get_current_unit_price_hash( $display = false ) {
		global $wp_filter;

		$price_hash = array( false );

		/**
		 * Create unique cache key based on the tax location (affects displayed/cached prices), product version and active price filters.
		 * DEVELOPERS should filter this hash if offering conditonal pricing to keep it unique.
		 * @var string
		 */
		if ( $display && wc_tax_enabled() ) {
			$price_hash = array(
				get_option( 'woocommerce_tax_display_shop', 'excl' ),
				WC_Tax::get_rates(),
				empty( WC()->customer ) ? false : WC()->customer->is_vat_exempt(),
			);
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

		return $price_hash;
	}

	protected function set_unit_prices( $variation_id, $price, $display = false ) {
		$prices = ! is_array( $price ) ? array( 'price' => $price ) : $price;
		$prices = wp_parse_args(
			$prices,
			array(
				'price'         => '',
				'sale_price'    => '',
				'regular_price' => '',
			)
		);

		if ( '' === $prices['sale_price'] ) {
			$prices['sale_price'] = $prices['price'];
		}

		if ( '' === $prices['regular_price'] ) {
			$prices['regular_price'] = $prices['price'];
		}

		$unit_prices = $this->get_variation_unit_prices( $display );
		$price_hash  = $this->get_current_unit_price_hash( $display );

		if ( array_key_exists( $price_hash, $this->unit_prices_array ) ) {
			foreach ( $prices as $price_type => $price ) {
				$this->unit_prices_array[ $price_hash ][ $price_type ][ $variation_id ] = $price;
			}
		}
	}
}
