<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Tax settings.
 *
 * @class        WC_GZD_Settings_Tab_Taxes
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_License extends WC_GZD_Settings_Tab {

	public function get_description() {
		return WC_germanized()->is_pro() ? '' : __( 'Follow 3 simple steps to become a pro.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return WC_germanized()->is_pro() ? __( 'License', 'woocommerce-germanized' ) : sprintf( __( 'Upgrade to %s', 'woocommerce-germanized' ), '<span class="wc-gzd-pro wc-gzd-pro-outlined">' . __( 'pro', 'woocommerce-germanized' ) . '</span>' );
	}

	public function get_name() {
		return 'license';
	}

	public function is_pro() {
		return true;
	}

	public function output() {
		$GLOBALS['hide_save_button'] = true;

		include_once __DIR__ . '/views/html-admin-settings-license.php';
	}

	public function get_tab_settings( $current_section = '' ) {
		return array();
	}

	public function hide_from_main_panel() {
		return true;
	}

	protected function get_pro_content_html() {
		return '';
	}

	public function is_enabled() {
		return false;
	}

	public function save() {}
}
