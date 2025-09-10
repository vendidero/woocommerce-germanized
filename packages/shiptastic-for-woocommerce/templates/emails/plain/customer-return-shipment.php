<?php
/**
 * Customer return shipment (plain text)
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/emails/plain/customer-return-shipment.php.
 *
 * HOWEVER, on occasion Shiptastic will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Shiptastic/Templates/Emails/Plain
 * @version 4.3.0
 */
defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html_x( 'Hi %s,', 'shipments', 'woocommerce-germanized' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";

if ( $is_confirmation ) {
	echo esc_html_x( 'Your return request has been accepted. Please follow the instructions beneath to return your shipment.', 'shipments', 'woocommerce-germanized' );
} else {
	echo esc_html_x( 'A new return has been added to your order. Please follow the instructions beneath to return your shipment.', 'shipments', 'woocommerce-germanized' );
}

echo "\n\n";

/* This hook is documented in templates/emails/customer-shipment.php */
do_action( 'woocommerce_shiptastic_email_shipment_details', $shipment, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo "\n----------------------------------------\n\n";

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
