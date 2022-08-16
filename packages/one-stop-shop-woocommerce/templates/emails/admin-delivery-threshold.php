<?php
/**
 * Admin delivery threshold notification.
 *
 * @version 1.0.1
 *
 * @var \Vendidero\OneStopShop\Report $report
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer billing full name */ ?>
	<p><?php echo wp_kses_post( sprintf( _x( 'Your OSS delivery threshold of %1$s has been reached. Please take action immediately. Visit the <a href="%2$s">OSS Settings Panel</a> for details.', 'oss', 'woocommerce-germanized' ), wc_price( \Vendidero\OneStopShop\Package::get_delivery_notification_threshold() ), esc_url( \Vendidero\OneStopShop\Settings::get_settings_url() ) ) ); ?></p>

	<h2><?php echo esc_html_x( 'Report Details', 'oss', 'woocommerce-germanized' ); ?></h2>

	<ul>
		<li><?php echo esc_html_x( 'Period', 'oss', 'woocommerce-germanized' ); ?>: <?php echo esc_html( $report->get_date_start()->format( wc_date_format() ) ); ?> - <?php echo esc_html( $report->get_date_end()->format( wc_date_format() ) ); ?></li>
		<li><?php echo esc_html_x( 'Net total', 'oss', 'woocommerce-germanized' ); ?>: <?php echo wc_price( $report->get_net_total() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
		<li><?php echo esc_html_x( 'Tax total', 'oss', 'woocommerce-germanized' ); ?>: <?php echo wc_price( $report->get_tax_total() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
	</ul>

	<a class="button button-primary" href="<?php echo esc_url( $report->get_url() ); ?>"><?php echo esc_html_x( 'See report details', 'oss', 'woocommerce-germanized' ); ?></a>
<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
