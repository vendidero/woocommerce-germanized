<?php

namespace Vendidero\Shiptastic;

defined( 'ABSPATH' ) || exit;

class EmailLocale {

	/**
	 * @var \WC_Email|null
	 */
	protected $email = null;

	/**
	 * @param \WC_Email $email
	 */
	public function __construct( $email ) {
		$this->email = $email;
	}

	/**
	 * @return \WC_Email
	 */
	public function get_email() {
		return $this->email;
	}

	/**
	 * Switch to site locale
	 */
	public function setup_locale() {
		if ( $this->get_email()->is_customer_email() && apply_filters( 'woocommerce_email_setup_locale', true ) ) {
			Package::load_plugin_textdomain();
		}
	}

	/**
	 * Restore locale
	 */
	public function restore_locale() {
		if ( $this->get_email()->is_customer_email() && apply_filters( 'woocommerce_email_restore_locale', true ) ) {
			Package::load_plugin_textdomain();
		}
	}

	/**
	 * Adds better compatibility to multi-language-plugins such as WPML.
	 * Should be called during trigger method after setting up the email object
	 * so that e.g. order data is available.
	 */
	public function setup_email_locale( $lang = false ) {
		if ( apply_filters( 'woocommerce_shiptastic_allow_switching_email_locale', true ) ) {
			do_action( 'woocommerce_shiptastic_switch_email_locale', $this->get_email(), $lang );
		}
	}

	public function restore_email_locale() {
		if ( apply_filters( 'woocommerce_shiptastic_allow_restoring_email_locale', true ) ) {
			do_action( 'woocommerce_shiptastsic_restore_email_locale', $this->get_email() );
		}
	}
}
