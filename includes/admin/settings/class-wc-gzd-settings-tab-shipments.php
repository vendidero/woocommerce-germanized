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
		return __( 'Create shipments, improve packing and manage available shipping providers.', 'woocommerce-germanized' );
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
		return array();
	}

	public function get_pointers() {
		return array();
	}

	public function get_tab_settings( $current_section = '' ) {
		return array();
	}
}
