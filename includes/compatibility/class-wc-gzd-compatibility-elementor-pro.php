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

	public function load() {
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'init_widgets' ), 10 );

		/**
		 * Copy
		 */
		add_action(	'elementor/element/parse_css', function( $post_css, $element ) {
			if ( is_a( $element, '\ElementorPro\Modules\Woocommerce\Widgets\Checkout' ) ) {
				$rules = $post_css->get_stylesheet()->get_rules();

				foreach( $rules as $query_hash => $inner_rules ) {
					$query = array();

					if ( 'all' !== $query_hash ) {
						$query_parts = explode( '-', $query_hash );

						foreach( $query_parts as $typed_query ) {
							$inner_parts = explode( '_', $typed_query );

							if ( sizeof( $inner_parts ) > 0 ) {
								$query[ $inner_parts[0] ] = $inner_parts[1];
							}
						}
					}

					foreach( $inner_rules as $rule_selector => $rule ) {
						if ( strstr( $rule_selector, '#payment #place_order' ) || strstr( $rule_selector, '#payment .place-order' ) ) {
							$new_rule_selector = str_replace( '#payment ', '', $rule_selector );

							$post_css->get_stylesheet()->add_rules( $new_rule_selector, $rule, ( ! empty( $query ) ? $query : null ) );
						}
					}
				}
			}
		}, 10, 2 );

		add_action( 'elementor/frontend/widget/before_render', function ( $element ) {
			if ( is_a( $element, '\ElementorPro\Modules\Woocommerce\Widgets\Checkout' ) ) {
				if ( ! defined( 'WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS' ) && apply_filters( 'woocommerce_gzd_elementor_pro_disable_checkout_adjustments', true ) ) {
					define( 'WC_GZD_DISABLE_CHECKOUT_ADJUSTMENTS', true );
					wc_gzd_maybe_disable_checkout_adjustments();
				}
			}
		} );

		add_action( 'elementor/frontend/after_enqueue_styles', function() {
			wp_add_inline_style( 'elementor-pro', '
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
			' );
		} );
	}

	public function init_widgets() {
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
		);

		$widget_manager = Plugin::$instance->widgets_manager;

		foreach ( $widgets as $widget ) {
			$classname = 'class-' . str_replace( '_', '-', strtolower( $widget ) ) . '.php';

			include_once 'elementor/widgets/' . $classname;
			$widget_manager->register_widget_type( new $widget() );
		}
	}
}