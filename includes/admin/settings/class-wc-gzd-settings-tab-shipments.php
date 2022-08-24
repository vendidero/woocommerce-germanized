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

	protected function get_additional_breadcrumb_items( $breadcrumb ) {
		return Settings::get_additional_breadcrumb_items( $breadcrumb );
	}

	protected function get_breadcrumb_label( $label ) {
		$current_section = $this->get_current_section();

		$label = parent::get_breadcrumb_label( $label );

		if ( empty( $current_section ) ) {
			return $label . '<a href="' . esc_url( admin_url( 'admin.php?page=wc-gzd-shipments' ) ) . '" class="page-title-action" target="_blank">' . _x( 'Manage', 'shipments', 'woocommerce-germanized' ) . '</a>';
		} else {
			return $label;
		}
	}

	protected function after_save( $settings, $current_section = '' ) {
		parent::after_save( $settings, $current_section );

		Settings::after_save( $current_section );
	}

	public function get_pointers() {
		return Settings::get_pointers( $this->get_current_section() );
	}

	public function get_tab_settings( $current_section = '' ) {
		$settings = Settings::get_settings( $current_section );

		if ( 'packaging' === $current_section && \Vendidero\Germanized\Shipments\Package::is_packing_supported() ) {
			$settings = array_merge( $settings, $this->get_auto_packing_settings() );
		}

		return $settings;
	}

	protected function get_auto_packing_settings() {
		return array(
			array(
				'title' => __( 'Automated packing', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'automated_packing_options',
				'desc'  => '<div class="notice inline notice-warning wc-gzd-premium-overlay"><p>' . sprintf( __( '%1$sUpgrade to %2$spro%3$s%4$s to unlock this feature and enjoy premium support.', 'woocommerce-germanized' ), '<a href="https://vendidero.de/woocommerce-germanized" class="button button-primary wc-gzd-button">', '<span class="wc-gzd-pro">', '</span>', '</a>' ) . '</p></div>',
			),
			array(
				'title' => '',
				'id'    => 'woocommerce_gzdp_enable_shipment_auto_packing',
				'img'   => WC_Germanized()->plugin_url() . '/assets/images/pro/settings-inline-packing.png?v=' . WC_germanized()->version,
				'href'  => 'https://vendidero.de/woocommerce-germanized/features#shipments',
				'type'  => 'image',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'automated_packing_options',
			),
		);
	}
}
