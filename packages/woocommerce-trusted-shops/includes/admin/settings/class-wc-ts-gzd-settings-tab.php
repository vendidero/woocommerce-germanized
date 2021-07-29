<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Vendidero\TrustedShops\Package;

/**
 * Adds Germanized Email settings.
 *
 * @class 		WC_GZD_Settings_Tab_Emails
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_TS_GZD_Settings_Tab extends WC_GZD_Settings_Tab {

	public function __construct() {
		parent::__construct();

		add_action( 'woocommerce_gzd_admin_settings_scripts', array( $this, 'register_scripts' ), 10 );
		add_action( 'woocommerce_gzd_admin_settings_after_trusted_shops', array( $this, 'review_exporter' ) );
		add_action( 'woocommerce_gzd_admin_settings_before_trusted_shops', array( $this, 'before_output' ) );
	}

	public function get_help_link() {
		$admin = WC_trusted_shops()->trusted_shops->get_dependency( 'admin' );

		return $admin->get_trusted_url( 'https://support.trustedshops.com/en/apps/woocommerce' );
	}

	public function before_output() {
		do_action( 'woocommerce_ts_admin_settings_before', $this->get_settings_for_section_core( '' ) );
	}

	public function review_exporter() {
		$admin = WC_trusted_shops()->trusted_shops->get_dependency( 'admin' );
		$admin->review_collector_export();
	}

	public function register_scripts() {
		if ( isset( $_GET['tab'] ) && 'germanized-trusted_shops' === $_GET['tab'] ) {
			do_action( 'woocommerce_trusted_shops_load_admin_scripts' );
		}
	}

	public function get_description() {
		return _x( 'Setup your Trusted Shops Integration.', 'trusted-shops', 'woocommerce-germanized' );
	}

	public function get_label() {
		return _x( 'Trusted Shops', 'trusted-shops', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'trusted_shops';
	}

	protected function output_description() {}

	public function get_tab_settings( $current_section = '' ) {
		$admin    = WC_trusted_shops()->trusted_shops->get_dependency( 'admin' );
		$settings = $admin->get_settings();

		return $settings;
	}

	public function get_sidebar( $current_section = '' ) {
		$admin    = WC_trusted_shops()->trusted_shops->get_dependency( 'admin' );
		$sidebar  = $admin->get_sidebar();

		return $sidebar;
	}

	protected function get_enable_option_name() {
		$option_prefix = Package::is_integration() ? 'gzd_' : '';
		$option_name   = 'woocommerce_' . $option_prefix . 'trusted_shops_id';

		return $option_name;
	}

	public function is_enabled() {
		$value = get_option( $this->get_enable_option_name() );

		return ( ! empty( $value ) ? true : false );
	}

	protected function before_save( $settings, $current_section = '' ) {
		do_action( 'woocommerce_ts_before_save', $settings );

		parent::before_save( $settings, $current_section );
	}

	protected function after_save( $settings, $current_section = '' ) {
		do_action( 'woocommerce_ts_after_save', $settings );

		parent::after_save( $settings, $current_section );
	}
}
