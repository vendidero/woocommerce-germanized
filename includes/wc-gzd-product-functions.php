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
 * Register unit price update hook while cronjob is running
 */
function wc_gzd_register_scheduled_unit_sales() {
	add_action( 'updated_post_meta', 'wc_gzd_check_price_update', 0, 4 );
}
add_action( 'woocommerce_scheduled_sales', 'wc_gzd_register_scheduled_unit_sales', 0 );

function wc_gzd_get_gzd_product( $product ) {
	
	if ( ! isset( $product->gzd_product ) || ! is_object( $product->gzd_product ) ) {
		$factory = WC()->product_factory;

		if ( ! is_a( $factory, 'WC_GZD_Product_Factory' ) ) {
			$factory = new WC_GZD_Product_Factory();
			WC()->product_factory = $factory;
		}

		$product->gzd_product = $factory->get_gzd_product( $product );
	}

	return $product->gzd_product;
}

/**
 * Unregister unit price update hook
 */
function wc_gzd_unregister_scheduled_unit_sales() {
	remove_action( 'updated_post_meta', 'wc_gzd_check_price_update', 0 );
}
add_action( 'woocommerce_scheduled_sales', 'wc_gzd_unregister_scheduled_unit_sales', 20 );

/**
 * Update the unit price to sale price if product is on sale
 */
function wc_gzd_check_price_update( $meta_id, $post_id, $meta_key, $meta_value ) {

	if ( $meta_key != '_price' )
		return;

	$product = wc_get_product( $post_id );
	$sale_price = get_post_meta( $post_id, '_unit_price_sale', true );
	$regular_price = get_post_meta( $post_id, '_unit_price_regular', true );
	
	if ( $product->is_on_sale() && $sale_price ) {
		update_post_meta( $post_id, '_unit_price', $sale_price );
	} else {
		update_post_meta( $post_id, '_unit_price', $regular_price );
	}

}

function wc_gzd_get_small_business_product_notice() {
	return apply_filters( 'woocommerce_gzd_small_business_product_notice', wc_gzd_get_small_business_notice() );
}

function wc_gzd_is_revocation_exempt( $product, $type = 'digital' ) {
	if ( 'digital' === $type && ( $checkbox = wc_gzd_get_legal_checkbox( 'download' ) ) ) {

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

/**
 * Checks whether the product matches one of the types.
 *
 * @param array|string $types multiple types are OR connected
 * @param $product
 *
 * @return bool
 */
function wc_gzd_product_matches_extended_type( $types, $product ) {

	if ( empty( $types ) )
		return false;

	$matches_type = false;

	if ( is_a( $product, 'WC_GZD_Product' ) ) {
		$product = $product->get_wc_product();
	}

	if ( ! is_array( $types ) )
		$types = array( $types );

	if ( in_array( $product->get_type(), $types ) ) {
		$matches_type = true;
	} else {
		foreach ( $types as $type ) {
			if ( 'service' === $type ) {
				$matches_type = wc_gzd_get_gzd_product( $product )->is_service();
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
		$parent_id = wc_gzd_get_crud_data( $product, 'parent' );

		// Check parent product type
		if ( $parent_id ) {
			$parent_type = wc_gzd_get_product_type( $parent_id );

			if ( $parent_type && in_array( $parent_type, $types ) ) {
				$matches_type = true;
			}
		}
	}

	return $matches_type;
}