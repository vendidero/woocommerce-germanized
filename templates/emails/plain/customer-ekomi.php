<?php
/**
 * Customer eKomi review notification (plain)
 *
 * @author Vendidero
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$base 		= get_option( 'woocommerce_email_base_color' );
$base_text 	= wc_light_or_dark( $base, '#202020', '#ffffff' );
$text 		= get_option( 'woocommerce_email_text_color' );

echo "= " . $email_heading . " =\n\n";

echo sprintf( _x( 'Dear %s %s,', 'ekomi', 'woocommerce-germanized' ), $order->billing_first_name, $order->billing_last_name ) . "\n";

echo sprintf( _x( 'You have recently shopped at %s. Thank you! We would be glad if you spent some time to write a review about your order. To do so please follow follow the link.', 'ekomi', 'woocommerce-germanized' ), get_bloginfo( 'name' ) ) . "\n\n";

echo esc_url( $order->ekomi_review_link ) . "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );