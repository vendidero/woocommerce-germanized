<?php
/**
 * Email Shipment details
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/email-shipment-details.php.
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

$text_align = is_rtl() ? 'right' : 'left';

/*
 * Action that fires before outputting a Shipment's table in an Email.
 *
 * @param \Vendidero\Germanized\Shipments\Shipment $shipment The shipment instance.
 * @param boolean                                  $sent_to_admin Whether to send this email to admin or not.
 * @param boolean                                  $plain_text Whether this email is in plaintext format or not.
 * @param WC_Email                                 $email The email instance.
 *
 * @since 3.0.0
 * @package Vendidero/Germanized/Shipments
 */
do_action( 'woocommerce_gzd_email_before_shipment_table', $shipment, $sent_to_admin, $plain_text, $email ); ?>

<h2>
	<?php
	if ( $sent_to_admin ) {
		$before = '<a class="link" href="' . esc_url( $shipment->get_edit_shipment_url() ) . '">';
		$after  = '</a>';
	} else {
		$before = '';
		$after  = '';
	}
	/* translators: %s: Order ID. */
	echo wp_kses_post( $before . ( ! $sent_to_admin ? sprintf( _x( 'Details to your %s', 'shipments', 'woocommerce-germanized' ), wc_gzd_get_shipment_label_title( $shipment->get_type() ) ) : sprintf( _x( '[%1$s #%2$s]', 'shipments', 'woocommerce-germanized' ), wc_gzd_get_shipment_label_title( $shipment->get_type() ), $shipment->get_shipment_number() ) ) . $after );
	?>
</h2>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
		<tr>
			<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo esc_html_x( 'Product', 'shipments', 'woocommerce-germanized' ); ?></th>
			<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo esc_html_x( 'Quantity', 'shipments', 'woocommerce-germanized' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		echo wc_gzd_get_email_shipment_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$shipment,
			array(
				'show_sku'      => $sent_to_admin,
				'show_image'    => false,
				'image_size'    => array( 32, 32 ),
				'plain_text'    => $plain_text,
				'sent_to_admin' => $sent_to_admin,
			)
		);
		?>
		</tbody>
	</table>
</div>

<?php
/*
 * Action that fires after outputting a Shipment's table in an Email.
 *
 * @param \Vendidero\Germanized\Shipments\Shipment $shipment The shipment instance.
 * @param boolean                                  $sent_to_admin Whether to send this email to admin or not.
 * @param boolean                                  $plain_text Whether this email is in plaintext format or not.
 * @param WC_Email                                 $email The email instance.
 *
 * @since 3.0.0
 * @package Vendidero/Germanized/Shipments
 */
do_action( 'woocommerce_gzd_email_after_shipment_table', $shipment, $sent_to_admin, $plain_text, $email ); ?>
