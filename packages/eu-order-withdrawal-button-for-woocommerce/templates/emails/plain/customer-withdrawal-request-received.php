<?php
/**
 * Customer withdrawal request received email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-withdrawal-request-received.php.
 *
 * HOWEVER, on occasion EU OWB will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/OrderWithdrawalButton/Templates
 * @version 2.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = \Vendidero\OrderWithdrawalButton\Package::has_email_improvements_enabled();
$withdrawal_date            = eu_owb_get_order_withdrawal_date_received( $order, $withdrawal );
$withdrawal_name            = eu_owb_get_order_withdrawal_full_name( $order, $withdrawal );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( ! empty( $withdrawal_name ) ) {
	/* translators: %s: Customer full name */
	echo sprintf( esc_html_x( 'Hi %s,', 'owb', 'woocommerce-germanized' ), esc_html( $withdrawal_name ) ) . "\n\n";
} else {
	echo esc_html_x( 'Hi,', 'owb', 'woocommerce-germanized' ) . "\n\n";
}

echo sprintf( esc_html_x( 'We’ve received your withdrawal request for order #%1$s on %2$s at %3$s and it is now being processed.', 'owb', 'woocommerce-germanized' ), esc_html( $order->get_order_number() ), esc_html( wc_format_datetime( $withdrawal_date ) ), esc_html( wc_format_datetime( $withdrawal_date, wc_time_format() ) ) ) . "\n\n";

do_action( 'eu_owb_woocommerce_withdrawal_request_details', $order, $sent_to_admin, $plain_text, $email, $withdrawal );

do_action( 'eu_owb_woocommerce_withdrawal_request_meta', $order, $sent_to_admin, $plain_text, $email, $withdrawal );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
