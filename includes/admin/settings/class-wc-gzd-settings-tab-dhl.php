<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Vendidero\Germanized\DHL\Admin\Settings;

/**
 * Adds Germanized Tax settings.
 *
 * @class        WC_GZD_Settings_Tab_Taxes
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_DHL extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Integrate DHL & Deutsche Post Services such as Labels for Shipments and Returns.', 'woocommerce-germanized' );
	}

	protected function get_breadcrumb_label( $label ) {
		$label = parent::get_breadcrumb_label( $label );

		if ( empty( $this->get_current_section() ) ) {
			$label .= '<a href="https://www.dhl.de/de/geschaeftskunden/paket/kunde-werden/angebot-dhl-geschaeftskunden-online.html" class="page-title-action" target="_blank">' . _x( 'Not yet a customer?', 'dhl', 'woocommerce-germanized' ) . '</a>';
		} elseif( 'internetmarke' === $this->get_current_section() ) {
			$label .= '<a href="https://portokasse.deutschepost.de/portokasse/#!/register/" class="page-title-action" target="_blank">' . _x( 'No Portokasse yet?', 'dhl', 'woocommerce-germanized' ) . '</a>';
		}

		return $label;
	}

	public function get_label() {
		return __( 'DHL & Deutsche Post', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'dhl';
	}

	public function get_help_link() {
		return 'https://vendidero.de/dokument/dhl-integration-einrichten';
	}

	public function get_tab_settings( $current_section = '' ) {
		$settings = Settings::get_settings( $current_section );

		return $settings;
	}

	public function get_sections() {
		return Settings::get_sections();
	}

	public function get_section_description( $section ) {
		return Settings::get_section_description( $section );
	}

	public function get_pointers() {
		return Settings::get_pointers( $this->get_current_section() );
	}

	protected function get_enable_option_name() {
		return 'woocommerce_gzd_dhl_enable';
	}

	public function supports_disabling() {
		return true;
	}

	public function disable() {
		parent::disable();

		update_option( 'woocommerce_gzd_dhl_internetmarke_enable', 'no' );
	}

	public function is_enabled() {
		$is_enabled = parent::is_enabled();

		if ( \Vendidero\Germanized\DHL\Package::is_internetmarke_enabled() ) {
			$is_enabled = true;
		}

		return $is_enabled;
	}
}