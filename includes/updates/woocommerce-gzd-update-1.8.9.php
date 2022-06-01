<?php

// Get all variable products
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$parcel_settings = get_option( 'woocommerce_gzd_checkout_legal_parcel_delivery_checkbox_methods', array() );

if ( class_exists( 'WC_Shipping_Zones' ) ) {

	$shipping_methods_options = WC_GZD_Admin::instance()->get_shipping_method_instances_options();
	$new_options              = array();

	if ( ! empty( $parcel_settings ) ) {
		foreach ( (array) $parcel_settings as $method ) {

			if ( 'downloadable' === $method ) {
				continue;
			}

			foreach ( $shipping_methods_options as $key => $option ) {
				$key_method = explode( ':', $key );

				if ( isset( $key_method[0] ) && $key_method[0] === $method ) {
					array_push( $new_options, $key );
				}
			}
		}
	}

	update_option( 'woocommerce_gzd_checkout_legal_parcel_delivery_checkbox_methods', $new_options );

}


