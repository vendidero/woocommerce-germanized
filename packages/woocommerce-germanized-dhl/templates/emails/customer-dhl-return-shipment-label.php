<?php
/**
 * Customer DHL return shipment label email.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/customer-dhl-return-shipment-label.php.
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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
	<p><?php printf( __( 'Hi %s,', 'woocommerce' ), $order->get_billing_first_name() ); ?></p><?php // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>

	<p>
		<?php
			printf( _x( 'You\'ve requested a return for your order #%s. Please find the DHL label attached to this email.', 'dhl', 'woocommerce-germanized' ), $order->get_order_number() ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		?>
	</p>

	<p>
		<?php
			printf( _x( 'Please print the DHL label attached to this email and stick it on your parcel.', 'dhl', 'woocommerce-germanized' ), '' ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		?>
	</p>
<?php

/*
 * Output Email details for a Return Shipment.
 *
 * @hooked \Vendidero\Germanized\Shipments\Email::email_details() Adds shipment table.
 *
 * @param \Vendidero\Germanized\Shipments\Shipment $shipment The shipment instance.
 * @param boolean                                  $sent_to_admin Whether to send this email to admin or not.
 * @param boolean                                  $plain_text Whether this email is in plaintext format or not.
 * @param WC_Email                                 $email The email instance.
 *
 * @since 3.0.0
 * @package Vendidero/Germanized/DHL
 */
do_action( 'woocommerce_gzd_email_return_shipment_details', $shipment, $sent_to_admin, $plain_text, $email );

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
