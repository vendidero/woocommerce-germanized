<?php
/**
 * PolyLang Helper
 *
 * Specific configuration for PolyLang
 *
 * @class 		WC_GZD_Compatibility_PolyLang
 * @category	Class
 * @author 		vendidero
 */
class WC_GZD_Compatibility_Polylang extends WC_GZD_Compatibility {

	public function __construct() {
		parent::__construct(
			'PolyLang',
			'polylang/polylang.php'
		);
	}

	/**
	 * Maybe define AJAX for Woo requests right after plugins are loaded so that PolyLang changes to the right language by default.
	 */
	public function after_plugins_loaded() {
		if ( ! empty( $_GET['wc-ajax'] ) ) {
			wc_maybe_define_constant( 'DOING_AJAX', true );
			wc_maybe_define_constant( 'WC_DOING_AJAX', true );
		}
	}

	public function load() {
		// Set language field for AJAX revocation and email language
		add_action( 'woocommerce_gzd_after_revocation_form_fields', array( $this, 'set_language_field' ), 10 );
		// Set language field for AJAX Checkout
		add_filter( 'woocommerce_review_order_before_submit', array( $this, 'set_language_field' ), 10 );
	}

	public function set_language_field() {
		echo '<input type="hidden" name="lang" value="' . esc_attr( pll_current_language() ) . '" />';
	}
}