<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'yes' === get_option( 'woocommerce_gzd_shipping_tax' ) || 'yes' === get_option( 'woocommerce_gzd_fee_tax' ) ) {
	/**
	 * Do only use the global shipping tax notice (applies for fees too) for additional costs.
	 */
	update_option( 'woocommerce_gzd_shipping_tax', 'yes' );

	if ( 'yes' === get_option( 'woocommerce_calc_taxes' ) && 'no' === get_option( 'woocommerce_prices_include_tax' ) ) {
		// Show notice
		update_option( '_wc_gzd_show_shipping_tax_excl_notice', 'yes' );

		$notices = WC_GZD_Admin_Notices::instance();

		if ( $note = $notices->get_note( 'shipping_excl_tax' ) ) {
			$note->reset();
		}
	}
}
