<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// TS Option updates
$status = get_option( 'woocommerce_gzd_trusted_shops_review_reminder_status' );

if ( ! empty( $status ) && ! is_array( $status ) ) {
	$status = array( $status );
	update_option( 'woocommerce_gzd_trusted_shops_review_reminder_status', $status );
}

// Single product small business option
if ( 'yes' === get_option( 'woocommerce_gzd_small_enterprise' ) && 'yes' === get_option( 'woocommerce_gzd_display_product_detail_small_enterprise' ) ) {
	update_option( 'woocommerce_gzd_display_product_detail_tax_info', 'yes' );
}
?>