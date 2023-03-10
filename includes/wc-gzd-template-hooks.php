<?php
/**
 * Action/filter hooks used for functions/templates
 *
 * @author        Vendidero
 * @version     1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Add germanized body classes
 */
add_filter( 'body_class', 'wc_gzd_body_class' );

/**
 * Hide certain HTML output if activated via options
 */
add_filter( 'woocommerce_germanized_hide_delivery_time_text', 'woocommerce_gzd_template_maybe_hide_delivery_time', 10, 2 );
add_filter( 'woocommerce_germanized_hide_shipping_costs_text', 'woocommerce_gzd_template_maybe_hide_shipping_costs', 10, 2 );

if ( get_option( 'woocommerce_gzd_display_digital_delivery_time_text' ) !== '' ) {
	add_filter( 'woocommerce_germanized_empty_delivery_time_text', 'woocommerce_gzd_template_digital_delivery_time_text', 10, 2 );
}

add_filter( 'woocommerce_get_price_html', 'woocommerce_gzd_template_sale_price_label_html', 50, 2 );

/**
 * Maybe add specific more variants available notice to price html in case
 * explicitly activated via woocommerce_gzd_show_variable_more_variants_notice.
 */
add_filter( 'woocommerce_get_price_html', 'woocommerce_gzd_template_add_more_variants_price_notice', 100, 2 );
add_filter( 'woocommerce_gzd_unit_price_html', 'woocommerce_gzd_template_add_more_variants_unit_price_notice', 100, 2 );

/**
 * Single Product
 */
foreach ( wc_gzd_get_single_product_shopmarks() as $shopmark ) {
	$shopmark->execute();
}

/**
 * Single Product - Grouped
 */
foreach ( wc_gzd_get_single_product_grouped_shopmarks() as $shopmark ) {
	$shopmark->execute();
}

/**
 * Product Loop
 */
foreach ( wc_gzd_get_product_loop_shopmarks() as $shopmark ) {
	$shopmark->execute();
}

/**
 * Product Block
 */
foreach ( wc_gzd_get_product_block_shopmarks() as $shopmark ) {
	$shopmark->execute();
}

// Make sure to add a global product object to allow getting the grouped parent product within child display
add_action( 'woocommerce_before_add_to_cart_form', 'woocommerce_gzd_template_single_setup_global_product' );

add_filter( 'woocommerce_available_variation', 'woocommerce_gzd_add_variation_options', 5000, 3 );

if ( 'no' === get_option( 'woocommerce_gzd_display_listings_add_to_cart' ) ) {
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
}

if ( 'yes' === get_option( 'woocommerce_gzd_display_listings_link_details' ) ) {
	add_filter( 'woocommerce_loop_add_to_cart_link', 'woocommerce_gzd_template_loop_add_to_cart', 99, 2 );
}

/**
 * Review Omnibus-Policy.
 *
 * @see https://www.haendlerbund.de/de/news/aktuelles/rechtliches/4145-omnibus-rezensionen-gekennzeichnet
 */
add_action(
	'init',
	function() {
		if ( apply_filters( 'woocommerce_gzd_enable_rating_authenticity_notices', wc_reviews_enabled() ) ) {
			if ( 'yes' === get_option( 'woocommerce_gzd_display_rating_authenticity_notice' ) ) {
				add_filter( 'woocommerce_product_get_rating_html', 'woocommerce_gzd_template_product_rating_authenticity_status_filter', 500 );
				add_action( 'woocommerce_gzd_after_product_grid_block_after_rating', 'woocommerce_gzd_template_product_rating_authenticity_status_loop', 20 );
			}

			if ( 'yes' === get_option( 'woocommerce_gzd_display_review_authenticity_notice' ) ) {
				add_action( 'woocommerce_review_after_comment_text', 'woocommerce_gzd_template_product_review_authenticity_status', 20 );
				add_filter(
					'pre_option_woocommerce_review_rating_verification_label',
					function() {
						return 'no';
					},
					500
				);
			}
		}
	},
	50
);

/**
 * Widgets
 */
add_action( 'woocommerce_widget_product_item_start', 'woocommerce_gzd_template_product_widget_filters_start', 10, 1 );
add_action( 'woocommerce_widget_product_item_end', 'woocommerce_gzd_template_product_widget_filters_end', 10, 1 );

/**
 * Add hooks to blocks via DOM adjustments.
 */
add_filter( 'woocommerce_blocks_product_grid_item_html', 'wc_gzd_template_adjust_product_grid_block_html', 1, 3 );

// Additional product blocks which do not inherit from \Automattic\WooCommerce\Blocks\BlockTypes\AbstractProductGrid
foreach ( array( 'woocommerce/featured-product' ) as $block_type ) {
	add_filter( "render_block_{$block_type}", 'wc_gzd_template_adjust_product_block_html', 150, 2 );
}

/**
 * Cart, Checkout taxes
 */
add_action( 'init', 'woocommerce_gzd_register_checkout_total_taxes', 10 );

function woocommerce_gzd_register_checkout_total_taxes() {
	if ( wc_gzd_show_taxes_before_total() ) {
		add_action( 'woocommerce_cart_totals_before_order_total', 'woocommerce_gzd_template_cart_total_tax', 1 );
		add_action( 'woocommerce_review_order_before_order_total', 'woocommerce_gzd_template_cart_total_tax', 1 );
	} else {
		add_action( 'woocommerce_cart_totals_after_order_total', 'woocommerce_gzd_template_cart_total_tax', 1 );
		add_action( 'woocommerce_review_order_after_order_total', 'woocommerce_gzd_template_cart_total_tax', 1 );
	}
}

/**
 * Cart Hooks
 */
foreach ( wc_gzd_get_cart_shopmarks() as $shopmark ) {
	$shopmark->execute();
}

// Small enterprises
if ( wc_gzd_is_small_business() ) {
	add_action( 'woocommerce_cart_totals_after_order_total', 'woocommerce_gzd_template_checkout_small_business_info', wc_gzd_get_hook_priority( 'cart_small_business_info' ) );
	add_action( 'woocommerce_review_order_after_order_total', 'woocommerce_gzd_template_checkout_small_business_info', wc_gzd_get_hook_priority( 'checkout_small_business_info' ) );
}

/**
 * Make sure to load woocommerce_gzd_maybe_add_small_business_vat_notice on init so that child-theme adjustments
 * for woocommerce_gzd_small_business_show_total_vat_notice might work.
 */
if ( wc_gzd_is_small_business() ) {
	add_action( 'init', 'woocommerce_gzd_maybe_add_small_business_vat_notice', 20 );
}

function woocommerce_gzd_maybe_add_small_business_vat_notice() {
	/**
	 * Filter to show incl. VAT for small business after order/cart total.
	 *
	 * This filter serves for shops which want to enable a incl. VAT notice
	 * for small businesses. Some institutions (e.g. Händlerbund) state that this is necessary.
	 *
	 * ```php
	 * function ex_enable_small_business_vat_notice() {
	 *      return true;
	 * }
	 * add_filter( 'woocommerce_gzd_small_business_show_total_vat_notice', 'ex_enable_small_business_vat_notice', 10 );
	 * ```
	 *
	 * @param bool $enable Whether to enable the notice or not.
	 *
	 * @since 1.8.7
	 *
	 */
	if ( apply_filters( 'woocommerce_gzd_small_business_show_total_vat_notice', false ) ) {
		add_filter( 'woocommerce_get_formatted_order_total', 'woocommerce_gzd_template_small_business_total_vat_notice', 10, 1 );
		add_filter( 'woocommerce_cart_totals_order_total_html', 'woocommerce_gzd_template_small_business_total_vat_notice', 10, 1 );
		add_action( 'woocommerce_widget_shopping_cart_total', 'woocommerce_gzd_template_small_business_mini_cart_vat_notice', 12 );
	}
}

// Differential Taxation for cart & order
if ( 'yes' === get_option( 'woocommerce_gzd_differential_taxation_checkout_notices' ) ) {
	add_action( 'woocommerce_cart_totals_after_order_total', 'woocommerce_gzd_template_differential_taxation_notice_cart', wc_gzd_get_hook_priority( 'cart_small_business_info' ) );
	add_action( 'woocommerce_order_details_after_order_table', 'woocommerce_gzd_template_differential_taxation_notice_order', 10 );
	add_action( 'woocommerce_pay_order_before_submit', 'woocommerce_gzd_template_differential_taxation_notice_order', 10 );
	add_action( 'woocommerce_review_order_after_order_total', 'woocommerce_gzd_template_differential_taxation_notice_cart', wc_gzd_get_hook_priority( 'checkout_small_business_info' ) );
}

// Photovoltaic systems
if ( 'yes' === get_option( 'woocommerce_gzd_photovoltaic_systems_checkout_info' ) ) {
	add_action( 'woocommerce_before_checkout_form', 'woocommerce_gzd_template_photovoltaic_systems_checkout_notice', 20 );
}

/**
 * Mini Cart
 */
add_action( 'woocommerce_before_mini_cart_contents', 'woocommerce_gzd_template_mini_cart_remove_hooks', 5 );
add_action( 'woocommerce_before_mini_cart_contents', 'woocommerce_gzd_template_mini_cart_add_hooks', 10 );
// Some themes/plugins (e.g. Elementor pro) might not execute the woocommerce_after_mini_cart hook
add_action( 'woocommerce_mini_cart_contents', 'woocommerce_gzd_template_mini_cart_maybe_remove_hooks', 10000 );

add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'woocommerce_gzd_template_mini_cart_taxes', 10 );

/**
 * Checkout
 */
add_action( 'woocommerce_review_order_before_cart_contents', 'woocommerce_gzd_template_checkout_table_content_replacement' );
add_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_table_product_hide_filter_removal' );

/**
 * Checkout Hooks
 */
foreach ( wc_gzd_get_checkout_shopmarks() as $shopmark ) {
	$shopmark->execute();
}

if ( 'yes' === get_option( 'woocommerce_gzd_display_checkout_edit_data_notice' ) ) {
	add_action( 'woocommerce_before_order_notes', 'woocommerce_gzd_template_checkout_edit_data_notice', wc_gzd_get_hook_priority( 'checkout_edit_data_notice' ), 1 );
}

WC_GZD_Hook_Priorities::instance()->force_hook_order(
	'woocommerce_checkout_order_review',
	array(
		array(
			'function'     => 'woocommerce_checkout_payment',
			'new_priority' => 'woocommerce_order_review',
		),
		array(
			'function'     => 'woocommerce_order_review',
			'new_priority' => 'woocommerce_checkout_payment',
		),
	)
);

// Load ajax relevant hooks on init with fallback
if ( did_action( 'init' ) ) {
	if ( ! wp_doing_ajax() ) {
		woocommerce_gzd_checkout_load_ajax_relevant_hooks();
	}
} else {
	add_action(
		'init',
		function() {
			if ( ! wp_doing_ajax() ) {
				woocommerce_gzd_checkout_load_ajax_relevant_hooks();
			}
		}
	);
}

// Remove WooCommerce Terms checkbox
add_filter( 'woocommerce_checkout_show_terms', 'woocommerce_gzd_template_set_wc_terms_hide', 100 );

// Temporarily remove order button from payment.php - then add again to show after product table
add_action( 'woocommerce_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_remove_filter', 1500 );
add_action( 'woocommerce_review_order_after_submit', 'woocommerce_gzd_template_set_order_button_show_filter', 1500 );
add_action( 'woocommerce_gzd_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_show_filter', 1500 );

/**
 * Render Checkboxes (except checkout)
 */
add_action( 'woocommerce_pay_order_before_submit', 'woocommerce_gzd_template_render_pay_for_order_checkboxes', 10 );
add_action( 'woocommerce_register_form', 'woocommerce_gzd_template_render_register_checkboxes', 19 );
add_filter( 'comment_form_submit_button', 'woocommerce_gzd_template_render_review_checkboxes', 10, 2 );

// Add terms placeholder in case validation takes place by third-party plugins (e.g. WooCommerce PayPal Payments)
add_action( 'woocommerce_pay_order_before_submit', 'woocommerce_gzd_template_checkout_set_terms_manually', 0 );

// Maybe remove checkout adjustments during AJAX requests and before rendering checkout
add_action( 'woocommerce_checkout_init', 'wc_gzd_maybe_disable_checkout_adjustments', 20 );
add_action( 'woocommerce_checkout_update_order_review', 'wc_gzd_maybe_disable_checkout_adjustments', 20 );

function woocommerce_gzd_checkout_load_ajax_relevant_hooks() {
	add_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_order_submit', wc_gzd_get_hook_priority( 'checkout_order_submit' ) );
	add_action( 'woocommerce_checkout_after_order_review', 'woocommerce_gzd_template_order_submit_fallback', 50 );

	// Render checkout checkboxes
	add_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );
	add_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_checkout_set_terms_manually', wc_gzd_get_hook_priority( 'checkout_set_terms' ) );

	// Add payment title heading
	add_action( 'woocommerce_review_order_before_payment', 'woocommerce_gzd_template_checkout_payment_title' );
}

// Display back to cart button
if ( get_option( 'woocommerce_gzd_display_checkout_back_to_cart_button' ) === 'yes' ) {
	add_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_back_to_cart' );
}

// Force order button text
add_filter( 'woocommerce_order_button_text', 'woocommerce_gzd_template_order_button_text', 9999 );

// Forwarding fee
add_action( 'woocommerce_review_order_after_order_total', 'woocommerce_gzd_template_checkout_forwarding_fee_notice' );

/**
 * Order details & Thankyou
 */
add_filter( 'woocommerce_thankyou_order_received_text', 'woocommerce_gzd_template_order_success_text', 0, 1 );
add_action( 'woocommerce_thankyou', 'woocommerce_gzd_template_order_pay_now_button', wc_gzd_get_hook_priority( 'order_pay_now_button' ), 1 );

// Set Hooks before order details table
add_action( 'woocommerce_thankyou', 'woocommerce_gzd_template_order_item_hooks', 0 );

// Add Hooks to pay form
add_action( 'before_woocommerce_pay', 'woocommerce_gzd_template_order_item_hooks', 10 );

if ( 'yes' === get_option( 'woocommerce_gzd_hide_order_success_details' ) ) {
	remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', WC_GZD_Hook_Priorities::instance()->get_priority( 'woocommerce_thankyou', 'woocommerce_order_details_table' ) );
}

/**
 * Filter to turn on Woo default privacy checkbox in checkout.
 *
 * Germanized disables the default WooCommerce privacy checkbox to replace it with it's own
 * data privacy checkbox instead.
 *
 * @param bool $enable Set to `false` to re-enable Woo default privacy checkbox.
 *
 * @since 1.9.10
 *
 */
if ( apply_filters( 'woocommerce_gzd_disable_wc_privacy_policy_checkbox', true ) ) {
	remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20 );
	remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30 );
	remove_action( 'woocommerce_register_form', 'wc_registration_privacy_policy_text', 20 );

	// If other plugins or themes use that function, make sure we are emptying the text.
	add_filter( 'woocommerce_get_privacy_policy_text', 'wc_gzd_template_empty_wc_privacy_policy_text', 999, 2 );
}

/**
 * Footer
 */
if ( 'yes' === get_option( 'woocommerce_gzd_display_footer_vat_notice' ) ) {
	add_action( 'woocommerce_gzd_footer_msg', 'woocommerce_gzd_template_footer_vat_info', wc_gzd_get_hook_priority( 'gzd_footer_vat_info' ) );
	add_action( 'wp_footer', 'woocommerce_gzd_template_footer_vat_info', wc_gzd_get_hook_priority( 'footer_vat_info' ) );
}
if ( 'yes' === get_option( 'woocommerce_gzd_display_footer_sale_price_notice' ) ) {
	add_action( 'woocommerce_gzd_footer_msg', 'woocommerce_gzd_template_footer_sale_info', wc_gzd_get_hook_priority( 'gzd_footer_sale_info' ) );
	add_action( 'wp_footer', 'woocommerce_gzd_template_footer_sale_info', wc_gzd_get_hook_priority( 'footer_sale_info' ) );
}

