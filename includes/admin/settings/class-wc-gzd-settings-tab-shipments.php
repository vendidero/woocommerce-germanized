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

	public function __construct() {
		parent::__construct();

		$this->id = 'shipments';

		add_filter( 'woocommerce_gzd_shipments_settings_main_breadcrumb', array( $this, 'register_main_breadcrumb' ) );
	}

	public function register_main_breadcrumb( $main_breadcrumb ) {
		$basic_main_breadcrumb = array(
			array(
				'class' => 'main',
				'href'  => admin_url( 'admin.php?page=wc-settings&tab=germanized' ),
				'title' => __( 'Germanized', 'woocommerce-germanized' ),
			),
		);

		return array_merge( $basic_main_breadcrumb, $main_breadcrumb );
	}

	public function get_description() {
		$description = __( 'Configure shipments and manage shipping providers.', 'woocommerce-germanized' );

		if ( class_exists( '\Vendidero\Germanized\Shipments\ShippingProvider\Helper' ) ) {
			$integrations  = \Vendidero\Germanized\Shipments\ShippingProvider\Helper::instance()->get_available_shipping_provider_integrations();
			$provider_list = array();

			foreach ( $integrations as $integration ) {
				$provider_list[] = $integration->get_title();
			}

			if ( ! empty( $provider_list ) ) {
				$provider_list = implode( ', ', $provider_list );
				$pos           = strrpos( $provider_list, ', ' );

				if ( false !== $pos ) {
					$provider_list = substr_replace( $provider_list, ' & ', $pos, strlen( ', ' ) );
				}

				$description = sprintf( __( 'Configure shipments and manage shipping providers, e.g. %s.', 'woocommerce-germanized' ), trim( $provider_list ) );
			}
		}

		return $description;
	}

	public function get_label() {
		return _x( 'Shipments & more', 'shipments-settings-tab', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'shipments';
	}

	public function get_help_link() {
		return 'https://vendidero.de/dokument/sendungen-zu-bestellungen-erzeugen';
	}

	public function get_sections() {
		return array();
	}

	public function get_pointers() {
		return array();
	}

	public function get_tab_settings( $current_section = '' ) {
		return array();
	}
}
