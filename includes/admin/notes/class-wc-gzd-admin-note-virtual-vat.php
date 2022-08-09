<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_GZD_Admin_Note_Virtual_Vat.
 */
class WC_GZD_Admin_Note_Virtual_Vat extends WC_GZD_Admin_Note {

	public function is_disabled() {
		$has_virtual_vat_legacy_enabled = 'yes' === get_option( 'woocommerce_gzd_enable_virtual_vat' );

		if ( ! $has_virtual_vat_legacy_enabled || \Vendidero\EUTaxHelper\Helper::oss_procedure_is_enabled() ) {
			return true;
		}

		return parent::is_disabled();
	}

	public function get_name() {
		return 'virtual_vat';
	}

	public function get_title() {
		return __( 'Virtual VAT option (MOSS)', 'woocommerce-germanized' );
	}

	public function get_content() {
		return sprintf( __( 'Seems like you activated the <a href="%1$s">virtual VAT option</a> (or one of your products uses the custom <code>virtual-rate</code> tax class) which is now deprecated as the OSS procedure replaces the MOSS procedure. Please consider <a href="%2$s">migrating to the OSS procedure</a>, if applicable.', 'woocommerce-germanized' ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-taxes' ) ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-oss' ) ) );
	}

	public function get_actions() {
		return array(
			array(
				'url'        => admin_url( 'admin.php?page=wc-settings&tab=germanized-oss' ),
				'title'      => __( 'Manage OSS settings', 'woocommerce-germanized' ),
				'is_primary' => true,
			),
			array(
				'url'        => 'https://vendidero.de/one-stop-shop-verfahren-oss-in-woocommerce-einfach-umsetzen',
				'title'      => __( 'Learn more', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => false,
			),
		);
	}
}
