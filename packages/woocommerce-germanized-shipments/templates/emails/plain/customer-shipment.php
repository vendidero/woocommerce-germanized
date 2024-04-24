<?php
/**
 * Customer Shipment (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/plain/customer-shipment.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Shipments/Templates/Emails/Plain
 * @version 1.0.1
 */
defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n"; // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

if ( $partial_shipment ) {
	/* translators: %s: Site title */
	printf( esc_html_x( 'Your order on %1$s has been partially shipped via %2$s. Find details below for your reference:', 'shipments', 'woocommerce-germanized' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), wc_gzd_get_shipment_shipping_provider_title( $shipment ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
} else {
	/* translators: %s: Site title */
	printf( esc_html_x( 'Your order on %1$s has been shipped via %2$s. Find details below for your reference:', 'shipments', 'woocommerce-germanized' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), wc_gzd_get_shipment_shipping_provider_title( $shipment ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
}

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
