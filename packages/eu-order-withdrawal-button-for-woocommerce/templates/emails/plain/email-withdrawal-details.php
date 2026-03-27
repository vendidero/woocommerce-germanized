<?php
/**
 * E-Mail withdrawal details
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-withdrawal-details.php.
 *
 * HOWEVER, on occasion EU OWB will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/OrderWithdrawalButton/Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$text_align                 = is_rtl() ? 'right' : 'left';
$email_improvements_enabled = \Vendidero\OrderWithdrawalButton\Package::has_email_improvements_enabled();

do_action( 'eu_owb_woocommerce_withdrawal_before_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php
	echo wp_kses_post( _x( 'Withdrawal summary', 'owb', 'woocommerce-germanized' ) );
	echo "\n==========\n";
?>

<?php echo wp_kses_post( _x( 'Order', 'owb', 'woocommerce-germanized' ) ); ?>: <?php echo wp_kses_post( $order->get_order_number() ) . "\n"; ?>
<?php echo wp_kses_post( _x( 'Received on', 'owb', 'woocommerce-germanized' ) ); ?>: <?php echo esc_html( sprintf( _x( '%1$s at %2$s', 'owb-datetime', 'woocommerce-germanized' ), wc_format_datetime( eu_owb_get_order_withdrawal_date_received( $order ) ), wc_format_datetime( eu_owb_get_order_withdrawal_date_received( $order ), wc_time_format() ) ) ) . "\n"; ?>
<?php echo wp_kses_post( _x( 'E-Mail', 'owb', 'woocommerce-germanized' ) ); ?>: <?php echo wp_kses_post( eu_owb_get_order_withdrawal_email( $order ) ) . "\n"; ?>

<?php
echo "\n" . eu_owb_get_email_withdrawal_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$order,
	array(
		'show_sku'      => $sent_to_admin,
		'show_image'    => false,
		'image_size'    => array( 32, 32 ),
		'plain_text'    => true,
		'sent_to_admin' => $sent_to_admin,
	)
);

echo "==========\n\n";

if ( $sent_to_admin ) {
	/* translators: %s: Order link. */
	echo "\n" . sprintf( esc_html_x( 'View order: %s', 'owb', 'woocommerce-germanized' ), esc_url( $order->get_edit_order_url() ) ) . "\n";
}
?>

<?php
/**
 * Action hook to add custom content after order details in email.
 *
 * @param WC_Order $order Order object.
 * @param bool     $sent_to_admin Whether it's sent to admin or customer.
 * @param bool     $plain_text Whether it's a plain text email.
 * @param WC_Email $email Email object.
 * @since 2.5.0
 */
do_action( 'eu_owb_woocommerce_withdrawal_after_order_table', $order, $sent_to_admin, $plain_text, $email );
?>
