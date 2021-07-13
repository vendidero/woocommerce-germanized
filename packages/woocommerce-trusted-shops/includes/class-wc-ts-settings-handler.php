<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_TS_Settings_Handler' ) ) :

/**
 * Adds Settings Interface to WooCommerce Settings Tabs
 *
 * @class 		WC_GZD_Settings_Germanized
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_TS_Settings_Handler extends WC_Settings_Page {

	/**
	 * Adds Hooks to output and save settings
	 */
	public function __construct() {
		$this->id    = 'trusted-shops';
		$this->label = _x( 'Trusted Shops', 'trusted-shops', 'woocommerce-germanized' );

		parent::__construct();
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		$admin    = WC_trusted_shops()->trusted_shops->get_dependency( 'admin' );
		$settings = $admin->get_settings();

		return $settings;
	}

	public function get_settings_for_section_core( $section_id ) {
		$admin    = WC_trusted_shops()->trusted_shops->get_dependency( 'admin' );
		$settings = $admin->get_settings();

		return $settings;
	}

	public function output() {
		global $current_section;

		$settings = $this->get_settings_for_section_core( '' );
		$sidebar  = $this->get_sidebar();

		include_once( WC_trusted_shops()->plugin_path() . '/includes/admin/views/html-settings-section.php' );
	}

	public function get_sidebar() {
		$admin    = WC_trusted_shops()->trusted_shops->get_dependency( 'admin' );
		$sidebar  = $admin->get_sidebar();

		return $sidebar;
	}

	/**
	 * Save settings
	 */
	public function save() {
		$settings = $this->get_settings_for_section_core( '' );

		do_action( 'woocommerce_ts_before_save', $settings );
		WC_Admin_Settings::save_fields( $settings );
		do_action( 'woocommerce_ts_after_save', $settings );
	}
}

endif;

?>
