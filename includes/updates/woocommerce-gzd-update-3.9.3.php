<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_create_page' ) ) {
	include_once( WC()->plugin_path() . '/includes/admin/wc-admin-functions.php' );
}

$pages = array(
	'review_authenticity' => array(
		'name'    => _x( 'review-authenticity', 'Page slug', 'woocommerce-germanized' ),
		'title'   => _x( 'Review Authenticity', 'Page title', 'woocommerce-germanized' ),
		'content' => ''
	),
);

foreach ( $pages as $key => $page ) {
	wc_create_page( esc_sql( $page['name'] ), 'woocommerce_' . $key . '_page_id', $page['title'] );
}