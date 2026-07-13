<?php
/**
 * Email withdrawal details
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
 * @version 2.3.2
 */

defined( 'ABSPATH' ) || exit;

$text_align = is_rtl() ? 'right' : 'left';

$email_improvements_enabled = \Vendidero\OrderWithdrawalButton\Package::has_email_improvements_enabled();
$display_section_divider    = (bool) apply_filters( 'woocommerce_email_body_display_section_divider', true );
$heading_class              = $email_improvements_enabled ? 'email-order-detail-heading' : '';
$order_table_class          = $email_improvements_enabled ? 'email-order-details email-withdrawal-details' : '';
$order_total_text_align     = $email_improvements_enabled ? 'right' : 'left';
$order_quantity_text_align  = $email_improvements_enabled ? 'right' : 'left';
$verified_notice            = $withdrawal->has_verified_email() ? esc_html_x( 'verified', 'owb', 'woocommerce-germanized' ) : esc_html_x( 'unknown', 'owb', 'woocommerce-germanized' );

if ( $sent_to_admin ) {
	$formatted_contract_identification = '<a class="link" href="' . esc_url( $withdrawal->get_edit_order_url() ) . '">' . esc_html( $withdrawal->get_contract_identification() ) . '</a>';
} else {
	$formatted_contract_identification = esc_html( $withdrawal->get_contract_identification() );
}

do_action( 'eu_owb_woocommerce_withdrawal_before_order_table', $order, $sent_to_admin, $plain_text, $email, $withdrawal ); ?>

<h2 class="<?php echo esc_attr( $heading_class ); ?>">
	<?php echo wp_kses_post( _x( 'Withdrawal summary', 'owb', 'woocommerce-germanized' ) ); ?>
</h2>

<ul style="margin-bottom: <?php echo $email_improvements_enabled ? '24px' : '40px'; ?>;">
	<li><strong><?php echo wp_kses_post( _x( 'Contract Identification', 'owb', 'woocommerce-germanized' ) ); ?>:</strong> <span class="text"><?php echo wp_kses_post( $formatted_contract_identification ); ?></span></li>
	<li><strong><?php echo wp_kses_post( _x( 'Received on', 'owb', 'woocommerce-germanized' ) ); ?>:</strong> <span class="text"><?php echo esc_html( sprintf( _x( '%1$s at %2$s', 'owb-datetime', 'woocommerce-germanized' ), wc_format_datetime( $withdrawal->get_date_received() ), wc_format_datetime( $withdrawal->get_date_received(), wc_time_format() ) ) ); ?></span></li>
	<li><strong><?php echo wp_kses_post( _x( 'Email', 'owb', 'woocommerce-germanized' ) ); ?>:</strong> <span class="text"><?php echo wp_kses_post( $withdrawal->get_email() ) . ( $sent_to_admin ? ' (' . esc_html( $verified_notice ) . ')' : '' ); ?></span></li>
	<li><strong><?php echo wp_kses_post( _x( 'Full name', 'owb', 'woocommerce-germanized' ) ); ?>:</strong> <span class="text"><?php echo wp_kses_post( $withdrawal->get_formatted_full_name( true, 'email' ) ); ?></span></li>
	<?php if ( apply_filters( 'eu_order_woocommerce_withdrawal_email_show_verification_code', true, $withdrawal, $email, $sent_to_admin ) ) : ?>
		<li><strong><?php echo wp_kses_post( _x( 'Verification code', 'owb', 'woocommerce-germanized' ) ); ?>:</strong> <span class="text"><?php echo wp_kses_post( $withdrawal->get_verification_code() ); ?></span></li>
	<?php endif; ?>
</ul>

<?php if ( $withdrawal->get_additional_information() ) : ?>
	<?php if ( $display_section_divider ) : ?>
		<hr style="border: 0; border-top: 1px solid #1E1E1E; border-top-color: rgba(30, 30, 30, 0.2); margin: 20px 0;">
	<?php endif; ?>
	<table class="td font-family <?php echo esc_attr( $order_table_class ); ?>" cellspacing="0" cellpadding="6" style="width: 100%;" border="1" role="presentation">
		<tr class="order-customer-note">
			<td class="td text-align-left">
				<strong><?php echo esc_html_x( 'Additional Information', 'owb', 'woocommerce-germanized' ); ?></strong><br>
				<?php echo wp_kses( nl2br( eu_owb_wptexturize_withdrawal_additional_information( $withdrawal->get_additional_information() ) ), array( 'br' => array() ) ); ?>
			</td>
		</tr>
	</table>
<?php endif; ?>

<?php if ( $show_deleted_original && ( $original_order_id = eu_owb_order_withdrawal_request_get_original_order_id( $withdrawal ) ) ) : ?>
	<p><?php echo wp_kses_post( sprintf( _x( 'As you requested, we have deleted your original withdrawal request for order %1$s.', 'owb', 'woocommerce-germanized' ), esc_html( $original_order_id ) ) ); ?></p>
<?php endif; ?>

<?php if ( ! $hide_items && $withdrawal->has_items() ) : ?>
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
					$withdrawal,
					array(
						'show_sku'      => $sent_to_admin,
						'show_image'    => $email_improvements_enabled,
						'image_size'    => array( $image_size, $image_size ),
						'plain_text'    => $plain_text,
						'sent_to_admin' => $sent_to_admin,
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
