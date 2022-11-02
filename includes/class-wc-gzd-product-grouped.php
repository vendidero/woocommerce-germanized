<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * WooCommerce Germanized Grouped Product
 *
 * The WC_GZD_Product_Grouped Class is used to offer additional functionality for every grouped product.
 *
 * @class        WC_GZD_Product
 * @version        1.0.0
 * @author        Vendidero
 */
class WC_GZD_Product_Grouped extends WC_GZD_Product {

	protected $child_prices = null;

	protected $has_unit_price = false;

	protected function get_min_max_child_products() {
		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
		$children         = array_filter( array_map( 'wc_get_product', $this->get_wc_product()->get_children() ), 'wc_products_array_filter_visible_grouped' );
		$products         = array();
		$sort             = false;

		foreach ( $children as $child ) {
			if ( '' !== $child->get_price() ) {
				$child_prices[ $child->get_id() ] = 'incl' === $tax_display_mode ? wc_get_price_including_tax( $child ) : wc_get_price_excluding_tax( $child );
			}
		}

		if ( ! empty( $child_prices ) ) {
			$min_id = array_keys( $child_prices, min( $child_prices ), true )[0];
			$max_id = array_keys( $child_prices, max( $child_prices ), true )[0];

			/**
			 * In case the current price range format includes a starting from price only
			 * we will need to make sure that we do only check unit prices for variations
			 * that match the minimum price.
			 */
			if ( woocommerce_gzd_price_range_format_is_min_price() ) {
				asort( $child_prices );
				$min_price = min( $child_prices );
				$products  = array();
				$sort      = true;

				foreach ( $child_prices as $child_id => $price ) {
					if ( $price <= $min_price ) {
						$products[] = $child_id;
					}
				}
			} elseif ( woocommerce_gzd_price_range_format_is_max_price() ) {
				asort( $child_prices );
				$max_price = max( $child_prices );
				$products  = array();
				$sort      = true;

				foreach ( $child_prices as $child_id => $price ) {
					if ( $price >= $max_price ) {
						$products[] = $child_id;
					}
				}
			} elseif ( $min_id === $max_id ) {
				$products = $children;
				$sort     = true;
			} else {
				$products = array(
					wc_gzd_get_gzd_product( $min_id ),
					wc_gzd_get_gzd_product( $max_id ),
				);
			}
		}

		return array(
			'products' => $products,
			'sort'     => $sort,
		);
	}

	protected function get_child_unit_data() {
		if ( is_null( $this->child_prices ) ) {
			$min_max            = $this->get_min_max_child_products();
			$this->child_prices = array();

			if ( ! empty( $min_max['products'] ) ) {
				$sort     = $min_max['sort'];
				$children = $min_max['products'];

				if ( ! empty( $children ) ) {
					$this->has_unit_price = true;

					foreach ( $children as $child ) {
						$child = wc_gzd_get_gzd_product( $child );

						if ( ! $child ) {
							continue;
						}

						if ( $child->has_unit() ) {
							$unit = $child->get_unit();

							if ( ! isset( $this->child_prices[ $unit ] ) ) {
								$this->child_prices[ $unit ]           = array();
								$this->child_prices[ $unit ]['prices'] = array(
									'incl' => array(
										'price'         => array(),
										'regular_price' => array(),
										'sale_price'    => array(),
									),
									'excl' => array(
										'price'         => array(),
										'regular_price' => array(),
										'sale_price'    => array(),
									),
								);
							}

							if ( ! isset( $this->child_prices[ $unit ]['base'] ) ) {
								$this->child_prices[ $unit ]['base'] = $child->get_unit_base();
							}

							// Recalculate new prices
							$prices_incl = wc_gzd_recalculate_unit_price(
								array(
									'base'     => $this->child_prices[ $unit ]['base'],
									'products' => $child->get_unit_product(),
								),
								$child
							);

							$prices_excl = wc_gzd_recalculate_unit_price(
								array(
									'base'     => $this->child_prices[ $unit ]['base'],
									'products' => $child->get_unit_product(),
									'tax_mode' => 'excl',
								),
								$child
							);

							if ( empty( $prices_incl ) || empty( $prices_excl ) ) {
								$this->has_unit_price = false;
								continue;
							}

							$this->child_prices[ $unit ]['prices']['incl']['price'][]         = $prices_incl['unit'];
							$this->child_prices[ $unit ]['prices']['incl']['regular_price'][] = $prices_incl['regular'];
							$this->child_prices[ $unit ]['prices']['incl']['sale_price'][]    = $prices_incl['sale'];

							$this->child_prices[ $unit ]['prices']['excl']['price'][]         = $prices_excl['unit'];
							$this->child_prices[ $unit ]['prices']['excl']['regular_price'][] = $prices_excl['regular'];
							$this->child_prices[ $unit ]['prices']['excl']['sale_price'][]    = $prices_excl['sale'];
						} else {
							$this->has_unit_price = false;
						}
					}
				}

				if ( $sort && ! empty( $this->child_prices ) ) {
					foreach ( $this->child_prices as $unit => $data ) {
						asort( $this->child_prices[ $unit ]['prices']['incl']['price'] );
						asort( $this->child_prices[ $unit ]['prices']['incl']['regular_price'] );
						asort( $this->child_prices[ $unit ]['prices']['incl']['sale_price'] );
						asort( $this->child_prices[ $unit ]['prices']['excl']['price'] );
						asort( $this->child_prices[ $unit ]['prices']['excl']['regular_price'] );
						asort( $this->child_prices[ $unit ]['prices']['excl']['sale_price'] );
					}
				}
			}
		}

		return $this->child_prices;
	}

	protected function get_child_unit_prices( $tax_display = '' ) {
		$tax_display = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_shop', 'excl' );
		$data        = $this->get_child_unit_data();
		$prices      = array();

		if ( ! empty( $data ) ) {
			$prices = array_values( $data );
			$prices = $prices[0]['prices'][ $tax_display ];
		}

		return $prices;
	}

	protected function get_child_units() {
		$data = $this->get_child_unit_data();

		return array_keys( $data );
	}

	public function get_unit( $context = 'view' ) {
		$data = $this->get_child_unit_data();

		if ( ! empty( $data ) ) {
			$keys = array_keys( $data );
			$unit = $keys[0];

			if ( $unit ) {
				return $unit;
			}
		}

		return '';
	}

	/**
	 * Returns unit base html
	 *
	 * @return string
	 */
	public function get_unit_base( $context = 'view' ) {
		$data      = $this->get_child_unit_data();
		$base_data = array();
		$base      = false;

		if ( ! empty( $data ) ) {
			$base_data = array_values( $data );
			$base      = $base_data[0]['base'];
		}

		return $base;
	}

	public function get_delivery_time_html( $context = 'view' ) {
		/**
		 * Filter that decides whether to hide delivery time for grouped products or not.
		 *
		 * @param bool $hide Whether to hide delivery time or not.
		 * @param WC_GZD_Product_Grouped $product The product object.
		 *
		 * @since 2.3.1
		 *
		 */
		if ( apply_filters( 'woocommerce_gzd_hide_delivery_time_for_grouped_product', true, $this ) ) {
			return '';
		}

		return parent::get_delivery_time_html( $context );
	}

	/**
	 * Show unit prices only if every product has a unit price and shares the same unit.
	 *
	 * @return bool
	 */
	public function has_unit() {
		$data = $this->get_child_unit_data();

		return $this->has_unit_price && 1 === count( $data );
	}

	/**
	 * Returns the price in html format.
	 *
	 * @param string $price (default: '').
	 *
	 * @return string
	 */
	public function get_unit_price_html( $show_sale = true, $tax_display = '' ) {
		$price = '';

		if ( $this->has_unit() ) {

			$prices        = $this->get_child_unit_prices( $tax_display );
			$min_price     = current( $prices['price'] );
			$max_price     = end( $prices['price'] );
			$min_reg_price = current( $prices['regular_price'] );
			$max_reg_price = end( $prices['regular_price'] );

			if ( $min_price !== $max_price ) {
				$price = woocommerce_gzd_format_unit_price_range( $min_price, $max_price );
			} elseif ( $this->child->is_on_sale() && $min_reg_price === $max_reg_price ) {
				$price = wc_format_sale_price( wc_price( $max_reg_price ), wc_price( $min_price ) );
			} else {
				$price = wc_price( $min_price );
			}

			/**
			 * Filter to adjust grouped product unit price.
			 * In case of Woo version > 3.0.0 this filter can contain the formatted sale price too.
			 *
			 * @param string $price The price.
			 * @param WC_GZD_Product_Grouped $product The product object.
			 *
			 * @since 2.3.1
			 *
			 */
			$price = apply_filters( 'woocommerce_gzd_grouped_unit_price_html', $price, $this );
			$price = wc_gzd_format_unit_price( $price, $this->get_unit_html(), $this->get_unit_base_html(), wc_gzd_format_product_units_decimal( $this->get_unit_product() ) );
		}

		/** This filter is documented in includes/abstract/abstract-wc-gzd-product.php */
		return apply_filters( 'woocommerce_gzd_unit_price_html', $price, $this );
	}
}
