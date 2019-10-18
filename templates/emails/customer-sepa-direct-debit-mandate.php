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

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>