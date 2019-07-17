<?php
/**
 * WPML Helper
 *
 * Specific configuration for WPML
 *
 * @class 		WC_GZD_WPML_Helper
 * @category	Class
 * @author 		vendidero
 */
class WC_GZD_Compatibility_Wpml extends WC_GZD_Compatibility {

	protected $dynamic_unit_pricing = null;

	protected $new_language = false;

	public function __construct() {
		parent::__construct( 
			'WPML', 
			'sitepress-multilingual-cms/sitepress.php', 
			array( 
				'version' => get_option( 'icl_sitepress_version', '1.0.0' )
			) 
		);
	}

	public function is_activated() {
		return parent::is_activated() && wc_gzd_get_dependencies()->is_plugin_activated( 'woocommerce-multilingual/wpml-woocommerce.php' );
	}

	public function load() {

		// Support unit price for multiple currencies
		if ( function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on() ) {
			$this->dynamic_unit_pricing = new WC_GZD_Compatibility_Woocommerce_Dynamic_Pricing();
			$this->dynamic_unit_pricing->load();

			add_action( 'woocommerce_gzd_before_get_unit_price_html', array( $this, 'before_show_product_unit_price' ), 10, 1 );
		}

		// Prevent double sending order confirmation email to admin
		if ( wc_gzd_send_instant_order_confirmation() ) {
			add_action( 'wp_loaded', array( $this, 'unregister_order_confirmation_hooks' ) );
			add_action( 'woocommerce_germanized_before_order_confirmation', array( $this, 'send_order_admin_confirmation' ) );
		}

		add_action( 'woocommerce_gzd_get_term', array( $this, 'unhook_terms_clause' ), 10 );
        add_action( 'woocommerce_gzd_after_get_term', array( $this, 'rehook_terms_clause' ), 10 );

        add_action( 'woocommerce_gzd_after_revocation_form_fields', array( $this, 'set_language_field' ), 10 );

		$this->filter_page_ids();
	}

	public function reload_locale() {
	    unload_textdomain( 'woocommerce-germanized' );
	    WC_germanized()->load_plugin_textdomain();

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
        do_action('wpml_add_language_form_field' );
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
                remove_filter('woocommerce_email_heading_new_order', array($woocommerce_wpml->emails, 'new_order_email_heading'), 10);
                remove_filter('woocommerce_email_subject_new_order', array($woocommerce_wpml->emails, 'new_order_email_subject'), 10);
            }

            // Instantiate mailer to make sure that new order email is known
            $mailer = WC()->mailer();

            if ( method_exists( $woocommerce_wpml->emails, 'admin_email' ) ) {
                 $woocommerce_wpml->emails->admin_email( $order_id );
            } elseif ( method_exists( $woocommerce_wpml->emails, 'new_order_admin_email' ) ) {
				$woocommerce_wpml->emails->new_order_admin_email( $order_id );
            }
		
			// Stop Germanized from sending the notification
			add_filter( 'woocommerce_germanized_order_email_admin_confirmation_sent', array( $this, 'set_order_admin_confirmation' ) );
		}
	}

	public function set_order_admin_confirmation( $is_sent ) {
		return true;
	}

	public function unregister_order_confirmation_hooks() {
		
		global $woocommerce_wpml;
		
		if ( isset( $woocommerce_wpml ) ) {
			$statuses = array(
				'woocommerce_order_status_pending_to_processing_notification',
        		'woocommerce_order_status_pending_to_completed_notification',
        		'woocommerce_order_status_pending_to_on-hold_notification',
			);
		
			foreach ( $statuses as $status ) {
				if ( method_exists( $woocommerce_wpml->emails, 'admin_email' ) ) {
                    remove_action( $status, array( $woocommerce_wpml->emails, 'admin_email' ), 9 );
                } elseif ( method_exists( $woocommerce_wpml->emails, 'new_order_admin_email' ) ) {
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

        if ( isset( $sitepress ) && is_callable( array( $sitepress, 'get_current_language' ) ) && is_callable( array( $sitepress, 'switch_lang' ) ) ) {

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

                unload_textdomain( 'default' );
                unload_textdomain( 'woocommerce' );

                // Init WC locale.
                WC()->load_plugin_textdomain();
                $this->reload_locale();

                load_default_textdomain( get_locale() );
            }

            /**
             * WPML language switched.
             *
             * Fires whenever Germanized has explicitly changed the language.
             *
             * @since 2.2.9
             *
             * @param string $lang The new language code.
             * @param string $wc_gzd_original_lang The old language code.
             */
            do_action( 'woocommerce_gzd_wpml_switched_language', $lang, $wc_gzd_original_lang );
        }

        /**
         * WPML language switch.
         *
         * Fires whenever Germanized was asked to programatically change the language.
         *
         * @since 2.2.9
         *
         * @param string $lang The new language code.
         * @param string $wc_gzd_original_lang The old language code.
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
            'shipping_costs_page_id'
        );
        
        foreach ( $woo_pages as $page ) {
        	add_filter( 'woocommerce_get_' . $page, array( $this, 'translate_page' ) );
            add_filter( 'option_woocommerce_' . $page, array( $this, 'translate_page') );
        }
	}

	public function translate_page( $id ) {
        global $pagenow;
        
        if( is_admin() && $pagenow === 'options-permalink.php' )
            return $id;
        
        return apply_filters( 'translate_object_id', $id, 'page', true );
    }

}