<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( '\Vendidero\Shiptastic\Package' ) && \Vendidero\Shiptastic\Package::has_dependencies() ) {
	global $wpdb;
	$wpdb->hide_errors();

	if ( isset( $wpdb->gzd_shipping_providermeta ) ) {
		$sql    = "DELETE FROM `{$wpdb->gzd_shipping_providermeta}` WHERE `meta_id` NOT IN (SELECT * FROM (SELECT MAX(`pm`.`meta_id`) FROM `{$wpdb->gzd_shipping_providermeta}` pm GROUP BY `pm`.`gzd_shipping_provider_id`, `pm`.`meta_key`) x)";
		$result = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
