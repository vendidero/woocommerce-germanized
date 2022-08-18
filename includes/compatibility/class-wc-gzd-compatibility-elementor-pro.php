<?php

use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_GZD_Compatibility_Elementor_Pro extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'Elementor Pro';
	}

	public static function get_path() {
		return 'elementor-pro/elementor-pro.php';
	}

	public function after_plugins_loaded() {
		/**
		 * On Editor - Register Germanized frontend hooks before the Editor init to load checkout adjustments.
		 */
		if ( ! empty( $_REQUEST['action'] ) && 'elementor' === $_REQUEST['action'] && is_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_action(
				'init',
				function() {
					if ( wc_gzd_checkout_adjustments_disabled() ) {
						return;
					}

					WC_germanized()->frontend_includes();
				},
				6
			);
		}

		add_action(
			'woocommerce_checkout_init',
			function() {
				if ( ! wc_gzd_checkout_adjustments_disabled() ) {
					add_filter(
						'wc_gzd_checkout_params',
						function( $params ) {
							$params['custom_heading_container'] = apply_filters( 'woocommerce_gzd_elementor_pro_review_order_heading_container', '.e-checkout__order_review-2' );

							return $params;
						},
						10
					);
				}

				if ( isset( $_POST['action'], $_POST['editor_post_id'] ) && 'elementor_ajax' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					if ( wc_gzd_checkout_adjustments_disabled() ) {
						return;
					}

					/**
					 * woocommerce_review_order_after_payment hooks is not executed during ajax requests (see checkout/payment.php) which will fail loading the hooks accordingly.
					 * Use a static filter to make sure AJAX hooks are still firing.
					 */
					add_action(
						'woocommerce_checkout_before_order_review',
						function() {
							add_filter( 'wp_doing_ajax', array( $this, 'disable_ajax_callback' ), 1000 );
						},
						0
					);

					add_action(
						'woocommerce_checkout_after_order_review',
						function() {
							remove_filter( 'wp_doing_ajax', array( $this, 'disable_ajax_callback' ), 1000 );
						},
						5000
					);

					woocommerce_gzd_checkout_load_ajax_relevant_hooks();
				}
			},
			100
		);
	}

	public static function disable_ajax_callback() {
		return false;
	}

	public function load() {
		add_action( 'elementor/widgets/register', array( $this, 'init_widgets' ), 10, 1 );

		/**
		 * Copy
		 */
		add_action(
			'elementor/element/parse_css',
			function( $post_css, $element ) {
				if ( is_a( $element, '\ElementorPro\Modules\Woocommerce\Widgets\Checkout' ) ) {
					$rules = $post_css->get_stylesheet()->get_rules();

					foreach ( $rules as $query_hash => $inner_rules ) {
						$query = array();

						if ( 'all' !== $query_hash ) {
							$query_parts = explode( '-', $query_hash );

							foreach ( $query_parts as $typed_query ) {
								$inner_parts = explode( '_', $typed_query );

								if ( count( $inner_parts ) > 0 ) {
									$query[ $inner_parts[0] ] = $inner_parts[1];
								}
							}
						}

						foreach ( $inner_rules as $rule_selector => $rule ) {
							if ( strstr( $rule_selector, '#payment #place_order' ) || strstr( $rule_selector, '#payment .place-order' ) ) {
								$new_rule_selector = str_replace( '#payment ', '', $rule_selector );

								$post_css->get_stylesheet()->add_rules( $new_rule_selector, $rule, ( ! empty( $query ) ? $query : null ) );
							}
						}
					}
				}
			},
			10,
			2
		);

		add_action(
			'elementor/frontend/widget/before_render',
			function ( $element ) {
				if ( is_a( $element, '\ElementorPro\Modules\Woocommerce\Widgets\Checkout' ) ) {
					if ( ! defined( 'WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS' ) && apply_filters( 'woocommerce_gzd_elementor_pro_disable_checkout_adjustments', false ) ) {
						define( 'WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS', true );
						wc_gzd_maybe_disable_checkout_adjustments();
					}
				}
			}
		);

		add_action(
			'elementor/frontend/after_enqueue_styles',
			function() {
				wp_add_inline_style(
					'elementor-pro',
					'
				.elementor-widget-woocommerce-checkout-page .woocommerce table.woocommerce-checkout-review-order-table {
				    border-radius: var(--sections-border-radius, 3px);
				    padding: var(--sections-padding, 16px 30px);
				    margin: var(--sections-margin, 0 0 24px 0);
				    border-style: var(--sections-border-type, solid);
				    border-color: var(--sections-border-color, #D4D4D4);
				    border-width: 1px;
				}
				.elementor-widget-woocommerce-checkout-page .woocommerce .woocommerce-checkout #payment {
					border: none;
					padding: 0;
				}
				.elementor-widget-woocommerce-checkout-page .woocommerce-checkout .place-order {
					display: -webkit-box;
					display: -ms-flexbox;
					display: flex;
					-webkit-box-orient: vertical;
					-webkit-box-direction: normal;
					-ms-flex-direction: column;
					flex-direction: column;
					-ms-flex-wrap: wrap;
					flex-wrap: wrap;
					padding: 0;
					margin-bottom: 0;
					margin-top: 1em;
					-webkit-box-align: var(--place-order-title-alignment, stretch);
					-ms-flex-align: var(--place-order-title-alignment, stretch);
					align-items: var(--place-order-title-alignment, stretch); 
				}
				.elementor-widget-woocommerce-checkout-page .woocommerce-checkout #place_order {
					background-color: #5bc0de;
					width: var(--purchase-button-width, auto);
					float: none;
					color: var(--purchase-button-normal-text-color, #ffffff);
					min-height: auto;
					padding: var(--purchase-button-padding, 1em 1em);
					border-radius: var(--purchase-button-border-radius, 3px); 
		        }
		        .elementor-widget-woocommerce-checkout-page .woocommerce-checkout #place_order:hover {
					background-color: #5bc0de;
					color: var(--purchase-button-hover-text-color, #ffffff);
					border-color: var(--purchase-button-hover-border-color, #5bc0de);
					-webkit-transition-duration: var(--purchase-button-hover-transition-duration, 0.3s);
					-o-transition-duration: var(--purchase-button-hover-transition-duration, 0.3s);
					transition-duration: var(--purchase-button-hover-transition-duration, 0.3s); 
                }
			'
				);
			}
		);
	}

	public function init_widgets( $widgets_manager ) {
		if ( ! class_exists( 'ElementorPro\Modules\Woocommerce\Widgets\Products_Base' ) ) {
			return;
		}

		include_once 'elementor/widgets/abstact-class-wc-gzd-elementor-widget.php';

		$widgets = array(
			'WC_GZD_Elementor_Widget_Product_Tax_Notice',
			'WC_GZD_Elementor_Widget_Product_Shipping_Notice',
			'WC_GZD_Elementor_Widget_Product_Unit_Price',
			'WC_GZD_Elementor_Widget_Product_Units',
			'WC_GZD_Elementor_Widget_Product_Delivery_Time',
			'WC_GZD_Elementor_Widget_Product_Defect_Description',
			'WC_GZD_Elementor_Widget_Product_Deposit',
			'WC_GZD_Elementor_Widget_Product_Deposit_Packaging_Type',
			'WC_GZD_Elementor_Widget_Product_Nutrients',
			'WC_GZD_Elementor_Widget_Product_Ingredients',
			'WC_GZD_Elementor_Widget_Product_Allergenic',
			'WC_GZD_Elementor_Widget_Product_Nutri_Score',
		);

		foreach ( $widgets as $widget ) {
			$classname = 'class-' . str_replace( '_', '-', strtolower( $widget ) ) . '.php';

			include_once WC_GERMANIZED_ABSPATH . 'includes/compatibility/elementor/widgets/' . $classname;

			if ( is_callable( array( $widgets_manager, 'register' ) ) ) {
				$widgets_manager->register( new $widget() );
			} else {
				$widgets_manager->register_widget_type( new $widget() );
			}
		}
	}
}
