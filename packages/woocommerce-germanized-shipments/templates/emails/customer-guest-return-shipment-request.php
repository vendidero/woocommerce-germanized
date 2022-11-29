<?php
/**
 * Customer guest return shipment request.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/customer-guest-return-shipment-request.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Shipments/Templates/Emails
 * @version 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
	<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?></p>

	<p>
		<?php printf( esc_html_x( 'You\'ve requested a return to your order %s. Please follow the link to add your return request.', 'shipments', 'woocommerce-germanized' ), esc_html( $order->get_order_number() ) ); ?>
	</p>

	<p>
		<a class="wc-button button" href="<?php echo esc_url( $add_return_request_url ); ?>"><?php echo esc_html_x( 'Add return request', 'shipments', 'woocommerce-germanized' ); ?></a>
	</p>

	<p><?php printf( esc_html_x( 'If you cannot follow the link above please copy this url and paste it to your browser bar: %s', 'shipments', 'woocommerce-germanized' ), esc_url( $add_return_request_url ) ); ?></p>
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
