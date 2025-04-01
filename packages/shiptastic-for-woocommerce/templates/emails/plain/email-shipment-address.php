<?php
/**
 * Email Shipment Address (plain text)
 *
 * This template can be overridden by copying it to yourtheme/stiptastic/emails/plain/email-shipment-address.php.
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
defined( 'ABSPATH' ) || exit;

echo "\n" . esc_html_x( 'Shipment goes to:', 'shipments', 'woocommerce-germanized' ) . "\n\n";
echo preg_replace( '#<br\s*/?>#i', "\n", $shipment->get_formatted_address() ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
