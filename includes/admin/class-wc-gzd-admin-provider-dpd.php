<?php
use Vendidero\Germanized\Shipments\ShippingProvider\Simple;

defined( 'ABSPATH' ) || exit;

class WC_GZD_Admin_Provider_DPD extends Simple {

	public function get_edit_link( $section = '' ) {
		return 'https://vendidero.de/woocommerce-germanized/features';
	}

	public function get_name( $context = 'view' ) {
		return '_dpd';
	}

	public function get_description( $context = 'view' ) {
		return __( 'Create DPD labels and return labels conveniently', 'woocommerce-germanized' );
	}

	public function get_title( $context = 'view' ) {
		return __( 'DPD', 'woocommerce-germanized' ) . ' <span class="wc-gzd-pro wc-gzd-pro-outlined">' . __( 'pro', 'woocommerce-germanized' ) . '</span>';
	}

	public function is_manual_integration() {
		return false;
	}

	public function is_pro() {
		return true;
	}

	public function is_activated() {
		return false;
	}

	public function activate() {
		return false;
	}

	public function get_help_link() {
		return 'https://vendidero.de/woocommerce-germanized/features';
	}

	public function save() {
		return false;
	}
}
