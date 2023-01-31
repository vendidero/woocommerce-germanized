<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_GZD_Email_Helper {

	/**
	 * @var WC_Email|null
	 */
	protected $email = null;

	/**
	 * WC_GZDP_Email_Helper constructor.
	 *
	 * @param WC_Email $email
	 */
	public function __construct( $email ) {
		$this->email = $email;
	}

	/**
	 * @return WC_Email
	 */
	public function get_email() {
		return $this->email;
	}

	/**
	 * Switch Woo and Germanized to site locale
	 */
	public function setup_locale() {
		if ( function_exists( 'wc_gzd_switch_to_site_locale' ) && $this->get_email()->is_customer_email() && apply_filters( 'woocommerce_email_setup_locale', true ) ) {
			wc_gzd_switch_to_site_locale();
		}

		$this->get_email()->setup_locale();
	}

	/**
	 * Restore Woo and Germanized locale
	 */
	public function restore_locale() {
		if ( function_exists( 'wc_gzd_restore_locale' ) && $this->get_email()->is_customer_email() && apply_filters( 'woocommerce_email_restore_locale', true ) ) {
			wc_gzd_restore_locale();
		}

		$this->get_email()->restore_locale();
	}

	/**
	 * Adds better compatibility to multi-language-plugins such as WPML.
	 * Should be called during trigger method after setting up the email object
	 * so that e.g. order data is available.
	 */
	public function setup_email_locale( $lang = false ) {
		if ( function_exists( 'wc_gzd_switch_to_email_locale' ) && apply_filters( 'woocommerce_gzd_email_setup_locale', true ) ) {
			wc_gzd_switch_to_email_locale( $this->get_email(), $lang );
		}
	}

	public function restore_email_locale() {
		if ( function_exists( 'wc_gzd_restore_email_locale' ) && apply_filters( 'woocommerce_gzd_email_restore_locale', true ) ) {
			wc_gzd_restore_email_locale( $this->get_email() );
		}
	}
}
