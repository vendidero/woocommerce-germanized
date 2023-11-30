<?php
/**
 * Email shipment tracking (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/plain/email-order-shipments.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Shipments/Templates/Emails/Plain
 * @version 1.0.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$count = 0;

foreach ( $shipments as $key => $shipment ) {
	$count++;

	echo "\n";

	if ( count( $shipments ) > 1 ) {
		echo sprintf( esc_html_x( 'Shipment %1$d of %2$d', 'shipments', 'woocommerce-germanized' ), esc_html( $count ), esc_html( count( $shipments ) ) ) . "\n\n";
	}

	if ( $shipment->get_est_delivery_date() ) {
		echo esc_html( _x( 'Estimated date:', 'shipments', 'woocommerce-germanized' ) ) . ' ' . esc_html( wc_format_datetime( $shipment->get_est_delivery_date(), wc_date_format() ) ) . "\n\n";
	}

	if ( $shipment->has_tracking() ) {
		if ( $shipment->get_tracking_url() ) {
			echo esc_html( _x( 'Track your shipment', 'shipments', 'woocommerce-germanized' ) ) . ': ' . esc_url( $shipment->get_tracking_url() ) . "\n";
		}

		if ( $shipment->has_tracking_instruction() ) {
			echo esc_html( $shipment->get_tracking_instruction( true ) ) . "\n";
		}
	} else {
		echo esc_html( _x( 'Sorry, this shipment does currently not support tracking.', 'shipments', 'woocommerce-germanized' ) ) . "\n";
	}
}
