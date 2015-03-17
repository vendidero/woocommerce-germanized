<?php
/**
 * Action/filter hooks used for functions/templates
 *
 * @author 		Vendidero
 * @version     1
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Product Summary Box
 */
if ( get_option( 'woocommerce_gzd_display_product_detail_unit_price' ) == 'yes' )
	add_action( 'woocommerce_single_product_summary', 'woocommerce_gzd_template_single_price_unit', 10 );
if ( get_option( 'woocommerce_gzd_display_product_detail_tax_info' ) == 'yes' || get_option( 'woocommerce_gzd_display_product_detail_shipping_costs' ) == 'yes' )
	add_action( 'woocommerce_single_product_summary', 'woocommerce_gzd_template_single_legal_info', 11 );
if ( get_option( 'woocommerce_gzd_display_product_detail_delivery_time' ) == 'yes' )
	add_action( 'woocommerce_single_product_summary', 'woocommerce_gzd_template_single_delivery_time_info', 27 );
if ( get_option( 'woocommerce_gzd_small_enterprise' ) == 'yes' ) {
	add_action( 'woocommerce_after_cart_totals', 'woocommerce_gzd_template_small_business_info' );
	add_action( 'woocommerce_review_order_after_order_total', 'woocommerce_gzd_template_checkout_small_business_info', 25 );
}
add_filter( 'woocommerce_available_variation', 'woocommerce_gzd_add_variation_options', 0, 3 );

/**
 * Product Loop Items
 */
if ( get_option( 'woocommerce_gzd_display_listings_unit_price' ) == 'yes' )
	add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_gzd_template_single_price_unit', 10 );
if ( get_option( 'woocommerce_gzd_display_listings_shipping_costs' ) == 'yes' )
	add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_gzd_template_single_shipping_costs_info', 8 );
if ( get_option( 'woocommerce_gzd_display_listings_delivery_time' ) == 'yes' )
	add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_gzd_template_single_delivery_time_info', 7 );
if ( get_option( 'woocommerce_gzd_display_listings_tax_info' ) == 'yes' )
	add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_gzd_template_single_tax_info', 6 );
if ( get_option( 'woocommerce_gzd_display_listings_add_to_cart' ) == 'no' )
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
if ( get_option( 'woocommerce_gzd_display_listings_link_details' ) == 'yes' )
	add_filter( 'woocommerce_loop_add_to_cart_link', 'woocommerce_gzd_template_loop_add_to_cart', 99, 2 );

/**
 * Cart
 */
add_action( 'woocommerce_cart_totals_after_order_total', 'woocommerce_gzd_template_cart_total_tax', 1 );
add_filter( 'woocommerce_cart_item_price', 'wc_gzd_cart_product_unit_price', 0, 2 );
add_filter( 'woocommerce_cart_item_subtotal', 'wc_gzd_cart_product_unit_price', 0, 2 );
// Remove cart item name filter within checkout
add_action( 'woocommerce_review_order_before_cart_contents', 'woocommerce_gzd_template_checkout_remove_cart_name_filter' );
// Add item name filter within cart
add_filter( 'woocommerce_cart_item_name', 'wc_gzd_cart_product_delivery_time', 0, 2 );
add_filter( 'woocommerce_cart_item_name', 'wc_gzd_cart_product_item_desc', 0, 3 );

/**
 * Checkout
 */
add_action( 'woocommerce_review_order_after_order_total', 'woocommerce_gzd_template_cart_total_tax', 1 );
add_action( 'woocommerce_review_order_before_cart_contents', 'woocommerce_gzd_template_checkout_table_content_replacement' );
add_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_table_product_hide_filter_removal' );
add_filter( 'woocommerce_checkout_cart_item_quantity', 'wc_gzd_cart_product_delivery_time', 0, 2 );
add_filter( 'woocommerce_checkout_cart_item_quantity', 'wc_gzd_cart_product_item_desc', 0, 2 );

// Do only hook if is no ajax request - fallback if theme misses no-ajax-check before applying hooks & filters
if ( ! is_ajax() && version_compare( WC()->version, '2.3', '>=' ) ) {

	remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
	remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
	add_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 10 );
	add_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 20 );
	add_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_order_submit', 21 );
	add_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_checkout_legal', 2 );
	add_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_checkout_set_terms_manually', 3 );
	// Temporarily remove order button from payment.php - then add again to show after product table
	add_action( 'woocommerce_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_remove_filter', PHP_INT_MAX );
	add_action( 'woocommerce_review_order_after_submit', 'woocommerce_gzd_template_set_order_button_show_filter', PHP_INT_MAX );
	add_action( 'woocommerce_gzd_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_show_filter', PHP_INT_MAX );
	if ( get_option( 'woocommerce_gzd_checkout_legal_digital_checkbox' ) == 'yes' )
		add_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_digital_checkbox', 4 );
	
	// Add payment title heading
	add_action( 'woocommerce_review_order_before_payment', 'woocommerce_gzd_template_checkout_payment_title' );
	// Remove default term box
	add_filter( 'woocommerce_checkout_show_terms', 'woocommerce_gzd_remove_term_checkbox' );

}

// Before 2.3
if ( version_compare( WC()->version, '2.3', '<' ) ) {

	add_action( 'woocommerce_gzd_before_shop_table', 'woocommerce_gzd_template_checkout_legal', 2 );
	add_action( 'woocommerce_gzd_before_shop_table', 'woocommerce_gzd_template_checkout_set_terms_manually' );
	if ( get_option( 'woocommerce_gzd_checkout_legal_digital_checkbox' ) == 'yes' )
		add_action( 'woocommerce_gzd_before_shop_table', 'woocommerce_gzd_digital_checkbox', 1 );

	// Add payment title heading
	add_action( 'woocommerce_review_order_before_payment', 'woocommerce_gzd_template_checkout_payment_title' );
	// Remove default term box
	add_filter( 'woocommerce_checkout_show_terms', 'woocommerce_gzd_remove_term_checkbox' );

}

// Display back to cart button
if ( get_option( 'woocommerce_gzd_display_checkout_back_to_cart_button' ) == 'yes' )
	add_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_back_to_cart' );

// Force order button text
add_filter( 'woocommerce_order_button_text', 'woocommerce_gzd_template_order_button_text', PHP_INT_MAX );

/**
 * Checkout Validation
*/
add_action( 'woocommerce_after_checkout_validation', 'woocommerce_gzd_checkout_validation', 1, 1 );

/**
 * Order details & Thankyou
 */
// Remove order again button (legally incompliant)
remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );
add_action( 'woocommerce_thankyou_order_received_text', 'woocommerce_gzd_template_order_success_text', 0, 1 );
add_action( 'woocommerce_order_item_quantity_html', 'wc_gzd_cart_product_delivery_time', 0, 2 );
add_action( 'woocommerce_order_item_quantity_html', 'wc_gzd_cart_product_item_desc', 0, 2 );
add_filter( 'woocommerce_order_formatted_line_subtotal', 'wc_gzd_cart_product_unit_price', 0, 2 );
if ( get_option( 'woocommerce_gzd_hide_order_success_details' ) == 'yes' )
	remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
if ( get_option( 'woocommerce_gzd_trusted_shops_id' ) )
	add_action( 'woocommerce_thankyou', 'woocommerce_gzd_template_checkout_thankyou_trusted_shops', 10, 1 );

/**
 * Footer
 */
if ( get_option( 'woocommerce_gzd_display_footer_vat_notice' ) == 'yes' ) {
	add_action ( 'woocommerce_gzd_footer_msg', 'woocommerce_gzd_template_footer_vat_info', 0 );
	add_action ( 'wp_footer', 'woocommerce_gzd_template_footer_vat_info', 5 );
}
if ( get_option( 'woocommerce_gzd_display_footer_sale_price_notice' ) == 'yes' ) {
	add_action ( 'woocommerce_gzd_footer_msg', 'woocommerce_gzd_template_footer_sale_info', 0 );
	add_action ( 'wp_footer', 'woocommerce_gzd_template_footer_sale_info', 5 );
}
?>