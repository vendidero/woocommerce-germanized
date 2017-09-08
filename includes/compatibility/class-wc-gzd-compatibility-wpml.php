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

		// Observe order update and trigger hook
		add_action( 'post_updated', array( $this, 'observe_order_update' ), 0, 3 );
		
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

	public function before_show_product_unit_price( $product ) {
		$product->recalculate_unit_price();
	}

	public function set_language_field() {
		if( function_exists('wpml_the_language_input_field' ) ) {
			wpml_the_language_input_field();
		}
	}

	public function unhook_terms_clause() {
        global $sitepress;

        remove_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 4 );
    }

    public function rehook_terms_clause() {
        global $sitepress;

        add_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 4 );
    }

	public function send_order_admin_confirmation( $order_id ) {
		global $woocommerce_wpml;
		
		if ( isset( $woocommerce_wpml ) && isset( $woocommerce_wpml->emails ) && is_object( $woocommerce_wpml->emails ) ) {
		
			// Instantiate mailer to make sure that new order email is known
			$mailer = WC()->mailer();
			
			if ( method_exists( $woocommerce_wpml->emails, 'admin_email' ) )
				$woocommerce_wpml->emails->admin_email( $order_id );
			elseif ( method_exists( $woocommerce_wpml->emails, 'new_order_admin_email' ) )
				$woocommerce_wpml->emails->new_order_admin_email( $order_id );
		
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
				if ( method_exists( $woocommerce_wpml->emails, 'admin_email' ) )
					remove_action( $status, array( $woocommerce_wpml->emails, 'admin_email' ), 9 );
				elseif ( method_exists( $woocommerce_wpml->emails, 'new_order_admin_email' ) )
					remove_action( $status, array( $woocommerce_wpml->emails, 'new_order_admin_email' ), 9 );
			}
		}
	}

	public function observe_order_update( $post_id, $post_after, $post_before ) {

		if ( 'shop_order' === $post_after->post_type ) {

			do_action( 'woocommerce_gzd_before_order_post_status', $post_id );

			$order = wc_get_order( $post_id );
			$lang = null;

			// Reset GZD Locale
			if ( $lang = get_post_meta( $post_id, 'wpml_language', true ) ) {
				$this->set_language( $lang );
			}
		}

	}

	public function set_language( $lang ) {

		global $sitepress, $woocommerce;

		$sitepress->switch_lang( $lang, true );
        $this->locale = $sitepress->get_locale( $lang );
       	
       	add_filter( 'plugin_locale', array( $this, 'set_locale' ), 10, 2 );

        unload_textdomain( 'woocommerce' );
        unload_textdomain( 'woocommerce-germanized' );
        unload_textdomain( 'woocommerce-germanized-pro' );
        unload_textdomain( 'default' );
        
        $woocommerce->load_plugin_textdomain();
        WC_germanized()->load_plugin_textdomain();

        do_action( 'woocommerce_gzd_wpml_lang_changed', $lang );
        
        load_default_textdomain();
        
        global $wp_locale;
        $wp_locale = new WP_Locale();

	}

	public function set_locale( $locale, $domain ) {

		if( in_array( $domain, array( 'woocommerce', 'woocommerce-germanized', 'woocommerce-germanized-pro' ) ) && $this->locale ) {
            $locale = $this->locale;
        }

        return $locale;
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
        
        if( is_admin() && $pagenow == 'options-permalink.php' )
            return $id;
        
        return apply_filters( 'translate_object_id', $id, 'page', true );
    }

}