<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Adds Settings Interface to WooCommerce Settings Tabs
 *
 * @class 		WC_GZD_Settings_Germanized
 * @version		1.0.0
 * @author 		Vendidero
 */
abstract class WC_GZD_Settings_Tab extends WC_Settings_Page {

	protected $is_enabled = true;

	public function __construct() {
		$this->id = 'germanized-' . $this->get_name();

		parent::__construct();

		remove_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
	}

	public function get_description() {}

	public function supports_disabling() {
		return false;
	}

	public function get_link() {
		return admin_url( 'admin.php?page=wc-settings&tab=' . sanitize_title( $this->get_id() ) );
	}

	abstract public function get_name();
}