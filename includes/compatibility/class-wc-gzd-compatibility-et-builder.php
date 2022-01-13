<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ET Builder (e.g. Divi) compatibility
 *
 * Divi uses a custom hook-builder for WooCommerce (see Divi/includes/builder/feature/woocommerce-modules.php et_builder_wc_relocate_single_product_summary)
 * to dynamically relocate hooks (e.g. shopmarks) to the right place/widget in the builder.
 */
class WC_GZD_Compatibility_ET_Builder extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'ET Builder';
	}

	public static function is_plugin() {
		return false;
	}

	public static function is_applicable() {
		return static::is_activated();
	}

	public static function is_activated() {
		return defined( 'ET_BUILDER_THEME' ) && ET_BUILDER_THEME;
	}

	public static function get_path() {
		return '';
	}

	public function load() {
		add_action( 'woocommerce_checkout_init', array( $this, 'before_checkout' ) );

		/**
		 * Disable empty price HTML shopmark check during builder requests to prevent incompatibilities from being
         * triggered by Germanized.
		 */
        add_filter( 'woocommerce_gzd_shopmarks_empty_price_html_check_enabled', function( $is_enabled ) {
	        if ( isset( $_GET['et_fb'] ) && ! empty( $_GET['et_fb'] ) ) {
		        $is_enabled = false;
	        }

	        return $is_enabled;
        } );

		// Disable adjusting payment and order review heading
		add_filter( 'wc_gzd_checkout_params', function( $params ) {
			if ( $this->is_et_builder_checkout() ) {
				$params['adjust_heading'] = false;
			}

			return $params;
		}, 10 );

		if ( wp_doing_ajax() && function_exists( 'et_builder_is_loading_data' ) && et_builder_is_loading_data() ) {
			$this->remove_checkout_adjustments( true );
		}
	}

	protected function is_et_builder_single_product() {
		global $post;

		if ( $post && is_singular( 'product' ) ) {
			if ( wc_post_content_has_shortcode( 'et_pb_wc_price' ) || wc_post_content_has_shortcode( 'et_pb_wc_description' ) ) {
				return true;
			}
		}

		return false;
	}

	protected function is_et_builder_checkout() {
		global $post;

		if ( $post ) {
			if ( wc_post_content_has_shortcode( 'et_pb_wc_checkout_billing' ) || wc_post_content_has_shortcode( 'et_pb_wc_checkout_payment_info' ) || wc_post_content_has_shortcode( 'et_pb_wc_checkout_order_details' ) ) {
				return true;
			}
		}

		return false;
	}

	protected function remove_checkout_adjustments( $is_builder_request = false ) {
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 20 );
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 10 );

		// Restore defaults
		if ( $is_builder_request ) {
			add_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
			add_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		}

		remove_action( 'woocommerce_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_remove_filter', 1500 );
		remove_action( 'woocommerce_review_order_after_submit', 'woocommerce_gzd_template_set_order_button_show_filter', 1500 );
		remove_action( 'woocommerce_gzd_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_show_filter', 1500 );

		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_order_submit', wc_gzd_get_hook_priority( 'checkout_order_submit' ) );
		remove_action( 'woocommerce_checkout_after_order_review', 'woocommerce_gzd_template_order_submit_fallback', 50 );

		remove_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );
		remove_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_checkout_set_terms_manually', wc_gzd_get_hook_priority( 'checkout_set_terms' ) );

		add_action( 'woocommerce_review_order_before_payment', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );
		add_action( 'woocommerce_review_order_before_payment', 'woocommerce_gzd_template_checkout_set_terms_manually', wc_gzd_get_hook_priority( 'checkout_set_terms' ) );

		add_action( 'woocommerce_review_order_before_payment', function() {
			echo '<input type="hidden" name="is_et_compatibility_checkout" value="yes" />';
		}, 50 );

		remove_action( 'woocommerce_review_order_before_payment', 'woocommerce_gzd_template_checkout_payment_title' );
	}

	public function before_checkout() {
		if ( wp_doing_ajax() && isset( $_POST['post_data'] ) ) {
			$result = array();
			$data   = wp_unslash( $_POST['post_data'] );
			parse_str( $data, $result );

			/**
			 * Make sure to remove these hooks on AJAX requests too.
			 */
			if ( isset( $result['is_et_compatibility_checkout'] ) ) {
				$this->remove_checkout_adjustments();
			}
		} elseif ( $this->is_et_builder_checkout() ) {
			$this->remove_checkout_adjustments();
		}
	}
}