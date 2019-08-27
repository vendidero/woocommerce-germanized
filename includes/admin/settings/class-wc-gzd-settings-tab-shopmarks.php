<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Shopmark settings.
 *
 * @class 		WC_GZD_Settings_Tab_Shopmarks
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Tab_Shopmarks extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Adjust shopmark related settings and adjust which labels shall be attached to your product data.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Shopmarks', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'shopmarks';
	}

	public function get_sections() {
		return array(
			''               => __( 'General', 'woocommerce-germanized' ),
			'delivery_times' => __( 'Delivery times', 'woocommerce-germanized' ),
			'unit_prices'    => __( 'Unit prices', 'woocommerce-germanized' ),
			'price_labels'   => __( 'Price labels', 'woocommerce-germanized' ),
		);
	}

	public function get_tab_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = $this->get_general_settings();
		} elseif( 'delivery_times' === $current_section ) {
			$settings = $this->get_delivery_time_settings();
		} elseif( 'unit_prices' === $current_section ) {
			$settings = $this->get_unit_price_settings();
		} elseif( 'price_labels' === $current_section ) {
			$settings = $this->get_price_label_settings();
		}

		return $settings;
	}

	protected function get_general_settings() {
		return array(
			array( 'title' => __( 'Shipping Costs', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'shipping_costs_options' ),

			array(
				'title' 	=> __( 'Notice Text', 'woocommerce-germanized' ),
				'desc' 		=> '<div class="wc-gzd-additional-desc">' . __( 'This text will be used to inform the customer about shipping costs. Use {link}{/link} to insert link to shipping costs page.', 'woocommerce-germanized' ) . '</div>',
				'desc_tip'	=> false,
				'id' 		=> 'woocommerce_gzd_shipping_costs_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( 'plus {link}Shipping Costs{/link}', 'woocommerce-germanized' ),
			),
			array(
				'title' 	=> __( 'Free Shipping Text', 'woocommerce-germanized' ),
				'desc' 		=> '<div class="wc-gzd-additional-desc">' . __( 'This text will be used to inform the customer about free shipping. Leave empty to disable notice. Use {link}{/link} to insert link to shipping costs page.', 'woocommerce-germanized' ) . '</div>',
				'desc_tip'	=> false,
				'id' 		=> 'woocommerce_gzd_free_shipping_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> '',
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_costs_options' ),

			array( 'title' => __( 'Display', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'shopmark_options', 'desc' => __( 'Choose where to show which shopmarks within your shop.', 'woocommerce-germanized' ) ),

			array(
				'title' 	=> __( 'Show within Product Listings', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Shipping Costs', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_shipping_costs_info',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),
			array(
				'desc' 		=> __( 'Tax Info', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_tax_info',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),
			array(
				'desc' 		=> __( 'Base Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_price_unit',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),
			array(
				'desc' 		=> __( 'Product Units', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_product_units',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'no',
				'checkboxgroup'		=> '',
			),
			array(
				'desc' 		=> __( 'Delivery Time Notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_delivery_time_info',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),
			array(
				'desc' 	=> __( 'Price Labels', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_sale_price_labels',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
				'checkboxgroup'	=> 'end',
			),
			array(
				'title' 	=> __( 'Single Product', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Shipping Costs notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_shipping_costs_info',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),
			array(
				'desc' 		=> __( 'Tax Info', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_tax_info',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),
			array(
				'desc' 		=> __( 'Base Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_price_unit',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),
			array(
				'desc' 		=> __( 'Product Units', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_product_units',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'no',
				'checkboxgroup'		=> '',
			),
			array(
				'desc' 		=> __( 'Delivery Time Notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_delivery_time_info',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),
			array(
				'desc' 	=> __( 'Price Labels', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_sale_price_labels',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
				'checkboxgroup'	=> 'end',
			),
			array(
				'title' 	=> __( 'Product Widgets', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Shipping Costs notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_widget_shipping_costs',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),
			array(
				'desc' 		=> __( 'Tax Info', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_widget_tax_info',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),
			array(
				'desc' 		=> __( 'Base Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_widget_unit_price',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),
			array(
				'desc' 		=> __( 'Product Units', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_widget_product_units',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'no',
				'checkboxgroup'		=> '',
			),
			array(
				'desc' 		=> __( 'Delivery Time Notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_widget_delivery_time',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'end',
			),

			array(
				'title' 	=> __( 'Cart', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Base Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_cart_product_unit_price',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),
			array(
				'desc' 		=> __( 'Product Units', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_cart_product_units',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),
			array(
				'desc' 		=> __( 'Delivery Time', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_cart_product_delivery_time',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> '',
			),
			array(
				'desc' 		=> __( 'Description', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_cart_product_item_desc',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup' => 'end',
			),
			array(
				'title' 	=> __( 'Checkout', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Base Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_product_unit_price',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),
			array(
				'desc' 		=> __( 'Product Units', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_product_units',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),
			array(
				'desc' 		=> __( 'Delivery Time', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_product_delivery_time',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> '',
			),
			array(
				'desc' 		=> __( 'Description', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_product_item_desc',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup' => 'end',
			),
			array(
				'title' 	=> __( 'Mini Cart', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Base Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_mini_cart_product_unit_price',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),
			array(
				'desc' 		=> __( 'Product Units', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_mini_cart_product_units',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> '',
			),
			array(
				'desc' 		=> __( 'Delivery Time', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_mini_cart_product_delivery_time',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> '',
			),
			array(
				'desc' 		=> __( 'Description', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_mini_cart_product_item_desc',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'end',
			),

			array(
				'title' 	=> __( 'E-mails', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Base Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_emails_unit_price',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),
			array(
				'desc' 		=> __( 'Product Units', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_emails_product_units',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> '',
			),
			array(
				'desc' 		=> __( 'Delivery Time Notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_emails_delivery_time',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> '',
			),
			array(
				'desc' 		=> __( 'Short Description', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_emails_product_item_desc',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'yes',
				'checkboxgroup'	=> '',
			),

			array( 'type' => 'sectionend', 'id' => 'shopmark_options' ),

			array( 'title' => __( 'Footer', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'footer_options' ),

			array(
				'title' 	=> __( 'Notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Attach a global VAT notice to your footer.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_footer_vat_notice',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'checkboxgroup'	=> 'start'
			),
			array(
				'desc' 		=> __( 'Attach a global sale price notice to your footer.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_footer_sale_price_notice',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'no',
				'checkboxgroup'	=> 'end',
			),

			array( 'type' => 'sectionend', 'id' => 'footer_options' ),
		);
	}

	protected function get_delivery_time_settings() {
		$delivery_terms = array( '' => __( 'None', 'woocommerce-germanized' ) );
		$terms          = get_terms( 'product_delivery_time', array( 'fields' => 'id=>name', 'hide_empty' => false ) );

		if ( ! is_wp_error( $terms ) ) {
			$delivery_terms = $delivery_terms + $terms;
		}

		$product_types        = wc_get_product_types();
		$digital_type_options = array_merge( array(
			'downloadable'  => __( 'Downloadable Product', 'woocommerce-germanized' ),
			'virtual'		=> __( 'Virtual Product', 'woocommerce-germanized' ),
			'service'       => __( 'Service', 'woocommerce-germanized' )
		), $product_types );

		return array(
			array( 'title' => '', 'type' => 'title', 'id' => 'delivery_time_options', 'desc' => '' ),

			array(
				'title' 	=> __( 'Fallback', 'woocommerce-germanized' ),
				'desc_tip' 	=> __( 'This delivery time will be added to every product if no delivery time has been chosen individually', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_default_delivery_time',
				'css' 		=> 'min-width:250px;',
				'default'	=> '',
				'type' 		=> 'select',
				'class'		=> 'chosen_select',
				'options'	=>	$delivery_terms,
				'desc'		=>  '<a href="' . admin_url( 'edit-tags.php?taxonomy=product_delivery_time&post_type=product' ) . '">' . __( 'Manage Delivery Times', 'woocommerce-germanized' ) . '</a>',
			),
			array(
				'title' 	=> __( 'Format', 'woocommerce-germanized' ),
				'desc' 		=> '<div class="wc-gzd-additional-desc"> ' . __( 'This text will be used to indicate delivery time for products. Use {delivery_time} as placeholder.', 'woocommerce-germanized' ) . '</div>',
				'desc_tip'	=> false,
				'id' 		=> 'woocommerce_gzd_delivery_time_text',
				'type' 		=> 'text',
				'default'	=> __( 'Delivery time: {delivery_time}', 'woocommerce-germanized' ),
			),
			array(
				'title' 	=> __( 'Digital text', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_digital_delivery_time_text',
				'default'	=> '',
				'type' 		=> 'text',
				'desc_tip'	=> __( 'Enter a text which will be shown as digital delivery time text (replacement for default digital time on digital products).', 'woocommerce-germanized' ),
			),
			array(
				'title' 	=> __( 'Backorder', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Hide delivery time if a product is on backorder.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_delivery_time_disable_backorder',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),
			array(
				'title' 	=> __( 'Not in Stock', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Hide delivery time if a product is not in stock.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_delivery_time_disable_not_in_stock',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),
			array(
				'title' 	=> __( 'Hide Notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Select product types for which you might want to disable the delivery time notice.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_display_delivery_time_hidden_types',
				'class' 	=> 'chosen_select',
				'type'		=> 'multiselect',
				'options'	=> $digital_type_options,
				'default'	=> array( 'external', 'virtual' ),
			),

			array( 'type' => 'sectionend', 'id' => 'delivery_time_options' ),
		);
	}

	protected function get_unit_price_settings() {
		return array(
			array( 'type' => 'title', 'title' => '', 'id' => 'unit_price_options' ),
			array(
				'title' 	=> __( 'Format', 'woocommerce-germanized' ),
				'desc' 		=> '<div class="wc-gzd-additional-desc">' . __( 'This text will be used to display the base price. Use {price} to insert the price. If you want to specifically format base price output use {base}, {unit} and {base_price} as placeholders.', 'woocommerce-germanized' ) . '</div>',
				'desc_tip'	=> false,
				'id' 		=> 'woocommerce_gzd_unit_price_text',
				'type' 		=> 'text',
				'default'	=> __( '{price}', 'woocommerce-germanized' ),
			),
			array(
				'title' 	=> __( 'Variable Price', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable price range base prices for variable products.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_unit_price_enable_variable',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),
			array(
				'title' 	=> __( 'Product units format', 'woocommerce-germanized' ),
				'desc' 		=> '<div class="wc-gzd-additional-desc">' . __( 'This text will be used to display the product units. Use {product_units} to insert the amount of product units. Use {unit} to insert the unit. Optionally display the formatted unit price with {unit_price}.', 'woocommerce-germanized' ) . '</div>',
				'desc_tip'	=> false,
				'id' 		=> 'woocommerce_gzd_product_units_text',
				'type' 		=> 'text',
				'default'	=> __( 'Product contains: {product_units} {unit}', 'woocommerce-germanized' ),
			),
			array( 'type' => 'sectionend', 'id' => 'unit_price_options' ),
		);
	}

	protected function get_price_label_settings() {
		$labels = array_merge( array( '' => __( 'None', 'woocommerce-germanized' ) ), WC_Germanized()->price_labels->get_labels() );

		return array(
			array( 'type' => 'title', 'title' => '', 'id' => 'price_label_options' ),
			array(
				'title' 	=> __( 'Fallback Sale Label', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_default_sale_price_label',
				'css' 		=> 'min-width:250px;',
				'default'	=> '',
				'type' 		=> 'select',
				'class'		=> 'chosen_select',
				'options'	=>	$labels,
				'desc'		=>  '<a href="' . admin_url( 'edit-tags.php?taxonomy=product_price_label&post_type=product' ) . '">' . __( 'Manage Price Labels', 'woocommerce-germanized' ) . '</a><div class="wc-gzd-additional-desc">' . __( 'Choose whether you would like to have a default sale price label to inform the customer about the regular price (e.g. Recommended Retail Price).', 'woocommerce-germanized' ) . '</div>',
			),
			array(
				'title' 	=> __( 'Fallback Regular Label', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_default_sale_price_regular_label',
				'css' 		=> 'min-width:250px;',
				'default'	=> '',
				'type' 		=> 'select',
				'class'		=> 'chosen_select',
				'options'	=>	$labels,
				'desc'		=>  '<a href="' . admin_url( 'edit-tags.php?taxonomy=product_price_label&post_type=product' ) . '">' . __( 'Manage Price Labels', 'woocommerce-germanized' ) . '</a><div class="wc-gzd-additional-desc">' . __( 'Choose whether you would like to have a default sale price regular label to inform the customer about the sale price (e.g. New Price).', 'woocommerce-germanized' ) . '</div>',
			),
			array( 'type' => 'sectionend', 'id' => 'price_label_options' ),
		);
	}
}