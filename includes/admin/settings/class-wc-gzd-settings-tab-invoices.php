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
class WC_GZD_Settings_Tab_Invoices extends WC_GZD_Settings_Tab {

	/**
	 * Germanized Pro extends this tab - keep its naming untouched and only
	 * use the accounting naming for the pro preview.
	 *
	 * @return bool
	 */
	protected function is_preview() {
		return ! WC_germanized()->is_pro();
	}

	public function get_description() {
		if ( $this->is_preview() ) {
			return __( 'Create (e-)invoices and connect lexware office, sevdesk & DATEV.', 'woocommerce-germanized' );
		}

		return \Vendidero\Germanized\PluginsHelper::is_shiptastic_plugin_active() ? __( 'Create invoices, e-invoices, packing slips & commercial invoices conveniently.', 'woocommerce-germanized' ) : __( 'Create invoices and e-invoices conveniently.', 'woocommerce-germanized' );
	}

	public function get_label() {
		if ( $this->is_preview() ) {
			$label = __( 'Accounting', 'woocommerce-germanized' );
		} else {
			$label = \Vendidero\Germanized\PluginsHelper::is_shiptastic_plugin_active() ? __( 'Invoices & Packing Slips', 'woocommerce-germanized' ) : __( 'Invoices', 'woocommerce-germanized' );
		}

		return $label . ' <span class="wc-gzd-pro wc-gzd-pro-outlined">' . __( 'pro', 'woocommerce-germanized' ) . '</span>';
	}

	public function get_name() {
		return 'invoices';
	}

	public function get_help_link() {
		return 'https://vendidero.de/doc/allgemein/buchhaltung-im-ueberblick';
	}

	public function is_pro() {
		return true;
	}

	public function get_tab_settings( $current_section = '' ) {
		return $this->get_pro_preview_settings(
			array(
				'title'        => __( 'Accounting with Germanized Pro', 'woocommerce-germanized' ),
				'desc'         => __( 'Create invoices and cancellations automatically and transfer them to your accounting software or tax advisor.', 'woocommerce-germanized' ),
				'features'     => array(
					__( 'Create invoices and cancellations automatically, e.g. based on order status or payment method', 'woocommerce-germanized' ),
					__( 'E-invoices in ZUGFeRD, XRechnung and Peppol BIS 3.0 format', 'woocommerce-germanized' ),
					__( 'Transfer your documents to your lexware office and/or sevdesk account', 'woocommerce-germanized' ),
					__( 'DATEV export for your tax advisor', 'woocommerce-germanized' ),
					__( 'Export your documents as CSV and ZIP', 'woocommerce-germanized' ),
				),
				'href'         => 'https://vendidero.de/woocommerce-germanized/features#accounting',
				'placeholders' => array(
					array(
						'title' => 'Invoices',
						'type'  => 'title',
					),
					array(
						'title' => 'Automation',
						'type'  => 'toggle',
						'value' => 'yes',
						'desc'  => 'Automatically create invoices for new orders.',
					),
					array(
						'title' => 'Order status(es)',
						'type'  => 'select',
						'value' => 'Processing, Completed',
					),
					array(
						'title' => 'Format',
						'type'  => 'text',
						'value' => 'RE-{number}',
					),
					array(
						'title' => 'Last Number',
						'type'  => 'text',
						'value' => '1042',
					),
					array(
						'title' => 'E-Invoice',
						'type'  => 'select',
						'value' => 'ZUGFeRD (Comfort)',
					),
					array(
						'title' => 'Cancellations',
						'type'  => 'title',
					),
					array(
						'title' => 'Automation',
						'type'  => 'toggle',
						'value' => 'yes',
					),
					array(
						'title' => 'Format',
						'type'  => 'text',
						'value' => 'ST-{number}',
					),
					array(
						'title' => 'External Services',
						'type'  => 'title',
					),
					array(
						'title' => 'lexware office',
						'type'  => 'toggle',
						'value' => 'yes',
					),
					array(
						'title' => 'sevdesk',
						'type'  => 'toggle',
						'value' => 'no',
					),
					array(
						'title' => 'DATEV',
						'type'  => 'title',
					),
					array(
						'title' => 'Accounting system',
						'type'  => 'select',
						'value' => 'SKR03',
					),
					array(
						'title' => 'Account length',
						'type'  => 'text',
						'value' => '4',
					),
					array(
						'title' => 'Book payments',
						'type'  => 'toggle',
						'value' => 'yes',
					),
				),
			)
		);
	}

	public function is_enabled() {
		return false;
	}
}
