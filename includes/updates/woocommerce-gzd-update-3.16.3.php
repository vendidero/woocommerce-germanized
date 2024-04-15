<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( '\Vendidero\Germanized\Shipments\Package' ) && \Vendidero\Germanized\Shipments\Package::has_dependencies() ) {
	global $wpdb;
	$wpdb->hide_errors();

	\Vendidero\Germanized\Shipments\Admin\Admin::remove_duplicate_provider_meta();
}
