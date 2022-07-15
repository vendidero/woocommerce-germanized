<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( 'yes' === get_option( 'oss_use_oss_procedure' ) || 'yes' === get_option( 'oss_enable_auto_observation' ) ) && ! \Vendidero\Germanized\PluginsHelper::is_oss_plugin_active() ) {
	update_option( 'woocommerce_gzd_is_oss_standalone_update', 'yes' );
}
