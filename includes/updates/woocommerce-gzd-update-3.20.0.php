<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uses_shipments = false;
$uses_dhl_or_dp = false;

if ( \Vendidero\Germanized\Packages::load_shipping_package() ) {
	$available_providers = \Vendidero\Germanized\Shiptastic::get_shipping_providers( true );
	$uses_dhl_or_dp      = \Vendidero\Germanized\Shiptastic::is_shipping_provider_active( 'dhl' ) || \Vendidero\Germanized\Shiptastic::is_shipping_provider_active( 'deutsche_post' );
	$uses_shipments      = ! empty( $available_providers );

	if ( ! $uses_shipments ) {
		$uses_shipments = \Vendidero\Germanized\Shiptastic::has_created_shipments();
	}
}

if ( $uses_shipments && ! \Vendidero\Germanized\PluginsHelper::is_shiptastic_plugin_active() ) {
	update_option( 'woocommerce_gzd_is_shiptastic_standalone_update', 'yes' );
}

if ( $uses_dhl_or_dp && ! \Vendidero\Germanized\PluginsHelper::is_shiptastic_dhl_plugin_active() ) {
	update_option( 'woocommerce_gzd_is_shiptastic_standalone_update', 'yes' );
	update_option( 'woocommerce_gzd_is_shiptastic_dhl_standalone_update', 'yes' );
}
