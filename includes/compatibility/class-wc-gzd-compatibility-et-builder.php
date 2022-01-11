<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ET Builder (e.g. Divi) compatibility
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

		add_action( 'woocommerce_before_single_product', function() {
			if ( $this->is_et_builder_single_product() ) {
				if ( ! wc_post_content_has_shortcode( 'gzd_product_tax_notice' ) || ! wc_post_content_has_shortcode( 'gzd_product_shipping_notice' ) ) {
					if ( current_user_can( 'manage_woocommerce' ) ) {
						?>
							<div class="wc-gzd-builder-notice" style="background: rgba(255,83,83,.1);color: #ff5353;border-radius: 6px;font-size: .9em;     display: block;margin-bottom: 1em;padding: 0.5em;width: 100%;">
								<p>
									<?php printf( __( 'Seems like you are using the Divi builder to build your product page. Please do make sure to place your <a %1$s>shopmarks</a> (e.g. VAT notice) accordingly by using <a %2$s>shortcodes</a>.', 'woocommerce-germanized' ), 'style="color: #ff5353; text-decoration: underline;" href="https://vendidero.de/dokument/preisauszeichnungen-anpassen#pagebuilder"', 'style="color: #ff5353; text-decoration: underline;" href="https://wordpress.org/plugins/woocommerce-germanized/#installation"' ); ?>
									<?php printf( __( 'Place them by inserting a text widget to the builder and paste one of the following shortcodes:', 'woocommerce-germanized' ) ); ?>
								</p>
								<ul style="margin-left: 1em; margin-bottom: 0; padding-bottom: 0;">
									<li>[gzd_product_tax_notice]</li>
									<li>[gzd_product_shipping_notice]</li>
									<li>[gzd_product_delivery_time]</li>
									<li>[gzd_product_unit_price]</li>
                                    <li>[gzd_product_defect_description]</li>
								</ul>
							</div>
						<?php
					}
				} else {
					/**
					 * Remove default shopmarks which are shown at wrong places due to Builder structure
					 */
					foreach( wc_gzd_get_single_product_shopmarks() as $shopmark ) {
						$shopmark->remove();
					}
				}
			}
		} );
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