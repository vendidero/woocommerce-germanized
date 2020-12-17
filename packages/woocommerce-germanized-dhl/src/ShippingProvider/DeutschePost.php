<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\DHL\ShippingProvider;

use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\Shipments\ShippingProvider;

defined( 'ABSPATH' ) || exit;

class DeutschePost extends ShippingProvider {

	public function is_manual_integration() {
		return false;
	}

	public function supports_labels( $label_type ) {
		$label_types = array( 'simple', 'return' );

		return in_array( $label_type, $label_types );
	}

	public function is_activated() {
		return Package::is_internetmarke_enabled();
	}

	public function get_title( $context = 'view' ) {
		return _x( 'Deutsche Post', 'dhl', 'woocommerce-germanized' );
	}

	public function get_name( $context = 'view' ) {
		return 'deutsche_post';
	}

	public function get_description( $context = 'view' ) {
		return _x( 'Integration for products of the Deutsche Post through Internetmarke.', 'dhl', 'woocommerce-germanized' );
	}

	public function get_additional_options_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=germanized-dhl&section=internetmarke' );
	}

	public function get_default_tracking_url_placeholder() {
		return 'https://www.deutschepost.de/sendung/simpleQueryResult.html?form.sendungsnummer={tracking_id}&form.einlieferungsdatum_tag={label_date_day}&form.einlieferungsdatum_monat={label_date_month}&form.einlieferungsdatum_jahr={label_date_year}';
	}

	public function get_tracking_url_placeholder( $context = 'view' ) {
		$data = parent::get_tracking_url_placeholder( $context );

		// In case the option value is not stored in DB yet
		if ( 'view' === $context && empty( $data ) ) {
			$data = $this->get_default_tracking_url_placeholder();
		}

		return $data;
	}

	public function get_tracking_desc_placeholder( $context = 'view' ) {
		$data = parent::get_tracking_desc_placeholder( $context );

		// In case the option value is not stored in DB yet
		if ( 'view' === $context && empty( $data ) ) {
			$data = $this->get_default_tracking_desc_placeholder();
		}

		return $data;
	}

	public function deactivate() {
		update_option( 'woocommerce_gzd_dhl_internetmarke_enable', 'no' );

		/**
		 * This action is documented in woocommerce-germanized-shipments/src/ShippingProvider.php
		 */
		do_action( 'woocommerce_gzd_shipping_provider_activated', $this );
	}

	public function activate() {
		update_option( 'woocommerce_gzd_dhl_internetmarke_enable', 'yes' );

		/**
		 * This action is documented in woocommerce-germanized-shipments/src/ShippingProvider.php
		 */
		do_action( 'woocommerce_gzd_shipping_provider_deactivated', $this );
	}
}
