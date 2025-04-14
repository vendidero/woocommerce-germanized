<?php
/**
 * Email Shipment items (plain text)
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/emails/plain/email-shipment-items.php.
 *
 * HOWEVER, on occasion Shiptastic will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Shiptastic/Templates/Emails/Plain
 * @version 4.3.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

foreach ( $items as $item_id => $item ) :
	$product       = $item->get_product();
	$sku           = $item->get_sku();
	$purchase_note = '';

	/* This filter is documented in templates/emails/email-shipment-items.php */
	if ( ! apply_filters( 'woocommerce_shiptastic_shipment_item_visible', true, $item ) ) {
		continue;
	}

	/* This filter is documented in templates/emails/email-shipment-items.php */
	echo wp_kses_post( apply_filters( 'woocommerce_shiptastic_shipment_item_name', $item->get_name(), $item, false ) );

	if ( $show_sku && $sku ) {
		echo ' (#' . esc_html( $sku ) . ')';
	}

	/* This filter is documented in templates/emails/email-shipment-items.php */
	echo ' X ' . wp_kses_post( apply_filters( 'woocommerce_shiptastic_email_shipment_item_quantity', $item->get_quantity(), $item ) );
	echo "\n";

	/* This hook is documented in templates/emails/email-shipment-items.php */
	do_action( 'woocommerce_shiptastic_shipment_item_meta', $item_id, $item, $shipment, $plain_text );

	echo "\n\n";
endforeach;
