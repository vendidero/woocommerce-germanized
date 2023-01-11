<?php
/**
 * Customer Shipment
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/customer-shipment.php.
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
		<?php
		if ( $partial_shipment ) {
			/* translators: %s: Site title */
			printf( esc_html_x( 'Your order on %1$s has been partially shipped via %2$s. Find details below for your reference:', 'shipments', 'woocommerce-germanized' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), esc_html( wc_gzd_get_shipment_shipping_provider_title( $shipment ) ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		} else {
			/* translators: %s: Site title */
			printf( esc_html_x( 'Your order on %1$s has been shipped via %2$s. Find details below for your reference:', 'shipments', 'woocommerce-germanized' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), esc_html( wc_gzd_get_shipment_shipping_provider_title( $shipment ) ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		}
		?>
	</p>
<?php

/*
 * Output Email details for a Shipment.
 *
 * @hooked \Vendidero\Germanized\Shipments\Email::email_tracking() Adds tracking info.
 * @hooked \Vendidero\Germanized\Shipments\Email::email_address() Adds shipping address.
 * @hooked \Vendidero\Germanized\Shipments\Email::email_details() Adds shipment table.
 *
 * @param \Vendidero\Germanized\Shipments\Shipment $shipment The shipment instance.
 * @param boolean                                  $sent_to_admin Whether to send this email to admin or not.
 * @param boolean                                  $plain_text Whether this email is in plaintext format or not.
 * @param WC_Email                                 $email The email instance.
 *
 * @since 3.0.0
 * @package Vendidero/Germanized/Shipments
 */
do_action( 'woocommerce_gzd_email_shipment_details', $shipment, $sent_to_admin, $plain_text, $email );

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
