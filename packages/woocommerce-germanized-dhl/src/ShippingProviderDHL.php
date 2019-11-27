<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\DHL;

use Vendidero\Germanized\Shipments\ShippingProvider;

defined( 'ABSPATH' ) || exit;

class ShippingProviderDHL extends ShippingProvider  {

	public function is_manual_integration() {
		return false;
	}

	public function supports_labels( $label_type ) {
		return in_array( $label_type, array(
			'simple',
			'return'
		) );
	}

	public function get_edit_link() {
		return admin_url( 'admin.php?page=wc-settings&tab=germanized-dhl' );
	}

	public function is_activated() {
		return Package::is_enabled();
	}

	public function get_title( $context = 'view' ) {
		return __( 'DHL', 'dhl', 'woocommerce-germanized' );
	}

	public function get_name( $context = 'view' ) {
		return 'dhl';
	}

	public function get_description( $context = 'view' ) {
		return _x( 'Complete DHL integration supporting labels, preferred services and packstation delivery.', 'dhl', 'woocommerce-germanized' );
	}

	public function get_tracking_url_placeholder( $context = 'view' ) {
		return 'https://www.dhl.de/de/privatkunden/pakete-empfangen/verfolgen.html?lang=de&idc={tracking_id}&rfn=&extendedSearch=true';
	}

	public function get_tracking_desc_placeholder( $context = 'view' ) {
		return Package::get_setting( 'label_tracking_desc' );
	}

	public function deactivate() {
		update_option( 'woocommerce_gzd_dhl_enable', 'no' );
	}

	public function activate() {
		update_option( 'woocommerce_gzd_dhl_enable', 'yes' );
	}
}
