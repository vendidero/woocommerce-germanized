<?php
/**
 * Email Shipment details (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/plain/email-shipment-details.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Shipments/Templates/Emails/Plain
 * @version 1.0.1
 */
defined( 'ABSPATH' ) || exit;

/* This hook is documented in templates/emails/customer-shipment.php */
do_action( 'woocommerce_gzd_email_before_shipment_table', $shipment, $sent_to_admin, $plain_text, $email );

/* translators: %1$s: Order ID. %2$s: Order date */
echo wp_kses_post( wc_strtoupper( ( ! $sent_to_admin ? sprintf( _x( 'Details to your %s', 'shipments', 'woocommerce-germanized' ), wc_gzd_get_shipment_label_title( $shipment->get_type() ) ) : sprintf( _x( '[%1$s #%2$s]', 'shipments', 'woocommerce-germanized' ), wc_gzd_get_shipment_label_title( $shipment->get_type() ), $shipment->get_shipment_number() ) ) ) ) . "\n";
echo "\n" . wc_gzd_get_email_shipment_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$shipment,
	array(
		'show_sku'      => $sent_to_admin,
		'show_image'    => false,
		'image_size'    => array( 32, 32 ),
		'plain_text'    => true,
		'sent_to_admin' => $sent_to_admin,
	)
);

echo "==========\n\n";

if ( $sent_to_admin ) {
	/* translators: %s: Shipment link. */
	echo "\n" . sprintf( esc_html_x( 'View shipment: %s', 'shipments', 'woocommerce-germanized' ), esc_url( $shipment->get_edit_shipment_url() ) ) . "\n";
}

/* This hook is documented in templates/emails/customer-shipment.php */
do_action( 'woocommerce_gzd_email_after_shipment_table', $shipment, $sent_to_admin, $plain_text, $email );
