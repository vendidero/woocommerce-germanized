<?php
/**
 * Admin delivery threshold notification.
 *
 * @version 1.0.0
 *
 * @var \Vendidero\OneStopShop\Report $report
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( esc_html_x( 'Your OSS delivery threshold of %1$s has been reached. Please take action immediately. Visit the OSS Settings Panel (%2$s) for details.', 'oss', 'woocommerce-germanized' ), wp_strip_all_tags( wc_price( \Vendidero\OneStopShop\Package::get_delivery_notification_threshold() ) ), esc_url( \Vendidero\OneStopShop\Settings::get_settings_url() ) );

echo "\n\n";

echo $report->get_url();

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo "\n----------------------------------------\n\n";

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
