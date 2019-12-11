<?php
/**
 * Class WC_GZD_Email file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class WC_GZD_Email extends WC_Email {

	/**
	 * Switch Woo and Germanized to site locale
	 */
	public function setup_locale() {
		if ( $this->is_customer_email() && apply_filters( 'woocommerce_email_setup_locale', true ) ) {
			wc_gzd_switch_to_site_locale();
		}

		parent::setup_locale();
	}

	/**
	 * Restore Woo and Germanized locale
	 */
	public function restore_locale() {
		if ( $this->is_customer_email() && apply_filters( 'woocommerce_email_restore_locale', true ) ) {
			wc_gzd_restore_locale();
		}

		parent::restore_locale();
	}

	/**
	 * Adds better compatibility to multi-language-plugins such as WPML.
	 * Should be called during trigger method after setting up the email object
	 * so that e.g. order data is available.
	 */
	public function setup_email_locale( $lang = false ) {
		if ( apply_filters( 'woocommerce_gzd_email_setup_locale', true ) ) {
			wc_gzd_switch_to_email_locale( $this, $lang );
		}
	}

	public function restore_email_locale() {
		if ( apply_filters( 'woocommerce_gzd_email_restore_locale', true ) ) {
			wc_gzd_restore_email_locale( $this );
		}
	}
}