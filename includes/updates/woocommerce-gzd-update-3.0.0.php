<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

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

// DHL settings
if ( 'yes' === get_option( 'woocommerce_gzd_dhl_parcel_shops' ) ) {
	update_option( 'woocommerce_gzd_dhl_parcel_pickup_packstation_enable', 'yes' );
	update_option( 'woocommerce_gzd_dhl_parcel_pickup_parcelshop_enable', 'yes' );
	update_option( 'woocommerce_gzd_dhl_parcel_pickup_postoffice_enable', 'yes' );

	$methods = get_option( 'woocommerce_gzd_dhl_parcel_shop_disabled_shipping_methods' );

	if ( ! empty( $methods ) ) {
		update_option( 'woocommerce_gzd_dhl_parcel_pickup_shipping_methods_excluded', $methods );
	}

	update_option( 'woocommerce_gzd_dhl_parcel_pickup_map_enable', get_option( 'woocommerce_gzd_dhl_parcel_shop_finder' ) );
}

// Support old post numbers
$wpdb->hide_errors();
$wpdb->update( $wpdb->usermeta, array( 'meta_key' => 'shipping_parcelshop_post_number' ), array( 'meta_key' => 'shipping_dhl_postnumber' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key

// Update last 50 orders parcelshop legacy data
$orders = wc_get_orders(
	array(
		'limit'   => 50,
		'offset'  => 0,
		'orderby' => 'date',
		'order'   => 'DESC',
		'type'    => 'shop_order',
	)
);

if ( ! empty( $orders ) ) {
	foreach ( $orders as $wc_order ) {
		if ( ! $wc_order->get_meta( '_shipping_address_type' ) ) {

			// Germanized legacy parcel shop data
			if ( $wc_order->get_meta( '_shipping_parcelshop_post_number' ) && $wc_order->get_meta( '_shipping_parcelshop' ) ) {
				$wc_order->update_meta_data( '_shipping_address_type', 'dhl' );
				$wc_order->update_meta_data( '_shipping_dhl_postnumber', $wc_order->get_meta( '_shipping_parcelshop_post_number' ) );

				$wc_order->save();
			}
		}
	}
}

