<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( \Vendidero\Germanized\Shiptastic::needs_shiptastic_standalone() && ! \Vendidero\Germanized\PluginsHelper::is_shiptastic_plugin_active() ) {
	update_option( 'woocommerce_gzd_is_shiptastic_standalone_update', 'yes' );
}

if ( ! \Vendidero\Germanized\PluginsHelper::is_shiptastic_dhl_plugin_active() && \Vendidero\Germanized\Shiptastic::needs_shiptastic_dhl_standalone() ) {
	update_option( 'woocommerce_gzd_is_shiptastic_standalone_update', 'yes' );
	update_option( 'woocommerce_gzd_is_shiptastic_dhl_standalone_update', 'yes' );
}
