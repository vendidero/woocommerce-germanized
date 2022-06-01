<?php

use Vendidero\Germanized\DHL\Package;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Update shipping provider to DHL if available
if ( Package::has_dependencies() && Package::is_enabled() ) {

	// Make sure shipping zones are loaded
	include_once WC_ABSPATH . 'includes/class-wc-shipping-zones.php';

	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {

		foreach ( $zone['shipping_methods'] as $method ) {

			$instance_settings = get_option( $method->get_instance_option_key() );
			$has_dhl           = wc_string_to_bool( isset( $instance_settings['enable_dhl'] ) ? $instance_settings['enable_dhl'] : 'yes' );

			if ( is_array( $instance_settings ) && $has_dhl ) {
				$instance_settings['shipping_provider'] = 'dhl';
				update_option( $method->get_instance_option_key(), $instance_settings, 'yes' );
			}
		}
	}
}
