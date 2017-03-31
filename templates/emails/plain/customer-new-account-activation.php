<?php
/**
 * Customer new account activation (plain text)
 *
 * @author		WooThemes
 * @package 	WooCommerceGermanized/Templates/Emails/Plain
 * @version 	2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo "= " . $email_heading . " =\n\n";

echo sprintf( __( "Thanks for creating an account on %s. Please follow the activation link to activate your account:", 'woocommerce-germanized' ), esc_html( $blogname ) ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_attr( $user_activation_url ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( get_option( 'woocommerce_registration_generate_password' ) == 'yes' && $password_generated ) {

	echo sprintf( __( "Your password has been automatically generated: <strong>%s</strong>", 'woocommerce-germanized' ), esc_html( $user_pass ) );

}

echo sprintf( __( "If you haven't created an account on %s please ignore this email.", "woocommerce-germanized" ), esc_html( $blogname ) ) . "\n\n";;

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );