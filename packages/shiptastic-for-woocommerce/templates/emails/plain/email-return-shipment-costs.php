<?php
/**
 * Email return shipment costs (plain text)
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/emails/plain/email-return-shipment-costs.php.
 *
 * HOWEVER, on occasion Shiptastic will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Shiptastic/Templates/Emails/Plain
 * @version 4.7.1
 */
defined( 'ABSPATH' ) || exit;

if ( $shipment->has_return_costs() ) {
	echo wp_kses_post( sprintf( _x( 'The return shipping costs of %s will be automatically deducted from your refund amount.', 'shipments', 'woocommerce-germanized' ), wc_price( $shipment->get_return_costs() ) ) . PHP_EOL );
}
