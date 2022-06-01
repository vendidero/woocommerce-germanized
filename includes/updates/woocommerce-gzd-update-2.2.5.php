<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// TS Option updates

if ( 'disable' !== get_option( 'woocommerce_gzd_trusted_shops_trustbadge_variant' ) ) {
	update_option( 'woocommerce_gzd_trusted_shops_trustbadge_enable', 'yes' );
} else {
	update_option( 'woocommerce_gzd_trusted_shops_trustbadge_enable', 'no' );
}

if ( 'disable' === get_option( 'woocommerce_gzd_trusted_shops_trustbadge_variant' ) ) {
	update_option( 'woocommerce_gzd_trusted_shops_trustbadge_variant', 'standard' );
}

$reviews_enabled = get_option( 'woocommerce_gzd_trusted_shops_enable_reviews' );

update_option( 'woocommerce_gzd_trusted_shops_reviews_enable', $reviews_enabled );


