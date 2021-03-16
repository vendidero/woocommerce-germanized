<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Vendidero\Germanized\Shipments\Admin\ProviderSettings;

/**
 * Adds Germanized Shipments settings.
 *
 * @class        WC_GZD_Settings_Tab_Shipments
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_Shipping_Provider extends WC_GZD_Settings_Tab {

	public function get_description() {
		return ProviderSettings::get_description();
	}

	protected function get_breadcrumb() {
		$breadcrumb = array(
			array(
				'class' => 'main',
				'href'  => admin_url( 'admin.php?page=wc-settings&tab=germanized' ),
				'title' => __( 'Germanized', 'woocommerce-germanized' )
			)
		);

		$breadcrumb = array_merge( $breadcrumb, ProviderSettings::get_breadcrumb() );

		return $breadcrumb;
	}

	public function hide_from_main_panel() {
		return true;
	}

	public function get_label() {
		return __( 'Shipping Provider', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'shipping_provider';
	}

	public function get_help_link() {
		return ProviderSettings::get_help_link();
	}

	public function get_sections() {
		return ProviderSettings::get_sections();
	}

	public function save() {
		ProviderSettings::save();
	}

	public function output() {
		parent::output();
	}

	public function get_tab_settings( $current_section = '' ) {
		return ProviderSettings::get_settings( $current_section );
	}
}