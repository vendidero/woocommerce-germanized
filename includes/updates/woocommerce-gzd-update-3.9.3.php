<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_create_page' ) ) {
	include_once WC()->plugin_path() . '/includes/admin/wc-admin-functions.php';
}

$review_post_status = ( 'yes' === get_option( 'woocommerce_enable_reviews' ) ) ? 'publish' : 'draft';

$pages_data = array(
	'review_authenticity' => array(
		'name'    => _x( 'review-authenticity', 'Page slug', 'woocommerce-germanized' ),
		'title'   => _x( 'Review Authenticity', 'Page title', 'woocommerce-germanized' ),
		'content' => '',
	),
);

/**
 * Temporarily patch bug in WooCommerce Multilingual
 *
 * @see https://wordpress.org/support/topic/fatal-error-wcml_store_pages-does-not-have-a-method-check_store_page_id/
 */
if ( class_exists( 'woocommerce_wpml' ) && class_exists( 'WCML_Store_Pages' ) ) {
	global $woocommerce_wpml;

	if ( $woocommerce_wpml && isset( $woocommerce_wpml->store ) ) {
		remove_filter( 'woocommerce_create_page_id', array( $woocommerce_wpml->store, 'check_store_page_id' ), 10 );
	}
}

foreach ( $pages_data as $key => $page_data ) {
	wc_create_page( esc_sql( $page_data['name'] ), 'woocommerce_' . $key . '_page_id', $page_data['title'], '', 0, $review_post_status );
}

/**
 * Show legal news note
 */
WC_GZD_Admin_Notices::instance()->activate_legal_news_note();
