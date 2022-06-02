<?php
/**
 * Customer SEPA direct debit mandate.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/customer-sepa-direct-debit-mandate.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( esc_html__( 'Please see the SEPA direct debit mandate for order %s attached to this email.', 'woocommerce-germanized' ), esc_html( $order->get_order_number() ) ); ?></p>
<?php echo wp_kses_post( wptexturize( $gateway->generate_mandate_by_order( $order ) ) ); ?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
