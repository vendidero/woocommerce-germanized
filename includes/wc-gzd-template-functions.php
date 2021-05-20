<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template functions
 *
 * @author        Vendidero
 * @version     1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! function_exists( 'woocommerce_gzd_template_single_legal_info' ) ) {

	/**
	 * Single Product price per unit.
	 */
	function woocommerce_gzd_template_single_legal_info() {
		global $product;

		wc_get_template( 'single-product/legal-info.php' );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_grouped_single_legal_info' ) ) {

	/**
	 * Single Product delivery time info
	 */
	function woocommerce_gzd_template_grouped_single_legal_info( $html, $grouped_child ) {
		ob_start();
		wc_get_template( 'single-product/legal-info.php' );
		$legal_html = ob_get_clean();

		return $html . $legal_html;
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_single_price_unit' ) ) {

	/**
	 * Single Product price per unit.
	 */
	function woocommerce_gzd_template_single_price_unit() {
		global $product;

		if ( ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		if ( in_array( $product->get_type(), apply_filters( 'woocommerce_gzd_product_types_supporting_unit_prices', array(
			'simple',
			'external',
			'variable',
			'grouped'
		) ) ) ) {
			wc_get_template( 'single-product/price-unit.php' );
		}
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_single_setup_global_product' ) ) {

	function woocommerce_gzd_template_single_setup_global_product() {
		global $product, $wc_gzd_global_product;

		$wc_gzd_global_product = wc_gzd_get_product( $product );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_grouped_single_price_unit' ) ) {

	/**
	 * Grouped Product price per unit.
	 * If grouped parent has unit price, recalculate child unit prices with grouped parent unit base.
	 */
	function woocommerce_gzd_template_grouped_single_price_unit( $html, $grouped_child ) {
		global $wc_gzd_global_product;

		$gzd_product = wc_gzd_get_product( $wc_gzd_global_product );
		$gzd_child   = wc_gzd_get_product( $grouped_child );

		if ( $gzd_product->has_unit() ) {
			$gzd_child->recalculate_unit_price( array(
				'base' => $gzd_product->get_unit_base(),
			) );
		}

		ob_start();
		wc_get_template( 'single-product/price-unit.php', array( 'gzd_product' => $gzd_child ) );
		$unit_html = ob_get_clean();

		return $html . $unit_html;
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_single_shipping_costs_info' ) ) {

	/**
	 * Single Product Shipping costs info
	 */
	function woocommerce_gzd_template_single_shipping_costs_info() {
		wc_get_template( 'single-product/shipping-costs-info.php' );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_single_delivery_time_info' ) ) {

	/**
	 * Single Product delivery time info
	 */
	function woocommerce_gzd_template_single_delivery_time_info() {
		wc_get_template( 'single-product/delivery-time-info.php' );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_grouped_single_delivery_time_info' ) ) {

	/**
	 * Grouped single product delivery time info
	 */
	function woocommerce_gzd_template_grouped_single_delivery_time_info( $html, $grouped_child ) {
		ob_start();
		wc_get_template( 'single-product/delivery-time-info.php' );
		$legal_html = ob_get_clean();

		return $html . $legal_html;
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_single_tax_info' ) ) {

	/**
	 * Single Product delivery time info
	 */
	function woocommerce_gzd_template_single_tax_info() {
		wc_get_template( 'single-product/tax-info.php' );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_single_product_units' ) ) {

	function woocommerce_gzd_template_single_product_units() {
		wc_get_template( 'single-product/units.php' );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_add_more_variants_unit_price_notice' ) ) {

	/**
	 * @param $price
	 * @param WC_GZD_Product $product
	 */
	function woocommerce_gzd_template_add_more_variants_unit_price_notice( $price, $product ) {
		if ( woocommerce_gzd_show_add_more_variants_notice( $product ) ) {
			$price = $price . ' ' . woocommerce_gzd_get_more_variants_notice( $product );
		}

		return $price;
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_add_more_variants_price_notice' ) ) {

	/**
	 * @param $price
	 * @param WC_GZD_Product $product
	 */
	function woocommerce_gzd_template_add_more_variants_price_notice( $price, $product ) {
		if ( woocommerce_gzd_show_add_more_variants_notice( $product ) ) {
			$gzd_product = wc_gzd_get_gzd_product( $product );

			/**
			 * In case the product has a unit price - add the notice to the unit price (which comes afterwards)
			 */
			if ( $gzd_product->has_unit() && 'no' !== get_option( 'woocommerce_gzd_unit_price_enable_variable' ) ) {
				return $price;
			} else {
				$price = $price . ' ' . woocommerce_gzd_get_more_variants_notice( $product );
			}
		}

		return $price;
	}
}

/**
 * @param $price
 * @param WC_Product $product
 *
 * @return mixed
 */
function woocommerce_gzd_price_notice( $price, $product ) {
	if ( woocommerce_gzd_show_add_more_variants_notice( $product ) ) {
		$gzd_product = wc_gzd_get_gzd_product( $product );

		if ( $gzd_product->has_unit() && 'no' !== get_option( 'woocommerce_gzd_unit_price_enable_variable' ) ) {
			return $price;
		} else {
			$price = $price . ' ' . woocommerce_gzd_get_more_variants_notice( $product );
		}
	}

	return $price;
}

if ( ! function_exists( 'woocommerce_gzd_template_grouped_single_product_units' ) ) {

	function woocommerce_gzd_template_grouped_single_product_units( $html, $grouped_child ) {
		ob_start();
		wc_get_template( 'single-product/units.php' );
		$legal_html = ob_get_clean();

		return $html . $legal_html;
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_small_business_info' ) ) {

	/**
	 * small business info
	 */
	function woocommerce_gzd_template_small_business_info() {
		wc_get_template( 'global/small-business-info.php' );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_small_business_info' ) ) {

	/**
	 * small business info within checkout
	 */
	function woocommerce_gzd_template_checkout_small_business_info() {
		echo '<tr class="order-total"><td colspan="2">';
		wc_get_template( 'global/small-business-info.php' );
		echo '</td></tr>';
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_footer_vat_info' ) ) {

	/**
	 * footer vat info
	 */
	function woocommerce_gzd_template_footer_vat_info() {
		echo do_shortcode( '[gzd_vat_info]' );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_footer_sale_info' ) ) {

	/**
	 * footer sale info
	 */
	function woocommerce_gzd_template_footer_sale_info() {
		echo do_shortcode( '[gzd_sale_info]' );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_cart_total_tax' ) ) {

	function woocommerce_gzd_template_cart_total_tax() {
		wc_gzd_cart_totals_order_total_tax_html();
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_edit_data_notice' ) ) {

	/**
	 * Display edit data notice
	 */
	function woocommerce_gzd_template_checkout_edit_data_notice() {
		wc_get_template( 'checkout/edit-data-notice.php' );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_back_to_cart' ) ) {

	/**
	 * Display back to cart button within checkout cart
	 */
	function woocommerce_gzd_template_checkout_back_to_cart() {
		echo '<tr><td colspan="5" class="actions"><a class="button" href="' . wc_get_cart_url() . '">' . __( 'Edit Order', 'woocommerce-germanized' ) . '</a></td></tr>';
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_payment_title' ) ) {

	/**
	 * Checkout payment gateway title
	 */
	function woocommerce_gzd_template_checkout_payment_title() {
		echo '<h3 id="order_payment_heading" style="' . ( WC()->cart && WC()->cart->get_total( 'edit' ) <= 0 ? 'display: none;' : '' ) . '">' . __( 'Choose a Payment Gateway', 'woocommerce-germanized' ) . '</h3>';
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_set_terms_manually' ) ) {

	/**
	 * Set terms checkbox manually
	 */
	function woocommerce_gzd_template_checkout_set_terms_manually() {
		echo '<input type="checkbox" name="terms" value="1" style="display: none;" />';
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_table_content_replacement' ) ) {

	/**
	 * Replaces default review-order.php product table by gzd product table template (checkout/review-order-product-table.php).
	 * Adds filter to hide default review order product table output.
	 */
	function woocommerce_gzd_template_checkout_table_content_replacement() {
		wc_get_template( 'checkout/review-order-product-table.php' );
		add_filter( 'woocommerce_checkout_cart_item_visible', 'woocommerce_gzd_template_checkout_table_product_hide', 1500 );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_table_product_hide' ) ) {

	/**
	 * Returns false to make sure default review order product table output is suppressed.
	 *
	 * @return boolean
	 */
	function woocommerce_gzd_template_checkout_table_product_hide() {
		return false;
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_table_product_hide_filter_removal' ) ) {

	/**
	 * Remove review order product table cart item visibility filter after output has been suppressed.
	 */
	function woocommerce_gzd_template_checkout_table_product_hide_filter_removal() {
		remove_filter( 'woocommerce_checkout_cart_item_visible', 'woocommerce_gzd_template_checkout_table_product_hide', 1500 );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_order_button_text' ) ) {

	/**
	 * Manipulate the order submit button text
	 */
	function woocommerce_gzd_template_order_button_text( $text ) {
		return __( get_option( 'woocommerce_gzd_order_submit_btn_text' ), 'woocommerce-germanized' );
	}

}

if ( ! function_exists( 'woocommerce_gzd_add_variation_options' ) ) {

	/**
	 * Add delivery time and unit price to variations
	 */
	function woocommerce_gzd_add_variation_options( $options, $product, $variation ) {
		$gzd_product = wc_gzd_get_product( $variation );

		$options = array_merge( $options, array(
			'delivery_time'       => $gzd_product->get_delivery_time_html(),
			'unit_price'          => $gzd_product->get_unit_price_html(),
			'product_units'       => $gzd_product->get_unit_product_html(),
			'tax_info'            => $gzd_product->get_tax_info(),
			'shipping_costs_info' => $gzd_product->get_shipping_costs_html(),
		) );

		return $options;
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_order_success_text' ) ) {

	/**
	 * Manipulate order success text
	 */
	function woocommerce_gzd_template_order_success_text( $text ) {
		return ( get_option( 'woocommerce_gzd_order_success_text' ) ? get_option( 'woocommerce_gzd_order_success_text' ) : $text );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_loop_add_to_cart' ) ) {

	/**
	 * Custom add to cart button
	 */
	function woocommerce_gzd_template_loop_add_to_cart( $text, $product ) {
		return sprintf(
			'<a href="%s" class="button">%s</a>',
			esc_attr( $product->get_permalink() ),
			esc_html( get_option( 'woocommerce_gzd_display_listings_link_details_text' ) )
		);
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_order_submit' ) ) {

	/**
	 * Adds custom order submit template (at the end of checkout)
	 */
	function woocommerce_gzd_template_order_submit( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'checkout'          => WC()->checkout(),
			'order_button_text' => apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) ),
			'include_nonce'     => false,
		) );

		wc_get_template( 'checkout/order-submit.php', $args );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_order_submit_fallback' ) ) {

	function woocommerce_gzd_template_order_submit_fallback() {
		if ( ! did_action( 'woocommerce_checkout_order_review' ) && apply_filters( 'woocommerce_gzd_insert_order_submit_fallback', true ) ) {
			woocommerce_gzd_template_order_submit();
		}
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_order_pay_now_button' ) ) {

	/**
	 * Pay now button on success page
	 */
	function woocommerce_gzd_template_order_pay_now_button( $order_id ) {

		$show = ( isset( $_GET['retry'] ) && $_GET['retry'] );

		/**
		 * Filter to allow disabling the pay now button.
		 *
		 * @param bool $show Whether to show or hide the button.
		 * @param int $order_id The order id.
		 *
		 * @since 1.0.0
		 *
		 */
		if ( apply_filters( 'woocommerce_gzd_show_pay_now_button', $show, $order_id ) ) {
			WC_GZD_Checkout::instance()->add_payment_link( $order_id );
		}
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_set_order_button_remove_filter' ) ) {

	/**
	 * Temporarily add a filter which removes order button html (that's how we get the order button at the end of checkout since WC 2.3)
	 */
	function woocommerce_gzd_template_set_order_button_remove_filter() {
		add_filter( 'woocommerce_order_button_html', 'woocommerce_gzd_template_button_temporary_hide', 1500 );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_button_temporary_hide' ) ) {

	/**
	 * Filter which temporarily sets order button html to false (stop displaying)
	 */
	function woocommerce_gzd_template_button_temporary_hide( $text ) {
		return false;
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_set_order_button_show_filter' ) ) {

	/**
	 * Remove the order button html filter after payment.php has been parsed
	 */
	function woocommerce_gzd_template_set_order_button_show_filter() {
		remove_filter( 'woocommerce_order_button_html', 'woocommerce_gzd_template_button_temporary_hide', 1500 );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_set_wc_terms_hide' ) ) {

	function woocommerce_gzd_template_set_wc_terms_hide( $show ) {
		return false;
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_forwarding_fee_notice' ) ) {

	function woocommerce_gzd_template_checkout_forwarding_fee_notice() {

		$gateways = WC()->payment_gateways()->get_available_payment_gateways();

		if ( ! ( $key = WC()->session->get( 'chosen_payment_method' ) ) || ! isset( $gateways[ $key ] ) ) {
			return;
		}

		$gateway = $gateways[ $key ];

		if ( $gateway->get_option( 'forwarding_fee' ) ) {
			/**
			 * Filter to adjust the forwarding fee checkout notice.
			 *
			 * @param string $html The notice.
			 *
			 * @since 1.0.0
			 *
			 */
			echo apply_filters( 'woocommerce_gzd_forwarding_fee_checkout_text', '<tr><td colspan="2">' . sprintf( __( 'Plus %s forwarding fee (charged by the transport agent)', 'woocommerce-germanized' ), wc_price( $gateway->get_option( 'forwarding_fee' ) ) ) . '</td></tr>' );
		}
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_maybe_hide_delivery_time' ) ) {

	function woocommerce_gzd_template_maybe_hide_delivery_time( $hide, $product ) {
		$types = get_option( 'woocommerce_gzd_display_delivery_time_hidden_types', array() );

		if ( ! empty( $types ) && wc_gzd_product_matches_extended_type( $types, $product ) ) {
			return true;
		}

		return $hide;
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_maybe_hide_shipping_costs' ) ) {

	function woocommerce_gzd_template_maybe_hide_shipping_costs( $hide, $product ) {
		$types = get_option( 'woocommerce_gzd_display_shipping_costs_hidden_types', array() );

		if ( wc_gzd_product_matches_extended_type( $types, $product ) ) {
			return true;
		}

		return $hide;

	}

}

if ( ! function_exists( 'woocommerce_gzd_template_digital_delivery_time_text' ) ) {

	function woocommerce_gzd_template_digital_delivery_time_text( $text, $product ) {

		if ( $product->is_downloadable() && get_option( 'woocommerce_gzd_display_digital_delivery_time_text' ) !== '' ) {
			/**
			 * Filter to adjust delivery time text for digital products.
			 *
			 * @param string $html The notice.
			 * @param WC_Product $product The product object.
			 *
			 * @since 1.6.3
			 *
			 */
			return apply_filters( 'woocommerce_germanized_digital_delivery_time_text', get_option( 'woocommerce_gzd_display_digital_delivery_time_text' ), $product );
		}

		return $text;

	}
}

if ( ! function_exists( 'woocommerce_gzd_template_sale_price_label_html' ) ) {

	function woocommerce_gzd_template_sale_price_label_html( $price, $product ) {

		if ( ! is_product() && get_option( 'woocommerce_gzd_display_listings_sale_price_labels' ) === 'no' ) {
			return $price;
		} elseif ( is_product() && get_option( 'woocommerce_gzd_display_product_detail_sale_price_labels' ) === 'no' ) {
			return $price;
		}

		return wc_gzd_get_product( $product )->add_labels_to_price_html( $price );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_small_business_total_vat_notice' ) ) {

	function woocommerce_gzd_template_small_business_total_vat_notice( $total ) {
		return $total . ' <span class="includes_tax wc-gzd-small-business-includes-tax">' . __( 'incl. VAT', 'woocommerce-germanized' ) . '</span>';
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_small_business_mini_cart_vat_notice' ) ) {

	function woocommerce_gzd_template_small_business_mini_cart_vat_notice() {
		echo ' <span class="includes_tax wc-gzd-small-business-includes-tax">' . __( 'incl. VAT', 'woocommerce-germanized' ) . '</span>';
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_differential_taxation_notice_cart' ) ) {

	function woocommerce_gzd_template_differential_taxation_notice_cart() {
		$contains_differentail_taxation = wc_gzd_cart_contains_differential_taxed_product();

		if ( $contains_differentail_taxation ) {
			wc_get_template( 'checkout/differential-taxation-notice.php', array( 'notice' => wc_gzd_get_differential_taxation_checkout_notice() ) );
		}
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_order_item_hooks' ) ) {

	function woocommerce_gzd_template_order_item_hooks() {
		add_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_units', wc_gzd_get_hook_priority( 'order_product_units' ), 3 );
		add_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_delivery_time', wc_gzd_get_hook_priority( 'order_product_delivery_time' ), 3 );
		add_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_item_desc', wc_gzd_get_hook_priority( 'order_product_item_desc' ), 3 );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_mini_cart_taxes' ) ) {

	function woocommerce_gzd_template_mini_cart_taxes() {
		$hidden_for_types           = get_option( 'woocommerce_gzd_display_shipping_costs_hidden_types', array() );
		$show_shipping              = empty( $hidden_for_types ) ? true : false;
		$show_differential_taxation = ( 'yes' === get_option( 'woocommerce_gzd_differential_taxation_checkout_notices' ) ? wc_gzd_cart_contains_differential_taxed_product() : false );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( $_product = $cart_item['data'] ) {
				if ( ! wc_gzd_product_matches_extended_type( $hidden_for_types, $_product ) ) {
					$show_shipping = true;
				}
			}
		}

		// Do only show shipping notice if shipping costs are > 0
		if ( is_callable( array( WC()->cart, 'get_shipping_total' ) ) ) {
			if ( WC()->cart->get_shipping_total() <= 0 ) {
				$show_shipping = false;
			}
		}

		wc_get_template( 'cart/mini-cart-totals.php', array(
				/**
				 * Filter that allows disabling tax notices within mini cart.
				 *
				 * @param bool $enable Whether to enable or not.
				 *
				 * @since 2.0.2
				 *
				 */
				'taxes'               => ( apply_filters( 'woocommerce_gzd_show_mini_cart_totals_taxes', true ) ) ? wc_gzd_get_cart_total_taxes( false ) : array(),

				/**
				 * Filter that allows disabling shipping costs notice within mini cart.
				 *
				 * @param bool $enable Whether to enable or not.
				 *
				 * @since 2.0.2
				 *
				 */
				'shipping_costs_info' => ( apply_filters( 'woocommerce_gzd_show_mini_cart_totals_shipping_costs_notice', $show_shipping ) ) ? wc_gzd_get_shipping_costs_text() : '',

				/**
				 * Filter that allows disabling differential taxation notice within mini cart.
				 *
				 * @param bool $enable Whether to enable or not.
				 *
				 * @since 2.0.2
				 *
				 */
				'differential_taxation_info' => ( apply_filters( 'woocommerce_gzd_show_mini_cart_totals_differential_taxation_notice', $show_differential_taxation ) ) ? wc_gzd_get_differential_taxation_checkout_notice() : ''
			)
		);
	}

}

if ( ! function_exists( 'wc_gzd_template_empty_wc_privacy_policy_text' ) ) {

	function wc_gzd_template_empty_wc_privacy_policy_text( $text, $type ) {

		// Lets check if Germanized takes care of displaying the legal checkboxes
		if ( did_action( 'woocommerce_gzd_before_legal_checkbox_terms' ) || did_action( 'woocommerce_gzd_before_legal_checkbox_privacy' ) ) {
			return '';
		}

		return $text;
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_render_checkout_checkboxes' ) ) {

	function woocommerce_gzd_template_render_checkout_checkboxes() {
		WC_GZD_Legal_Checkbox_Manager::instance()->render( 'checkout' );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_render_register_checkboxes' ) ) {

	function woocommerce_gzd_template_render_register_checkboxes() {
		WC_GZD_Legal_Checkbox_Manager::instance()->render( 'register' );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_render_pay_for_order_checkboxes' ) ) {

	function woocommerce_gzd_template_render_pay_for_order_checkboxes() {
		WC_GZD_Legal_Checkbox_Manager::instance()->render( 'pay_for_order' );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_render_review_checkboxes' ) ) {

	function woocommerce_gzd_template_render_review_checkboxes( $html, $args ) {
		global $post;

		if ( ! $post || $post->post_type !== 'product' ) {
			return $html;
		}

		$manager       = WC_GZD_Legal_Checkbox_Manager::instance();
		$checkbox_html = '';

		ob_start();
		$manager->render( 'reviews' );
		$checkbox_html .= ob_get_clean();

		return $checkbox_html . $html;
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_product_widget_filters_start' ) ) {

	function woocommerce_gzd_template_product_widget_filters_start( $args ) {
		remove_filter( 'woocommerce_get_price_html', 'woocommerce_gzd_template_product_blocks', 50 );
		add_filter( 'woocommerce_get_price_html', 'woocommerce_gzd_template_product_widget_price_html', 100, 2 );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_product_widget_filters_end' ) ) {

	function woocommerce_gzd_template_product_widget_filters_end( $args ) {
		remove_filter( 'woocommerce_get_price_html', 'woocommerce_gzd_template_product_widget_price_html', 100 );
		add_filter( 'woocommerce_get_price_html', 'woocommerce_gzd_template_product_blocks', 50, 2 );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_product_widget_price_html' ) ) {

	function woocommerce_gzd_template_product_widget_price_html( $html, $product ) {
		$html = woocommerce_gzd_template_add_price_html_suffixes( $html, $product, array(), 'product_widget' );

		return $html;
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_mini_cart_remove_hooks' ) ) {

	function woocommerce_gzd_template_mini_cart_remove_hooks() {

		if ( ! did_action( 'woocommerce_before_mini_cart_contents' ) ) {
			return;
		}

		/**
		 * Remove cart hooks to prevent duplicate notices
		 */
		foreach ( wc_gzd_get_cart_shopmarks() as $shopmark ) {
			$shopmark->remove();
		}
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_mini_cart_add_hooks' ) ) {

	function woocommerce_gzd_template_mini_cart_add_hooks() {

		/**
		 * This filter serves to manually disable mini cart item legal details.
		 *
		 * @param bool $disable Whether to disable or not.
		 *
		 * @since 2.2.11
		 *
		 */
		if ( apply_filters( 'woocommerce_gzd_disable_mini_cart_item_legal_details', false ) ) {
			return;
		}

		foreach ( wc_gzd_get_mini_cart_shopmarks() as $shopmark ) {
			$shopmark->execute();
		}
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_mini_cart_maybe_remove_hooks' ) ) {

	function woocommerce_gzd_template_mini_cart_maybe_remove_hooks() {

		if ( ! did_action( 'woocommerce_before_mini_cart_contents' ) ) {
			return;
		}

		/**
		 * Remove mini cart hooks after mini cart rendering finished
		 */
		foreach ( wc_gzd_get_mini_cart_shopmarks() as $shopmark ) {
			$shopmark->remove();
		}

		/**
		 * Readd cart hooks to make sure they are placed accordingly.
		 */
		if ( is_cart() ) {
			foreach ( wc_gzd_get_cart_shopmarks() as $shopmark ) {
				$shopmark->execute();
			}
		}
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_product_blocks' ) ) {

	function woocommerce_gzd_template_product_blocks( $price_html, $product ) {

		$post = get_post();

		if ( $post && wc_gzd_post_has_woocommerce_block( $post->post_content ) ) {
			$price_html = woocommerce_gzd_template_add_price_html_suffixes( $price_html, $product );
		}

		return $price_html;
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_add_price_html_suffixes' ) ) {

	function woocommerce_gzd_template_add_price_html_suffixes( $price_html, $org_product, $args = array(), $location = 'product_widget' ) {
		global $product;

		$old_product = false;

		if ( $product && is_a( $product, 'WC_Product' ) ) {
			$old_product = $product;
		}

		$product = $org_product;

		$args = wp_parse_args( $args, array(
			'price_unit'          => array(
				'show'     => wc_string_to_bool( get_option( "woocommerce_gzd_display_{$location}_unit_price", true ) ),
				'priority' => 10,
			),
			'tax_info'            => array(
				'show'     => wc_string_to_bool( get_option( "woocommerce_gzd_display_{$location}_tax_info", true ) ),
				'priority' => 20,
			),
			'shipping_costs_info' => array(
				'show'     => wc_string_to_bool( get_option( "woocommerce_gzd_display_{$location}_shipping_costs", true ) ),
				'priority' => 30,
			),
			'product_units'       => array(
				'show'     => wc_string_to_bool( get_option( "woocommerce_gzd_display_{$location}_product_units", false ) ),
				'priority' => 40,
			),
			'delivery_time_info'  => array(
				'show'     => wc_string_to_bool( get_option( "woocommerce_gzd_display_{$location}_delivery_time", true ) ),
				'priority' => 50,
			),
		) );

		/**
		 * In some cases (e.g. product widgets) Germanized has to add legal information
		 * as a suffix because no other filters exist. This filter serves to decide which
		 * info to append and in which order.
		 *
		 * @param array $args The data to be appended.
		 * @param string $location The location e.g. product_widget.
		 *
		 * @since 2.2.0
		 *
		 */
		$args = apply_filters( 'woocommerce_gzd_template_add_price_html_suffixes_args', $args, $location );

		// Re-order tabs by priority.
		if ( ! function_exists( '_sort_priority_callback' ) ) {
			/**
			 * Sort Priority Callback Function
			 *
			 * @param array $a Comparison A.
			 * @param array $b Comparison B.
			 *
			 * @return bool
			 */
			function _sort_priority_callback( $a, $b ) {
				if ( ! isset( $a['priority'], $b['priority'] ) || $a['priority'] === $b['priority'] ) {
					return 0;
				}

				return ( $a['priority'] < $b['priority'] ) ? - 1 : 1;
			}
		}

		uasort( $args, '_sort_priority_callback' );

		$suffix = '';

		foreach ( $args as $method_suffix => $options ) {
			if ( ! $options['show'] ) {
				continue;
			}

			$method_name = $method_suffix;

			if ( function_exists( "woocommerce_gzd_template_single_{$method_suffix}" ) ) {
				$method_name = "woocommerce_gzd_template_single_{$method_suffix}";
			}

			if ( function_exists( $method_name ) ) {
				ob_start();
				$method_name();
				$suffix .= ob_get_clean();
			}
		}

		/**
		 * Filter that allows adjusting the HTML suffix for product widgets.
		 *
		 * @param string $html The suffix.
		 * @param array $args The data which was appended.
		 * @param string $location The location.
		 *
		 * @since 2.2.0
		 *
		 */
		$suffix = apply_filters( 'woocommerce_gzd_template_add_price_html_suffix', $suffix, $args, $location );

		$new_html = $price_html . $suffix;

		// Restore old global variable
		if ( $old_product ) {
			$product = $old_product;
		}

		return $new_html;
	}
}

?>