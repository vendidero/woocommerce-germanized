<?php

// Get all variable products
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Select variations
$update_variations = $wpdb->get_results(
	"
	SELECT DISTINCT posts.ID AS variation_id, posts.post_parent AS variation_parent
	FROM {$wpdb->posts} as posts
	LEFT OUTER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_unit_base'
	LEFT OUTER JOIN {$wpdb->postmeta} AS postmeta1 ON posts.ID = postmeta1.post_id AND postmeta1.meta_key = '_unit'
	WHERE posts.post_type = 'product_variation'
	AND postmeta.meta_value <> ''
	AND postmeta1.meta_value <> ''
	GROUP BY variation_parent
"
);

foreach ( $update_variations as $variation ) {

	$unit_base = get_post_meta( $variation->variation_id, '_unit_base', true );
	$unit      = get_post_meta( $variation->variation_id, '_unit', true );

	// Set first variation values to new parent values
	update_post_meta( $variation->variation_parent, '_unit_base', $unit_base );
	update_post_meta( $variation->variation_parent, '_unit', $unit );

}

// Rename all _unit of children
$wpdb->query(
	"
	UPDATE {$wpdb->postmeta} pm
	LEFT OUTER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
	SET pm.meta_key = '_unit_pre'
	WHERE p.post_type = 'product_variation'
	AND pm.meta_key = '_unit'
"
);

// Rename all _unit_base of children
$wpdb->query(
	"
	UPDATE {$wpdb->postmeta} pm
	LEFT OUTER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
	SET pm.meta_key = '_unit_base_pre'
	WHERE p.post_type = 'product_variation'
	AND pm.meta_key = '_unit_base'
"
);

// Update hide virtual shipping costs
if ( get_option( 'woocommerce_gzd_display_shipping_costs_virtual' ) === 'yes' ) {
	// Delete virtual from hidden shipping costs types (default)
	$types = array_diff( get_option( 'woocommerce_gzd_display_shipping_costs_hidden_types', array( 'virtual' ) ), array( 'virtual' ) );
	update_option( 'woocommerce_gzd_display_shipping_costs_hidden_types', $types );
}


