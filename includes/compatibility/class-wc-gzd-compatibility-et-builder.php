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

		add_action( 'woocommerce_checkout_init', function() {
			if ( $this->is_et_builder_checkout() && ! defined( 'WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS' ) ) {
				define( 'WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS', true );
			}
		} );

		/**
		 * Divi has a bug: In case the summary hooks are moved within their custom logic to the actual Divi module, Divi does not recognize which hook
		 * adds which output. Instead, it appends the whole hook output each time the hook is being applied which leads to multiple outputs of the same content.
		 * Use a static priority to make sure the output is only added once until Divi fixes the problem.
		 *
		 * Last tested with Divi 4.14.7.
		 */
		add_filter( 'et_builder_wc_relocate_single_product_summary_output_priority', function( $output_priority, $callback_name ) {
			if ( strstr( $callback_name, 'woocommerce_gzd_' ) ) {
				return 5;
			}

			return $output_priority;
		}, 10, 2 );

		/**
		 * Divi seems to set hooks on its own - make sure Germanized does not restore defaults
		 */
		add_action( 'woocommerce_gzd_disabled_checkout_adjustments', function() {
			if ( wp_doing_ajax() && function_exists( 'et_builder_is_loading_data' ) && et_builder_is_loading_data() ) {
				remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
				remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );

				/**
				 * By default, do not re-add default order_review hooks - only in case the module matches
				 */
				if ( isset( $_REQUEST['module_type'] ) ) {
					if ( 'et_pb_wc_checkout_order_details' === $_REQUEST['module_type'] ) {
						add_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
					} elseif ( 'et_pb_wc_checkout_payment_info' === $_REQUEST['module_type'] ) {
						add_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
					}
				}
			} else {
				remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
				remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
			}
		} );

		/**
		 * Disable checkout adjustments during editor requests
		 */
		if ( wp_doing_ajax() && function_exists( 'et_builder_is_loading_data' ) && et_builder_is_loading_data() ) {
			if ( ! defined( 'WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS' ) ) {
				define( 'WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS', true );
			}
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
}