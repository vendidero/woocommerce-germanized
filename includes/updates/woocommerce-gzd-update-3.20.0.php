<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uses_shipments = false;
$uses_dhl_or_dp = false;

if ( \Vendidero\Germanized\Packages::load_shipping_package() && class_exists( '\Vendidero\Shiptastic\ShippingProvider\Helper' ) ) {
	$providers           = \Vendidero\Shiptastic\ShippingProvider\Helper::instance();
	$available_providers = $providers->get_available_shipping_providers();
	$uses_dhl_or_dp      = $providers->is_shipping_provider_activated( 'dhl' ) || $providers->is_shipping_provider_activated( 'deutsche_post' );
	$uses_shipments      = ! empty( $available_providers );

	if ( ! $uses_shipments ) {
		$shipments = wc_stc_get_shipments(
			array(
				'limit'  => 1,
				'type'   => array( 'simple', 'return' ),
				'return' => 'ids',
			)
		);

		$uses_shipments = ! empty( $shipments );
	}
}

if ( $uses_shipments && ! \Vendidero\Germanized\PluginsHelper::is_shiptastic_plugin_active() ) {
	update_option( 'woocommerce_gzd_is_shiptastic_standalone_update', 'yes' );
}

if ( $uses_dhl_or_dp && ! \Vendidero\Germanized\PluginsHelper::is_shiptastic_dhl_plugin_active() ) {
	update_option( 'woocommerce_gzd_is_shiptastic_standalone_update', 'yes' );
	update_option( 'woocommerce_gzd_is_shiptastic_dhl_standalone_update', 'yes' );
}
