<?php

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility script for https://wordpress.org/plugins/cartflows/
 *
 * @class        WC_GZD_Compatibility_Cartflows
 * @category     Class
 * @author       vendidero
 */
class WC_GZD_Compatibility_Cartflows extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'Cartflows';
	}

	public static function get_path() {
		return 'cartflows/cartflows.php';
	}

	public function load() {
		/**
		 * AJAX action support
		 */
		add_action(
			'cartflows_woo_checkout_update_order_review',
			function( $post_data ) {
				if ( ! $this->enable_cartflows_support() ) {
					return;
				}

				/**
				 * Make sure AJAX refresh does not contain custom product table
				 */
				remove_action( 'woocommerce_review_order_before_cart_contents', 'woocommerce_gzd_template_checkout_table_content_replacement', 10 );
				remove_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_table_product_hide_filter_removal', 10 );

				/**
				 * It's an opt-in
				 */
				if ( isset( $post_data['_wcf_optin_id'] ) ) {
					remove_filter( 'woocommerce_order_button_text', 'woocommerce_gzd_template_order_button_text', 9999 );

					remove_action( 'woocommerce_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_remove_filter', 1500 );
					remove_action( 'woocommerce_review_order_after_submit', 'woocommerce_gzd_template_set_order_button_show_filter', 1500 );
					remove_action( 'woocommerce_gzd_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_show_filter', 1500 );
				}
			}
		);

		/**
		 * Checkout support
		 */
		add_action(
			'cartflows_checkout_form_before',
			function( $checkout_id ) {
				if ( ! $this->enable_cartflows_support() ) {
					return;
				}

				if ( ! is_callable( array( 'Cartflows_Modern_Checkout', 'get_instance' ) ) || ! function_exists( 'wcf' ) || ! is_callable( array( wcf()->options, 'get_checkout_meta_value' ) ) ) {
					return;
				}

				$cf_modern_checkout = Cartflows_Modern_Checkout::get_instance();

				if ( ! is_callable( array( $cf_modern_checkout, 'is_modern_checkout_layout' ) ) ) {
					return;
				}

				if ( Cartflows_Modern_Checkout::get_instance()->is_modern_checkout_layout( $checkout_id ) ) {
					$checkout_layout = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-checkout-layout' );

					/**
					 * Do not adjust payment-wrap as it is managed by cartflows.
					 */
					if ( 'modern-checkout' === $checkout_layout ) {
						remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', WC_GZD_Hook_Priorities::instance()->get_priority( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment' ) );
					}

					/**
					 * Do not replace product table
					 */
					remove_action( 'woocommerce_review_order_before_cart_contents', 'woocommerce_gzd_template_checkout_table_content_replacement', 10 );
					remove_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_table_product_hide_filter_removal', 10 );

					/**
					 * Add checkboxes right before the order review table
					 */
					remove_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );
					add_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_render_checkout_checkboxes', 1 );

					/**
					 * Do not render an additional payment selection title
					 */
					remove_action( 'woocommerce_review_order_before_payment', 'woocommerce_gzd_template_checkout_payment_title' );
				}
			},
			5,
			1
		);

		/**
		 * Opt-in support
		 */
		add_action(
			'cartflows_optin_form_before',
			function( $optin_id ) {
				if ( ! $this->enable_cartflows_support() ) {
					return;
				}

				/**
				 * Do not force button texts
				 */
				remove_filter( 'woocommerce_order_button_text', 'woocommerce_gzd_template_order_button_text', 9999 );

				/**
				 * Remove checkboxes
				 */
				remove_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );

				/**
				 * Do not adjust the submit button
				 */
				remove_action( 'woocommerce_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_remove_filter', 1500 );
				remove_action( 'woocommerce_review_order_after_submit', 'woocommerce_gzd_template_set_order_button_show_filter', 1500 );
				remove_action( 'woocommerce_gzd_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_show_filter', 1500 );

				/**
				 * Output the helper element to make sure JS does not fiddle with the order_review_heading.
				 */
				?>
			<input type="hidden" name="wc_gzd_checkout_disabled" id="wc_gzd_checkout_disabled" />
				<?php
			},
			5,
			1
		);
	}

	protected function enable_cartflows_support() {
		return apply_filters( 'woocommerce_gzd_enable_cartflows_support', true );
	}
}
