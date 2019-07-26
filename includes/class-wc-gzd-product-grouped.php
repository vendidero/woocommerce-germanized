<?php

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

/**
 * WooCommerce Germanized Grouped Product
 *
 * The WC_GZD_Product_Grouped Class is used to offer additional functionality for every grouped product.
 *
 * @class 		WC_GZD_Product
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Product_Grouped extends WC_GZD_Product {

    protected $child_prices = null;

    protected $has_unit_price = false;

    /**
     * Callback for array filter to get visible grouped products only.
     *
     * @since  3.1.0
     * @param  WC_Product $product WC_Product object.
     * @return bool
     */
    public function _filter_visible_grouped( $product ) {
        return $product && is_a( $product, 'WC_GZD_Product' ) && ( 'publish' === $product->get_status() || current_user_can( 'edit_product', $product->get_id() ) );
    }

    protected function get_child_unit_data() {
        if ( is_null( $this->child_prices ) ) {
            $children           = array_filter( array_map( 'wc_gzd_get_gzd_product', $this->get_children() ), array( $this, '_filter_visible_grouped' ) );
            $this->child_prices = array();

            if ( ! empty( $children ) ) {
                $this->has_unit_price = true;

                foreach ( $children as $child ) {

                    if ( ! $child ) {
                        continue;
                    }

                    if ( $child->has_unit() ) {
                        $unit = $child->get_unit_raw();

                        if ( ! isset( $this->child_prices[ $unit ] ) ) {
                            $this->child_prices[ $unit ] = array();
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
                            $this->child_prices[ $unit ]['base'] = $child->get_unit_base_raw();
                        }

                        // Recalculate new prices
                        $prices_incl  = wc_gzd_recalculate_unit_price( array(
                            'base'     => $this->child_prices[ $unit ]['base'],
                            'products' => $child->get_unit_products(),
                        ), $child );

                        $prices_excl  = wc_gzd_recalculate_unit_price( array(
                            'base'     => $this->child_prices[ $unit ]['base'],
                            'products' => $child->get_unit_products(),
                            'tax_mode' => 'excl',
                        ), $child );

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

            if ( ! empty( $this->child_prices ) ) {
                foreach( $this->child_prices as $unit => $data ) {
                    asort( $this->child_prices[ $unit ]['prices']['incl']['price'] );
                    asort( $this->child_prices[ $unit ]['prices']['incl']['regular_price'] );
                    asort( $this->child_prices[ $unit ]['prices']['incl']['sale_price'] );
                    asort( $this->child_prices[ $unit ]['prices']['excl']['price'] );
                    asort( $this->child_prices[ $unit ]['prices']['excl']['regular_price'] );
                    asort( $this->child_prices[ $unit ]['prices']['excl']['sale_price'] );
                }
            }
        }

        return $this->child_prices;
    }

    protected function get_child_unit_prices() {
        $tax_display = get_option( 'woocommerce_tax_display_shop', 'excl' );
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

    public function get_unit() {
        $data = $this->get_child_unit_data();

        if ( ! empty( $data ) ) {
            $keys = array_keys( $data );
            $unit = $keys[0];

            if ( $unit ) {
                return WC_germanized()->units->$unit;
            }
        }

        return '';
    }

    /**
     * Returns unit base html
     *
     * @return string
     */
    public function get_unit_base_raw() {
        $data        = $this->get_child_unit_data();
        $base_data   = array();
        $base        = false;

        if ( ! empty( $data ) ) {
            $base_data = array_values( $data );
            $base      = $base_data[0]['base'];
        }

        return $base;
    }

    public function get_delivery_time_html() {

        /**
         * Filter that decides whether to hide delivery time for grouped products or not.
         *
         * @since 2.3.1
         *
         * @param bool                   $hide Whether to hide delivery time or not.
         * @param WC_GZD_Product_Grouped $product The product object.
         */
        if ( apply_filters( 'woocommerce_gzd_hide_delivery_time_for_grouped_product', true, $this ) ) {
            return '';
        }

        return parent::get_delivery_time_html();
    }

    /**
     * Returns unit base html
     *
     * @return string
     */
    public function get_unit_base() {
        $base        = $this->get_unit_base_raw();
        /** This filter is documented in includes/abstract/abstract-wc-gzd-product.php */
        $hide_amount = apply_filters( 'woocommerce_gzd_unit_base_hide_amount', 1 );
        /** This filter is documented in includes/abstract/abstract-wc-gzd-product.php */
        $separator   = apply_filters( 'wc_gzd_unit_price_base_seperator', ' ' );

        return ( $base ) ? ( $base != $hide_amount ? '<span class="unit-base">' . $base . '</span>' . $separator : '' ) . '<span class="unit">' . $this->get_unit() . '</span>' : '';
    }

    /**
     * Show unit prices only if every product has a unit price and shares the same unit.
     *
     * @return bool
     */
    public function has_unit() {
        $data = $this->get_child_unit_data();

        return $this->has_unit_price && 1 === sizeof( $data );
    }

    /**
     * Returns the price in html format.
     *
     * @param string $price (default: '').
     * @return string
     */
    public function get_unit_html( $show_sale = true ) {
	    $price = '';

        if ( $this->has_unit() ) {

            $prices        = $this->get_child_unit_prices();
            $text          = get_option( 'woocommerce_gzd_unit_price_text' );

            $min_price     = current( $prices['price'] );
            $max_price     = end( $prices['price'] );
            $min_reg_price = current( $prices['regular_price'] );
            $max_reg_price = end( $prices['regular_price'] );

            if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {

                if ( $min_price !== $max_price ) {
                    $price = wc_format_price_range( $min_price, $max_price );
                } elseif ( $this->is_on_sale() && $min_reg_price === $max_reg_price ) {
                    $price = wc_format_sale_price( wc_price( $max_reg_price ), wc_price( $min_price ) );
                } else {
                    $price = wc_price( $min_price );
                }

                /**
                 * Filter to adjust grouped product unit price.
                 * In case of Woo version > 3.0.0 this filter can contain the formatted sale price too.
                 *
                 * @since 2.3.1
                 *
                 * @param string                 $price The price.
                 * @param WC_GZD_Product_Grouped $product The product object.
                 */
                $price = apply_filters( 'woocommerce_gzd_grouped_unit_price_html', $price, $this );

            } else {

                $price = $min_price !== $max_price ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce-germanized' ), wc_price( $min_price ), wc_price( $max_price ) ) : wc_price( $min_price );

                if ( $this->is_on_sale() ) {
                    $min_regular_price = current( $prices['regular_price'] );
                    $max_regular_price = end( $prices['regular_price'] );
                    $regular_price     = $min_regular_price !== $max_regular_price ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce-germanized' ), wc_price( $min_regular_price ), wc_price( $max_regular_price ) ) : wc_price( $min_regular_price );

                    /**
                     * Filter to adjust grouped product unit sale price for Woo version < 3.0.0.
                     *
                     * @since 2.3.1
                     *
                     * @param string                 $price The price range.
                     * @param WC_GZD_Product_Grouped $product The product object.
                     */
                    $price        	   = apply_filters( 'woocommerce_gzd_grouped_unit_sale_price_html', $this->get_price_html_from_to( $regular_price, $price, false ), $this );
                } else {

                    /** This filter is documented in includes/class-wc-gzd-product-grouped.php */
                    $price 	   		   = apply_filters( 'woocommerce_gzd_grouped_unit_price_html', $price, $this );
                }
            }

            if ( strpos( $text, '{price}' ) !== false ) {
                $replacements = array(
                    /** This filter is documented in includes/abstract/abstract-wc-gzd-product.php */
                    '{price}' => $price . apply_filters( 'wc_gzd_unit_price_seperator', ' / ' ) . $this->get_unit_base(),
                );
            } else {
                $replacements = array(
                    '{base_price}' => $price,
                    '{unit}'       => '<span class="unit">' . $this->get_unit() . '</span>',
                    '{base}'       => $this->get_unit_base(),
                );
            }

            $price = wc_gzd_replace_label_shortcodes( $text, $replacements );
        }

        /** This filter is documented in includes/abstract/abstract-wc-gzd-product.php */
        return apply_filters( 'woocommerce_gzd_unit_price_html', $price, $this );
    }
}
