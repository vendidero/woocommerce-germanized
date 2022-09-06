<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( get_option( 'woocommerce_gzd_trusted_shops_id' ) && \Vendidero\Germanized\PluginsHelper::needs_trusted_shops_migration() ) {
	update_option( 'woocommerce_gzd_is_ts_standalone_update', 'yes' );
}
