<?php
/**
 * Customer SEPA direct debit mandate
 *
 * @author 		Vendidero
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( __( "Please see the SEPA direct debit mandate for order %s attached to this email.", 'woocommerce-germanized' ), $order->get_order_number() ); ?></p>

<?php echo $gateway->generate_mandate_by_order( $order ); ?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>