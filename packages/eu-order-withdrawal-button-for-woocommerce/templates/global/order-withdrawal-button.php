<?php
/**
 * Order withdrawal button.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/order-withdrawal-button.php.
 *
 * HOWEVER, on occasion EU OWB will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/OrderWithdrawalButton/Templates
 * @version 2.2.0
 */
defined( 'ABSPATH' ) || exit;

$include_wrapper = wc_string_to_bool( $include_wrapper );
?>
<?php echo $include_wrapper ? '<p class="eu-owb-order-withdraw-from-contract-button align-center has-text-align-center">' : ''; ?>
	<a href="<?php echo esc_url( eu_owb_get_withdrawal_page_permalink() ); ?>" class="<?php echo esc_attr( $button_classes ); ?>"><?php echo esc_html( $button_text ); ?></a>
<?php echo $include_wrapper ? '</p>' : ''; ?>