<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Tax settings.
 *
 * @class 		WC_GZD_Settings_Tab_Taxes
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Tab_Taxes extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Find tax related settings like shipping costs taxation here.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Taxes', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'taxes';
	}

	public function get_tab_settings( $current_section = '' ) {
		$shipping_tax_example = sprintf( __( 'By choosing this option shipping cost taxation will be calculated based on tax rates within cart. Imagine the following example. Further information can be found <a href="%s" target="_blank">here</a>. %s', 'woocommerce-germanized' ), 'http://www.it-recht-kanzlei.de/umsatzsteuer-versandkosten-mehrwertsteuer.html', '<table class="wc-gzd-tax-example"><thead><tr><th>Produkt</th><th>Preis</th><th>MwSt.-Satz</th><th>Anteil</th><th>MwSt.</th></tr></thead><tbody><tr><td>Buch</td><td>' . wc_price( 40 ) . '</td><td>7%</td><td>40%</td><td>' . wc_price( 2.62 ) . '</td></tr><tr><td>DVD</td><td>' . wc_price( 60 ) . '</td><td>19%</td><td>60%</td><td>' . wc_price( 9.58 ) . '</td></tr><tr><td>Versand</td><td>' . wc_price( 5 ) . '</td><td>7% | 19%</td><td>40% | 60%</td><td>' . wc_price( 0.13 ) . ' | ' . wc_price( 0.48 ) . '</td></tr></tbody></table>' );

		return array(
			array( 'title' => __( 'Shipping costs', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'shipping_tax_options' ),

			array(
				'title' 	=> __( 'Split-tax', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable split-tax calculation for shipping costs.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . $shipping_tax_example . '</div>',
				'id' 		=> 'woocommerce_gzd_shipping_tax',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),
			array(
				'title' 	=> __( 'Force', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Force split-tax calculation for shipping methods.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_shipping_tax_force',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_shipping_tax' => '',
				),
				'desc_tip'	=> __( 'This option will overwrite settings for each individual shipping method to force tax calculation (instead of only calculating tax for those methods which are taxeable).', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_tax_options' ),

			array( 'title' => __( 'Fees', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'fee_tax_options' ),

			array(
				'title' 	=> __( 'Split-tax', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable split-tax calculation for fees.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_fee_tax',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),
			array(
				'title' 	=> __( 'Force', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Force split-tax calculation for fees.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_fee_tax_force',
				'default'	=> 'yes',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_fee_tax' => '',
				),
				'type' 		=> 'gzd_toggle',
				'desc_tip'	=> __( 'This option will overwrite settings for each individual fee to force tax calculation (instead of only calculating tax for those fees which are taxeable).', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'fee_tax_options' ),

			array( 'title' => __( 'Differential Taxation', 'woocommerce-germanized' ), 'type' => 'title', 'desc' => '', 'id' => 'differential_taxation_options' ),

			array(
				'title' 	=> __( 'Taxation Notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable differential taxation text notice beneath product price.', 'woocommerce-germanized' ) . ' <div class="wc-gzd-additional-desc">' . __( 'If you have disabled this option, a normal VAT notice will be displayed, which is sufficient as Trusted Shops states. To further inform your customers you may enable this notice.', 'woocommerce-germanized' ) . '</div>',
				'id' 		=> 'woocommerce_gzd_differential_taxation_show_notice',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),
			array(
				'title' 	=> __( 'Notice Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be shown as a further notice for the customer to inform him about differential taxation.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_differential_taxation_notice_text',
				'type' 		=> 'textarea',
				'css' 		=> 'width:100%; height: 50px;',
				'default'	=> __( 'incl. VAT (differential taxation according to §25a UStG.)', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Checkout & E-Mails', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable differential taxation notice during checkout and in emails.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_differential_taxation_checkout_notices',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),

			array( 'type' => 'sectionend', 'id' => 'differential_taxation_options' ),

			array( 'title' => __( 'VAT', 'woocommerce-germanized' ), 'type' => 'title', 'desc' => '', 'id' => 'vat_options' ),

			array(
				'title' 	=> __( 'Virtual VAT', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable if you want to charge your customer\'s countries\' VAT for virtual products.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'New EU VAT rule applies on 01.01.2015. Make sure that every digital or virtual product has chosen the right tax class (Virtual Rate or Virtual Reduced Rate). Gross prices will not differ from the prices you have chosen for affected products. In fact the net price will differ depending on the VAT rate of your customers\' country. Shop settings will be adjusted to show prices including tax. More information can be found <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'http://ec.europa.eu/taxation_customs/taxation/vat/how_vat_works/telecom/index_de.htm#new_rules' ) . '</div>',
				'id' 		=> 'woocommerce_gzd_enable_virtual_vat',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> __( 'Tax Rate', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Hide specific tax rate within shop pages.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_hide_tax_rate_shop',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'desc_tip'	=> __( 'This option will make sure that within shop pages no specific tax rates are shown. Instead only incl. tax or excl. tax notice is shown.', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'differential_taxation_options' ),
		);
	}
}