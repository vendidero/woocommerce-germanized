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
		if ( in_array( $product->product_type, array( 'simple', 'external' ) ) )
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

if ( ! function_exists( 'woocommerce_gzd_proceed_to_checkout_fallback' ) ) {

	/**
	 * Display proceed to checkout for older version of WooCommerce
	 */
	function woocommerce_gzd_proceed_to_checkout_fallback() {
		$checkout_url = WC()->cart->get_checkout_url();
		?>
		<a href="<?php echo $checkout_url; ?>" class="checkout-button button alt wc-forward"><?php _e( 'Proceed to Checkout', 'woocommerce' ); ?></a>
		<?php
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
		echo '<tr><td colspan="5" class="actions"><a class="button" href="' . WC()->cart->get_cart_url() . '">' . __( 'Edit Order', 'woocommerce-germanized' ) . '</a></td></tr>';
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
		echo '<p class="form-row legal terms"><label class="checkbox" for="legal">' . ( get_option( 'woocommerce_gzd_display_checkout_legal_no_checkbox' ) == 'no' ? '<input type="checkbox" class="input-checkbox" name="legal" id="legal" />' : '' ) . ' ' . wc_gzd_get_legal_text() . '</label></p>';
	}

}

if ( ! function_exists( 'woocommerce_gzd_digital_checkbox' ) ) {

	function woocommerce_gzd_digital_checkbox() {
		$items = WC()->cart->get_cart();
		$is_downloadable = false;
		if ( ! empty( $items ) ) {
			foreach ( $items as $cart_item_key => $values ) {
				$_product = apply_filters( 'woocommerce_cart_item_product', $values[ 'data' ], $values, $cart_item_key );
				if ( $_product->is_downloadable() )
					$is_downloadable = true;
			}
		}
		if ( $is_downloadable ) {
			echo '<p class="form-row data-download terms">
				<input type="checkbox" class="input-checkbox" name="download-revocate" id="data-download" />
				<label for="data-download" class="checkbox">' . wc_gzd_get_legal_text_digital() . '</label>
			</p>';
		}
	}

}

if ( ! function_exists( 'woocommerce_gzd_checkout_validation' ) ) {

	/**
	 * Validate checkbox data
	 */
	function woocommerce_gzd_checkout_validation( $posted ) {
		if ( ! isset( $_POST['woocommerce_checkout_update_totals'] ) ) {
			if ( ! isset( $_POST[ 'legal' ] ) && get_option( 'woocommerce_gzd_display_checkout_legal_no_checkbox' ) == 'no' )
				wc_add_notice( wc_gzd_get_legal_text_error(), 'error' );
			// Check if cart contains downloadable product
			$items = WC()->cart->get_cart();
			$is_downloadable = false;
			if ( ! empty( $items ) && get_option( 'woocommerce_gzd_checkout_legal_digital_checkbox' ) == 'yes' ) {
				foreach ( $items as $cart_item_key => $values ) {
					$_product = apply_filters( 'woocommerce_cart_item_product', $values[ 'data' ], $values, $cart_item_key );
					if ( $_product->is_downloadable() )
						$is_downloadable = true;
				}
			}
			if ( $is_downloadable && ! isset( $_POST[ 'download-revocate' ] ) )
				wc_add_notice( __( 'To get immediate access to digital content you have to agree to the losal of your right to cancel.', 'woocommerce-germanized' ), 'error' );
		}
	}

}

if ( ! function_exists( 'woocommerce_gzd_remove_term_checkbox' ) ) {

	/**
	 * Removes default term checkbox
	 */
	function woocommerce_gzd_remove_term_checkbox() {
		return false;
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

if ( ! function_exists( 'woocommerce_gzd_template_checkout_thankyou_trusted_shops' ) ) {

	/**
	 * Add Trusted Shops template to order success
	 */
	function woocommerce_gzd_template_checkout_thankyou_trusted_shops( $order_id ) {
		wc_get_template( 'trusted-shops/thankyou.php', array( 'order_id' => $order_id ) );
	}

}

if ( ! function_exists( 'woocommerce_gzd_add_variation_options' ) ) {

	/**
	 * Add delivery time and unit price to variations
	 */
	function woocommerce_gzd_add_variation_options( $options, $product, $variation ) {
		$options[ 'delivery_time' ] = $variation->gzd_product->get_delivery_time_html();
		$options[ 'unit_price' ] = $variation->gzd_product->get_unit_html();
		$options[ 'tax_info' ] = $variation->gzd_product->get_tax_info();
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
		WC_GZD_Checkout::instance()->add_payment_link( $order_id );
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

?>