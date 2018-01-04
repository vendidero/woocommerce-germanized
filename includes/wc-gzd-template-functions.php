<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Template functions
 *
 * @author 		Vendidero
 * @version     1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists( 'woocommerce_gzd_template_single_legal_info' ) ) {

	/**
	 * Single Product price per unit.
	 */
	function woocommerce_gzd_template_single_legal_info() {
		global $product;
		wc_get_template( 'single-product/legal-info.php' );
	}
}

if ( ! function_exists( 'woocommerce_gzd_template_single_price_unit' ) ) {

	/**
	 * Single Product price per unit.
	 */
	function woocommerce_gzd_template_single_price_unit() {
		global $product;
		if ( in_array( $product->get_type(), apply_filters( 'woocommerce_gzd_product_types_supporting_unit_prices', array( 'simple', 'external', 'variable' ) ) ) )
			wc_get_template( 'single-product/price-unit.php' );
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
		echo '<tr><td colspan="5" class="actions"><a class="button" href="' . wc_gzd_get_cart_url() . '">' . __( 'Edit Order', 'woocommerce-germanized' ) . '</a></td></tr>';
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_payment_title' ) ) {

	/**
	 * Checkout payment gateway title
	 */
	function woocommerce_gzd_template_checkout_payment_title() {
		echo '<h3 id="order_payment_heading">' . __( 'Choose a Payment Gateway', 'woocommerce-germanized' ) . '</h3>';
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_legal' ) ) {

	/**
	 * text legal info within checkout (may contain checkbox)
	 */
	function woocommerce_gzd_template_checkout_legal() {
		wc_get_template( 'checkout/terms.php', array( 'gzd_checkbox' => true ) );
	}

}

if ( ! function_exists( 'woocommerce_gzd_digital_checkbox' ) ) {

	function woocommerce_gzd_digital_checkbox() {
		
		$items = WC()->cart->get_cart();
		$is_downloadable = false;
		
		if ( ! empty( $items ) ) {
		
			foreach ( $items as $cart_item_key => $values ) {
				$_product = apply_filters( 'woocommerce_cart_item_product', $values[ 'data' ], $values, $cart_item_key );
				if ( wc_gzd_is_revocation_exempt( $_product ) ) {
					$is_downloadable = true;
				}
			}

		}
		
		if ( $is_downloadable )
			wc_get_template( 'checkout/terms-digital.php' );
	}

}

if ( ! function_exists( 'woocommerce_gzd_service_checkbox' ) ) {

	function woocommerce_gzd_service_checkbox() {
		
		$items = WC()->cart->get_cart();
		$is_service = false;
		
		if ( ! empty( $items ) ) {
		
			foreach ( $items as $cart_item_key => $values ) {
				$_product = apply_filters( 'woocommerce_cart_item_product', $values[ 'data' ], $values, $cart_item_key );
				
				if ( wc_gzd_is_revocation_exempt( $_product, 'service' ) )
					$is_service = true;
			}

		}
		
		if ( $is_service )
			wc_get_template( 'checkout/terms-service.php' );
	}

}

if ( ! function_exists( 'woocommerce_gzd_parcel_delivery_checkbox' ) ) {

	function woocommerce_gzd_parcel_delivery_checkbox() {

		$rates  = wc_gzd_get_chosen_shipping_rates();
		$ids    = array();
		$titles = array();

		foreach ( $rates as $rate ) {

			array_push( $ids, $rate->id );

			if ( method_exists( $rate, 'get_label' ) ) {
				array_push( $titles, $rate->get_label() );
			} else {
				array_push( $titles, $rate->label );
			}
		}

		wc_get_template( 'checkout/terms-parcel-delivery.php', array(
			'titles' => $titles,
			'show'   => wc_gzd_is_parcel_delivery_data_transfer_checkbox_enabled( $ids )
		) );
	}
}

if ( ! function_exists( 'woocommerce_gzd_refresh_parcel_delivery_checkbox_fragment' ) ) {

	function woocommerce_gzd_refresh_parcel_delivery_checkbox_fragment( $fragments ) {

		ob_start();
		woocommerce_gzd_parcel_delivery_checkbox();
		$delivery_checkbox = ob_get_clean();

		$fragments[ '.data-parcel-delivery' ] = $delivery_checkbox;

		return $fragments;
	}

}

if ( ! function_exists( 'woocommerce_gzd_checkout_validation' ) ) {

	/**
	 * Validate checkbox data
	 */
	function woocommerce_gzd_checkout_validation( $posted ) {
		if ( ! isset( $_POST[ 'woocommerce_checkout_update_totals' ] ) ) {
			
			if ( ! isset( $_POST[ 'legal' ] ) && get_option( 'woocommerce_gzd_display_checkout_legal_no_checkbox' ) == 'no' )
				wc_add_notice( wc_gzd_get_legal_text_error(), 'error' );
			
			// Check if cart contains downloadable product
			$items = WC()->cart->get_cart();
			$is_downloadable = false;
			$is_service = false;
			
			if ( ! empty( $items ) && ( get_option( 'woocommerce_gzd_checkout_legal_digital_checkbox' ) === 'yes' || get_option( 'woocommerce_gzd_checkout_legal_service_checkbox' ) === 'yes' ) ) {
			
				foreach ( $items as $cart_item_key => $values ) {
			
					$_product = apply_filters( 'woocommerce_cart_item_product', $values[ 'data' ], $values, $cart_item_key );
			
					if ( wc_gzd_is_revocation_exempt( $_product ) )
						$is_downloadable = true;

					if ( wc_gzd_is_revocation_exempt( $_product, 'service' ) )
						$is_service = true;

				}
			}
			
			if ( get_option( 'woocommerce_gzd_checkout_legal_digital_checkbox' ) === 'yes' && $is_downloadable && ! isset( $_POST[ 'download-revocate' ] ) )
				wc_add_notice( wc_gzd_get_legal_text_digital_error(), 'error' );

			if ( get_option( 'woocommerce_gzd_checkout_legal_service_checkbox' ) === 'yes' && $is_service && ! isset( $_POST[ 'service-revocate' ] ) )
				wc_add_notice( wc_gzd_get_legal_text_service_error(), 'error' );

			if ( ( wc_gzd_is_parcel_delivery_data_transfer_checkbox_enabled( wc_gzd_get_chosen_shipping_rates( array( 'value' => 'id' ) ) ) && get_option( 'woocommerce_gzd_checkout_legal_parcel_delivery_checkbox_required' ) === 'yes' ) && ! isset( $_POST[ 'parcel-delivery' ] ) )
				wc_add_notice( __( 'Please accept our parcel delivery agreement', 'woocommerce-germanized' ), 'error' );
		}
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_set_terms_manually' ) ) {

	/**
	 * Set terms checkbox manually
	 */
	function woocommerce_gzd_template_checkout_set_terms_manually() {
		echo '<input type="hidden" name="terms" value="1" />';
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_table_content_replacement' ) ) {

	/**
	 * Replaces default review-order.php product table by gzd product table template (checkout/review-order-product-table.php).
	 * Adds filter to hide default review order product table output.
	 */
	function woocommerce_gzd_template_checkout_table_content_replacement() {
		wc_get_template( 'checkout/review-order-product-table.php' );
		add_filter( 'woocommerce_checkout_cart_item_visible', 'woocommerce_gzd_template_checkout_table_product_hide', PHP_INT_MAX );
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
		remove_filter( 'woocommerce_checkout_cart_item_visible', 'woocommerce_gzd_template_checkout_table_product_hide', PHP_INT_MAX );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_remove_cart_name_filter' ) ) {

	/**
	 * Removes the cart item name filter (using checkout quantity html) if within checkout
	 */
	function woocommerce_gzd_template_checkout_remove_cart_name_filter() {
		remove_filter( 'woocommerce_cart_item_name', 'wc_gzd_cart_product_units', wc_gzd_get_hook_priority( 'cart_product_units' ), 2 );
		remove_filter( 'woocommerce_cart_item_name', 'wc_gzd_cart_product_delivery_time', wc_gzd_get_hook_priority( 'cart_product_delivery_time' ), 2 );
		remove_filter( 'woocommerce_cart_item_name', 'wc_gzd_cart_product_item_desc', wc_gzd_get_hook_priority( 'cart_product_item_desc' ), 2 );
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

		$gzd_product = wc_gzd_get_gzd_product( $variation );

		$options = array_merge( $options, array(
			'delivery_time'         => '',
			'unit_price'            => '',
			'product_units'         => '',
			'tax_info'              => '',
			'shipping_costs_info'   => '',
		) );

		if ( get_option( 'woocommerce_gzd_display_product_detail_delivery_time' ) === 'yes' )
			$options[ 'delivery_time' ] 		= $gzd_product->get_delivery_time_html();

		if ( get_option( 'woocommerce_gzd_display_product_detail_unit_price' ) === 'yes' )
			$options[ 'unit_price' ] 			= $gzd_product->get_unit_html();

		if ( get_option( 'woocommerce_gzd_display_product_detail_product_units' ) === 'yes' )
			$options[ 'product_units' ] 		= $gzd_product->get_product_units_html();

		if ( get_option( 'woocommerce_gzd_display_product_detail_tax_info' ) === 'yes' )
			$options[ 'tax_info' ] 				= $gzd_product->get_tax_info();

		if ( get_option( 'woocommerce_gzd_display_product_detail_shipping_costs' ) === 'yes' )
			$options[ 'shipping_costs_info' ] 	= $gzd_product->get_shipping_costs_html();

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
	function woocommerce_gzd_template_order_submit() {
		wc_get_template( 'checkout/order-submit.php', array(
			'checkout'           => WC()->checkout(),
			'order_button_text'  => apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) )
		) );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_order_pay_now_button' ) ) {
	
	/**
	 * Pay now button on success page
	 */
	function woocommerce_gzd_template_order_pay_now_button( $order_id ) {

		$show = ( isset( $_GET[ 'retry' ] ) && $_GET[ 'retry' ] );

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
		add_filter( 'woocommerce_order_button_html', 'woocommerce_gzd_template_button_temporary_hide', PHP_INT_MAX );
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
		remove_filter( 'woocommerce_order_button_html', 'woocommerce_gzd_template_button_temporary_hide', PHP_INT_MAX );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_set_wc_terms_hide' ) ) {

	function woocommerce_gzd_template_set_wc_terms_hide( $show ) {
		return false;
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_customer_account_checkbox' ) ) {

	function woocommerce_gzd_template_customer_account_checkbox() {
		wc_get_template( 'myaccount/form-register-checkbox.php' );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_customer_account_checkbox_error' ) ) {

	function woocommerce_gzd_template_customer_account_checkbox_error( $validation_error, $username, $password, $email ) {
		
		if ( ! isset( $_POST[ 'privacy' ] ) && empty( $_POST[ 'privacy' ] ) )
			return new WP_Error( 'privacy', __( 'Please accept the creation of a new customer account', 'woocommerce-germanized' ) );

		return $validation_error;
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_checkout_forwarding_fee_notice' ) ) {

	function woocommerce_gzd_template_checkout_forwarding_fee_notice() {

		$gateways = WC()->payment_gateways()->get_available_payment_gateways();
		
		if ( ! ( $key = WC()->session->get('chosen_payment_method') ) || ! isset( $gateways[ $key ] ) )
			return;
		
		$gateway = $gateways[ $key ];

		if ( $gateway->get_option( 'forwarding_fee' ) )
			echo apply_filters( 'woocommerce_gzd_forwarding_fee_checkout_text', '<tr><td colspan="2">' . sprintf( __( 'Plus %s forwarding fee (charged by the transport agent)', 'woocommerce-germanized' ), wc_price( $gateway->get_option( 'forwarding_fee' ) ) ) . '</td></tr>' );
	
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_maybe_hide_delivery_time' ) ) {

	function woocommerce_gzd_template_maybe_hide_delivery_time( $hide, $product ) {

		$types = get_option( 'woocommerce_gzd_display_delivery_time_hidden_types', array() );

		if ( ! empty( $types ) && wc_gzd_product_matches_extended_type( $types, $product ) )
			return true;

		// Hide delivery time if product is not in stock
		if ( ! $product->is_in_stock() )
			return true;

		return $hide;

	}

}

if ( ! function_exists( 'woocommerce_gzd_template_maybe_hide_shipping_costs' ) ) {

	function woocommerce_gzd_template_maybe_hide_shipping_costs( $hide, $product ) {

		$types = get_option( 'woocommerce_gzd_display_shipping_costs_hidden_types', array() );

		if ( wc_gzd_product_matches_extended_type( $types, $product ) )
			return true;

		return $hide;

	}

}

if ( ! function_exists( 'woocommerce_gzd_template_digital_delivery_time_text' ) ) {

	function woocommerce_gzd_template_digital_delivery_time_text( $text, $product ) {

		if ( $product->is_downloadable() && get_option( 'woocommerce_gzd_display_digital_delivery_time_text' ) !== '' )
			return apply_filters( 'woocommerce_germanized_digital_delivery_time_text', get_option( 'woocommerce_gzd_display_digital_delivery_time_text' ), $product );

		return $text;

	}

}

if ( ! function_exists( 'woocommerce_gzd_template_sale_price_label_html' ) ) {

	function woocommerce_gzd_template_sale_price_label_html( $price, $product ) {

		if ( ! is_product() && get_option( 'woocommerce_gzd_display_listings_sale_price_labels' ) === 'no' )
			return $price;
		elseif ( is_product() && get_option( 'woocommerce_gzd_display_product_detail_sale_price_labels' ) === 'no' )
			return $price;

		return wc_gzd_get_gzd_product( $product )->add_labels_to_price_html( $price );
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_small_business_total_vat_notice' ) ) {

	function woocommerce_gzd_template_small_business_total_vat_notice( $total ) {
		return $total . ' <span class="includes_tax wc-gzd-small-business-includes-tax">' . __( 'incl. VAT', 'woocommerce-germanized' ) . '</span>';
	}

}

if ( ! function_exists( 'woocommerce_gzd_template_differential_taxation_notice_cart' ) ) {

	function woocommerce_gzd_template_differential_taxation_notice_cart() {
		$cart = WC()->cart;
		$contains_differentail_taxation = false;

		foreach( $cart->get_cart() as $cart_item_key => $values ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $values[ 'data' ], $values, $cart_item_key );

				if ( wc_gzd_get_gzd_product( $_product )->is_differential_taxed() ) {
				$contains_differentail_taxation = true;
				break;
			}
		}

		if ( $contains_differentail_taxation ) {

			$mark = apply_filters( 'woocommerce_gzd_differential_taxation_notice_text_mark', '** ' );
			$notice = apply_filters( 'woocommerce_gzd_differential_taxation_notice_text_checkout', $mark . wc_gzd_get_differential_taxation_notice_text() );

			wc_get_template( 'checkout/differential-taxation-notice.php', array( 'notice' => $notice ) );
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
		wc_get_template( 'cart/mini-cart-totals.php', array(
			'taxes' => apply_filters( 'woocommerce_gzd_show_mini_cart_totals_taxes', true ) ? wc_gzd_get_cart_total_taxes( false ) : array(),
			'shipping_costs_info' => apply_filters( 'woocommerce_gzd_show_mini_cart_totals_shipping_costs_notice', true ) ? wc_gzd_get_shipping_costs_text() : '' )
		);
	}

}

?>