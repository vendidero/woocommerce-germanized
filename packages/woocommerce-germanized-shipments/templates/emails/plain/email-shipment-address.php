<?php
/**
 * Email Shipment Address (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/plain/email-shipment-address.php.
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

echo "\n" . esc_html_x( 'Shipment goes to:', 'shipments', 'woocommerce-germanized' ) . "\n\n";
echo preg_replace( '#<br\s*/?>#i', "\n", $shipment->get_formatted_address() ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
