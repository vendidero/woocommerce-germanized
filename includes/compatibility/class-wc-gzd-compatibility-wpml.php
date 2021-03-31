<?php

/**
 * WPML Helper
 *
 * Specific configuration for WPML
 *
 * @class        WC_GZD_WPML_Helper
 * @category    Class
 * @author        vendidero
 */
class WC_GZD_Compatibility_WPML extends WC_GZD_Compatibility {

	protected $dynamic_unit_pricing = null;

	protected $new_language = false;

	protected $email_locale = false;

	protected $email_old_lang = false;

	protected $email_lang = false;

	public static function get_name() {
		return 'WPML';
	}

	public static function get_path() {
		return 'sitepress-multilingual-cms/sitepress.php';
	}

	public static function is_activated() {
		return parent::is_activated() && wc_gzd_get_dependencies()->is_plugin_activated( 'woocommerce-multilingual/wpml-woocommerce.php' );
	}

	public function load() {

		// Support unit price for multiple currencies
		if ( function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on() ) {
			$this->dynamic_unit_pricing = new WC_GZD_Compatibility_Woocommerce_Dynamic_Pricing();
			$this->dynamic_unit_pricing->load();

			add_action( 'woocommerce_gzd_before_get_unit_price_html', array(
				$this,
				'before_show_product_unit_price'
			), 10, 1 );
		}

		// Prevent double sending order confirmation email to admin
		if ( wc_gzd_send_instant_order_confirmation() ) {
			add_action( 'wp_loaded', array( $this, 'unregister_order_confirmation_hooks' ) );
			add_action( 'woocommerce_germanized_before_order_confirmation', array(
				$this,
				'send_order_admin_confirmation'
			) );
		}

		add_action( 'woocommerce_gzd_get_term', array( $this, 'unhook_terms_clause' ), 10 );
		add_action( 'woocommerce_gzd_after_get_term', array( $this, 'rehook_terms_clause' ), 10 );

		// Add language field to revocation form
		add_action( 'woocommerce_gzd_after_revocation_form_fields', array( $this, 'set_language_field' ), 10 );

		// Setup and restore email customer locale
		add_action( 'woocommerce_gzd_switch_email_locale', array( $this, 'setup_email_locale' ), 10, 2 );
		add_action( 'woocommerce_gzd_restore_email_locale', array( $this, 'restore_email_locale' ), 10, 1 );

		// Add compatibility with email string translation by WPML
		add_filter( 'wcml_emails_options_to_translate', array( $this, 'register_email_options' ), 10, 1 );
		add_filter( 'wcml_emails_section_name_prefix', array( $this, 'filter_email_section_prefix' ), 10, 2 );

		$this->filter_page_ids();

		/**
		 * This action fires after Germanized has loaded it's WPML compatibility script.
		 *
		 * @param WC_GZD_Compatibility_WPML $compatibility
		 * @since 3.0.8
		 */
		do_action( 'woocommerce_gzd_wpml_compatibility_loaded', $this );
	}

	/**
	 * Switch current email to a certain language by reloading locale and triggering Woo WPML.
	 *
	 * @param $lang
	 */
	public function switch_email_lang( $lang ) {
		global $woocommerce_wpml, $sitepress;

		$current_language = $sitepress->get_current_language();

		if ( empty( $current_language ) ) {
			$current_language = $sitepress->get_default_language();
		}

		$this->email_old_lang = $current_language;

		if ( isset( $woocommerce_wpml->emails ) && is_callable( array( $woocommerce_wpml->emails, 'change_email_language' ) ) ) {
			$woocommerce_wpml->emails->change_email_language( $lang );

			$this->email_locale = $sitepress->get_locale( $lang );
			$this->reload_locale();
		}
	}

	/**
	 * Filters the Woo WPML email language based on a global variable.
	 *
	 * @param $lang
	 */
	public function filter_email_lang( $p_lang ) {
		if ( $this->email_lang ) {
			$p_lang = $this->email_lang;
		}

		return $p_lang;
	}

	/**
	 * Setup email locale based on customer.
	 *
	 * @param WC_Email       $email
	 * @param string|boolean $lang
	 */
	public function setup_email_locale( $email, $lang ) {
		global $sitepress;

		$object = $email->object;

		if ( ! $email->is_customer_email() ) {
			// Lets check the recipients language
			$recipients = explode( ',', $email->get_recipient() );

			foreach ( $recipients as $recipient ) {
				$user = get_user_by( 'email', $recipient );

				if ( $user ) {
					$lang = $sitepress->get_user_admin_language( $user->ID, true );
				} else {
					$lang = $sitepress->get_default_language();
				}
			}
		} else {
			if ( $object ) {

				if ( is_a( $object, 'WC_Order' ) ) {
					$lang = $object->get_meta( 'wpml_language', true );
				}
			}
		}

		/**
		 * This filter allows adjusting the language determined for the current email instance.
		 * The WPML compatibility will then try to switch to the language (if not empty).
		 *
		 * @param string   $lang Language e.g. en
		 * @param WC_Email $email The email instance.
		 *
		 * @since 3.0.8
		 */
		$lang = apply_filters( 'woocommerce_gzd_wpml_email_lang', $lang, $email );

		if ( ! empty( $lang ) ) {
			$this->email_lang = $lang;

			add_filter( 'plugin_locale', array( $this, 'filter_email_locale' ), 50 );
			add_filter( 'wcml_email_language', array( $this, 'filter_email_lang' ), 10 );

			$this->switch_email_lang( $lang );

			/*
			 * Reload email settings to make sure that translated strings are loaded from DB.
			 * This must happen before get_subject() and get_heading() etc. is called - therefor before triggering
			 * the send method.
			 */
			$email->init_settings();

			/**
			 * Manually adjust subject + heading option which does seem to cause problems
			 * for custom emails such as invoice and cancellation email.
			 */
			if ( $subject = $this->translate_email_setting( $email->id, 'subject' ) ) {
				$email->settings['subject'] = $subject;
			}

			if ( $heading = $this->translate_email_setting( $email->id, 'heading' ) ) {
				$email->settings['heading'] = $heading;
			}

			/**
			 * This action fires as soon as the WPML email language has been switched by the Germanized compatibility script.
			 *
			 * @param string   $lang Language e.g. en
			 * @param WC_Email $email The email instance.
			 *
			 * @since 3.1.2
			 */
			do_action( 'woocommerce_gzd_wpml_switched_email_language', $this->email_lang, $email );
		}
	}

	protected function translate_email_setting( $email_id, $option_name = 'heading' ) {
		global $woocommerce_wpml;

		if ( ! is_callable( array( $woocommerce_wpml->emails, 'wcml_get_translated_email_string' ) ) ) {
			return false;
		}

		$domain     = 'admin_texts_woocommerce_' . $email_id . '_settings';
		$namePrefix = '[woocommerce_' . $email_id . '_settings]';

		return $woocommerce_wpml->emails->wcml_get_translated_email_string( $domain, $namePrefix . $option_name, false, $this->email_lang );
	}

	/**
	 * Restore email locale after successfully sending the email
	 */
	public function restore_email_locale() {
		global $sitepress;

		if ( $this->email_locale ) {

			$old_lang = $this->email_old_lang ? $this->email_old_lang : $sitepress->get_default_language();

			$sitepress->switch_lang( $old_lang );
			remove_filter( 'plugin_locale', array( $this, 'filter_email_locale' ), 50 );
			remove_filter( 'wcml_email_language', array( $this, 'filter_email_lang' ), 10 );

			$this->email_lang     = false;
			$this->email_old_lang = false;
			$this->email_locale   = false;

			$this->reload_locale();
		}
	}

	/**
	 * Force the locale to be filtered while changing email language.
	 *
	 * @param $locale
	 */
	public function filter_email_locale( $locale ) {
		if ( $this->email_locale && ! empty( $this->email_locale ) ) {
			$locale = $this->email_locale;
		}

		return $locale;
	}

	protected function get_emails() {
		/**
		 * Filter to register custom emails for which to enable WPML email string translation compatibility.
		 *
		 * @param array $emails Class name as key and email id as value.
		 *
		 * @since 3.0.8
		 */
		return apply_filters( 'woocommerce_gzd_wpml_email_ids', array(
			'WC_GZD_Email_Customer_Paid_For_Order'            => 'customer_paid_for_order',
			'WC_GZD_Email_Customer_New_Account_Activation'    => 'customer_new_account_activation',
			'WC_GZD_Email_Customer_Revocation'                => 'customer_revocation',
			'WC_GZD_Email_Customer_SEPA_Direct_Debit_Mandate' => 'customer_sepa_direct_debit_mandate',
		) );
	}

	protected function get_email_options() {
		$email_options = array();

		foreach( $this->get_emails() as $key => $email_id ) {
			$email_options[ $key ] = 'woocommerce_' . $email_id . '_settings';
		}

		return $email_options;
	}

	public function register_email_options( $options ) {
		$email_options = $this->get_email_options();

		return array_merge( $options, $email_options );
	}

	public function filter_email_section_prefix( $prefix, $email_option ) {
		$email_options = $this->get_email_options();

		if ( in_array( $email_option, $email_options ) ) {
			$key    = array_search( $email_option, $email_options );
			$prefix = 'wc_gzd_email_';

			if ( $key && strpos( $key, 'GZDP_' ) !== false ) {
				$prefix = 'wc_gzdp_email_';
			} elseif( $key && strpos( $key, 'TS_' ) !== false ) {
				$prefix = 'wc_ts_email_';
			}
		}

		return $prefix;
	}

	/**
	 * Reload default, WC and WC Germanized locale
	 */
	public function reload_locale() {
		unload_textdomain( 'default' );
		unload_textdomain( 'woocommerce' );

		// Init WC locale.
		WC()->load_plugin_textdomain();

		unload_textdomain( 'woocommerce-germanized' );
		WC_germanized()->load_plugin_textdomain();

		load_default_textdomain( get_locale() );

		/**
		 * Reload locale.
		 *
		 * Fires after Germanized plugin textdomain was reloaded programmatically.
		 *
		 * @since 2.2.9
		 */
		do_action( 'woocommerce_gzd_reload_locale' );
	}

	public function before_show_product_unit_price( $product ) {
		$product->recalculate_unit_price();
	}

	public function set_language_field() {
		do_action( 'wpml_add_language_form_field' );
	}

	public function unhook_terms_clause() {
		global $sitepress;

		remove_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10 );
	}

	public function rehook_terms_clause() {
		global $sitepress;

		add_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 4 );
	}

	public function send_order_admin_confirmation( $order_id ) {
		global $woocommerce_wpml;

		if ( isset( $woocommerce_wpml ) && isset( $woocommerce_wpml->emails ) && is_object( $woocommerce_wpml->emails ) ) {

			// Remove duplicate filters which lead to non-replaced placeholders
			if ( method_exists( $woocommerce_wpml->emails, 'new_order_email_heading' ) ) {
				remove_filter( 'woocommerce_email_heading_new_order', array(
					$woocommerce_wpml->emails,
					'new_order_email_heading'
				), 10 );
				remove_filter( 'woocommerce_email_subject_new_order', array(
					$woocommerce_wpml->emails,
					'new_order_email_subject'
				), 10 );
			}

			// Instantiate mailer to make sure that new order email is known
			$mailer = WC()->mailer();

			if ( method_exists( $woocommerce_wpml->emails, 'admin_email' ) ) {
				$woocommerce_wpml->emails->admin_email( $order_id );
			} elseif ( method_exists( $woocommerce_wpml->emails, 'new_order_admin_email' ) ) {
				$woocommerce_wpml->emails->new_order_admin_email( $order_id );
			}

			// Stop Germanized from sending the notification
			add_filter( 'woocommerce_germanized_order_email_admin_confirmation_sent', array(
				$this,
				'set_order_admin_confirmation'
			) );
		}
	}

	public function set_order_admin_confirmation( $is_sent ) {
		return true;
	}

	public function unregister_order_confirmation_hooks() {
		global $woocommerce_wpml;

		if ( isset( $woocommerce_wpml ) && isset( $woocommerce_wpml->emails ) && is_object( $woocommerce_wpml->emails ) ) {
			$statuses = array(
				'woocommerce_order_status_pending_to_processing_notification',
				'woocommerce_order_status_pending_to_completed_notification',
				'woocommerce_order_status_pending_to_on-hold_notification',
			);

			foreach ( $statuses as $status ) {
				if ( is_callable( array( $woocommerce_wpml->emails, 'admin_email' ) ) ) {
					remove_action( $status, array( $woocommerce_wpml->emails, 'admin_email' ), 9 );
				} elseif ( is_callable( array( $woocommerce_wpml->emails, 'new_order_admin_email' ) ) ) {
					remove_action( $status, array( $woocommerce_wpml->emails, 'new_order_admin_email' ), 9 );
				}
			}
		}
	}

	public function language_locale_filter( $default ) {
		global $sitepress;

		if ( $this->new_language && ! empty( $this->new_language ) ) {
			if ( isset( $sitepress ) && is_callable( array( $sitepress, 'get_locale' ) ) ) {
				return $sitepress->get_locale( $this->new_language );
			}
		}

		return $default;
	}

	public function language_user_locale_filter( $value, $user_id, $meta_key ) {
		if ( 'locale' === $meta_key ) {
			return get_locale();
		}

		return $value;
	}

	public function set_language( $lang, $set_default = false ) {
		global $sitepress;
		global $wc_gzd_original_lang;

		if ( $set_default ) {
			$wc_gzd_original_lang = $lang;
		} elseif ( ! isset( $wc_gzd_original_lang ) || empty( $wc_gzd_original_lang ) ) {
			// Make sure default language is stored within global to ensure reset works
			if ( is_callable( array( $sitepress, 'get_current_language' ) ) ) {
				$wc_gzd_original_lang = $sitepress->get_current_language();
			}
		}

		if ( isset( $sitepress ) && is_callable( array(
				$sitepress,
				'get_current_language'
			) ) && is_callable( array( $sitepress, 'switch_lang' ) ) ) {

			if ( $sitepress->get_current_language() != $lang ) {
				$this->new_language = $lang;
			}

			$sitepress->switch_lang( $lang, true );

			// Somehow WPML doesn't automatically change the locale
			if ( is_callable( array( $sitepress, 'reset_locale_utils_cache' ) ) ) {
				$sitepress->reset_locale_utils_cache();
			}

			// Filter locale because WPML does still use the user locale within admin panel
			add_filter( 'locale', array( $this, 'language_locale_filter' ), 50 );

			if ( function_exists( 'switch_to_locale' ) ) {
				switch_to_locale( get_locale() );

				// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
				add_filter( 'plugin_locale', 'get_locale' );

				$this->reload_locale();
			}

			/**
			 * WPML language switched.
			 *
			 * Fires whenever Germanized has explicitly changed the language.
			 *
			 * @param string $lang The new language code.
			 * @param string $wc_gzd_original_lang The old language code.
			 *
			 * @since 2.2.9
			 */
			do_action( 'woocommerce_gzd_wpml_switched_language', $lang, $wc_gzd_original_lang );
		}

		/**
		 * WPML language switch.
		 *
		 * Fires whenever Germanized was asked to programatically change the language.
		 *
		 * @param string $lang The new language code.
		 * @param string $wc_gzd_original_lang The old language code.
		 *
		 * @since 2.2.9
		 *
		 */
		do_action( 'woocommerce_gzd_wpml_switch_language', $lang, $wc_gzd_original_lang );
	}

	public function restore_language() {
		global $wc_gzd_original_lang;

		if ( isset( $wc_gzd_original_lang ) && ! empty( $wc_gzd_original_lang ) ) {
			$this->set_language( $wc_gzd_original_lang );
			$this->new_language = false;

			remove_filter( 'locale', array( $this, 'language_locale_filter' ), 50 );
		}
	}

	public function filter_page_ids() {

		$woo_pages = array(
			'revocation_page_id',
			'data_security_page_id',
			'imprint_page_id',
			'payment_methods_page_id',
			'shipping_costs_page_id',
			'terms_page_id',
		);

		foreach ( $woo_pages as $page ) {
			add_filter( 'woocommerce_get_' . $page, array( $this, 'translate_page' ) );
			add_filter( 'option_woocommerce_' . $page, array( $this, 'translate_page' ) );
		}
	}

	public function translate_page( $id ) {
		global $pagenow;

		if ( is_admin() && $pagenow === 'options-permalink.php' ) {
			return $id;
		}

		return apply_filters( 'translate_object_id', $id, 'page', true );
	}

}