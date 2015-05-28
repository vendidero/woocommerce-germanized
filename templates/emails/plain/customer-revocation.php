<?php
/**
 * Customer revocation confirmation (plain)
 *
 * @author Vendidero
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$fields = WC_GZD_Revocation::get_fields();

echo "= " . $email_heading . " =\n\n";

echo _x( 'By sending you this email we confirm your Revocation. Please review your data.', 'revocation-form', 'woocommerce-germanized' ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( ! empty( $fields ) ) {

	foreach ( $fields as $name => $field ) {

		if ( !empty( $user[ $name ] ) ) {

			echo $field[ 'label' ] . ": " . $user[ $name ] . "\n";

		}

	}

}

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );