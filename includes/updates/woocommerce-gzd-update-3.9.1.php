<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$wpdb->hide_errors();

if ( 'woocommerce_cart_item_subtotal' === get_option( 'woocommerce_gzd_display_mini_cart_deposit_filter' ) ) {
	update_option( 'woocommerce_gzd_display_mini_cart_deposit_filter', 'woocommerce_cart_item_price' );
}

$has_virtual_class_products = $wpdb->get_results( "SELECT SQL_CALC_FOUND_ROWS {$wpdb->posts}.ID FROM {$wpdb->posts} INNER JOIN {$wpdb->postmeta} ON ({$wpdb->posts}.ID = {$wpdb->postmeta}.post_id) WHERE {$wpdb->posts}.post_type = 'product' AND {$wpdb->posts}.post_status = 'publish' AND {$wpdb->postmeta}.meta_key = '_tax_class' AND {$wpdb->postmeta}.meta_value IN ('virtual-rate','virtual-reduced-rate') GROUP BY {$wpdb->posts}.ID LIMIT 1" );

/**
 * Activate legacy virtual VAT helper which was loaded
 * automatically on every load in older installs.
 */
if ( ! empty( $has_virtual_class_products ) ) {
	update_option( 'woocommerce_gzd_enable_virtual_vat', 'yes' );
}
