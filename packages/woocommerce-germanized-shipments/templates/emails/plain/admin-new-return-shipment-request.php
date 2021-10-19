<?php
/**
 * Admin new return shipment request (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/plain/admin-new-return-shipment-request.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Shipments/Templates/Emails/Plain
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer billing full name */
echo sprintf( esc_html_x( 'You’ve received the following return request from %s:', 'shipments', 'woocommerce-germanized' ), esc_html( $shipment->get_formatted_sender_full_name() ) ) . "\n\n";

echo "\n\n";

/* This hook is documented in templates/emails/customer-shipment.php */
do_action( 'woocommerce_gzd_email_shipment_details', $shipment, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo "\n----------------------------------------\n\n";

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
