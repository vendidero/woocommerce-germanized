<?php
/**
 * Customer DHL return shipment label email (plain-text).
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/plain/customer-dhl-return-shipment-label.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/DHL/Templates
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";

printf( esc_html_x( 'You\'ve requested a return for your order #%s. Please find the DHL label attached to this email.', 'dhl', 'woocommerce-germanized' ), $order->get_order_number() ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

echo "\n\n";

/* This filter is documented in templates/emails/customer-dhl-return-shipment-label.php */
do_action( 'woocommerce_gzd_email_return_shipment_details', $shipment, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
