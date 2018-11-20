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

	protected $strings_to_translate = array(
		'woocommerce_gzd_small_enterprise_text'               => '',
		'woocommerce_gzd_differential_taxation_notice_text'   => '',
		'woocommerce_gzd_shipping_costs_text'                 => '',
		'woocommerce_gzd_order_submit_btn_text'               => '',
		'woocommerce_gzd_complaints_procedure_text'           => '',
		'woocommerce_gzd_delivery_time_text'                  => '',
		'woocommerce_gzd_price_range_format_text'             => '',
		'woocommerce_gzd_unit_price_text'                     => '',
		'woocommerce_gzd_product_units_text'                  => '',
		'woocommerce_gzd_display_listings_link_details_text'  => '',
		'woocommerce_gzd_display_digital_delivery_time_text'  => '',
		'woocommerce_gzd_order_success_text'                  => '',
		'woocommerce_gzd_customer_account_text'               => '',
		'woocommerce_gzd_email_order_confirmation_text'       => '',
		'woocommerce_gzd_alternative_complaints_text_none'    => '',
		'woocommerce_gzd_alternative_complaints_text_willing' => '',
		'woocommerce_gzd_alternative_complaints_text_obliged' => '',
		'woocommerce_gzd_legal_checkboxes_settings'           => array(
			'label',
			'error_message',
			'confirmation'
		),
	);

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

		if ( is_admin() ) {
			add_action( 'woocommerce_gzd_admin_assets', array( $this, 'settings_script' ), 10, 3 );
			add_action( 'woocommerce_gzd_admin_localized_scripts', array( $this, 'settings_script_localization' ), 10, 1 );

			$this->admin_translate_options();
		}
	}

	public function settings_script( $gzd_admin, $admin_script_path, $suffix ) {
		wp_register_script( 'wc-gzd-settings-wpml', $admin_script_path . 'settings-wpml' . $suffix . '.js', array( 'jquery', 'woocommerce_settings' ), WC_GERMANIZED_VERSION, true );

		if ( isset( $_GET['tab'] ) && ( 'germanized' === $_GET['tab'] || 'email' === $_GET['tab'] ) ) {
			wp_enqueue_script( 'wc-gzd-settings-wpml' );
			wp_add_inline_style( 'woocommerce-gzd-admin', '.wc-gzd-wpml-notice {display: block; font-size: 12px; margin-top: 8px; line-height: 1.5em; background: #faf8e5; padding: 5px;} .wc-gzd-wpml-notice code {font-size: 12px; display: block; background: transparent; padding-left: 0;}' );
		}
	}

	public function settings_script_localization( $localized ) {
		$options      = array();
		$translatable = $this->get_translatable_options();

		foreach( $translatable as $option => $data ) {
			if ( 'woocommerce_gzd_legal_checkboxes_settings' === $option ) {

				$manager = WC_GZD_Legal_Checkbox_Manager::instance();
				$manager->do_register_action();

				foreach( $manager->get_checkboxes() as $id => $checkbox ) {
					foreach( $data as $value ) {
						$options[] = "#{$checkbox->get_form_field_id( $value )}";
					}
				}
			} else {
				$options[] = "#{$option}";
			}
		}

		$localized['wc-gzd-settings-wpml'] = array(
			'options' => $options
		);

		return $localized;
	}

	public function get_translatable_options() {
		return apply_filters( 'woocommerce_gzd_wpml_translatable_options', $this->strings_to_translate );
	}

	public function admin_translate_options() {
		if ( ! $this->supports_string_translation() ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'set_filters' ), 50 );
	}

	protected function enable_option_filters() {
		$enable = false;

		if ( isset( $_GET['tab'] ) && ( 'germanized' === $_GET['tab'] || 'email' === $_GET['tab'] ) ) {
			$enable = true;
		}

		return apply_filters( 'woocommerce_gzd_enable_wpml_string_translation_settings_filters', $enable );
	}

	public function set_filters() {
		if ( $this->enable_option_filters() ) {

			foreach( $this->get_translatable_options() as $option => $args ) {
				add_filter( 'option_' . $option, array( $this, 'translate_option_filter' ), 10, 2 );
				add_filter( 'pre_update_option_' . $option, array( $this, 'pre_update_translation_filter' ), 10, 3 );

				wc_gzd_remove_class_filter( 'option_' . $option, 'WPML_Admin_Texts', 'icl_st_translate_admin_string', 10 );
			}

			add_filter( 'woocommerce_gzd_get_settings_filter', array( $this, 'add_admin_notices' ), 10, 1 );
			add_filter( 'woocommerce_gzd_legal_checkbox_fields', array( $this, 'add_admin_notices_checkboxes' ), 10, 2 );
			add_filter( 'woocommerce_gzd_admin_email_order_confirmation_text_option', array( $this, 'add_admin_notices_email' ), 10, 1 );
		}
	}

	public function supports_string_translation() {
		$enabled = false;

		if ( defined( 'WPML_ST_VERSION' ) && wc_gzd_get_dependencies()->compare_versions( WPML_ST_VERSION, '2.8.7', '>=' ) ) {
			$enabled = true;
		}

		return apply_filters( 'woocommerce_gzd_enable_wpml_string_translation_settings', $enabled );
	}

	public function add_admin_notices_email( $setting ) {
		if ( isset( $setting['id'] ) && array_key_exists( $setting['id'], $this->get_translatable_options() ) ) {
			if ( $string_id = $this->get_string_id( $setting['id'] ) ) {
				$string_language = $this->get_string_language( $string_id, $setting['id'] );

				if ( $string_language !== $this->get_current_language() && 'all' !== $this->get_current_language() ) {
					$setting = $this->set_admin_notice_attribute( $setting, $string_id, $string_language );
				}
			}
		}

		return $setting;
	}

	public function add_admin_notices_checkboxes( $settings, $checkbox ) {
		$ids = array();
		$options = $this->get_translatable_options();

		foreach( $options['woocommerce_gzd_legal_checkboxes_settings'] as $option_key ) {
			$ids[] = $checkbox->get_form_field_id( $option_key );
		}

		foreach( $settings as $key => $setting ) {
			if ( isset( $setting['id'] ) && in_array( $setting['id'], $ids ) ) {
				$option_key  = str_replace( $checkbox->get_form_field_id_prefix(), '', $setting['id'] );
				$option_name = "[woocommerce_gzd_legal_checkboxes_settings][{$checkbox->get_id()}]{$option_key}";

				if ( $string_id = $this->get_string_id( $option_name, "admin_texts_woocommerce_gzd_legal_checkboxes_settings" ) ) {
					$string_language = $this->get_string_language( $string_id, $option_name );

					if ( $string_language !== $this->get_current_language() && 'all' !== $this->get_current_language() ) {
						$settings[ $key ] = $this->set_admin_notice_attribute( $settings[ $key ], $string_id, $string_language );
					}
				}
			}
		}

		return $settings;
	}

	public function add_admin_notices( $settings ) {
		foreach( $settings as $key => $setting ) {
			if ( isset( $setting['id'] ) && array_key_exists( $setting['id'], $this->get_translatable_options() ) ) {
				if ( $string_id = $this->get_string_id( $setting['id'] ) ) {
					$string_language = $this->get_string_language( $string_id, $setting['id'] );

					if ( $string_language !== $this->get_current_language() && 'all' !== $this->get_current_language() ) {
						$settings[ $key ] = $this->set_admin_notice_attribute( $settings[ $key ], $string_id, $string_language );
					}
				}
			} 
		}

		return $settings;
	}

	protected function set_admin_notice_attribute( $setting, $string_id, $string_language ) {
		if ( ! isset( $setting['custom_attributes'] ) || ! is_array( $setting['custom_attributes'] ) ) {
			$setting['custom_attributes'] = array();
		}

		$setting['custom_attributes']['data-wpml-notice'] = sprintf( __( 'This option may be translated via WPML. You may translate the original option (%s) %s to %s by adjusting the value above.', 'woocommerce-germanized' ), $this->get_language_name( $string_language ), '<code>' . $this->get_string_value( $string_id ) . '</code>', $this->get_language_name() );

		return $setting;
	}

	public function get_language_name( $language = '' ) {
		global $sitepress;

		if ( empty( $language ) ) {
			$language = $this->get_current_language();
		}

		return $sitepress->get_display_language_name( $language );
	}

	public function get_current_language() {
		return ICL_LANGUAGE_CODE;
	}

	public function get_string_language( $string_id, $option = '' ) {
		if ( $string = $this->get_string_by_id( $string_id ) ) {
			return $string->language;
		}

		global $WPML_String_Translation;

		return $WPML_String_Translation->get_current_string_language( $option );
	}

	public function get_string_by_id( $string_id ) {
		global $wpdb;

		$string = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}icl_strings WHERE id=%d LIMIT 1", $string_id ) );

		if ( $string ) {
			return $string[0];
		}

		return false;
	}

	public function get_string_id( $option, $context = '' ) {
		$context = empty( $context ) ? 'admin_texts_' . $option : $context;

		return icl_st_is_registered_string( $context, $option );
	}

	public function get_string_value( $string_id ) {
		if ( $string = $this->get_string_by_id( $string_id ) ) {
			return $string->value;
		}

		return false;
	}

	public function update_string_value( $string_id, $value ) {
		global $wpdb;

		$value = maybe_serialize( $value );

		$wpdb->update( "{$wpdb->prefix}icl_strings", array( 'value' => $value ), array( 'id' => $string_id ) );
	}

	public function delete_string_translation( $string_id, $language ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_string_translations WHERE string_id=%d AND language=%s", $string_id, $language ) );
	}

	public function update_string_translation( $option, $language, $value, $status = '' ) {
		if ( empty( $status ) ) {
			$status = ICL_TM_COMPLETE;
		}

		icl_update_string_translation( $option, $language, $value, $status );
	}

	public function get_string_translation( $string_id, $language, $status = '' ) {
		$translations = icl_get_string_translations_by_id( $string_id );
		$status       = empty( $status ) ? ICL_TM_COMPLETE : $status;

		if ( isset( $translations[ $language ] ) && $translations[ $language ]['status'] == $status ) {
			return $translations[ $language ]['value'];
		}

		return false;
	}

	public function register_string( $option, $value, $context = '' ) {
		$context = empty( $context ) ? 'admin_texts_' . $option : $context;

		return icl_register_string( $context, $option, $value );
	}

	public function pre_update_translation_filter( $new_value, $old_value, $option ) {
		$string_options = $this->get_translatable_options();

		if ( is_array( $new_value ) || is_array( $string_options[ $option ] ) ) {

			if ( ! is_array( $new_value ) ) {
				$new_value = array();
			}

			$args = $string_options[ $option ];

			foreach( $new_value as $id => $options ) {
				foreach( $options as $key => $value ) {
					if ( in_array( $key, $args ) ) {
						$old_value_internal       = isset( $old_value[ $id ][ $key ] ) ? $old_value[ $id ][ $key ] : '';
						$new_value[ $id ][ $key ] = $this->pre_update_translation( $value, $old_value_internal, "[{$option}][{$id}]{$key}", "admin_texts_{$option}" );
					}
				}
			}

			return $new_value;
		} else {
			return $this->pre_update_translation( $new_value, $old_value, $option );
		}
	}

	protected function pre_update_translation( $new_value, $old_value, $option, $context = '' ) {
		$org_string_id    = $this->get_string_id( $option, $context );
		$strings_language = $this->get_string_language( $org_string_id, $option );

		if ( $strings_language === $this->get_current_language() || 'all' === $this->get_current_language() ) {

			$current_string_value = $this->get_string_value( $org_string_id );

			// Update original string value
			if ( $org_string_id && ( $new_value !== $old_value || $current_string_value !== $new_value ) ) {
				$this->update_string_value( $org_string_id, $new_value );
			}

			return $new_value;
		}

		if ( $org_string_id ) {
			$org_string   = $this->get_string_value( $org_string_id );

			// Remove translation if it equals original string
			if ( $org_string === $new_value || empty( $new_value ) ) {
				$this->delete_string_translation( $org_string_id, $this->get_current_language() );

				return $old_value;
			}
		}

		$this->update_string_translation( $option, $this->get_current_language(), $new_value );

		return $old_value;
	}

	public function translate_option_filter( $org_value, $option ) {
		$string_options = $this->get_translatable_options();

		if ( is_array( $org_value ) || is_array( $string_options[ $option ] ) ) {

			if ( ! is_array( $org_value ) ) {
				$org_value = array();
			}

			$args = $string_options[ $option ];

			foreach( $org_value as $id => $options ) {
				foreach( $options as $key => $value ) {
					if ( in_array( $key, $args ) ) {
						$org_value[ $id ][ $key ] = $this->translate_option( $value, "[{$option}][{$id}]{$key}", "admin_texts_{$option}" );
					}
				}
			}

			return $org_value;
		} else {
			return $this->translate_option( $org_value, $option );
		}
	}

	protected function translate_option( $org_value, $option, $context = '' ) {
		$string_id          = $this->get_string_id( $option, $context );
		$language           = $this->get_current_language();

		if ( ! $string_id ) {
			$string_id      = $this->register_string( $option, $org_value, $context );
		}

		if ( $translation = $this->get_string_translation( $string_id, $language ) ) {
			return $translation;
		}

		return $org_value;
	}

	public function translate_option_checkboxes( $org_value, $option ) {
		if ( ! is_array( $org_value ) ) {
			$org_value = array();
		}

		foreach( $org_value as $checkbox_id => $options ) {
			foreach( $options as $option => $value ) {
				if ( in_array( $option, $this->get_checkbox_translateable_options() ) ) {
					$org_value[ $checkbox_id ][ $option ] = $this->translate_option( $value, "[woocommerce_gzd_legal_checkboxes_settings][{$checkbox_id}]{$option}", "admin_texts_woocommerce_gzd_legal_checkboxes_settings" );
				}
			}
		}

		return $org_value;
	}

	public function pre_update_translate_checkboxes( $new_value, $old_value, $option ) {
		return $new_value;
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
				remove_filter( 'woocommerce_email_heading_new_order',  array( $woocommerce_wpml->emails, 'new_order_email_heading' ), 10 );
				remove_filter( 'woocommerce_email_subject_new_order',  array( $woocommerce_wpml->emails, 'new_order_email_subject' ), 10 );
			}
		
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
        
        if( is_admin() && $pagenow === 'options-permalink.php' )
            return $id;
        
        return apply_filters( 'translate_object_id', $id, 'page', true );
    }

}