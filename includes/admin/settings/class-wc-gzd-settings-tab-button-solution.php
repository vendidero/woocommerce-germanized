<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Button Solution settings.
 *
 * @class        WC_GZD_Settings_Tab_Emails
 * @version        3.0.0
 * @author        Vendidero
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

	public function get_help_link() {
		return 'https://vendidero.de/dokument/umsetzung-der-button-loesung-im-woocommerce-checkout';
	}

	public function get_pointers() {
		$current  = $this->get_current_section();
		$pointers = array();

		if ( '' === $current ) {
			$pointers = array(
				'pointers' => array(
					'default' => array(
						'target'       => '#woocommerce_gzd_order_submit_btn_text',
						'next'         => 'color',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Buy now button', 'woocommerce-germanized' ) . '</h3><p>' . esc_html__( 'These settings help you comply to the button solution. The buy now button text is forced and static so that no payment gateway might override it.', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'color'   => array(
						'target'       => '#woocommerce_gzd_display_checkout_table_color',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-shipments&tutorial=yes' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Product table background', 'woocommerce-germanized' ) . '</h3><p>' . esc_html__( 'The product table within your checkout should be noticeable for your customers. You might want to choose a different background color for it.', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		}

		return $pointers;
	}

	public function get_tab_settings( $current_section = '' ) {
		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'button_solution_options',
			),
			array(
				'title'    => __( 'Button Text', 'woocommerce-germanized' ),
				'desc'     => __( 'This text serves as Button text for the Order Submit Button.', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'       => 'woocommerce_gzd_order_submit_btn_text',
				'type'     => 'text',
				'default'  => __( 'Buy Now', 'woocommerce-germanized' ),
			),
			array(
				'title'    => __( 'Product attributes', 'woocommerce-germanized' ),
				'desc'     => __( 'List all product attributes during cart and checkout.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_display_checkout_product_attributes',
				'default'  => 'no',
				'type'     => 'gzd_toggle',
				'desc_tip' => __( 'This option forces WooCommerce to output a list of all product attributes during cart and checkout.', 'woocommerce-germanized' ),
			),
			array(
				'title'   => __( 'Back to cart', 'woocommerce-germanized' ),
				'desc'    => __( 'Add a back to cart button to the checkout table.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . __( 'This button may let your customer edit their order before submitting. Some people state that this button should be hidden to avoid legal problems.', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'woocommerce_gzd_display_checkout_back_to_cart_button',
				'default' => 'no',
				'type'    => 'gzd_toggle',
			),
			array(
				'title'    => __( 'Edit data notice', 'woocommerce-germanized' ),
				'desc'     => __( 'Display an edit-your-data notice within checkout.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_display_checkout_edit_data_notice',
				'default'  => 'no',
				'type'     => 'gzd_toggle',
				'desc_tip' => __( 'This notice will be added right before the order comments field.', 'woocommerce-germanized' ),
			),
			array(
				'title'    => __( 'Product Table Color', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_display_checkout_table_color',
				'desc_tip' => __( 'Choose the color of your checkout product table. This table should be highlighted within your checkout page.', 'woocommerce-germanized' ),
				'default'  => '#eeeeee',
				'type'     => 'color',
			),
			array(
				'title'    => __( 'Thumbnails', 'woocommerce-germanized' ),
				'desc'     => __( 'Show product thumbnails within checkout table.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_display_checkout_thumbnails',
				'default'  => 'yes',
				'type'     => 'gzd_toggle',
				'desc_tip' => __( 'Uncheck if you don\'t want to show your product thumbnails within checkout table.', 'woocommerce-germanized' ),
			),
			array(
				'title'    => __( 'Hide Shipping Select', 'woocommerce-germanized' ),
				'desc'     => __( 'Hide shipping rate selection from checkout.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_display_checkout_shipping_rate_select',
				'default'  => 'no',
				'type'     => 'gzd_toggle',
				'desc_tip' => __( 'This option will hide shipping rate selection from checkout. By then customers will only be able to change their shipping rate on cart page.', 'woocommerce-germanized' ),
			),
			array(
				'title'    => __( 'Estimated taxes', 'woocommerce-germanized' ),
				'desc'     => __( 'Hide the "taxes and shipping estimated" text from the cart.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_display_hide_cart_tax_estimated',
				'default'  => 'yes',
				'type'     => 'gzd_toggle',
				'desc_tip' => __( 'By default WooCommerce adds a "taxes and shipping estimated" text to your cart. This might puzzle your customers and may not meet german law.', 'woocommerce-germanized' ),
			),
			array(
				'title'   => __( 'Fallback Mode', 'woocommerce-germanized' ),
				'desc'    => __( 'Force default WooCommerce checkout template.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . __( 'If you are facing problems within your checkout e.g. legally relevant data is not showing (terms, delivery time, unit price etc.) your theme seems to be incompatible (not using default WooCommerce hooks and filters). As a workaround you may use this fallback which ensures default review-order.php and form-checkout.php is used.', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'woocommerce_gzd_display_checkout_fallback',
				'default' => 'no',
				'type'    => 'gzd_toggle',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'button_solution_options',
			),

			array(
				'title' => __( 'Thankyou Page', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'thankyou_options',
			),

			array(
				'title'   => __( 'Information', 'woocommerce-germanized' ),
				'desc'    => __( 'Hide product table and customer data on order thankyou page.', 'woocommerce-germanized' ),
				'id'      => 'woocommerce_gzd_hide_order_success_details',
				'type'    => 'gzd_toggle',
				'default' => 'no',
			),
			array(
				'title'    => __( 'Order Success Text', 'woocommerce-germanized' ),
				'desc'     => __( 'Choose a custom text to display on order success page.', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'css'      => 'width:100%; height: 65px;',
				'id'       => 'woocommerce_gzd_order_success_text',
				'type'     => 'textarea',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'thankyou_options',
			),
		);
	}
}
