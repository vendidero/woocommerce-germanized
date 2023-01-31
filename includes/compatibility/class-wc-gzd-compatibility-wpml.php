<?php

defined( 'ABSPATH' ) || exit;

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

	protected $original_variation_id = false;

	protected $original_product_id = false;

	protected static $removed_get_term_filter = false;

	public static function get_name() {
		return 'WPML';
	}

	public static function get_path() {
		return 'sitepress-multilingual-cms/sitepress.php';
	}

	public static function is_activated() {
		return parent::is_activated() && \Vendidero\Germanized\PluginsHelper::is_plugin_active( 'woocommerce-multilingual' );
	}

	public function load() {
		// Support unit price for multiple currencies
		if ( function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on() ) {
			$this->dynamic_unit_pricing = new WC_GZD_Compatibility_Woocommerce_Dynamic_Pricing();
			$this->dynamic_unit_pricing->load();

			add_action(
				'woocommerce_gzd_before_get_unit_price_html',
				array(
					$this,
					'before_show_product_unit_price',
				),
				10,
				1
			);
		}

		// Prevent double sending order confirmation email to admin
		if ( wc_gzd_send_instant_order_confirmation() ) {
			add_action( 'wp_loaded', array( $this, 'unregister_order_confirmation_hooks' ) );
			add_action(
				'woocommerce_germanized_before_order_confirmation',
				array(
					$this,
					'send_order_admin_confirmation',
				)
			);
		}

		add_action( 'woocommerce_gzd_get_term', array( $this, 'unhook_terms_clause' ), 10 );
		add_action( 'woocommerce_gzd_after_get_term', array( $this, 'rehook_terms_clause' ), 10 );

		// Support delivery time slug translation at runtime (e.g. default delivery time, country-specific)
		add_filter( 'woocommerce_gzd_product_delivery_times', array( $this, 'filter_product_delivery_times' ), 10, 4 );
		add_filter( 'woocommerce_gzd_get_product_default_delivery_time', array( $this, 'filter_product_default_delivery_time' ), 10, 4 );
		add_filter( 'woocommerce_gzd_get_product_delivery_time_countries', array( $this, 'filter_product_country_specific_delivery_times' ), 10, 4 );

		add_filter( 'woocommerce_gzd_get_product_variation_default_delivery_time', array( $this, 'filter_product_default_delivery_time' ), 10, 4 );
		add_filter( 'woocommerce_gzd_get_product_variation_delivery_time_countries', array( $this, 'filter_product_country_specific_delivery_times' ), 10, 4 );

		// Force using the original term id for nutrient values to map to product data
		add_filter( 'woocommerce_gzd_product_nutrient_value_term_id', array( $this, 'filter_product_nutrient_value_term' ), 10, 3 );

		// Add language field to revocation form
		add_action( 'woocommerce_gzd_after_revocation_form_fields', array( $this, 'set_language_field' ), 10 );

		add_action( 'woocommerce_gzd_before_add_order_item_meta', array( $this, 'maybe_switch_order_meta_language' ), 10, 2 );
		add_action( 'woocommerce_gzd_after_add_order_item_meta', array( $this, 'maybe_restore_order_meta_language' ), 10, 2 );

		// Setup and restore email customer locale
		add_action( 'woocommerce_before_resend_order_emails', array( $this, 'force_admin_option_translation' ), 40 );
		add_filter( 'wcml_get_order_items_language', array( $this, 'maybe_filter_wpml_order_items_language' ), 10, 2 );
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
	 * Make sure to filter order items language in case order details or order confirmation is resent
	 * during admin edit order requests.
	 *
	 * @param string $language
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public function maybe_filter_wpml_order_items_language( $language, $order ) {
		if ( ! empty( $_POST['wc_order_action'] ) && is_a( $order, 'WC_Order' ) && $order->get_meta( 'wpml_language', true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$action = wc_clean( wp_unslash( $_POST['wc_order_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( in_array( $action, apply_filters( 'woocommerce_gzd_wpml_admin_order_items_translatable_actions', array( 'send_order_details', 'order_confirmation' ) ), true ) ) {
				$language = $order->get_meta( 'wpml_language', true );
			}
		}

		return $language;
	}

	/**
	 * Maybe translate custom order item product data to make sure delivery time, cart description
	 * is being stored in the actual user's language.
	 *
	 * @param WC_Order_Item_Product $item
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function maybe_switch_order_meta_language( $item, $order ) {
		if ( $order ) {
			$lang = $order->get_meta( 'wpml_language' );

			if ( ! empty( $lang ) ) {
				$this->switch_lang( $lang );

				$this->original_product_id   = false;
				$this->original_variation_id = false;

				if ( 'product_variation' === get_post_type( $item->get_product_id() ) ) {
					$this->original_variation_id = $item->get_variation_id();
					$translated_product_id       = apply_filters( 'translate_object_id', $this->original_variation_id, 'product_variation', true, $lang );

					$item->set_variation_id( $translated_product_id );
				} else {
					$this->original_product_id = $item->get_product_id();
					$translated_product_id     = apply_filters( 'translate_object_id', $this->original_product_id, 'product', true, $lang );

					$item->set_product_id( $translated_product_id );
				}
			}
		}
	}

	/**
	 * Maybe restore the language after updating item meta.
	 *
	 * @param WC_Order_Item_Product $item
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function maybe_restore_order_meta_language( $item, $order ) {
		if ( $order ) {
			$lang = $order->get_meta( 'wpml_language' );

			if ( ! empty( $lang ) ) {
				if ( $this->original_variation_id && 'product_variation' === get_post_type( $item->get_product_id() ) ) {
					$item->set_variation_id( $this->original_variation_id );
				} elseif ( $this->original_product_id ) {
					$item->set_product_id( $this->original_product_id );
				}

				$this->original_product_id   = false;
				$this->original_variation_id = false;

				$this->restore_language();
			}
		}
	}

	public function filter_product_nutrient_value_term( $term_id, $product, $context ) {
		global $sitepress;

		if ( $sitepress->get_default_language() !== $sitepress->get_current_language() ) {
			$original_id = (int) apply_filters( 'wpml_object_id', $term_id, 'product_nutrient', false, $sitepress->get_default_language() );

			if ( ! empty( $original_id ) && $original_id !== $term_id ) {
				$term_id = $original_id;
			}
		}

		return $term_id;
	}

	public function filter_product_delivery_times( $delivery_times, $gzd_product, $product, $context ) {
		if ( 'view' === $context ) {
			global $sitepress;

			if ( $sitepress->get_default_language() !== $sitepress->get_current_language() ) {
				foreach ( $delivery_times as $term ) {
					$translated_id = (int) apply_filters( 'wpml_object_id', $term->term_id, 'product_delivery_time', false, $sitepress->get_default_language() );

					if ( $translated_id !== $term->term_id ) {
						if ( $org_term = WC_germanized()->delivery_times->get_term_object( $translated_id, 'id' ) ) {
							$delivery_times[ $org_term->slug ]                       = $org_term;
							$delivery_times[ $org_term->slug ]->translated_term_id   = $term->term_id;
							$delivery_times[ $org_term->slug ]->translated_term_slug = $term->slug;
						}
					}
				}
			}
		}

		return $delivery_times;
	}

	public function filter_product_country_specific_delivery_times( $delivery_time_countries, $gzd_product, $product, $context ) {
		global $sitepress;

		if ( 'view' === $context && ! empty( $delivery_time_countries ) && $sitepress->get_default_language() !== $sitepress->get_current_language() ) {
			$delivery_times = $gzd_product->get_delivery_times();

			foreach ( $delivery_time_countries as $country => $delivery_time_country ) {
				if ( array_key_exists( $delivery_time_country, $delivery_times ) ) {
					$delivery_time_countries[ $country ] = $delivery_times[ $delivery_time_country ]->translated_term_slug;
				}
			}
		}

		return $delivery_time_countries;
	}

	public function filter_product_default_delivery_time( $default_delivery_time, $gzd_product, $product, $context ) {
		global $sitepress;

		if ( 'view' === $context && $sitepress->get_default_language() !== $sitepress->get_current_language() ) {
			$delivery_times = $gzd_product->get_delivery_times();

			if ( array_key_exists( $default_delivery_time, $delivery_times ) ) {
				if ( isset( $delivery_times[ $default_delivery_time ]->translated_term_slug ) ) {
					$default_delivery_time = $delivery_times[ $default_delivery_time ]->translated_term_slug;
				}
			}
		}

		return $default_delivery_time;
	}

	/**
	 * Switch current email to a certain language by reloading locale and triggering Woo WPML.
	 *
	 * @param $lang
	 */
	public function switch_lang( $lang ) {
		$this->set_language( $lang );
	}

	/**
	 * Newer version of WPML do not automatically translate string options in admin requests.
	 * Make sure these Germanized settings are translated during email sending and/or item meta updates.
	 *
	 * @return void
	 */
	public function force_admin_option_translation() {
		/**
		 * Force string translation for Germanized email related strings
		 */
		do_action(
			'wpml_st_force_translate_admin_options',
			apply_filters(
				'woocommerce_gzd_wpml_admin_relevant_string_options',
				array(
					'woocommerce_gzd_email_order_confirmation_text',
					'woocommerce_gzd_email_title_text',
					'woocommerce_gzd_complaints_procedure_text',
					'woocommerce_gzd_delivery_time_text',
					'woocommerce_gzd_unit_price_text',
					'woocommerce_gzd_product_units_text',
					'woocommerce_gzd_differential_taxation_notice_text',
					'woocommerce_gzd_small_enterprise_text',
					'woocommerce_gzd_deposit_text',
					'woocommerce_gzd_alternative_complaints_text_none',
					'woocommerce_gzd_alternative_complaints_text_willing',
					'woocommerce_gzd_alternative_complaints_text_obliged',
					'woocommerce_gzd_legal_checkboxes_settings',
				)
			)
		);
	}

	/**
	 * Filters the Woo WPML email language based on a global variable.
	 *
	 * @param $lang
	 */
	public function filter_email_lang( $p_lang ) {
		if ( $this->new_language ) {
			$p_lang = $this->new_language;
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
					$lang = $object->get_meta( 'wpml_language' );
				} elseif ( is_a( $object, '\Vendidero\Germanized\Shipments\Shipment' ) ) {
					if ( $order = $object->get_order() ) {
						$lang = $order->get_meta( 'wpml_language' );
					}
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
			add_filter( 'wcml_email_language', array( $this, 'filter_email_lang' ), 10 );

			$this->switch_lang( $lang );

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

			if ( $this->new_language ) {
				/**
				 * This action fires as soon as the WPML email language has been switched by the Germanized compatibility script.
				 *
				 * @param string   $lang Language e.g. en
				 * @param WC_Email $email The email instance.
				 *
				 * @since 3.1.2
				 */
				do_action( 'woocommerce_gzd_wpml_switched_email_language', $lang, $email );
			}
		}
	}

	protected function translate_email_setting( $email_id, $option_name = 'heading' ) {
		global $woocommerce_wpml;

		if ( ! is_callable( array( $woocommerce_wpml->emails, 'wcml_get_translated_email_string' ) ) ) {
			return false;
		}

		if ( ! $this->new_language ) {
			return false;
		}

		$domain      = 'admin_texts_woocommerce_' . $email_id . '_settings';
		$name_prefix = '[woocommerce_' . $email_id . '_settings]';

		return $woocommerce_wpml->emails->wcml_get_translated_email_string( $domain, $name_prefix . $option_name, false, $this->new_language );
	}

	/**
	 * Restore email locale after successfully sending the email
	 */
	public function restore_email_locale() {
		global $wc_gzd_original_lang;

		if ( isset( $wc_gzd_original_lang ) && ! empty( $wc_gzd_original_lang ) ) {
			remove_filter( 'wcml_email_language', array( $this, 'filter_email_lang' ), 10 );
		}

		$this->restore_language();
	}

	protected function get_emails() {
		/**
		 * Filter to register custom emails for which to enable WPML email string translation compatibility.
		 *
		 * @param array $emails Class name as key and email id as value.
		 *
		 * @since 3.0.8
		 */
		return apply_filters( 'woocommerce_gzd_wpml_email_ids', WC_germanized()->get_custom_email_ids() );
	}

	protected function get_email_options() {
		$email_options = array();

		foreach ( $this->get_emails() as $key => $email_id ) {
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

		if ( in_array( $email_option, $email_options, true ) ) {
			$key    = array_search( $email_option, $email_options, true );
			$prefix = 'wc_gzd_email_';

			if ( $key && strpos( $key, 'GZDP_' ) !== false ) {
				$prefix = 'wc_gzdp_email_';
			} elseif ( $key && strpos( $key, 'TS_' ) !== false ) {
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

		if ( has_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ) ) ) {
			self::$removed_get_term_filter = true;
			remove_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1 );
		}
	}

	public function rehook_terms_clause() {
		global $sitepress;

		add_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 4 );

		if ( self::$removed_get_term_filter ) {
			add_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1 );
			self::$removed_get_term_filter = false;
		}
	}

	public function send_order_admin_confirmation( $order_id ) {
		global $woocommerce_wpml;

		if ( isset( $woocommerce_wpml ) && isset( $woocommerce_wpml->emails ) && is_object( $woocommerce_wpml->emails ) ) {

			// Remove duplicate filters which lead to non-replaced placeholders
			if ( method_exists( $woocommerce_wpml->emails, 'new_order_email_heading' ) ) {
				remove_filter(
					'woocommerce_email_heading_new_order',
					array(
						$woocommerce_wpml->emails,
						'new_order_email_heading',
					),
					10
				);
				remove_filter(
					'woocommerce_email_subject_new_order',
					array(
						$woocommerce_wpml->emails,
						'new_order_email_subject',
					),
					10
				);
			}

			// Instantiate mailer to make sure that new order email is known
			$mailer = WC()->mailer();

			if ( method_exists( $woocommerce_wpml->emails, 'admin_email' ) ) {
				$woocommerce_wpml->emails->admin_email( $order_id );
			} elseif ( method_exists( $woocommerce_wpml->emails, 'new_order_admin_email' ) ) {
				$woocommerce_wpml->emails->new_order_admin_email( $order_id );
			}

			// Stop Germanized from sending the notification
			add_filter(
				'woocommerce_germanized_order_email_admin_confirmation_sent',
				array(
					$this,
					'set_order_admin_confirmation',
				)
			);
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

		if ( isset( $sitepress ) && is_callable(
			array(
				$sitepress,
				'get_current_language',
			)
		) && is_callable( array( $sitepress, 'switch_lang' ) ) ) {
			if ( $sitepress->get_current_language() !== $lang ) {
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

			$this->force_admin_option_translation();

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
			'review_authenticity_page_id',
		);

		foreach ( $woo_pages as $page ) {
			add_filter( 'woocommerce_get_' . $page, array( $this, 'translate_page' ) );
			add_filter( 'option_woocommerce_' . $page, array( $this, 'translate_page' ) );
		}
	}

	public function translate_page( $id ) {
		global $pagenow;

		if ( is_admin() && 'options-permalink.php' === $pagenow ) {
			return $id;
		}

		return apply_filters( 'wpml_object_id', $id, 'page', true );
	}
}
