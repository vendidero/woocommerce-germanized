<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Vendidero\Germanized\Shipments\Admin\Settings;

/**
 * Adds Germanized Shipments settings.
 *
 * @class        WC_GZD_Settings_Tab_Shipments
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_Shipments extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Create shipments for your orders and improve default shipment handling.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Shipments', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'shipments';
	}

	public function get_help_link() {
		return 'https://vendidero.de/dokument/sendungen-zu-bestellungen-erzeugen';
	}

	public function get_sections() {
		return Settings::get_sections();
	}

	public function save() {
		global $current_section;

		if ( 'provider' === $current_section && isset( $_GET['provider'] ) ) {
			$provider = wc_clean( wp_unslash( $_REQUEST['provider'] ) );
			Settings::save_provider( $provider );
		} else {
			parent::save();
		}
	}

	public function output() {
		$current_section = $this->get_current_section();

		if ( 'provider' === $current_section && ! isset( $_GET['provider'] ) ) {
			Settings::output_providers();
		} else {
			parent::output();
		}
	}

	protected function get_additional_breadcrumb_items( $breadcrumb ) {
		return Settings::get_additional_breadcrumb_items( $breadcrumb );
	}

	protected function get_breadcrumb_label( $label ) {
		$current_section = $this->get_current_section();

		$label = parent::get_breadcrumb_label( $label );

		if ( empty( $current_section ) ) {
			return $label . '<a href="' . admin_url( 'admin.php?page=wc-gzd-shipments' ) . '" class="page-title-action" target="_blank">' . _x( 'Manage', 'shipments', 'woocommerce-germanized' ) . '</a>';
		} else {
			return $label . Settings::get_section_title_link( $current_section );
		}
	}

	public function get_pointers() {
		return Settings::get_pointers( $this->get_current_section() );
	}

	public function get_tab_settings( $current_section = '' ) {
		return Settings::get_settings( $current_section );
	}
}