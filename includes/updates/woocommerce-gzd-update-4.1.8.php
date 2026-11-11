<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The legal checkbox settings are needed on (almost) every request - make sure the option is autoloaded.
 * update_option() does not adjust the autoload flag in case the value did not change.
 */
if ( function_exists( 'wp_set_option_autoload' ) ) {
	wp_set_option_autoload( 'woocommerce_gzd_legal_checkboxes_settings', true );
} else {
	$checkbox_options = get_option( 'woocommerce_gzd_legal_checkboxes_settings', null );

	if ( ! is_null( $checkbox_options ) ) {
		delete_option( 'woocommerce_gzd_legal_checkboxes_settings' );
		add_option( 'woocommerce_gzd_legal_checkboxes_settings', $checkbox_options, '', 'yes' );
	}
}
