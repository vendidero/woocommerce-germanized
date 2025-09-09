<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$wpdb->hide_errors();

$table_name = $wpdb->prefix . 'woocommerce_stc_shipping_providermeta';

if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s;', $table_name ) ) ) {
	$sql    = "DELETE FROM `{$table_name}` WHERE `meta_id` NOT IN (SELECT * FROM (SELECT MAX(`pm`.`meta_id`) FROM `{$table_name}` pm GROUP BY `pm`.`stc_shipping_provider_id`, `pm`.`meta_key`) x)";
	$result = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}
