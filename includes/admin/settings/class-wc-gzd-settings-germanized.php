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

	public $premium_sections = array();

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
		add_filter( 'woocommerce_gzd_get_settings_display', array( $this, 'get_display_settings' ) );
		add_action( 'woocommerce_gzd_before_save_section_', array( $this, 'before_save' ), 0, 1 );
		add_action( 'woocommerce_gzd_after_save_section_', array( $this, 'after_save' ), 0, 1 );
		add_action( 'woocommerce_admin_field_image', array( $this, 'image_field' ), 0, 1 );
		add_action( 'woocommerce_admin_field_hidden', array( $this, 'hidden_field' ), 0, 1 );

		if ( ! WC_Germanized()->is_pro() ) {
			// Premium sections
			$this->premium_sections = array(
				'invoices' => sprintf( __( 'Invoices & Packing Slips %s', 'woocommerce-germanized' ), '<span class="wc-gzd-premium-section-tab">pro</span>' ),
				'checkout' => sprintf( __( 'Multistep Checkout %s', 'woocommerce-germanized' ), '<span class="wc-gzd-premium-section-tab">pro</span>' ),
				'agbs'     => sprintf( __( 'Terms & Conditions generator %s', 'woocommerce-germanized' ), '<span class="wc-gzd-premium-section-tab">pro</span>' ),
				'widerruf' => sprintf( __( 'Revocation generator %s', 'woocommerce-germanized' ), '<span class="wc-gzd-premium-section-tab">pro</span>' ),
			);

			add_filter( 'woocommerce_gzd_settings_sections', array( $this, 'set_premium_sections' ), 0 );
			foreach ( $this->premium_sections as $key => $section ) {
				add_filter( 'woocommerce_gzd_get_settings_' . $key, array( $this, 'get_premium_settings' ), 0 );
				add_filter( 'wc_germanized_settings_section_before_' . $key, array( $this, 'output_premium_section' ), 0 );
				add_filter( 'woocommerce_gzd_get_sidebar_' . $key, array( $this, 'get_premium_sidebar' ), 0 );
			}
		}
	}

	public function image_field( $value ) {
		?>
		<tr valign="top">
			<th class="forminp forminp-image">
				<a href="<?php echo $value[ 'href' ]; ?>" target="_blank"><img src="<?php echo $value[ 'img' ]; ?>" /></a>
			</th>
		</tr>
		<?php
	}

	public function hidden_field( $value ) {
		$option_value = WC_Admin_Settings::get_option( $value[ 'id' ], $value[ 'default' ] );
		?>
		<tr valign="top" style="display: none">
			<th class="forminp forminp-image">
				 <input type="hidden" id="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo esc_attr( $option_value ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>" />
			</th>
		</tr>
		<?php
	}

	/**
	 * Gets setting sections
	 */
	public function get_sections() {
		$sections = apply_filters( 'woocommerce_gzd_settings_sections', array(
			''   		 	=> __( 'General Options', 'woocommerce-germanized' ),
			'display'       => __( 'Display Options', 'woocommerce-germanized' ),
		) );
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

		$email_order = wc_gzd_get_email_attachment_order();

		$email_settings = array();

		foreach ( $email_order as $key => $order ) {

			array_push( $email_settings, array(

				'title' 	=> sprintf( __( 'Attach %s', 'woocommerce-germanized' ), $order ),
				'desc' 		=> sprintf( __( 'Attach %s to the following email templates', 'woocommerce-germanized' ), $order ),
				'id' 		=> 'woocommerce_gzd_mail_attach_' . $key,
				'type' 		=> 'multiselect',
				'class'		=> 'chosen_select',
				'desc_tip'	=> true,
				'options'	=> $email_select,

			) );

		}

		$settings = array(

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

			array(
				'title' 	=> __( 'Add title field', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Add a title field to the address within checkout?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_address_field',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
			),

			array(
				'title' 	=> __( 'Disallow cancellations', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Don’t allow customers to manually cancel orders.', 'woocommerce-germanized' ),
				'desc_tip'	=> __( 'By default payment methods like PayPal allow order cancellation by clicking the abort link. This option will stop customers from manually cancel orders.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_stop_order_cancellation',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
			),

			array( 'type' => 'sectionend', 'id' => 'general_options' ),

			array(	'title' => __( 'Contract', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'contract_options', 'desc' => '<div class="notice inline notice-warning"><p>' . sprintf( __( '%sUpgrade to %spro%s%s to unlock this feature and get premium support.', 'woocommerce-germanized' ), '<a href="https://vendidero.de/woocommerce-germanized" class="button">', '<span class="wc-gzd-pro">', '</span>', '</a>' ) . '</p></div>' ),

			array(
				'title' 	=> '',
				'id' 		=> 'woocommerce_gzdp_contract_after_confirmation',
				'img'		=> WC_Germanized()->plugin_url() . '/assets/images/pro/settings-inline-contract.png',
				'href'      => 'https://vendidero.de/woocommerce-germanized#contract',
				'type' 		=> 'image',
			),

			array( 'type' => 'sectionend', 'id' => 'contract_options' ),

			array(	'title' => __( 'Legal Pages', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'legal_pages_options' ),

			array(
				'title' 	=> __( 'Terms & Conditions', 'woocommerce-germanized' ),
				'desc_tip' 	=> __( 'This page should contain your terms & conditions.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_terms_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc'		=> ( ! get_option( 'woocommerce_terms_page_id' ) ? sprintf( __( 'Don\'t have terms & conditions yet? <a href="%s">Generate now</a>!', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized&section=agbs' ) ) : '' ),
			),

			array(
				'title' 	=> __( 'Power of Revocation', 'woocommerce-germanized' ),
				'desc_tip' 	=> __( 'This page should contain information regarding your customer\'s Right of Revocation.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_revocation_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc'		=> ( ! get_option( 'woocommerce_revocation_page_id' ) ? sprintf( __( 'Don\'t have a revocation page yet? <a href="%s">Generate now</a>!', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized&section=widerruf' ) ) : '' ),
			),

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

			array(
				'title' 	=> __( 'Shipping Costs Tax', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable better taxation for shpping costs?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_shipping_tax',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> sprintf( __( 'By choosing this option shipping cost taxation will be calculated based on tax rates within cart. Imagine the following example. Further information can be found <a href="%s" target="_blank">here</a>. %s', 'woocommerce-germanized' ), 'http://www.it-recht-kanzlei.de/umsatzsteuer-versandkosten-mehrwertsteuer.html', '<table class="wc-gzd-tax-example"><thead><tr><th>Produkt</th><th>Preis</th><th>MwSt.-Satz</th><th>Anteil</th><th>MwSt.</th></tr></thead><tbody><tr><td>Buch</td><td>' . wc_price( 40 ) . '</td><td>7%</td><td>40%</td><td>' . wc_price( 2.62 ) . '</td></tr><tr><td>DVD</td><td>' . wc_price( 60 ) . '</td><td>19%</td><td>60%</td><td>' . wc_price( 9.58 ) . '</td></tr><tr><td>Versand</td><td>' . wc_price( 5 ) . '</td><td>7% | 19%</td><td>40% | 60%</td><td>' . wc_price( 0.13 ) . ' | ' . wc_price( 0.48 ) . '</td></tr></tbody></table>' ),
			),

			array(
				'title' 	=> __( 'Force Tax Calculation', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Force shipping costs tax calculation for every method?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_shipping_tax_force',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This option will overwrite settings for each individual shipping method to force tax calculation (instead of only calculating tax for those methods which are taxeable).', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_costs_options' ),

			array(	'title' => __( 'Fees', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'fees_options' ),

			array(
				'title' 	=> __( 'Fee Tax', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable better taxation for fees?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_fee_tax',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'  => __( 'By choosing this option fee taxation will be calculated based on tax rates within cart. See shipping costs taxation for more information.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Force Tax Calculation', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Force fee tax calculation for every fee?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_fee_tax_force',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This option will overwrite settings for each individual fee to force tax calculation (instead of only calculating tax for those fees which are taxeable).', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'fees_options' ),

			array( 'title' => __( 'Customers', 'woocommerce-germanized' ), 'type' => 'title', 'desc' => '', 'id' => 'customer_options' ),

			array(
				'title' 	=> __( 'Checkbox', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Add a checkbox to customer registration form.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_customer_account_checkbox',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
			),

			array(
				'title' 	=> __( 'Checkbox text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a Plain Text which will be shown as checkbox text for customer account creation. Use {term_link}{/term_link}, {data_security_link}{/data_security_link}, {revocation_link}{/revocation_link} as Placeholders for the links to legal pages.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'Yes, I’d like create a new account and have read and understood the {data_security_link}data privacy statement{/data_security_link}.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_customer_account_text',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Checkout', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Replace default WooCommerce text regarding account creation during checkout.', 'woocommerce-germanized' ),
				'desc_tip'	=> __( 'Use the text from above instead of the default WooCommerce text regarding account creation during checkout. This checkbox is only show if you have activated guest accounts.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_customer_account_checkout_checkbox',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
			),

			array(
				'title' 	=> __( 'Customer Double Opt In', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable customer double opt in on registration?', 'woocommerce-germanized' ),
				'desc_tip'  => sprintf( __( 'If customer chooses to create a customer account an email with an activation link will be sent by mail. Customer account will be marked as activated if user clicks on the link within the email. More information on this topic can be found <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'http://t3n.de/news/urteil-anmeldebestatigungen-double-opt-in-pflicht-592304/' ),
				'id' 		=> 'woocommerce_gzd_customer_activation',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Delete Unactivated After', 'woocommerce-germanized' ),
				'desc_tip' 	=> __( 'This will make sure unactivated customer accounts will be deleted after X days. Set to 0 if you don\'t want to automatically delete unactivated customers.', 'woocommerce-germanized' ),
				'desc'		=> __( 'days', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_customer_cleanup_interval',
				'type' 		=> 'number',
				'custom_attributes' => array( 'min' => 0, 'step' => 1 ),
				'default'	=> 7,
			),

			array( 'type' => 'sectionend', 'id' => 'customer_options' ),

			array(	'title' => __( 'Unit Price', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'unit_price_options' ),

			array(
				'title' 	=> __( 'Unit Price Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be used to display the unit price. Use {price} to insert the price.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_unit_price_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( '{price}', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'unit_price_options' ),

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

			array(	'title' => __( 'E-Mails', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'email_options', 'desc' => __( 'Use drag & drop to customize attachment order. Don\'t forget to save your changes.', 'woocommerce-germanized' ) ),

		);

		$settings = array_merge( $settings, $email_settings );

		$settings = array_merge( $settings, array( 

			array(
				'title' 	=> '',
				'id' 		=> 'woocommerce_gzd_mail_attach_order',
				'type' 		=> 'hidden',
				'default'	=> 'terms,revocation,data_security,imprint',
			),

			array( 'type' => 'sectionend', 'id' => 'email_options' ),

			array(	'title' => __( 'Virtual VAT', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'virtual_vat_options' ),

			array(
				'title' 	=> __( 'Enable Virtual VAT', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable if you want to charge your customer\'s countries\' VAT for virtual products.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_enable_virtual_vat',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> sprintf( __( 'New EU VAT rule applies on 01.01.2015. Make sure that every digital or virtual product has chosen the right tax class (Virtual Rate or Virtual Reduced Rate). Gross prices will not differ from the prices you have chosen for affected products. In fact the net price will differ depending on the VAT rate of your customers\' country. Shop settings will be adjusted to show prices including tax. More information can be found <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'http://ec.europa.eu/taxation_customs/taxation/vat/how_vat_works/telecom/index_de.htm#new_rules' ),
			),

			array( 'type' => 'sectionend', 'id' => 'virtual_vat_options' ),

			array(	'title' => _x( 'Invoices', 'invoices', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'invoice_options', 'desc' => '<div class="notice inline notice-warning"><p>' . sprintf( __( '%sUpgrade to %spro%s%s to unlock this feature and get premium support.', 'woocommerce-germanized' ), '<a href="https://vendidero.de/woocommerce-germanized" class="button">', '<span class="wc-gzd-pro">', '</span>', '</a>' ) . '</p></div>' ),

			array(
				'title' 	=> '',
				'id' 		=> 'woocommerce_gzdp_contract_after_confirmation',
				'img'		=> WC_Germanized()->plugin_url() . '/assets/images/pro/settings-inline-invoices.png',
				'href'      => 'https://vendidero.de/woocommerce-germanized#accounting',
				'type' 		=> 'image',
			),

			array( 'type' => 'sectionend', 'id' => 'invoice_options' ),

			array(	'title' => __( 'VAT', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'vat_options', 'desc' => '<div class="notice inline notice-warning"><p>' . sprintf( __( '%sUpgrade to %spro%s%s to unlock this feature and get premium support.', 'woocommerce-germanized' ), '<a href="https://vendidero.de/woocommerce-germanized" class="button">', '<span class="wc-gzd-pro">', '</span>', '</a>' ) . '</p></div>' ),

			array(
				'title' 	=> '',
				'id' 		=> 'woocommerce_gzdp_contract_after_confirmation',
				'img'		=> WC_Germanized()->plugin_url() . '/assets/images/pro/settings-inline-vat.png',
				'href'      => 'https://vendidero.de/woocommerce-germanized#vat',
				'type' 		=> 'image',
			),

			array( 'type' => 'sectionend', 'id' => 'vat_options' ),

		) ); // End general settings
	
		return apply_filters( 'woocommerce_germanized_settings', $settings );

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
				'desc_tip'	=> __( 'unset this option if you don\'t want to show the add to cart button within the product listings', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Link to Details', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Want to link to product details page instead of add to cart within listings?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_link_details',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'Decide whether you like to link to your product\'s details page instead of displaying an add to cart button within product listings.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Product Details Text', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_link_details_text',
				'default'	=> __( 'Details', 'woocommerce-germanized' ),
				'type' 		=> 'text',
				'desc_tip'	=> __( 'If you have chosen to link to product details page instead of add to cart URL you may want to change the button text.', 'woocommerce-germanized' ),
				'css' 		=> 'min-width:300px;',
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
				'desc' 		=> __( 'Tax Info', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_tax_info',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
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

			array(
				'title' 	=> __( 'Shipping Costs for Virtual', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Select this option if you want to display shipping costs notice for virtual products.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_shipping_costs_virtual',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
			),

			array( 'type' => 'sectionend', 'id' => 'product_options' ),

			array(	'title' => __( 'Checkout & Cart', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'checkout_options' ),

			( version_compare( WC()->version, '2.3', '>=' ) ?

				array(
					'title' 	=> __( 'Fallback Mode', 'woocommerce-germanized' ),
					'desc' 		=> __( 'Enable to make sure default review-order.php is not being overriden by theme.', 'woocommerce-germanized' ),
					'id' 		=> 'woocommerce_gzd_display_checkout_fallback',
					'default'	=> 'no',
					'type' 		=> 'checkbox',
					'desc_tip'	=> __( 'If you are facing problems within your checkout e.g. legally relevant data is not showing (terms, delivery time, unit price etc.) your theme seems to be incompatible (not using default WooCommerce hooks and filters). As a workaround you may use this fallback which ensures default review-order.php is used.', 'woocommerce-germanized' ),
				)

			: array() ),

			array(
				'title' 	=> __( 'Force free shipping', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Force free shipping method if available?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_free_shipping_select',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'By default WooCommerce will let customers choose other shipping methods than free shipping (if available). This option will force free shipping if available.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Hide taxes estimated', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Do you want to hide the "taxes and shipping estimated" text from your cart?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_hide_cart_tax_estimated',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'By default WooCommerce adds a "taxes and shipping estimated" text to your cart. This might puzzle your customers and may not meet german law.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Show Thumbnails', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show product thumbnails on checkout page?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_thumbnails',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'Uncheck if you don\'t want to show your product thumbnails within checkout table.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Hide Shipping Select', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Hide shipping rate selection from checkout?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_shipping_rate_select',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This option will hide shipping rate selection from checkout. By then customers will only be able to change their shipping rate on cart page.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Show back to cart button', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show back to cart button within your checkout table?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_back_to_cart_button',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This button may let your customer edit their order before submitting. Some people state that this button should be hidden to avoid legal problems.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Show edit data notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show a "check-your-entries" notice to the user?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_edit_data_notice',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This notice will be added right before the order comments field.', 'woocommerce-germanized' ),
			),

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
				'title' 	=> __( 'Show digital notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show checkbox for digital products.', 'woocommerce-germanized' ),
				'desc_tip'	=> __( 'Disable this option if you want your customers to obtain their right of recission even if digital products are being bought.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_legal_digital_checkbox',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Legal Digital Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a Plain Text which will be shown right above checkout submit button if a user has picked a digital product. See legal text option for possible placeholders.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'I want immediate access to the digital content and I acknowledge that thereby I lose my right to cancel once the service has begun.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_checkout_legal_text_digital',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Pay now Button', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Add a pay now button to emails and order success page.', 'woocommerce-germanized' ),
				'desc_tip' 	=> __( 'Add a pay now button to order confirmation email and order success page if the order awaits payment (PayPal etc).', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_order_pay_now_button',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
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
		$settings = $this->get_settings();
		$sidebar = $this->get_sidebar();
		if ( $this->get_sections() ) {
			foreach ( $this->get_sections() as $section => $name ) {
				if ( $section == $current_section ) {
					$settings = apply_filters( 'woocommerce_gzd_get_settings_' . $section, $this->get_settings() );
					$sidebar = apply_filters( 'woocommerce_gzd_get_sidebar_' . $section, $sidebar );
				}
			}
		}
		include_once( WC_Germanized()->plugin_path() . '/includes/admin/views/html-settings-section.php' );
	}

	public function get_sidebar() {
		ob_start();
		include_once( WC_Germanized()->plugin_path() . '/includes/admin/views/html-settings-sidebar.php' );
		$content = ob_get_clean();
		return $content;
	}

	/**
	 * Save settings
	 */
	public function save() {

		global $current_section;

		$settings = array();

		if ( $this->get_sections() ) {
			foreach ( $this->get_sections() as $section => $name ) {
				if ( $section == $current_section ) {
					$settings = apply_filters( 'woocommerce_gzd_get_settings_' . $section, $this->get_settings() );
				}
			}
		}
		if ( empty( $settings ) )
			return;

		do_action( 'woocommerce_gzd_before_save_section_' . $current_section, $settings );

		if ( apply_filters( 'wc_germanized_show_settings_' . $current_section, true ) )
			WC_Admin_Settings::save_fields( $settings );

		do_action( 'woocommerce_gzd_after_save_section_' . $current_section, $settings );
	}

	public function before_save( $settings ) {
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
				} else if ( $setting[ 'id' ] == 'woocommerce_gzd_enable_virtual_vat' ) {
					if ( get_option( 'woocommerce_gzd_enable_virtual_vat' ) != 'yes' && ! empty( $_POST[ 'woocommerce_gzd_enable_virtual_vat' ] ) ) {
						if ( ! empty( $_POST[ 'woocommerce_gzd_small_enterprise' ] ) )
							continue;
						// Update WooCommerce options to show prices including taxes
						// Check if is small business
						update_option( 'woocommerce_prices_include_tax', 'yes' );
						update_option( 'woocommerce_tax_display_shop', 'incl' );
						update_option( 'woocommerce_tax_display_cart', 'incl' );
						update_option( 'woocommerce_tax_total_display', 'itemized' );
					}
				}
			}
		}
	}

	public function after_save( $settings ) {
		if ( ! empty( $_POST[ 'woocommerce_gzd_small_enterprise' ] ) ) {
			update_option( 'woocommerce_gzd_shipping_tax', 'no' );
			update_option( 'woocommerce_gzd_shipping_tax_force', 'no' );
			update_option( 'woocommerce_gzd_fee_tax', 'no' );
			update_option( 'woocommerce_gzd_fee_tax_force', 'no' );
			if ( ! empty( $_POST[ 'woocommerce_gzd_enable_virtual_vat' ] ) ) {
				update_option( 'woocommerce_gzd_enable_virtual_vat', 'no' );
				WC_Admin_Settings::add_error( __( 'Sorry, but the new Virtual VAT rules cannot be applied to small business.', 'woocommerce-germanized' ) );
			}
		}
	}

	public function output_premium_section() {
		global $current_section;
		if ( ! isset( $this->premium_sections[ $current_section ] ) )
			return;
		$GLOBALS[ 'hide_save_button' ] = true;
		$section_title = $this->premium_sections[ $current_section ];
		include_once( WC_Germanized()->plugin_path() . '/includes/admin/views/html-settings-pro.php' );
	}

	public function set_premium_sections( $sections ) {
		return $sections + $this->premium_sections;
	}

	public function get_premium_settings() {
		return array();
	}

	public function get_premium_sidebar() {
		return '';
	}

}

endif;

?>