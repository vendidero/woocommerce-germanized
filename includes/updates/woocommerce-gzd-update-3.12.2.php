<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'yes' === get_option( 'woocommerce_gzd_shipping_tax' ) ) {
	update_option( 'woocommerce_gzd_tax_mode_additional_costs', 'split_tax' );
} else {
	update_option( 'woocommerce_gzd_tax_mode_additional_costs', 'none' );
}

delete_option( 'woocommerce_gzd_shipping_tax' );
