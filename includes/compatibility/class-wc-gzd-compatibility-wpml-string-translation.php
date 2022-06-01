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
class WC_GZD_Compatibility_WPML_String_Translation extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'WPML String Translation';
	}

	public static function get_path() {
		return 'wpml-string-translation/plugin.php';
	}

	public static function is_activated() {
		global $sitepress;

		return defined( 'WPML_ST_VERSION' ) && isset( $sitepress ) ? true : false;
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

		/**
		 * Filter that return WPML translatable string options.
		 *
		 * @param array $strings Array containing the option_name as key.
		 *
		 * @since 2.0.0
		 *
		 */
		return apply_filters(
			'woocommerce_gzd_wpml_translatable_options',
			array(
				'woocommerce_gzd_small_enterprise_text'   => '',
				'woocommerce_gzd_differential_taxation_notice_text' => '',
				'woocommerce_gzd_shipping_costs_text'     => '',
				'woocommerce_gzd_order_submit_btn_text'   => '',
				'woocommerce_gzd_complaints_procedure_text' => '',
				'woocommerce_gzd_delivery_time_text'      => '',
				'woocommerce_gzd_price_range_format_text' => '',
				'woocommerce_gzd_unit_price_text'         => '',
				'woocommerce_gzd_product_units_text'      => '',
				'woocommerce_gzd_display_listings_link_details_text' => '',
				'woocommerce_gzd_display_digital_delivery_time_text' => '',
				'woocommerce_gzd_order_success_text'      => '',
				'woocommerce_gzd_customer_account_text'   => '',
				'woocommerce_gzd_email_order_confirmation_text' => '',
				'woocommerce_gzd_alternative_complaints_text_none' => '',
				'woocommerce_gzd_alternative_complaints_text_willing' => '',
				'woocommerce_gzd_alternative_complaints_text_obliged' => '',
				'woocommerce_gzd_email_title_text'        => '',
				'woocommerce_gzd_legal_checkboxes_settings' => array(
					'label',
					'error_message',
					'confirmation',
				),
			)
		);
	}

	public function get_translatable_admin_options() {
		/**
		 * Filter to add further WPML translatable admin options.
		 *
		 * By default WPML allow only certain strings to be translated within the administration area (e.g. blog title).
		 * If you want some translatable strings to be loaded globally within the admin panel use the filter accordingly.
		 *
		 * @param array $strings Array containing admin strings.
		 *
		 * @since 2.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_wpml_translatable_admin_options', array() );
	}

	public function admin_translate_options() {
		$this->set_filters();
	}

	public function set_filters() {
		$admin_strings = $this->get_translatable_admin_options();

		if ( $this->enable_option_filters() ) {

			foreach ( $this->get_translatable_options() as $option => $args ) {

				add_filter( 'option_' . $option, array( $this, 'translate_option_filter' ), 10, 2 );
				add_filter( 'pre_update_option_' . $option, array( $this, 'pre_update_translation_filter' ), 10, 3 );

				wc_gzd_remove_class_filter( 'option_' . $option, 'WPML_Admin_Texts', 'icl_st_translate_admin_string', 10 );
			}
		} elseif ( ! empty( $admin_strings ) ) {

			foreach ( $admin_strings as $option => $args ) {
				add_filter( 'option_' . $option, array( $this, 'translate_option_filter' ), 10, 2 );
				wc_gzd_remove_class_filter( 'option_' . $option, 'WPML_Admin_Texts', 'icl_st_translate_admin_string', 10 );
			}
		}
	}

	protected function enable_option_filters() {
		$enable = false;

		if ( isset( $_GET['tab'] ) && ( strpos( $_GET['tab'], 'germanized' ) !== false || 'email' === $_GET['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
			$enable = true;
		}

		/**
		 * Filter that allows enabling WPML translation string filters.
		 *
		 * @param bool $enable Whether to enable filters or not.
		 *
		 * @since 2.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_enable_wpml_string_translation_settings_filters', $enable );
	}

	public function get_string_language( $string_id, $option = '' ) {
		if ( $string = $this->get_string_by_id( $string_id ) ) {
			return $string->language;
		}

		global $WPML_String_Translation; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		return $WPML_String_Translation->get_current_string_language( $option ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
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

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_string_translations WHERE string_id=%d AND language=%s", $string_id, $language ) );
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
		$option_names            = get_option( '_icl_admin_option_names', array() );
		$option_names[ $option ] = 1;

		update_option( '_icl_admin_option_names', $option_names );
	}

	public function get_string_translation( $string_id, $language, $status = '' ) {
		$translations = icl_get_string_translations_by_id( $string_id );
		$status       = empty( $status ) ? ICL_TM_COMPLETE : $status;

		if ( isset( $translations[ $language ] ) && $translations[ $language ]['status'] === $status ) {
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

			foreach ( $new_value as $id => $options ) {

				foreach ( $options as $key => $value ) {
					if ( in_array( $key, $args ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
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

				/**
				 * Filter that allows to disable deleting empty strings or strings that equal their parent value.
				 *
				 * This filter is used by our Trusted Shops integration to allow "real" option translation e.g. to allow
				 * one option to be set for a specific language only.
				 *
				 * @param bool $enable Whether to enable deletion or not.
				 * @param string $option The option name.
				 * @param string $new_value The new value.
				 * @param string $old_value The old value.
				 *
				 * @since 2.0.0
				 *
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

			foreach ( $org_value as $id => $options ) {
				if ( is_array( $options ) ) {
					foreach ( $options as $key => $value ) {
						if ( in_array( $key, $args ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
							$org_value[ $id ][ $key ] = $this->translate_option( $value, "[{$option}][{$id}]{$key}", "admin_texts_{$option}" );
						}
					}
				}
			}

			return $org_value;
		} else {
			return $this->translate_option( $org_value, $option );
		}
	}

	protected function translate_option( $org_value, $option, $context = '' ) {
		$string_id = $this->get_string_id( $option, $context );
		$language  = $this->get_current_language();

		if ( ! $string_id ) {
			$string_id = $this->register_string( $option, $org_value, $context );
		}

		$translation = $this->get_string_translation( $string_id, $language );

		if ( false !== $translation ) {
			$org_value = $translation;
		}

		return $org_value;
	}
}
