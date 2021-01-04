<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZD_Admin_Note_Shipping_Excl_Tax extends WC_GZD_Admin_Note {

	public function is_disabled() {
		if ( 'yes' !== get_option( '_wc_gzd_show_shipping_tax_excl_notice' ) ) {
			return true;
		}

		return parent::is_disabled();
	}

	public function dismiss( $and_note = true ) {
		delete_option( '_wc_gzd_show_shipping_tax_excl_notice' );

		parent::dismiss( $and_note );
	}

	public function get_name() {
		return 'shipping_excl_tax';
	}

	public function get_title() {
		return __( 'Shipping and fee taxes', 'woocommerce-germanized' );
	}

	public function get_content() {
		return __( 'Starting with the newest Germanized version your <strong>shipping costs and fees will no longer be treated including tax</strong>. This change in Germanized was necessary due to your prices being treated excluding taxes (see your WooCommerce tax settings). Due to the way WooCommerce calculates taxes for orders (based on prices excluding taxes) there is no consistent way to (re-)calculate shipping costs and/or fee taxes if they are treated including taxes. Please check your shipping costs and fees and edit costs accordingly.', 'woocommerce-germanized' );
	}

	public function get_actions() {
		return array(
			array(
				'url'        => admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
				'title'      => __( 'Manage shipping methods', 'woocommerce-germanized' ),
				'is_primary' => true,
			),
			array(
				'url'        => 'https://vendidero.de/dokument/steuerberechnung-fuer-versandkosten-und-gebuehren',
				'title'      => __( 'Learn more', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => false,
			),
		);
	}
}
