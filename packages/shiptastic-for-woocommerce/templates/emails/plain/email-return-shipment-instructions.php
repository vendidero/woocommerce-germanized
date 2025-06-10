<?php
/**
 * Email return shipment instructions (plain text)
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/emails/plain/email-return-shipment-instructions.php.
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

$provider = $shipment->get_shipping_provider_instance();

if ( $provider && $provider->has_return_instructions() ) {
	echo wp_kses_post( wpautop( wptexturize( $provider->get_return_instructions() ) ) . PHP_EOL );
}
