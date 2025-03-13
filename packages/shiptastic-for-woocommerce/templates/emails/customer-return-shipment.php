<?php
/**
 * Customer return shipment
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/emails/customer-return-shipment.php.
 *
 * HOWEVER, on occasion Shiptastic will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Shiptastic/Templates/Emails
 * @version 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
	<p><?php printf( esc_html_x( 'Hi %s,', 'shipments', 'woocommerce-germanized' ), esc_html( $order->get_billing_first_name() ) ); ?></p>

	<p>
		<?php
		if ( $is_confirmation ) {
			echo esc_html_x( 'Your return request has been accepted. Please follow the instructions beneath to return your shipment.', 'shipments', 'woocommerce-germanized' );
		} else {
			echo esc_html_x( 'A new return has been added to your order. Please follow the instructions beneath to return your shipment.', 'shipments', 'woocommerce-germanized' );
		}
		?>
	</p>
<?php

/*
 * Output Email details for a Shipment.
 *
 * @hooked \Vendidero\Shiptastic\Email::email_tracking() Adds tracking info.
 * @hooked \Vendidero\Shiptastic\Email::email_address() Adds shipping address.
 * @hooked \Vendidero\Shiptastic\Email::email_details() Adds shipment table.
 *
 * @param \Vendidero\Shiptastic\Shipment $shipment The shipment instance.
 * @param boolean                                  $sent_to_admin Whether to send this email to admin or not.
 * @param boolean                                  $plain_text Whether this email is in plaintext format or not.
 * @param WC_Email                                 $email The email instance.
 *
 * @package Vendidero/Shiptastic
 */
do_action( 'woocommerce_shiptastic_email_shipment_details', $shipment, $sent_to_admin, $plain_text, $email );

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
