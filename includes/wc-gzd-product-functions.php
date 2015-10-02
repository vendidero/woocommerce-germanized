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

function wc_gzd_is_revocation_exempt( $product ) {
	
	$digital_types = apply_filters( 'woocommerce_gzd_digital_product_types', get_option( 'woocommerce_gzd_checkout_legal_digital_types', array( 'downloadable' ) ) );
	if ( empty( $digital_types ) )
		return false;
	else if ( ! is_array( $digital_types ) )
		$digital_types = array( $digital_types );
	
	if ( in_array( 'downloadable', $digital_types ) && $product->is_downloadable() )
		return true;
	else if ( in_array( 'virtual', $digital_types ) && $product->is_virtual() )
		return true;
	else if ( in_array( $product->get_type(), $digital_types ) )
		return true;
	else if ( apply_filters( 'woocommerce_gzd_product_is_revocation_exception', false, $product ) )
		return true;

	return false;
}