<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_GZD_Settings_Germanized' ) ) :

/**
 * Adds Settings Interface to WooCommerce Settings Tabs
 *
 * @class 		WC_GZD_Settings_Germanized
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Germanized extends WC_Settings_Page {

	/**
	 * Adds Hooks to output and save settings
	 */
	public function __construct() {
		$this->id    = 'germanized';
		$this->label = __( 'Germanized', 'woocommerce-germanized' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );

	}

	/**
	 * Gets setting sections
	 */
	public function get_sections() {
		$sections = array(
			''   		 	=> __( 'General Options', 'woocommerce-germanized' ),
			'display'       => __( 'Display Options', 'woocommerce-germanized' ),
			'trusted_shops' => _x( 'Trusted Shops Options', 'trusted-shops', 'woocommerce-germanized' ),
			'ekomi'         => _x( 'eKomi Options', 'ekomi', 'woocommerce-germanized' ),
		);
		return $sections;
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		
		$delivery_terms = array('' => __( 'None', 'woocommerce-germanized' ));
		$terms = get_terms( 'product_delivery_time', array('fields' => 'id=>name', 'hide_empty' => false) );
		if ( !is_wp_error( $terms ) )
			$delivery_terms = $delivery_terms + $terms;

		$mailer 			= WC()->mailer();
		$email_templates 	= $mailer->get_emails();
		$email_select 		= array();
		foreach ( $email_templates as $email )
			$email_select[ $email->id ] = empty( $email->title ) ? ucfirst( $email->id ) : ucfirst( $email->title );

		return apply_filters( 'woocommerce_germanized_settings', array(

			array(	'title' => __( 'General', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'general_options' ),

			array(
				'title' 	=> __( 'Small-Enterprise-Regulation', 'woocommerce-germanized' ),
				'desc' 		=> __( 'VAT based on &#167;19 UStG', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_small_enterprise',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> sprintf( __( 'set this Option if you have chosen <a href="%s" target="_blank">&#167;19 UStG</a>', 'woocommerce-germanized' ), esc_url( 'http://www.gesetze-im-internet.de/ustg_1980/__19.html' ) )
			),

			array(
				'title' 	=> __( 'Show no VAT notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show no VAT &#167;19 UStG notice on single product', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_small_enterprise',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
			),

			array(
				'title' 	=> __( 'Submit Order Button Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text serves as Button text for the Order Submit Button.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_order_submit_btn_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( 'Buy Now', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Phone as required field', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Should phone number be a required field within checkout?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_phone_required',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
			),

			array( 'type' => 'sectionend', 'id' => 'general_options' ),

			array(	'title' => __( 'Legal Pages', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'legal_pages_options' ),

			array(
				'title' 	=> __( 'Imprint', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain an imprint with your company\'s information.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_imprint_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' 	=> __( 'Data Security Statement', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain information regarding your data security policy.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_data_security_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' 	=> __( 'Power of Revocation', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain information regarding your customer\'s Right of Revocation.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_revocation_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' 	=> __( 'Payment Methods', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain information regarding the Payment Methods that are chooseable during checkout.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_payment_methods_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' 	=> __( 'Shipping Methods', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain information regarding shipping methods that are chooseable during checkout.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_shipping_costs_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array( 'type' => 'sectionend', 'id' => 'legal_pages_options' ),

			array( 'title' => __( 'Delivery Times', 'woocommerce-germanized' ), 'type' => 'title', 'desc' => '', 'id' => 'delivery_times_options' ),

			array(
				'title' 	=> __( 'Default Delivery Time', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This delivery time will be added to every product if no delivery time has been chosen individually', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_default_delivery_time',
				'css' 		=> 'min-width:250px;',
				'default'	=> '',
				'type' 		=> 'select',
				'class'		=> 'chosen_select',
				'options'	=>	$delivery_terms,
				'desc_tip'	=>  true,
			),

			array(
				'title' 	=> __( 'Delivery Time Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be used to indicate delivery time for products. Use {delivery_time} as placeholder.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_delivery_time_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( 'Delivery time: {delivery_time}', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'delivery_times_options' ),

			array(	'title' => __( 'Shipping Costs', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'shipping_costs_options' ),

			array(
				'title' 	=> __( 'Shipping Costs Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be used to inform the customer about shipping costs. Use {link}{/link} to insert link to shipping costs page.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_shipping_costs_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( 'plus {link}Shipping Costs{/link}', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_costs_options' ),

			array(	'title' => __( 'Right of Recission', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'recission_options' ),

			array(
				'title' 	=> __( 'Revocation Address', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Type in an address, telephone/telefax number, email address which is to be used as revocation address', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_revocation_address',
				'type' 		=> 'textarea',
			),

			array( 'type' => 'sectionend', 'id' => 'recission_options' ),

			array(	'title' => __( 'E-Mails', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'email_options' ),

			array(
				'title' 	=> __( 'Attach Imprint', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Attach Imprint to the following email templates', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_mail_attach_imprint',
				'type' 		=> 'multiselect',
				'class'		=> 'chosen_select',
				'desc_tip'	=> true,
				'options'	=> $email_select,
			),

			array(
				'title' 	=> __( 'Attach Terms & Conditions', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Attach Terms & Conditions to the following email templates', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_mail_attach_terms',
				'type' 		=> 'multiselect',
				'class'		=> 'chosen_select',
				'desc_tip'	=> true,
				'options'	=> $email_select,
			),

			array(
				'title' 	=> __( 'Attach Power of Recission', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Attach Power of Recission to the following email templates', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_mail_attach_revocation',
				'type' 		=> 'multiselect',
				'class'		=> 'chosen_select',
				'desc_tip'	=> true,
				'options'	=> $email_select,
				'default'	=> array( 'customer_processing_order' ),
			),

			array(
				'title' 	=> __( 'Attach Data Security', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Attach Data Security Statement to the following email templates', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_mail_attach_data_security',
				'type' 		=> 'multiselect',
				'class'		=> 'chosen_select',
				'desc_tip'	=> true,
				'options'	=> $email_select,
			),

			array( 'type' => 'sectionend', 'id' => 'email_options' ),

		) ); // End general settings
	}

	public function get_display_settings() {

		return array(

			array(	'title' => __( 'General', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'general_options' ),

			array(
				'title' 	=> __( 'Add to Cart', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show add to cart button on listings?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_add_to_cart',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> sprintf( __( 'unset this option if you don\'t want to show the add to cart button within the product listings', 'woocommerce-germanized' ), esc_url( 'http://www.gesetze-im-internet.de/ustg_1980/__19.html' ) )
			),

			array(
				'title' 	=> __( 'Notice Footer', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show a global VAT notice within footer', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_footer_vat_notice',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'checkboxgroup'	=> 'start'
			),

			array(
				'desc' 		=> __( 'Show a global sale price notice within footer', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_footer_sale_price_notice',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
				'checkboxgroup'		=> 'end',
			),

			array( 'type' => 'sectionend', 'id' => 'general_options' ),

			array(	'title' => __( 'Products', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'product_options' ),

			array(
				'title' 	=> __( 'Show within Product Listings', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Shipping Costs notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_shipping_costs',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),

			array(
				'desc' 		=> __( 'Unit Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_unit_price',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Delivery Time Notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_delivery_time',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> 'end',
			),

			array(
				'title' 	=> __( 'Show on Product Detail Page', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Shipping Costs notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_shipping_costs',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),

			array(
				'desc' 		=> __( 'Tax Info', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_tax_info',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Unit Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_unit_price',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Delivery Time Notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_delivery_time',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> 'end',
			),

			array( 'type' => 'sectionend', 'id' => 'product_options' ),

			array(	'title' => __( 'Checkout & Cart', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'checkout_options' ),

			array(
				'title' 	=> __( 'Checkout Table Color', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_table_color',
				'desc_tip'	=> __( 'Choose the color of your checkout product table. This table should be highlighted within your checkout page.', 'woocommerce-germanized' ),
				'default'	=> '#eeeeee',
				'type' 		=> 'color',
			),

			array(
				'title' 	=> __( 'Checkout Legal Display', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Use Text without Checkbox', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_legal_no_checkbox',
				'desc_tip'	=> __( 'This version will remove checkboxes from Checkout and display a text instead. This seems to be legally compliant (Zalando & Co are using this option).', 'woocommerce-germanized' ),
				'default'	=> 'no',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Legal Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a Plain Text which will be shown right above checkout submit button. Use {term_link}{/term_link}, {data_security_link}{/data_security_link}, {revocation_link}{/revocation_link} as Placeholders for the links to legal pages.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'With your order, you agree to have read and understood our {term_link}Terms and Conditions{/term_link} and your {revocation_link}Right of Recission{/revocation_link}.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_checkout_legal_text',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Legal Text Error', 'woocommerce-germanized' ),
				'desc' 		=> __( 'If you have chosen to use checkbox validation please choose a error message which will be shown if the user doesn\'t check checkbox. Use {term_link}{/term_link}, {data_security_link}{/data_security_link}, {revocation_link}{/revocation_link} as Placeholders for the links to legal pages.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'To finish the order you have to accept to our {term_link}Terms and Conditions{/term_link} and {revocation_link}Right of Recission{/revocation_link}.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_checkout_legal_text_error',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Order Success Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a custom text to display on order success page.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_order_success_text',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Order Success Data', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Hide product table and customer data on order success page', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_hide_order_success_details',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
			),

			array( 'type' => 'sectionend', 'id' => 'checkout_options' ),

		);

	}

	public function output() {
		global $current_section;
		$sidebar = $this->get_sidebar();
		if ( $current_section ) {
			if ( $current_section == 'trusted_shops' ) {
				$settings = WC_germanized()->trusted_shops->get_settings();
				$sidebar = WC_germanized()->trusted_shops->get_sidebar();
			}
			else if ( $current_section == 'ekomi' )
				$settings = WC_germanized()->ekomi->get_settings();
			else if ( $current_section == 'display' )
				$settings = $this->get_display_settings();
 		} else {
			$settings = $this->get_settings();
		}
		echo '<div class="wc-gzd-admin-settings">';
		WC_Admin_Settings::output_fields( $settings );
		echo '</div>';
		echo $sidebar;
	}

	public function get_sidebar() {
		$html = '
			<div class="wc-gzd-admin-settings-sidebar">
				<h3>VendiPro - Typisch deutsch!</h3>
				<div class="wc-gzd-sidebar-img">
					<a href="http://vendidero.de/vendipro" target="_blank"><img class="browser" src="' . WC_germanized()->plugin_url() . '/assets/images/vendidero.jpg" /></a>
				</div>
				<p>VendiPro ist ein für den deutschen Markt entwickeltes WooCommerce Theme. Mit VendiPro sind alle WooCommerce und WooCommerce Germanized Einstellungen auch optisch perfekt auf den deutschen Markt abgestimmt.</p>
				<div class="wc-gzd-sidebar-action">
					<a class="button button-primary wc-gzd-button" href="http://vendidero.de/vendipro" target="_blank">jetzt entdecken</a>
					<span class="small">ab 49,95 € inkl. Mwst. und 1 Jahr Updates & Support!</span>
				</div>
			</div>
		';
		return $html;
	}

	/**
	 * Save settings
	 */
	public function save() {

		global $current_section;

		if ( $current_section ) {
			if ( $current_section == 'trusted_shops' )
				$settings = WC_germanized()->trusted_shops->get_settings();
			else if ( $current_section == 'ekomi' )
				$settings = WC_germanized()->ekomi->get_settings();
			else if ( $current_section == 'display' )
				$settings = $this->get_display_settings();
 		} else {
			$settings = $this->get_settings();
		}

		$update_rich_snippets = false;
		$update_reviews = false;

		if ( !empty( $settings ) ) {
			foreach ( $settings as $setting ) {
				if ( $setting[ 'id' ] == 'woocommerce_gzd_small_enterprise' ) {
					if ( get_option('woocommerce_gzd_small_enterprise') == 'no' && !empty( $_POST['woocommerce_gzd_small_enterprise'] ) ) {
						// Update woocommerce options to not show tax
						update_option( 'woocommerce_calc_taxes', 'no' );
						update_option( 'woocommerce_prices_include_tax', 'yes' );
						update_option( 'woocommerce_tax_display_shop', 'incl' );
						update_option( 'woocommerce_tax_display_cart', 'incl' );
						update_option( 'woocommerce_price_display_suffix', '' );
					} elseif ( get_option('woocommerce_gzd_small_enterprise') == 'yes' && ! isset( $_POST['woocommerce_gzd_small_enterprise'] ) ) {
						// Update woocommerce options to show tax
						update_option( 'woocommerce_calc_taxes', 'yes' );
						update_option( 'woocommerce_prices_include_tax', 'yes' );
					}
					break;
				} else if ( $setting[ 'id' ] == 'woocommerce_gzd_trusted_shops_review_widget_enable' ) {
					if ( ! empty( $_POST[ 'woocommerce_gzd_trusted_shops_review_widget_enable' ] ) && ! WC_germanized()->trusted_shops->is_review_widget_enabled() )
						$update_reviews = true;
				} else if ( $setting[ 'id' ] == 'woocommerce_gzd_trusted_shops_rich_snippets_enable' ) {
					if ( ! empty( $_POST[ 'woocommerce_gzd_trusted_shops_rich_snippets_enable' ] ) && ! WC_germanized()->trusted_shops->is_rich_snippets_enabled() )
						$update_rich_snippets = true;
				}
			}
		}

		WC_Admin_Settings::save_fields( $settings );

		// Trusted Shops API
		if ( $update_rich_snippets || $update_reviews ) {
			$trusted_shops = new WC_GZD_Trusted_Shops();
			if ( $update_rich_snippets )
				$trusted_shops->update_reviews();
			if ( $update_reviews )
				$trusted_shops->update_review_widget();
		}
	}

}

endif;

?>