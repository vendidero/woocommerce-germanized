<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Tax settings.
 *
 * @class        WC_GZD_Settings_Tab_Taxes
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_Taxes extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Adjust tax related settings e.g. Split-tax calculation for shipping costs.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Taxes', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'taxes';
	}

	public function get_sections() {
		$sections = array(
			''                      => __( 'VAT', 'woocommerce-germanized' ),
			'additional_costs'      => __( 'Additional Costs', 'woocommerce-germanized' ),
			'differential_taxation' => __( 'Differential Taxation', 'woocommerce-germanized' ),
		);

		if ( \Vendidero\Germanized\PluginsHelper::is_oss_plugin_active() ) {
			$sections = $sections + array(
				'oss' => __( 'One Stop Shop', 'woocommerce-germanized' ),
			);
		}

		return $sections;
	}

	public function get_help_link() {
		return 'https://vendidero.de/dokument/steuerberechnung-fuer-versandkosten-und-gebuehren';
	}

	protected function get_vat_settings() {
		$virtual_vat = 'yes' !== get_option( 'woocommerce_gzd_enable_virtual_vat' ) ? array() : array(
			'title'   => __( 'Virtual VAT', 'woocommerce-germanized' ),
			'desc'    => __( 'Enable if you want to charge your customer\'s countries\' VAT for virtual products.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'New EU VAT rule applies on 01.01.2015. Make sure that every digital or virtual product has chosen the right tax class (Virtual Rate or Virtual Reduced Rate). Gross prices will not differ from the prices you have chosen for affected products. In fact the net price will differ depending on the VAT rate of your customers\' country. Shop settings will be adjusted to show prices including tax. More information can be found <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'http://ec.europa.eu/taxation_customs/taxation/vat/how_vat_works/telecom/index_de.htm#new_rules' ) . '</div>',
			'id'      => 'woocommerce_gzd_enable_virtual_vat',
			'default' => 'no',
			'type'    => 'gzd_toggle',
		);

		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'vat_options',
			),

			$virtual_vat,

			array(
				'title'    => __( 'Tax Rate', 'woocommerce-germanized' ),
				'desc'     => __( 'Hide specific tax rate within shop pages.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_hide_tax_rate_shop',
				'default'  => 'yes',
				'type'     => 'gzd_toggle',
				'desc_tip' => __( 'This option will make sure that within shop pages no specific tax rates are shown. Instead only incl. tax or excl. tax notice is shown.', 'woocommerce-germanized' ),
			),

			array(
				'title'    => __( 'Tax totals', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_tax_totals_display',
				'default'  => 'after',
				'type'     => 'select',
				'options'  => array(
					'before' => __( 'Before total amount', 'woocommerce-germanized' ),
					'after'  => __( 'After total amount', 'woocommerce-germanized' ),
				),
				'desc_tip' => __( 'Decide whether to show tax totals before or after total amount.', 'woocommerce-germanized' ),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'vat_options',
			),
		);

		return array_merge( $settings, $this->get_vat_id_settings() );
	}

	protected function get_vat_id_settings() {
		return array(
			array(
				'title' => __( 'VAT ID', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'vat_id_options',
				'desc'  => '<div class="notice inline notice-warning wc-gzd-premium-overlay"><p>' . sprintf( __( '%1$sUpgrade to %2$spro%3$s%4$s to unlock this feature and enjoy premium support.', 'woocommerce-germanized' ), '<a href="https://vendidero.de/woocommerce-germanized" class="button button-primary wc-gzd-button">', '<span class="wc-gzd-pro">', '</span>', '</a>' ) . '</p></div>',
			),
			array(
				'title' => '',
				'id'    => 'woocommerce_gzdp_enable_vat_check',
				'img'   => WC_Germanized()->plugin_url() . '/assets/images/pro/settings-inline-vat.png?v=' . WC_germanized()->version,
				'href'  => 'https://vendidero.de/woocommerce-germanized/features#b2b',
				'type'  => 'image',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'vat_id_options',
			),
		);
	}

	protected function get_additional_costs_settings() {
		$split_tax_example             = sprintf( __( 'By choosing this option shipping cost and fee taxes will be calculated based on the tax rates included within the cart. The tax share is calculated based on net prices. Further information can be found <a href="%1$s" target="_blank">here</a>. %2$s', 'woocommerce-germanized' ), 'https://vendidero.de/dokument/steuerberechnung-fuer-versandkosten-und-gebuehren', '<table class="wc-gzd-tax-example"><thead><tr><th>' . __( 'Product', 'woocommerce-germanized' ) . '</th><th>' . __( 'Price', 'woocommerce-germanized' ) . '</th><th>' . __( 'Price (net)', 'woocommerce-germanized' ) . '</th><th>' . __( 'Tax rate', 'woocommerce-germanized' ) . '</th><th>' . __( 'Share', 'woocommerce-germanized' ) . '</th><th>' . __( 'Tax', 'woocommerce-germanized' ) . '</th></tr></thead><tbody><tr><td>' . __( 'Book', 'woocommerce-germanized' ) . '</td><td>' . wc_price( 40 ) . '</td><td>' . wc_price( 37.38 ) . '</td><td>7 %</td><td>42.56 %</td><td>' . wc_price( 2.62 ) . '</td></tr><tr><td>' . __( 'DVD', 'woocommerce-germanized' ) . '</td><td>' . wc_price( 60 ) . '</td><td>' . wc_price( 50.42 ) . '</td><td>19 %</td><td>57.43 %</td><td>' . wc_price( 9.58 ) . '</td></tr><tr><td>' . __( 'Shipping', 'woocommerce-germanized' ) . '</td><td>' . wc_price( 5 ) . '</td><td>' . wc_price( 4.40 ) . '</td><td>7 % | 19 %</td><td>42.56 % | 57.43 %</td><td>' . wc_price( 0.14 ) . ' | ' . wc_price( 0.46 ) . '</td></tr></tbody></table>' );
		$main_service_net_example      = sprintf( __( 'By choosing this option shipping cost and fee taxes will be calculated based on the tax class of the detected main service. %1$s Further information can be found <a href="%2$s" target="_blank">here</a>. %3$s', 'woocommerce-germanized' ), __( 'The main service is detected by choosing the line item with the highest total net amount.', 'woocommerce-germanized' ), 'https://vendidero.de/dokument/steuerberechnung-fuer-versandkosten-und-gebuehren', '<table class="wc-gzd-tax-example"><thead><tr><th>' . __( 'Product', 'woocommerce-germanized' ) . '</th><th>' . __( 'Price', 'woocommerce-germanized' ) . '</th><th>' . __( 'Price (net)', 'woocommerce-germanized' ) . '</th><th>' . __( 'Tax rate', 'woocommerce-germanized' ) . '</th><th>' . __( 'Tax', 'woocommerce-germanized' ) . '</th></tr></thead><tbody><tr><td>' . __( 'Book', 'woocommerce-germanized' ) . '</td><td>' . wc_price( 60 ) . '</td><td>' . wc_price( 56.07 ) . '</td><td>7 %</td><td>' . wc_price( 3.93 ) . '</td></tr><tr><td>' . __( 'DVD', 'woocommerce-germanized' ) . '</td><td>' . wc_price( 60 ) . '</td><td>' . wc_price( 50.42 ) . '</td><td>19 %</td><td>' . wc_price( 9.58 ) . '</td></tr><tr><td>' . __( 'Shipping', 'woocommerce-germanized' ) . '</td><td>' . wc_price( 5 ) . '</td><td>' . wc_price( 4.67 ) . '</td><td>7 %</td><td>' . wc_price( 0.33 ) . '</td></tr></tbody></table>' );
		$main_service_tax_rate_example = sprintf( __( 'By choosing this option shipping cost and fee taxes will be calculated based on the tax class of the detected main service. %1$s Further information can be found <a href="%2$s" target="_blank">here</a>. %3$s', 'woocommerce-germanized' ), __( 'The main service is detected by choosing the line item with the highest tax rate.', 'woocommerce-germanized' ), 'https://vendidero.de/dokument/steuerberechnung-fuer-versandkosten-und-gebuehren', '<table class="wc-gzd-tax-example"><thead><tr><th>' . __( 'Product', 'woocommerce-germanized' ) . '</th><th>' . __( 'Price', 'woocommerce-germanized' ) . '</th><th>' . __( 'Price (net)', 'woocommerce-germanized' ) . '</th><th>' . __( 'Tax rate', 'woocommerce-germanized' ) . '</th><th>' . __( 'Tax', 'woocommerce-germanized' ) . '</th></tr></thead><tbody><tr><td>' . __( 'Book', 'woocommerce-germanized' ) . '</td><td>' . wc_price( 60 ) . '</td><td>' . wc_price( 56.07 ) . '</td><td>7 %</td><td>' . wc_price( 3.93 ) . '</td></tr><tr><td>' . __( 'DVD', 'woocommerce-germanized' ) . '</td><td>' . wc_price( 60 ) . '</td><td>' . wc_price( 50.42 ) . '</td><td>19 %</td><td>' . wc_price( 9.58 ) . '</td></tr><tr><td>' . __( 'Shipping', 'woocommerce-germanized' ) . '</td><td>' . wc_price( 5 ) . '</td><td>' . wc_price( 4.20 ) . '</td><td>19 %</td><td>' . wc_price( 0.80 ) . '</td></tr></tbody></table>' );

		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'additional_costs_options',
			),

			array(
				'title'    => __( 'Tax calculation mode', 'woocommerce-germanized' ),
				'desc'     => __( 'Select the tax calculation mode for your additional costs (shipping, fees).', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'       => 'woocommerce_gzd_tax_mode_additional_costs',
				'type'     => 'radio',
				'default'  => 'split_tax',
				'options'  => array(
					'none'         => __( 'WooCommerce default', 'woocommerce-germanized' ),
					'split_tax'    => __( 'Proportionate', 'woocommerce-germanized' ),
					'main_service' => __( 'Based on main service', 'woocommerce-germanized' ),
				),
			),

			array(
				'title'             => __( 'Detect Main Service by', 'woocommerce-germanized' ),
				'desc'              => __( 'Choose how to detect the main service.', 'woocommerce-germanized' ),
				'desc_tip'          => true,
				'id'                => 'woocommerce_gzd_tax_mode_additional_costs_detect_main_service',
				'type'              => 'radio',
				'default'           => 'highest_net_amount',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_tax_mode_additional_costs' => 'main_service',
				),
				'options'           => array(
					'highest_net_amount' => __( 'Highest net amount', 'woocommerce-germanized' ),
					'highest_tax_rate'   => __( 'Highest tax rate', 'woocommerce-germanized' ),
				),
			),

			array(
				'title'             => __( 'Example', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_gzd_tax_mode_additional_costs_split_tax',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_tax_mode_additional_costs' => 'split_tax',
				),
				'type'              => 'html',
				'html'              => '<div class="wc-gzd-additional-desc">' . $split_tax_example . '</div>',
			),

			array(
				'title'             => __( 'Example', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_gzd_tax_mode_additional_costs_main_service_net_amount',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_tax_mode_additional_costs' => 'main_service',
					'data-show_if_woocommerce_gzd_tax_mode_additional_costs_detect_main_service' => 'highest_net_amount',
				),
				'type'              => 'html',
				'html'              => '<div class="wc-gzd-additional-desc">' . $main_service_net_example . '</div>',
			),

			array(
				'title'             => __( 'Example', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_gzd_tax_mode_additional_costs_main_service_tax_rate',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_tax_mode_additional_costs' => 'main_service',
					'data-show_if_woocommerce_gzd_tax_mode_additional_costs_detect_main_service' => 'highest_tax_rate',
				),
				'type'              => 'html',
				'html'              => '<div class="wc-gzd-additional-desc">' . $main_service_tax_rate_example . '</div>',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'additional_costs_options',
			),
		);
	}

	protected function get_differential_taxation_settings() {
		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'differential_taxation_options',
			),

			array(
				'title'   => __( 'Taxation Notice', 'woocommerce-germanized' ),
				'desc'    => __( 'Enable differential taxation text notice beneath product price.', 'woocommerce-germanized' ) . ' <div class="wc-gzd-additional-desc">' . __( 'If you have disabled this option, a normal VAT notice will be displayed, which is sufficient as Trusted Shops states. To further inform your customers you may enable this notice.', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'woocommerce_gzd_differential_taxation_show_notice',
				'default' => 'yes',
				'type'    => 'gzd_toggle',
			),
			array(
				'title'   => __( 'Mixed carts', 'woocommerce-germanized' ),
				'desc'    => __( 'Disallow buying normal and differential taxed products at the same time.', 'woocommerce-germanized' ) . ' <div class="wc-gzd-additional-desc">' . sprintf( __( 'Shipping costs for differential taxed products may not be taxed (compare %s) or must be taxed separately which is impossible within a single order. This option will prevent your customers from buying normal products and differential taxed products at the same time to prevent taxation problems.', 'woocommerce-germanized' ), '<a href="https://www.hk24.de/produktmarken/beratung-service/recht_und_steuern/steuerrecht/umsatzsteuer_mehrwertsteuer/umsatzsteuer_mehrwertsteuer_national/grundsaetzliches-allgemeines/differenzbesteuerung-gebrauchtwarenhandel/1167726">' . __( 'HK Hamburg', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'id'      => 'woocommerce_gzd_differential_taxation_disallow_mixed_carts',
				'default' => 'yes',
				'type'    => 'gzd_toggle',
			),
			array(
				'title'    => __( 'Notice Text', 'woocommerce-germanized' ),
				'desc'     => __( 'This text will be shown as a further notice for the customer to inform him about differential taxation.', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'       => 'woocommerce_gzd_differential_taxation_notice_text',
				'type'     => 'textarea',
				'css'      => 'width:100%; height: 50px;',
				'default'  => __( 'incl. VAT (differential taxation according to §25a UStG.)', 'woocommerce-germanized' ),
			),

			array(
				'title'   => __( 'Checkout & E-Mails', 'woocommerce-germanized' ),
				'desc'    => __( 'Enable differential taxation notice during checkout and in emails.', 'woocommerce-germanized' ),
				'id'      => 'woocommerce_gzd_differential_taxation_checkout_notices',
				'default' => 'yes',
				'type'    => 'gzd_toggle',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'differential_taxation_options',
			),
		);
	}

	protected function get_section_url( $section_id ) {
		if ( 'oss' === $section_id ) {
			return admin_url( 'admin.php?page=wc-settings&tab=germanized-oss' );
		}

		return parent::get_section_url( $section_id );
	}

	public function get_tab_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = $this->get_vat_settings();
		} elseif ( 'additional_costs' === $current_section ) {
			$settings = $this->get_additional_costs_settings();
		} elseif ( 'differential_taxation' === $current_section ) {
			$settings = $this->get_differential_taxation_settings();
		}

		return $settings;
	}
}
