<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cod_settings = (array) get_option( 'woocommerce_cod_settings', array() );

if ( ! empty( $cod_settings ) ) {
	if ( isset( $cod_settings['fee'] ) && ! empty( $cod_settings['fee'] ) ) {
		update_option( 'woocommerce_gzd_has_legacy_cod_fee', 'yes' );
		update_option( 'woocommerce_gzd_checkout_cod_gateway_fee', wc_format_decimal( $cod_settings['fee'] ) );
	}
	if ( isset( $cod_settings['forwarding_fee'] ) && ! empty( $cod_settings['forwarding_fee'] ) ) {
		update_option( 'woocommerce_gzd_has_legacy_cod_fee', 'yes' );
		update_option( 'woocommerce_gzd_checkout_cod_gateway_forwarding_fee', wc_format_decimal( $cod_settings['forwarding_fee'] ) );
	}
}
