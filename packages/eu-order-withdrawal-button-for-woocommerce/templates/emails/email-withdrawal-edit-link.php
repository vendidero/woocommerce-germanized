<?php
/**
 * E-Mail withdrawal edit link
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
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

$text_align                 = is_rtl() ? 'right' : 'left';
$email_improvements_enabled = \Vendidero\OrderWithdrawalButton\Package::has_email_improvements_enabled();
$heading_class              = $email_improvements_enabled ? 'email-order-detail-heading' : '';
?>
<h2 class="<?php echo esc_attr( $heading_class ); ?>">
	<?php echo esc_html_x( 'Want to withdraw certain items only?', 'owb', 'woocommerce-germanized' ); ?>
</h2>

<div class="withdrawal__edit_link_wrapper" style="margin-bottom: 40px;">
	<a href="<?php echo esc_url( $edit_withdrawal_link ); ?>" class="withdrawal__edit_link" id="notification__action_button"><?php echo esc_html_x( 'Choose items to withdraw now', 'owb', 'woocommerce-germanized' ); ?></a>
</div>

