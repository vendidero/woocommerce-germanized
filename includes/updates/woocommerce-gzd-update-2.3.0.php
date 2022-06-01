<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

update_option( 'woocommerce_gzd_display_product_detail_delivery_time_info', get_option( 'woocommerce_gzd_display_product_detail_delivery_time' ) );
update_option( 'woocommerce_gzd_display_product_detail_shipping_costs_info', get_option( 'woocommerce_gzd_display_product_detail_shipping_costs' ) );
update_option( 'woocommerce_gzd_display_product_detail_price_unit', get_option( 'woocommerce_gzd_display_product_detail_unit_price' ) );

update_option( 'woocommerce_gzd_display_listings_shipping_costs_info', get_option( 'woocommerce_gzd_display_listings_shipping_costs' ) );
update_option( 'woocommerce_gzd_display_listings_delivery_time_info', get_option( 'woocommerce_gzd_display_listings_delivery_time' ) );
update_option( 'woocommerce_gzd_display_listings_price_unit', get_option( 'woocommerce_gzd_display_listings_unit_price' ) );


