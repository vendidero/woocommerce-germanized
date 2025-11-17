<?php
/**
 * Email Shipment Address
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/emails/email-shipment-address.php.
 *
 * HOWEVER, on occasion Shiptastic will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Shiptastic/Templates/Emails
 * @version 4.3.11
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text_align = is_rtl() ? 'right' : 'left';
?>

<table id="addresses" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top; margin-bottom: 40px; padding:0;" border="0">
	<tr>
		<td style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border:0; padding:0;" valign="top" width="50%">
			<h2><?php echo ( 'return' === $shipment->get_type() ? esc_html_x( 'Return goes to:', 'shipments', 'woocommerce-germanized' ) : esc_html_x( 'Shipment goes to:', 'shipments', 'woocommerce-germanized' ) ); ?></h2>

			<address class="address">
				<?php echo wp_kses_post( $shipment->get_formatted_address() ); ?>
			</address>
		</td>
	</tr>
</table>
