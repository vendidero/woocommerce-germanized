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
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

$button_classes = implode(
	' ',
	array_filter(
		array(
			'button',
			eu_owb_get_element_class_name( 'button' ),
		)
	)
);
?>
<p class="eu-owb-order-withdraw-from-contract-button align-center has-text-align-center">
	<a href="<?php echo esc_url( eu_owb_get_withdrawal_page_permalink() ); ?>" class="<?php echo esc_attr( $button_classes ); ?>"><?php echo esc_html( eu_owb_get_withdrawal_button_text() ); ?></a>
</p>