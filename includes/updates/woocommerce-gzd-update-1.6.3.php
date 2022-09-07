<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'WC_trusted_shops' ) ) {
	return;
}

// Reset Trusted Shops Code Options
update_option( 'woocommerce_gzd_trusted_shops_integration_mode', 'standard' );
update_option( 'woocommerce_gzd_trusted_shops_product_sticker_code', WC_trusted_shops()->trusted_shops->get_product_sticker_code( false ) );
update_option( 'woocommerce_gzd_trusted_shops_product_widget_code', WC_trusted_shops()->trusted_shops->get_product_widget_code( false ) );
