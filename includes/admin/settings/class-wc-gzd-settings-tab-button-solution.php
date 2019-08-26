<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Button Solution settings.
 *
 * @class 		WC_GZD_Settings_Tab_Emails
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Tab_Button_Solution extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'These settings will help you to make sure your checkout complies with the button solution.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Button Solution', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'button_solution';
	}

	public function get_tab_settings( $current_section = '' ) {
		return array(
			array( 'title' => '', 'type' => 'title', 'id' => 'button_solution_options' ),

			array(
				'title' 	=> __( 'Button Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text serves as Button text for the Order Submit Button.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_order_submit_btn_text',
				'type' 		=> 'text',
				'default'	=> __( 'Buy Now', 'woocommerce-germanized' ),
			),
			array(
				'title' 	=> __( 'Product attributes', 'woocommerce-germanized' ),
				'desc' 		=> __( 'List all product attributes during cart and checkout.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_product_attributes',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'desc_tip'	=> __( 'This option forces WooCommerce to output a list of all product attributes during cart and checkout.', 'woocommerce-germanized' ),
			),
			array(
				'title' 	=> __( 'Back to cart', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Add a back to cart button to the checkout table.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_back_to_cart_button',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'desc_tip'	=> __( 'This button may let your customer edit their order before submitting. Some people state that this button should be hidden to avoid legal problems.', 'woocommerce-germanized' ),
			),
			array(
				'title' 	=> __( 'Edit data notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Display a edit-your-data notice within checkout.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_edit_data_notice',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'desc_tip'	=> __( 'This notice will be added right before the order comments field.', 'woocommerce-germanized' ),
			),
			array(
				'title' 	=> __( 'Product Table Color', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_table_color',
				'desc_tip'	=> __( 'Choose the color of your checkout product table. This table should be highlighted within your checkout page.', 'woocommerce-germanized' ),
				'default'	=> '#eeeeee',
				'type' 		=> 'color',
			),
			array(
				'title' 	=> __( 'Thankyou Page', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Hide product table and customer data on order thankyou page.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_hide_order_success_details',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'no',
			),
			array(
				'title' 	=> __( 'Order Success Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a custom text to display on order success page.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_order_success_text',
				'type' 		=> 'textarea',
			),

			array( 'type' => 'sectionend', 'id' => 'button_solution_options' ),
		);
	}
}