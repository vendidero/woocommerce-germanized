<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb, $wp_version;

if ( defined( 'WC_GZD_SHIPMENTS_REMOVE_ALL_DATA' ) && true === WC_GZD_SHIPMENTS_REMOVE_ALL_DATA ) {
	// Delete options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce_gzd_shipments\_%';" );

	$meta_keys = array(
		'_shipping_length',
		'_shipping_width',
		'_shipping_height',
		'_hs_code',
		'_manufacture_country',
		'_customs_description',
		'_is_non_returnable',
		'_date_shipped',
		'_return_request_key',
		'_pickup_location_customer_number',
		'_pickup_location_code',
	);

	// Delete gzd meta data
	$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta WHERE meta.meta_key IN ('" . join( "','", $meta_keys ) . "');" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	// Remove Tables
	$custom_tables = array(
		"{$wpdb->prefix}woocommerce_gzd_shipping_providermeta",
		"{$wpdb->prefix}woocommerce_gzd_shipping_provider",
		"{$wpdb->prefix}woocommerce_gzd_packagingmeta",
		"{$wpdb->prefix}woocommerce_gzd_packaging",
		"{$wpdb->prefix}woocommerce_gzd_shipment_labelmeta",
		"{$wpdb->prefix}woocommerce_gzd_shipment_labels",
		"{$wpdb->prefix}woocommerce_gzd_shipmentmeta",
		"{$wpdb->prefix}woocommerce_gzd_shipments",
		"{$wpdb->prefix}woocommerce_gzd_shipment_itemmeta",
		"{$wpdb->prefix}woocommerce_gzd_shipment_items",
	);

	foreach ( $custom_tables as $table ) {
		$result = $wpdb->query( 'DROP TABLE IF EXISTS ' . $table ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	// Clear any cached data that has been removed
	wp_cache_flush();
}
