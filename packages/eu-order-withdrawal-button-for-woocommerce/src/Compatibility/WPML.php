<?php

namespace Vendidero\OrderWithdrawalButton\Compatibility;

defined( 'ABSPATH' ) || exit;

class WPML implements Compatibility {

	private static $lang = null;

	private static $locale = null;

	private static $original_lang = null;

	public static function is_active() {
		return defined( 'ICL_SITEPRESS_VERSION' );
	}

	public static function init() {
		add_filter( 'wcml_emails_options_to_translate', array( __CLASS__, 'register_email_options' ), 10, 1 );
		add_filter( 'wcml_emails_section_name_prefix', array( __CLASS__, 'filter_email_section_prefix' ), 10, 2 );
		add_action( 'eu_owb_woocommerce_switch_email_locale', array( __CLASS__, 'setup_email_locale' ), 10, 2 );
		add_action( 'eu_owb_woocommerce_restore_email_locale', array( __CLASS__, 'restore_email_locale' ), 10, 1 );
		add_action( 'eu_owb_woocommerce_return_request_form_before_submit', array( __CLASS__, 'lang_field' ), 10, 1 );
		add_filter( 'eu_owb_woocommerce_order_withdrawal_request_additional_meta', array( __CLASS__, 'store_lang' ), 10, 1 );

		$woo_pages = array(
			'withdraw_from_contract_page_id',
		);

		foreach ( $woo_pages as $page ) {
			add_filter( 'woocommerce_get_' . $page, array( __CLASS__, 'translate_page' ) );
			add_filter( 'option_woocommerce_' . $page, array( __CLASS__, 'translate_page' ) );
		}
	}

	public static function store_lang( $meta ) {
		$lang = isset( $_POST['lang'] ) ? wc_clean( wp_unslash( $_POST['lang'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! empty( $lang ) ) {
			$meta['wpml_language'] = $lang;
		}

		return $meta;
	}

	public static function lang_field() {
		do_action( 'wpml_add_language_form_field' );
	}

	public static function translate_page( $id ) {
		global $pagenow;

		if ( is_admin() && 'options-permalink.php' === $pagenow ) {
			return $id;
		}

		return apply_filters( 'wpml_object_id', $id, 'page', true );
	}

	/**
	 * @param $emails
	 */
	public static function register_emails( $emails ) {
		return $emails;
	}

	protected static function get_emails() {
		return array(
			'EU_OWB_Email_Customer_Withdrawal_Request_Confirmed' => 'customer_withdrawal_request_confirmed',
			'EU_OWB_Email_Customer_Withdrawal_Request_Rejected'  => 'customer_withdrawal_request_rejected',
			'EU_OWB_Email_Customer_Withdrawal_Request_Received'  => 'customer_withdrawal_request_received',
		);
	}

	protected static function get_email_options() {
		$email_options = array();

		foreach ( self::get_emails() as $key => $email_id ) {
			$email_options[ $key ] = 'woocommerce_' . $email_id . '_settings';
		}

		return $email_options;
	}

	public static function register_email_options( $options ) {
		$email_options = array_values( self::get_email_options() );

		return array_merge( $options, $email_options );
	}

	public static function filter_email_section_prefix( $prefix, $email_option ) {
		$email_options = self::get_email_options();

		if ( in_array( $email_option, $email_options, true ) ) {
			$prefix = 'eu_owb_email_';
		}

		return $prefix;
	}

	/**
	 * @param \WC_Email $email
	 * @param string|bool $lang
	 *
	 * @return void
	 */
	public static function setup_email_locale( $email, $lang ) {
		global $sitepress;

		$object = $email->object;

		if ( ! $lang ) {
			if ( ! $email->is_customer_email() ) {
				// Let's check the recipients language
				$recipients = explode( ',', $email->get_recipient() );

				foreach ( $recipients as $recipient ) {
					$user = get_user_by( 'email', $recipient );

					if ( $user ) {
						$lang = $sitepress->get_user_admin_language( $user->ID, true );
					} else {
						$lang = $sitepress->get_default_language();
					}
				}
			} elseif ( $object ) {
				if ( is_a( $object, '\Vendidero\OrderWithdrawalButton\WithdrawalOrder' ) ) {
					$lang = $object->get_meta( '_wpml_language' );

					if ( $object->get_customer_id() > 0 ) {
						if ( $user = get_user_by( 'id', $object->get_customer_id() ) ) {
							$lang = apply_filters( 'wpml_user_language', $lang, $user->user_email );
						}
					}
				}
			}
		}

		if ( ! empty( $lang ) ) {
			add_filter( 'wcml_email_language', array( __CLASS__, 'filter_email_lang' ), 10 );
			add_filter( 'plugin_locale', array( __CLASS__, 'set_locale_for_emails' ), 10, 2 );

			if ( is_null( self::$original_lang ) ) {
				self::$original_lang = $sitepress->get_current_language();
			}

			self::$lang = $lang;
			$sitepress->switch_lang( $lang, true );
			self::$locale = $sitepress->get_locale( $lang );
		}
	}

	public static function restore_email_locale() {
		global $sitepress;

		remove_filter( 'wcml_email_language', array( __CLASS__, 'filter_email_lang' ), 10 );
		remove_filter( 'plugin_locale', array( __CLASS__, 'set_locale_for_emails' ), 10 );

		if ( ! is_null( self::$original_lang ) ) {
			$sitepress->switch_lang( self::$original_lang );
			self::$original_lang = null;
		}

		self::$lang   = null;
		self::$locale = null;
	}

	/**
	 * Set correct locale code for emails.
	 *
	 * @param string $locale
	 * @param string $domain
	 *
	 * @return string
	 */
	public static function set_locale_for_emails( $locale, $domain ) {
		if ( in_array( $domain, self::get_email_locales_to_translate(), true ) && self::$locale ) {
			$locale = self::$locale;
		}

		return $locale;
	}

	public static function get_email_locales_to_translate() {
		return apply_filters( 'eu_owb_woocommerce_wpml_email_locales_to_translate', array( 'woocommerce', 'woocommerce-germanized' ) );
	}

	public static function filter_email_lang( $p_lang ) {
		if ( self::$lang ) {
			$p_lang = self::$lang;
		}

		return $p_lang;
	}
}
