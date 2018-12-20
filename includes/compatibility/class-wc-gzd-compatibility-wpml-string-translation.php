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
class WC_GZD_Compatibility_Wpml_String_Translation extends WC_GZD_Compatibility {

    public function __construct() {
        parent::__construct(
            'WPML String Translation',
            'wpml-string-translation/plugin.php',
            array(
                'version' => defined( 'WPML_ST_VERSION' ) ? WPML_ST_VERSION : '1.0',
            )
        );
    }

    public function is_activated() {
        global $sitepress;

        return defined( 'WPML_ST_VERSION' ) && isset( $sitepress) ? true : false;
    }

    public function load() {
        if ( is_admin() ) {
            add_action( 'woocommerce_gzd_admin_assets', array( $this, 'settings_script' ), 10, 3 );
            add_action( 'woocommerce_gzd_admin_localized_scripts', array( $this, 'settings_script_localization' ), 10, 1 );

            $this->admin_translate_options();
        }
    }

    public function get_languages() {
        global $sitepress;
        $codes = array();

        if ( isset( $sitepress ) && is_callable( array( $sitepress, 'get_ls_languages' ) ) ) {
            $languages = $sitepress->get_ls_languages();

            if ( ! empty( $languages ) ) {
                $codes = array_keys( $languages );
            }
        }

        return $codes;
    }

    public function get_language_name( $language = '' ) {
        global $sitepress;

        if ( empty( $language ) ) {
            $language = $this->get_current_language();
        }

        return $sitepress->get_display_language_name( $language );
    }

    public function get_current_language() {
        global $sitepress;

        return $sitepress->get_current_language();
    }

    public function get_default_language() {
        global $sitepress;
        $default = '';

        if ( isset( $sitepress ) && is_callable( array( $sitepress, 'get_default_language' ) ) ) {
            $default = $sitepress->get_default_language();
        }

        return $default;
    }

    /**
     * Register strings that are translatable within the settings panel. Strings will be loaded in the admin user's language
     * within the settings screen only.
     *
     * @return array
     */
    public function get_translatable_options() {
        return apply_filters( 'woocommerce_gzd_wpml_translatable_options', array(
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
        ) );
    }

    /**
     * By default WPML allow only certain strings to be translated within the administration area (e.g. blog title).
     * If you want some translatable strings to be loaded globally within the admin panel use the filter accordingly.
     *
     * @return array
     */
    public function get_translatable_admin_options() {
        return apply_filters( 'woocommerce_gzd_wpml_translatable_admin_options', array() );
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

    public function admin_translate_options() {
        add_action( 'admin_init', array( $this, 'set_filters' ), 50 );
    }

    public function set_filters() {
        $admin_strings = $this->get_translatable_admin_options();

        if ( $this->enable_option_filters() ) {

            foreach( $this->get_translatable_options() as $option => $args ) {
                add_filter( 'option_' . $option, array( $this, 'translate_option_filter' ), 10, 2 );
                add_filter( 'pre_update_option_' . $option, array( $this, 'pre_update_translation_filter' ), 10, 3 );

                wc_gzd_remove_class_filter( 'option_' . $option, 'WPML_Admin_Texts', 'icl_st_translate_admin_string', 10 );
            }

            add_filter( 'woocommerce_gzd_get_settings_filter', array( $this, 'add_admin_notices' ), 10, 1 );
            add_filter( 'woocommerce_gzd_legal_checkbox_fields', array( $this, 'add_admin_notices_checkboxes' ), 10, 2 );
            add_filter( 'woocommerce_gzd_admin_email_order_confirmation_text_option', array( $this, 'add_admin_notices_email' ), 10, 1 );

        } elseif( ! empty( $admin_strings ) ) {

            foreach( $admin_strings as $option => $args ) {
                add_filter( 'option_' . $option, array( $this, 'translate_option_filter' ), 10, 2 );
                wc_gzd_remove_class_filter( 'option_' . $option, 'WPML_Admin_Texts', 'icl_st_translate_admin_string', 10 );
            }
        }
    }

    protected function enable_option_filters() {
        $enable = false;

        if ( isset( $_GET['tab'] ) && ( 'germanized' === $_GET['tab'] || 'email' === $_GET['tab'] ) ) {
            $enable = true;
        }

        return apply_filters( 'woocommerce_gzd_enable_wpml_string_translation_settings_filters', $enable );
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
        $ids     = array();
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
        // Remove notices for TS
        if ( isset( $_GET['section'] ) && 'trusted_shops' === $_GET['section'] ) {
            return $settings;
        }

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

        if ( $string_id = $this->get_string_id( $option ) ) {
            icl_add_string_translation( $string_id, $language, $value, $status );
        }

        icl_update_string_translation( $option, $language, $value, $status );

        // Make sure that the string is stored within the WPML translatable option names
        $option_names = get_option( '_icl_admin_option_names', array() );
        $option_names[ $option ] = 1;

        update_option( '_icl_admin_option_names', $option_names );
    }

    public function get_string_translation( $string_id, $language, $status = '' ) {
        $translations = icl_get_string_translations_by_id( $string_id );
        $status       = empty( $status ) ? ICL_TM_COMPLETE : $status;

        if ( isset( $translations[ $language ] ) && $translations[ $language ]['status'] == $status ) {
            return $translations[ $language ]['value'];
        }

        return false;
    }

    public function get_translated_string( $option, $language, $context = '' ) {
        $value = null;

        if ( $string_id = $this->get_string_id( $option, $context ) ) {
            $value = $this->get_string_translation( $string_id, $language );
        }

        return $value;
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
        $return_value     = $old_value;

        if ( $strings_language === $this->get_current_language() || 'all' === $this->get_current_language() ) {

            $current_string_value = $this->get_string_value( $org_string_id );

            // Update original string value
            if ( $org_string_id && ( $new_value !== $old_value || $current_string_value !== $new_value ) ) {
                $this->update_string_value( $org_string_id, $new_value );
            }

            $return_value = $new_value;

        } else {
            $update_translation = true;

            if ( $org_string_id ) {
                $org_string = $this->get_string_value( $org_string_id );

                /**
                 * Remove translation if it equals original string
                 * Use woocommerce_gzd_wpml_remove_translation_empty_equal filter to disallow string deletion which results in "real" option translations
                 */
                if ( ( $org_string === $new_value || empty( $new_value ) ) && apply_filters( 'woocommerce_gzd_wpml_remove_translation_empty_equal', true, $option, $new_value, $old_value ) ) {
                    $this->delete_string_translation( $org_string_id, $this->get_current_language() );

                    $return_value       = $old_value;
                    $update_translation = false;
                }
            }

            if ( $update_translation ) {
                $this->update_string_translation( $option, $this->get_current_language(), $new_value );
            }
        }

        // Allow WPML to delete the cache
        do_action( "update_option_{$option}", $old_value, $return_value, $option );

        return $return_value;
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

        $translation = $this->get_string_translation( $string_id, $language );

        if ( false !== $translation ) {
            $org_value = $translation;
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
}