<?php
/**
 * Customer new account activation email (plain-text).
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/plain/customer-new-account-activation.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 2.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( __( "Thanks for creating an account on %s. Please follow the activation link to activate your account:", 'woocommerce-germanized' ), esc_html( $blogname ) ) . "\n\n";

echo "\n----------------------------------------\n\n";

echo esc_url( $user_activation_url ) . "\n\n";

echo "\n----------------------------------------\n\n";

if ( get_option( 'woocommerce_registration_generate_password' ) == 'yes' && $password_generated ) {

	echo sprintf( __( "Your password has been automatically generated: <strong>%s</strong>", 'woocommerce-germanized' ), esc_html( $user_pass ) );

}

echo sprintf( __( "If you haven't created an account on %s please ignore this email.", "woocommerce-germanized" ), esc_html( $blogname ) ) . "\n\n";;

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );