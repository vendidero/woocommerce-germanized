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
 * @version 2.0.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( esc_html__( 'Thanks for creating an account on %s. Please follow the activation link to activate your account:', 'woocommerce-germanized' ), esc_html( $blogname ) ) . "\n\n";

echo "\n----------------------------------------\n\n";

echo esc_url( $user_activation_url ) . "\n\n";

echo "\n----------------------------------------\n\n";

// Only send the set new password link if the user hasn't set their password during sign-up.
if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) && $password_generated && $set_password_url ) {
	/* translators: URL follows */
	echo esc_html__( 'To set your password, visit the following address: ', 'woocommerce-germanized' ) . "\n\n";
	echo esc_html( $set_password_url ) . "\n\n";
}

echo sprintf( esc_html__( "If you haven't created an account on %s please ignore this email.", 'woocommerce-germanized' ), esc_html( $blogname ) ) . "\n\n";


echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
