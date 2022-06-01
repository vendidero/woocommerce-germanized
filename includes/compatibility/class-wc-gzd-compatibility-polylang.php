<?php

defined( 'ABSPATH' ) || exit;

/**
 * PolyLang Helper
 *
 * Specific configuration for PolyLang
 *
 * @class        WC_GZD_Compatibility_PolyLang
 * @category    Class
 * @author        vendidero
 */
class WC_GZD_Compatibility_Polylang extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'PolyLang';
	}

	public static function get_path() {
		return 'polylang/polylang.php';
	}

	/**
	 * Maybe define AJAX for Woo requests right after plugins are loaded so that PolyLang changes to the right language by default.
	 */
	public function after_plugins_loaded() {
		if ( ! empty( $_GET['wc-ajax'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wc_maybe_define_constant( 'DOING_AJAX', true );
			wc_maybe_define_constant( 'WC_DOING_AJAX', true );
		}

		// Refresh strings after wp action has been fired. Polylang initializes within wp action not init action (which is being triggered earlier).
		// See https://polylang.wordpress.com/documentation/documentation-for-developers/general/ and https://codex.wordpress.org/Plugin_API/Action_Reference
		add_action( 'wp', array( $this, 'refresh_checkbox' ), 50 );
	}

	public function load() {
		// Set language field for AJAX revocation and email language
		add_action( 'woocommerce_gzd_after_revocation_form_fields', array( $this, 'set_language_field' ), 10 );
		// Set language field for AJAX Checkout
		add_action( 'woocommerce_review_order_before_submit', array( $this, 'set_language_field' ), 10 );
	}

	public function refresh_checkbox() {
		if ( $manager = WC_GZD_Legal_Checkbox_Manager::instance() ) {
			$options = $manager->get_options( true );

			// Make sure we are not registering core checkboxes again
			foreach ( $options as $id => $checkbox_args ) {
				if ( isset( $checkbox_args['id'] ) ) {
					unset( $checkbox_args['id'] );
				}

				if ( $checkbox = $manager->get_checkbox( $id ) ) {
					$checkbox->update( $checkbox_args );
				}
			}
		}
	}

	public function set_language_field() {
		if ( function_exists( 'pll_current_language' ) ) {
			echo '<input type="hidden" name="lang" value="' . esc_attr( pll_current_language() ) . '" />';
		}
	}
}
