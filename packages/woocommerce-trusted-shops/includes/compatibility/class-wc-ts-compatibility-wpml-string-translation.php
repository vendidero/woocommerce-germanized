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
class WC_TS_Compatibility_Wpml_String_Translation extends WC_TS_Compatibility {

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
        return apply_filters( 'woocommerce_gzd_wpml_translatable_options', array() );
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

    public function admin_translate_options() {
        $this->set_filters();
    }

    public function set_filters() {
        $admin_strings = $this->get_translatable_admin_options();

        if ( $this->enable_option_filters() ) {

            foreach( $this->get_translatable_options() as $option => $args ) {
                add_filter( 'option_' . $option, array( $this, 'translate_option_filter' ), 10, 2 );
                add_filter( 'pre_update_option_' . $option, array( $this, 'pre_update_translation_filter' ), 10, 3 );

                wc_ts_remove_class_filter( 'option_' . $option, 'WPML_Admin_Texts', 'icl_st_translate_admin_string', 10 );
            }

        } elseif( ! empty( $admin_strings ) ) {

            foreach( $admin_strings as $option => $args ) {
                add_filter( 'option_' . $option, array( $this, 'translate_option_filter' ), 10, 2 );
                wc_ts_remove_class_filter( 'option_' . $option, 'WPML_Admin_Texts', 'icl_st_translate_admin_string', 10 );
            }
        }
    }

    protected function enable_option_filters() {
        $enable = false;

        if ( isset( $_GET['tab'] ) && ( 'trusted-shops' === $_GET['tab'] ) ) {
            $enable = true;
        }

        return apply_filters( 'woocommerce_gzd_enable_wpml_string_translation_settings_filters', $enable );
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

    public function pre_update_translate_checkboxes( $new_value, $old_value, $option ) {
        return $new_value;
    }
}
