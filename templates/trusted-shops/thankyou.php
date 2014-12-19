<?php
/**
 * Thankyou page
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$order = wc_get_order( $order_id );

?>

<div id="trustedShopsCheckout" style="display: none;">
	<span id="tsCheckoutOrderNr"><?php echo $order->id;?></span> 
	<span id="tsCheckoutBuyerEmail"><?php echo ( $order->billing_email ? $order->billing_email : '' ); ?></span>
	<span id="tsCheckoutBuyerId"><?php echo $order->user_id; ?></span>
	<span id="tsCheckoutOrderAmount"><?php echo $order->get_total(); ?></span>
	<span id="tsCheckoutOrderCurrency"><?php echo $order->get_order_currency(); ?></span>
	<span id="tsCheckoutOrderPaymentType"><?php echo WC_germanized()->trusted_shops->get_payment_gateway( $order->payment_method );?></span>
</div>