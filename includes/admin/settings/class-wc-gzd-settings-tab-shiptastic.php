<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Shipments settings.
 *
 * @class        WC_GZD_Settings_Tab_Shiptastic
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_Shiptastic extends WC_GZD_Settings_Tab {

	public function __construct() {
		parent::__construct();

		$this->id = 'shiptastic';

		add_filter( 'woocommerce_shiptastic_settings_main_breadcrumb', array( $this, 'register_main_breadcrumb' ) );
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

		if ( class_exists( '\Vendidero\Shiptastic\ShippingProvider\Helper' ) ) {
			$provider_available = \Vendidero\Shiptastic\ShippingProvider\Helper::instance()->get_available_shipping_provider_integrations();

			foreach ( array_slice( $provider_available, 0, 3 ) as $provider ) {
				$provider_name_list[] = $provider->get_title();
			}

			if ( ! empty( $provider_name_list ) ) {
				$provider_list = implode( ', ', $provider_name_list );
				$description   = sprintf( __( 'Configure shipments and manage shipping providers, e.g. %s & more.', 'woocommerce-germanized' ), trim( $provider_list ) );
			}
		}

		return $description;
	}

	public function get_label() {
		return _x( 'Shiptastic', 'shipments-settings-tab', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'shiptastic';
	}

	public function get_help_link() {
		return 'https://vendidero.de/doc/woocommerce-germanized/sendungen-zu-bestellungen-erzeugen';
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
