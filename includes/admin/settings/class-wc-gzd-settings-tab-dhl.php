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
		return __( 'Integrate Post & DHL Services such as Labels for Shipments and Returns.', 'woocommerce-germanized' );
	}

	protected function get_breadcrumb_label( $label ) {
		$current_section = $this->get_current_section();
		$help_link       = false;

		if ( empty( $current_section ) ) {
			$help_link = 'https://vendidero.de/dokument/dhl-integration-einrichten';
		} elseif( 'internetmarke' === $current_section ) {
			$help_link = 'https://vendidero.de/dokument/internetmarke-integration-einrichten';
		} elseif( 'labels' === $current_section ) {
			$help_link = 'https://vendidero.de/dokument/dhl-labels-zu-sendungen-erstellen';
		}

		if ( $help_link ) {
			$label = $label . '<a class="page-title-action" href="' . esc_url( $help_link ) . '" target="_blank">' . __( 'Learn more', 'woocommerce-germanized' ) . '</a>';
		}

		$label .= Settings::get_new_customer_label( $this->get_current_section() );

		return $label;
	}

	public function get_label() {
		return __( 'Post & DHL', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'dhl';
	}

	public function get_help_link() {
		return 'https://vendidero.de/dokumentation/post-dhl';
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