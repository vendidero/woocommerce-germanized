<?php
/**
 * Update WC to 2.0.9
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     2.0.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $woocommerce_germanized;

if ( get_option( 'woocommerce_gzd_trusted_review_reminder_days' ) ) {
	update_option( 'woocommerce_gzd_trusted_shops_review_reminder_days', get_option( 'woocommerce_gzd_trusted_review_reminder_days' ) );
	delete_option( 'woocommerce_gzd_trusted_review_reminder_days' );
}

/*
$args = array(
	'post_type' => array( 'product', 'product_variation' ),
	'posts_per_page' => -1
);

$loop = new WP_Query( $args );

if ( $loop->have_posts() ) {

	while ( $loop->have_posts() ) {

		global $post;
		$loop->the_post(); 
		$product = wc_get_product();

		if ( $product->gzd_product->has_unit() && $product->gzd_product->unit_base )
			update_post_meta( ( $product->is_type( 'variation' ) ? $product->variation_id : $product->id ), '_unit_product', round( $product->gzd_product->unit_base * 100, wc_get_price_decimals() ) );
	}
}

wp_reset_postdata();
*/