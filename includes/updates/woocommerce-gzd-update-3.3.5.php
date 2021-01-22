<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This option has been removed from Germanized core as WooCommerce
 * provides it's own option for it via the customizer.
 */
if ( 'yes' === get_option( 'woocommerce_gzd_checkout_phone_non_required' ) ) {
	update_option( 'woocommerce_checkout_phone_field', 'optional' );
}

