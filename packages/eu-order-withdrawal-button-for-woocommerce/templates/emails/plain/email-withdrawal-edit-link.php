<?php
/**
 * E-Mail withdrawal edit link
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-withdrawal-edit-link.php.
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
?>
<?php echo esc_html_x( 'Want to withdraw certain items only?', 'owb', 'woocommerce-germanized' ) . "\n\n"; ?>
<?php
echo sprintf( esc_html_x( 'Choose items to withdraw now: %s', 'owb', 'woocommerce-germanized' ), esc_url( $edit_withdrawal_link ) ) . "\n\n";
