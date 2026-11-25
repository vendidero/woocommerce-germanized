<?php
/**
 * Email withdrawal edit link
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-withdrawal-edit-link.php.
 *
 * HOWEVER, on occasion EU OWB will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/OrderWithdrawalButton/Templates
 * @version 2.4.0
 */
defined( 'ABSPATH' ) || exit;

$text_align                 = is_rtl() ? 'right' : 'left';
$email_improvements_enabled = \Vendidero\OrderWithdrawalButton\Package::has_email_improvements_enabled();
$heading_class              = $email_improvements_enabled ? 'email-order-detail-heading' : '';
$has_multiple               = eu_owb_order_withdrawal_request_has_multiple_orders( $withdrawal );
$needs_order_select         = eu_owb_order_withdrawal_request_needs_order_select( $withdrawal );
$edit_button_label          = ( $has_multiple || $needs_order_select ) ? esc_html_x( 'Edit withdrawal request', 'owb', 'woocommerce-germanized' ) : esc_html_x( 'Choose items', 'owb', 'woocommerce-germanized' );
?>
<h2 class="<?php echo esc_attr( $heading_class ); ?>">
	<?php if ( $has_multiple ) : ?>
		<?php echo esc_html_x( 'More than one matching order found', 'owb', 'woocommerce-germanized' ); ?>
	<?php elseif ( $needs_order_select ) : ?>
		<?php echo esc_html_x( 'We could not find an order based on your criteria', 'owb', 'woocommerce-germanized' ); ?>
	<?php else : ?>
		<?php echo esc_html_x( 'Want to withdraw certain items only?', 'owb', 'woocommerce-germanized' ); ?>
	<?php endif; ?>
</h2>

<div class="withdrawal__edit_link_wrapper" style="margin-bottom: 40px;">
	<?php if ( $has_multiple ) : ?>
		<p><?php echo esc_html_x( 'We found more than one order matching your criteria—please use the link below to edit your withdrawal or select a different order.', 'owb', 'woocommerce-germanized' ); ?></p>
	<?php elseif ( $needs_order_select ) : ?>
		<p><?php echo esc_html_x( 'We could not find an order based on your criteria—please use the link below to select one of the orders matching your email address.', 'owb', 'woocommerce-germanized' ); ?></p>
	<?php endif; ?>

	<?php
	if ( \Vendidero\OrderWithdrawalButton\Package::email_button_template_exists() ) :
		wc_get_template(
			'emails/email-button.php',
			array(
				'url'   => $edit_withdrawal_link,
				'label' => $edit_button_label,
			)
		);
	else :
		?>
		<a href="<?php echo esc_url( $edit_withdrawal_link ); ?>" class="withdrawal__edit_link" id="notification__action_button">
			<?php echo esc_html( $edit_button_label ); ?>
		</a>
	<?php endif; ?>
</div>

