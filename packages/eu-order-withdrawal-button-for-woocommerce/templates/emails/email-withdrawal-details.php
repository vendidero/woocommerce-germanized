<?php
/**
 * E-Mail withdrawal details
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-withdrawal-details.php.
 *
 * HOWEVER, on occasion EU OWB will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/OrderWithdrawalButton/Templates
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

$text_align = is_rtl() ? 'right' : 'left';

$email_improvements_enabled = \Vendidero\OrderWithdrawalButton\Package::has_email_improvements_enabled();
$heading_class              = $email_improvements_enabled ? 'email-order-detail-heading' : '';
$order_table_class          = $email_improvements_enabled ? 'email-order-details email-withdrawal-details' : '';
$order_total_text_align     = $email_improvements_enabled ? 'right' : 'left';
$order_quantity_text_align  = $email_improvements_enabled ? 'right' : 'left';
$verified_notice            = eu_owb_order_withdrawal_email_is_verified( $order, $withdrawal ) ? esc_html_x( 'verified', 'owb', 'woocommerce-germanized' ) : esc_html_x( 'unknown', 'owb', 'woocommerce-germanized' );

if ( $sent_to_admin ) {
	$formatted_order_number = '<a class="link" href="' . esc_url( $order->get_edit_order_url() ) . '">' . esc_html( $order->get_order_number() ) . '</a>';
} else {
	$formatted_order_number = esc_html( $order->get_order_number() );
}

do_action( 'eu_owb_woocommerce_withdrawal_before_order_table', $order, $sent_to_admin, $plain_text, $email, $withdrawal ); ?>

<h2 class="<?php echo esc_attr( $heading_class ); ?>">
	<?php echo wp_kses_post( _x( 'Withdrawal summary', 'owb', 'woocommerce-germanized' ) ); ?>
</h2>

<ul style="margin-bottom: <?php echo $email_improvements_enabled ? '24px' : '40px'; ?>;">
	<li><strong><?php echo wp_kses_post( _x( 'Order', 'owb', 'woocommerce-germanized' ) ); ?>:</strong> <span class="text"><?php echo wp_kses_post( $formatted_order_number ); ?></span></li>
	<li><strong><?php echo wp_kses_post( _x( 'Received on', 'owb', 'woocommerce-germanized' ) ); ?>:</strong> <span class="text"><?php echo esc_html( sprintf( _x( '%1$s at %2$s', 'owb-datetime', 'woocommerce-germanized' ), wc_format_datetime( eu_owb_get_order_withdrawal_date_received( $order, $withdrawal ) ), wc_format_datetime( eu_owb_get_order_withdrawal_date_received( $order, $withdrawal ), wc_time_format() ) ) ); ?></span></li>
	<li><strong><?php echo wp_kses_post( _x( 'E-Mail', 'owb', 'woocommerce-germanized' ) ); ?>:</strong> <span class="text"><?php echo wp_kses_post( eu_owb_get_order_withdrawal_email( $order, $withdrawal ) ) . ( $sent_to_admin ? ' (' . esc_html( $verified_notice ) . ')' : '' ); ?></span></li>
	<li><strong><?php echo wp_kses_post( _x( 'Full name', 'owb', 'woocommerce-germanized' ) ); ?>:</strong> <span class="text"><?php echo wp_kses_post( eu_owb_get_order_withdrawal_full_name( $order, $withdrawal, true ) ); ?></span></li>
</ul>

<?php if ( $show_deleted_original && ( $original_order_id = eu_owb_order_withdrawal_request_get_original_order_id( $withdrawal ) ) ) : ?>
	<p><?php echo wp_kses_post( sprintf( _x( 'As you requested, we have deleted your original withdrawal request for order %1$s.', 'owb', 'woocommerce-germanized' ), esc_html( $original_order_id ) ) ); ?></p>
<?php endif; ?>

<?php if ( ! $hide_items ) : ?>
	<div style="margin-bottom: <?php echo $email_improvements_enabled ? '24px' : '40px'; ?>;">
		<table class="td font-family <?php echo esc_attr( $order_table_class ); ?>" cellspacing="0" cellpadding="6" style="width: 100%;" border="1">
			<thead>
				<tr>
					<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo esc_html_x( 'Product', 'owb', 'woocommerce-germanized' ); ?></th>
					<th class="td" scope="col" style="text-align:<?php echo esc_attr( $order_quantity_text_align ); ?>;"><?php echo esc_html_x( 'Quantity', 'owb', 'woocommerce-germanized' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$image_size = $email_improvements_enabled ? 48 : 32;
				echo eu_owb_get_email_withdrawal_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$order,
					array(
						'show_sku'      => $sent_to_admin,
						'show_image'    => $email_improvements_enabled,
						'image_size'    => array( $image_size, $image_size ),
						'plain_text'    => $plain_text,
						'sent_to_admin' => $sent_to_admin,
						'withdrawal'    => $withdrawal,
					)
				);
				?>
			</tbody>
		</table>
	</div>
<?php endif; ?>

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
do_action( 'eu_owb_woocommerce_withdrawal_after_order_table', $order, $sent_to_admin, $plain_text, $email, $withdrawal );
?>
