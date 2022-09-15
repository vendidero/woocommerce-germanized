<?php
/**
 * Customer Trusted Shops Review Notification
 *
 * @author Vendidero
 * @version 1.0.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( esc_html_x( 'Dear %1$s %2$s,', 'trusted-shops', 'woocommerce-germanized' ), esc_html( wc_ts_get_crud_data( $order, 'billing_first_name' ) ), esc_html( wc_ts_get_crud_data( $order, 'billing_last_name' ) ) ) . "\n\n";

echo sprintf( esc_html_x( 'You have recently shopped at %s. Thank you! We would be glad if you spent some time to write a review about your order. To do so please follow follow the link.', 'trusted-shops', 'woocommerce-germanized' ), esc_html( get_bloginfo( 'name' ) ) ) . "\n\n";

echo "\n----------------------------------------\n\n";

echo esc_url( WC_trusted_shops()->trusted_shops->get_new_review_link( wc_ts_get_crud_data( $order, 'billing_email' ), $order->get_order_number() ) ) . "\n\n";

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
