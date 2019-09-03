<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Vendidero\Germanized\DHL\Admin\Settings;

/**
 * Adds Germanized Tax settings.
 *
 * @class 		WC_GZD_Settings_Tab_Taxes
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Tab_DHL extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Automatically generate DHL labels for your shipments.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'DHL', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'dhl';
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

	protected function get_enable_option_name() {
		return '';
	}

	public function supports_disabling() {
		return true;
	}
}