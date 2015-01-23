<?php
/**
 * Core Functions
 *
 * WC_GZD core functions.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Gets WC_GZD_Product by WC_Product
 * @param WC_Product|int $the_product 
 * @return WC_GZD_Product
 */
function wc_gzd_get_product( $the_product = false ) {
	global $product;
	if ( ! $the_product )
		$the_product = $product;
	if ( is_numeric( $the_product ) )
		$the_product = wc_get_product( $the_product );
	if ( isset( $the_product ) )
		return WC_GZD_Product::instance( $the_product );
	return false;
}