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
		add_filter(
			'woocommerce_gzd_update_page_content',
			function( $new_page_content, $page_id, $content, $original_content, $append, $is_shortcode ) {
				if ( $append && wc_gzd_content_has_shortcode( $original_content, 'et_pb_section' ) ) {
					$shortcode_to_replace = 'et_pb_section';

					if ( wc_gzd_content_has_shortcode( $original_content, 'et_pb_column' ) ) {
						$shortcode_to_replace = 'et_pb_column';
					}

					$new_page_content = preg_replace( '/\[\/' . $shortcode_to_replace . ']/', '[et_pb_text _module_preset="default"]' . wpautop( $content ) . '[/et_pb_text][/' . $shortcode_to_replace . ']', $original_content, 1 );
				}

				return $new_page_content;
			},
			10,
			6
		);

		/**
		 * Disable empty price HTML shopmark check during builder requests to prevent incompatibilities from being
		 * triggered by Germanized.
		 */
		add_filter(
			'woocommerce_gzd_shopmarks_empty_price_html_check_enabled',
			function( $is_enabled ) {
				if ( isset( $_GET['et_fb'] ) && ! empty( $_GET['et_fb'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$is_enabled = false;
				}

				return $is_enabled;
			}
		);

		add_action(
			'woocommerce_checkout_init',
			function() {
				if ( $this->is_et_builder_checkout() && ! defined( 'WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS' ) ) {
					define( 'WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS', true );
				}
			}
		);

		/**
		 * Divi has a bug: In case the summary hooks are moved within their custom logic to the actual Divi module, Divi does not recognize which hook
		 * adds which output. Instead, it appends the whole hook output each time the hook is being applied which leads to multiple outputs of the same content.
		 * Use a static priority to make sure the output is only added once until Divi fixes the problem.
		 *
		 * Last tested with Divi 4.14.7.
		 */
		add_filter(
			'et_builder_wc_relocate_single_product_summary_output_priority',
			function( $output_priority, $callback_name ) {
				if ( strstr( $callback_name, 'woocommerce_gzd_' ) ) {
					return 5;
				}

				return $output_priority;
			},
			10,
			2
		);

		/**
		 * Divi seems to set hooks on its own - make sure Germanized does not restore defaults
		 */
		add_action(
			'woocommerce_gzd_disabled_checkout_adjustments',
			function() {
				if ( wp_doing_ajax() && function_exists( 'et_builder_is_loading_data' ) && et_builder_is_loading_data() ) {
					remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', WC_GZD_Hook_Priorities::instance()->get_priority( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 10 ) );
					remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', WC_GZD_Hook_Priorities::instance()->get_priority( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 20 ) );

					/**
					 * By default, do not re-add default order_review hooks - only in case the module matches
					 */
					if ( isset( $_REQUEST['module_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						if ( 'et_pb_wc_checkout_order_details' === $_REQUEST['module_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							add_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
						} elseif ( 'et_pb_wc_checkout_payment_info' === $_REQUEST['module_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							add_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
						}
					}
				} else {
					remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', WC_GZD_Hook_Priorities::instance()->get_priority( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 10, true ) );
					remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', WC_GZD_Hook_Priorities::instance()->get_priority( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 20, true ) );

					/**
					 * Force default hook removal in case Germanized has already reverted the hook order.
					 */
					remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
					remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
				}
			}
		);

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
		$is_enabled = false;

		if ( $post && is_singular( 'product' ) ) {
			$is_enabled = $this->post_is_et_builder( $this->get_divi_builder_post(), 'single_product' );
		}

		return $is_enabled;
	}

	protected function post_is_et_builder( $post, $type = 'checkout' ) {
		$post_content = is_a( $post, 'WP_Post' ) ? $post->post_content : '';
		$is_enabled   = false;

		if ( 'checkout' === $type ) {
			if ( wc_gzd_content_has_shortcode( $post_content, 'et_pb_wc_checkout_billing' ) || wc_gzd_content_has_shortcode( $post_content, 'et_pb_wc_checkout_payment_info' ) || wc_gzd_content_has_shortcode( $post_content, 'et_pb_wc_checkout_order_details' ) ) {
				$is_enabled = true;
			}
		} elseif ( 'single_product' === $type ) {
			if ( wc_gzd_content_has_shortcode( $post_content, 'et_pb_wc_price' ) || wc_gzd_content_has_shortcode( $post_content, 'et_pb_wc_description' ) ) {
				$is_enabled = true;
			}
		}

		return $is_enabled;
	}

	/**
	 * Either use the global post object or the current Divi builder template post.
	 *
	 * @return WP_Post|null
	 */
	protected function get_divi_builder_post() {
		global $post;

		if ( function_exists( 'et_theme_builder_get_template_layouts' ) && defined( 'ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE' ) && function_exists( 'et_theme_builder_overrides_layout' ) ) {
			if ( et_theme_builder_overrides_layout( ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE ) ) {
				$layouts     = et_theme_builder_get_template_layouts();
				$body_layout = $layouts[ ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE ];

				if ( isset( $body_layout['id'] ) && ( $body_post = get_post( $body_layout['id'] ) ) ) {
					return $body_post;
				}
			}
		}

		return $post;
	}

	protected function is_et_builder_checkout() {
		return $this->post_is_et_builder( $this->get_divi_builder_post(), 'checkout' );
	}
}
