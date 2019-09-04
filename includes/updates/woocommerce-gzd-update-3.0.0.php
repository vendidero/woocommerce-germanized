<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

update_option( 'woocommerce_gzd_checkout_phone_non_required', ( get_option( 'woocommerce_gzd_checkout_phone_required' ) === 'no' ? 'yes' : 'no' ) );

// Shopmarks
update_option( 'woocommerce_gzd_display_single_product_legal', ( 'yes' === get_option( 'woocommerce_gzd_display_product_detail_shipping_costs_info' ) || 'yes' === get_option( 'woocommerce_gzd_display_product_detail_tax_info' ) ) ? 'yes' : 'no' );
update_option( 'woocommerce_gzd_display_single_product_unit_price', get_option( 'woocommerce_gzd_display_product_detail_price_unit' ) );
update_option( 'woocommerce_gzd_display_single_product_delivery_time', get_option( 'woocommerce_gzd_display_product_detail_delivery_time_info' ) );
update_option( 'woocommerce_gzd_display_single_product_units', get_option( 'woocommerce_gzd_display_product_detail_product_units' ) );

update_option( 'woocommerce_gzd_display_product_loop_unit_price', get_option( 'woocommerce_gzd_display_listings_price_unit' ) );
update_option( 'woocommerce_gzd_display_product_loop_tax', get_option( 'woocommerce_gzd_display_listings_tax_info' ) );
update_option( 'woocommerce_gzd_display_product_loop_shipping_costs', get_option( 'woocommerce_gzd_display_listings_shipping_costs_info' ) );
update_option( 'woocommerce_gzd_display_product_loop_delivery_time', get_option( 'woocommerce_gzd_display_listings_delivery_time_info' ) );
update_option( 'woocommerce_gzd_display_product_loop_units', get_option( 'woocommerce_gzd_display_listings_product_units' ) );

?>