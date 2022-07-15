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

	public function get_extension_name() {
		return 'one-stop-shop-woocommerce';
	}

	public function needs_install() {
		return ! \Vendidero\Germanized\PluginsHelper::is_oss_plugin_active();
	}

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
		if ( \Vendidero\Germanized\PluginsHelper::is_oss_plugin_active() ) {
			return Vendidero\OneStopShop\Settings::get_sections();
		} else {
			return array(
				'' => __( 'General', 'woocommerce-germanized' ),
			);
		}
	}

	public function get_tab_settings( $current_section = '' ) {
		if ( \Vendidero\Germanized\PluginsHelper::is_oss_plugin_active() ) {
			return Vendidero\OneStopShop\Settings::get_settings( $current_section );
		} else {
			return array();
		}
	}

	public function is_enabled() {
		if ( \Vendidero\Germanized\PluginsHelper::is_oss_plugin_active() && ( \Vendidero\OneStopShop\Package::oss_procedure_is_enabled() || \Vendidero\OneStopShop\Package::enable_auto_observer() ) ) {
			return true;
		}

		return false;
	}
}
