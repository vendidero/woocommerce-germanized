<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Shipments settings.
 *
 * @class        WC_GZD_Settings_Tab_Shipments
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_OSS extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Comply with the OSS procedure and conveniently generate tax reports.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'One Stop Shop', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'oss';
	}

	public function get_help_link() {
		return 'https://vendidero.github.io/one-stop-shop-woocommerce';
	}

	public function get_sections() {
		return \Vendidero\OneStopShop\Settings::get_sections();
	}

	public function get_tab_settings( $current_section = '' ) {
		$settings = \Vendidero\OneStopShop\Settings::get_settings( $current_section );

		return $settings;
	}

	protected function before_save( $settings, $current_section = '' ) {
		\Vendidero\OneStopShop\Settings::before_save();

		parent::before_save( $settings, $current_section );
	}

	protected function after_save( $settings, $current_section = '' ) {
		if ( 'yes' === get_option( 'oss_use_oss_procedure' ) ) {
			// Delete legacy options while migrating to OSS.
			delete_option( 'woocommerce_gzd_enable_virtual_vat' );
		}

		parent::after_save( $settings, $current_section );
	}

	public function is_enabled() {
		if ( \Vendidero\OneStopShop\Package::oss_procedure_is_enabled() || \Vendidero\OneStopShop\Package::enable_auto_observer() ) {
			return true;
		}

		return false;
	}
}