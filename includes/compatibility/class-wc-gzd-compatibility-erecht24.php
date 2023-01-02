<?php

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility script for eRecht24 legal texts for WordPress
 *
 * @class        WC_GZD_Compatibility_eRecht24
 * @category     Class
 * @author       vendidero
 */
class WC_GZD_Compatibility_ERecht24 extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'eRecht24 legal texts for WordPress';
	}

	public static function get_path() {
		return 'erecht24/erecht24.php';
	}

	public function load() {
		add_filter( 'woocommerce_gzd_email_attachment_content_shortcodes_allowed', array( $this, 'register_whitelisted_shortcodes' ) );
	}

	public function register_whitelisted_shortcodes( $shortcodes ) {
		$shortcodes = array_merge( $shortcodes, array( 'erecht24' ) );

		return $shortcodes;
	}
}
