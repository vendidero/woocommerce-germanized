<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// TS Option updates
$status_option = get_option( 'woocommerce_gzd_trusted_shops_review_reminder_status' );

if ( ! empty( $status_option ) && ! is_array( $status_option ) ) {
	$status_option = array( $status_option );
	update_option( 'woocommerce_gzd_trusted_shops_review_reminder_status', $status_option );
}

// Single product small business option
if ( 'yes' === get_option( 'woocommerce_gzd_small_enterprise' ) && 'yes' === get_option( 'woocommerce_gzd_display_product_detail_small_enterprise' ) ) {
	update_option( 'woocommerce_gzd_display_product_detail_tax_info', 'yes' );
}

