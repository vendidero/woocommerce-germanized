<?php

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility script for https://de.wordpress.org/plugins/legal-texts-connector-it-recht-kanzlei/
 *
 * @class        WC_GZD_Compatibility_Legal_Texts_Connector_IT_Recht_Kanzlei
 * @category     Class
 * @author       vendidero
 */
class WC_GZD_Compatibility_Legal_Texts_Connector_IT_Recht_Kanzlei extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'Legal Text Connector of the IT-Recht Kanzlei';
	}

	public static function get_path() {
		return 'legal-texts-connector-it-recht-kanzlei/legal-texts-connector-it-recht-kanzlei.php';
	}

	public function load() {
		add_filter( 'woocommerce_gzd_email_attachment_content_shortcodes_allowed', array( $this, 'register_whitelisted_shortcodes' ) );
	}

	public function register_whitelisted_shortcodes( $shortcodes ) {
		$shortcodes = array_merge( $shortcodes, array( 'agb_terms', 'agb_privacy', 'agb_revocation', 'agb_imprint' ) );

		return $shortcodes;
	}
}
