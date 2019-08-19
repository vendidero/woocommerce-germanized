<?php
/**
 * Product Functions
 *
 * WC_GZD product functions.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * @param WC_Product $product
 */
function _wc_gzd_check_unit_sale( $product ) {
	$gzd_product = wc_gzd_get_product( $product );

	if ( $gzd_product->has_unit() ) {
		if ( $product->is_on_sale() ) {
			$sale_price = $gzd_product->get_unit_price_sale();

			if ( ! empty( $sale_price ) ) {
				$gzd_product->set_unit_price( $sale_price );
			} else {
				$gzd_product->set_unit_price( $gzd_product->get_unit_price_regular() );
			}
		} else {
			$gzd_product->set_unit_price( $gzd_product->get_unit_price_regular() );
		}
	}
}

/**
 * Register unit price update hook while cronjob is running
 */
function wc_gzd_register_scheduled_unit_sales( $product_ids ) {
	add_action( 'woocommerce_before_product_object_save', '_wc_gzd_check_unit_sale', 10 );
}

add_action( 'wc_before_products_starting_sales', 'wc_gzd_register_scheduled_unit_sales', 10, 1 );
remove_action( 'wc_after_products_starting_sales', 'wc_gzd_register_scheduled_unit_sales', 10 );

add_action( 'wc_before_products_ending_sales', 'wc_gzd_register_scheduled_unit_sales', 10, 1 );
remove_action( 'wc_after_products_ending_sales', 'wc_gzd_register_scheduled_unit_sales', 10 );

/**
 * @param $product
 *
 * @return bool|WC_GZD_Product
 */
function wc_gzd_get_product( $product ) {
	return wc_gzd_get_gzd_product( $product );
}

/**
 * @param $product
 *
 * @return bool|WC_GZD_Product
 */
function wc_gzd_get_gzd_product( $product ) {

    if ( is_numeric( $product ) ) {
        $product = wc_get_product( $product );
    } elseif( is_a( $product, 'WC_GZD_Product' ) ) {
        return $product;
    }

    if ( ! $product ) {
        return false;
    }

	if ( ! isset( $product->gzd_product ) || ! is_a( $product->gzd_product, 'WC_GZD_Product' ) ) {
		$factory              = WC_germanized()->product_factory;
		$product->gzd_product = $factory->get_gzd_product( $product );
	}

	return $product->gzd_product;
}

function wc_gzd_get_small_business_product_notice() {

    /**
     * Filter to adjust the small business product notice.
     *
     * @since 1.0.0
     *
     * @param string $html The notice.
     */
	return apply_filters( 'woocommerce_gzd_small_business_product_notice', wc_gzd_get_small_business_notice() );
}

function wc_gzd_is_revocation_exempt( $product, $type = 'digital' ) {
	if ( 'digital' === $type && ( $checkbox = wc_gzd_get_legal_checkbox( 'download' ) ) ) {

        /**
         * Filter to allow adjusting which product types are considered digital types.
         * Digital product types are used to check whether a possible revocation exempt exists or not.
         *
         * @since 1.8.5
         *
         * @param array $types The product types.
         */
		$types = apply_filters( 'woocommerce_gzd_digital_product_types', $checkbox->types );

		if ( ! $checkbox->is_enabled() ) {
			return false;
		}

		if ( empty( $types ) ) {
			return false;
		} elseif ( ! is_array( $types ) ) {
			$types = array( $types );
		}

		foreach ( $types as $revo_type ) {
			if ( wc_gzd_product_matches_extended_type( $revo_type, $product ) ) {
				return true;
			}
		}
	} elseif ( 'service' === $type && ( $checkbox = wc_gzd_get_legal_checkbox( 'service' ) ) ) {

		if ( ! $checkbox->is_enabled() ) {
			return false;
		}

		if ( wc_gzd_get_gzd_product( $product )->is_service() ) {
			return true;
		}
	}

	return false;
}

function wc_gzd_needs_age_verification( $product ) {
	$needs_age_verification = false;

	if ( $gzd_product = wc_gzd_get_gzd_product( $product ) ) {
		$needs_age_verification = $gzd_product->needs_age_verification();
	}

	return $needs_age_verification;
}

/**
 * Checks whether the product matches one of the types.
 *
 * @param array|string $types multiple types are OR connected
 * @param WC_Product|WC_GZD_Product $product
 *
 * @return bool
 */
function wc_gzd_product_matches_extended_type( $types, $product ) {

	if ( empty( $types ) ) {
		return false;
	}

	$matches_type = false;

	if ( is_a( $product, 'WC_GZD_Product' ) ) {
		$product = $product->get_wc_product();
	}

	if ( ! is_array( $types ) ) {
		$types = array( $types );
	}

	if ( in_array( $product->get_type(), $types ) ) {
		$matches_type = true;
	} else {
		foreach ( $types as $type ) {
			if ( 'service' === $type ) {
				$matches_type = wc_gzd_get_product( $product )->is_service();
			} else {
				$getter = "is_" . $type;
				try {
					if ( is_callable( array( $product, $getter ) ) ) {
						$reflection = new ReflectionMethod( $product, $getter );

						if ( $reflection->isPublic() ) {
							$matches_type = $product->{$getter}() === true;
						}
					}
				} catch ( Exception $e ) {}
			}
			// Seems like we found a match - lets escape the loop
			if ( $matches_type === true ) {
				break;
			}
		}
	}

	if ( ! $matches_type ) {
		$parent_id = $product->get_parent_id();

		// Check parent product type
		if ( $parent_id ) {
			$parent_type = WC_Product_Factory::get_product_type( $parent_id );

			if ( $parent_type && in_array( $parent_type, $types ) ) {
				$matches_type = true;
			}
		}
	}

	return $matches_type;
}

/**
 * @param array $args
 * @param bool|WC_GZD_Product $product
 *
 * @return array|mixed|void
 */
function wc_gzd_recalculate_unit_price( $args = array(), $product = false ) {

    $default_args = array(
        'regular_price' => 0,
        'sale_price'    => 0,
        'price'         => 0,
        'base'          => 1,
        'products'      => 1,
        'tax_mode'      => 'incl',
    );

    if ( $product ) {
    	$wc_product = $product->get_wc_product();

        $default_args = array(
            'regular_price' => ( isset( $args['tax_mode'] ) && 'excl' === $args['tax_mode'] ) ? wc_get_price_excluding_tax( $wc_product, array( 'price' => $wc_product->get_regular_price() ) ) : wc_get_price_including_tax( $wc_product, array( 'price' => $wc_product->get_regular_price() ) ),
            'sale_price'    => ( isset( $args['tax_mode'] ) && 'excl' === $args['tax_mode'] ) ? wc_get_price_excluding_tax( $wc_product, array( 'price' => $wc_product->get_sale_price() ) ) : wc_get_price_including_tax( $wc_product, array( 'price' => $wc_product->get_sale_price() ) ),
            'price'         => ( isset( $args['tax_mode'] ) && 'excl' === $args['tax_mode'] ) ? wc_get_price_excluding_tax( $wc_product ) : wc_get_price_including_tax( $wc_product ),
            'base'          => $product->get_unit_base(),
            'products'      => $product->get_unit_product(),
        );
    }

    $args = wp_parse_args( $args, $default_args );

    $base         = $args['base'];
    $unit_product = $args['products'];

    $product_base = $base;

    if ( empty( $unit_product ) ) {
        // Set base multiplicator to 1
        $base = 1;
    } else {
        $product_base = $unit_product;
    }

    $prices = array();

    // Do not recalculate if unit base and/or product is empty
    if ( 0 == $product_base || 0 == $base ) {
        return $prices;
    }

    $prices['regular']  = wc_format_decimal( ( $args['regular_price'] / $product_base ) * $base, wc_get_price_decimals() );
    $prices['sale']     = '';

    if ( ! empty( $args['sale_price'] ) ) {
        $prices['sale'] = wc_format_decimal( ( $args['sale_price'] / $product_base ) * $base, wc_get_price_decimals() );
    }

    $prices['unit']     = wc_format_decimal( ( $args['price'] / $product_base ) * $base, wc_get_price_decimals() );

    /**
     * Filter to adjust unit price after a recalculation happened.
     *
     * @since 2.3.1
     *
     * @param array          $prices The price data.
     * @param WC_GZD_Product $product The product object.
     * @param array          $args Additional arguments.
     */
    return apply_filters( 'woocommerce_gzd_recalculated_unit_prices', $prices, $product, $args );
}