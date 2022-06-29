<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZD_Admin_Note_Base_Country extends WC_GZD_Admin_Note {

	public function is_disabled() {
		$is_disabled = true;

		if ( ! apply_filters( 'woocommerce_gzd_afghanistan_is_valid_base_country', false ) ) {
			if ( 'AF' === get_option( 'woocommerce_default_country' ) || 'AF' === get_option( 'woocommerce_gzd_shipments_shipper_address_country' ) || 'AF' === get_option( 'woocommerce_gzd_shipments_return_address_country' ) ) {
				$is_disabled = false;
			}
		}

		if ( ! $is_disabled ) {
			return parent::is_disabled();
		} else {
			return true;
		}
	}

	public function get_name() {
		return 'base_country';
	}

	public function get_title() {
		return __( 'Base country conflict detected', 'woocommerce-germanized' );
	}

	public function get_content() {
		$content  = '<p>' . sprintf( __( 'We found that one of your country options is set to Afghanistan. There is a <a href="%s">known bug</a> in WooCommerce which may lead to this issue. An incorrect country option can have unwanted effects on, among other things, tax calculation and shipping.', 'woocommerce-germanized' ), 'https://github.com/woocommerce/woocommerce/issues/32301' ) . '</p>';
		$content .= '<p>' . sprintf( __( 'Please check your <a href="%1$s">general settings</a> and your <a href="%2$s">shipment address settings</a>.', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=general' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-shipments&section=address' ) ) . '</p>';

		return $content;
	}
}
