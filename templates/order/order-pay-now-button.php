<?php
/**
 * 
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$order = wc_get_order( $order_id );
?>

<p>
	<a href="<?php echo $url;?>" class="button wc-gzdp-order-pay-button"><?php printf( __( 'Pay with %s', 'woocommerce-germanized' ), $order->payment_method_title ); ?></a>
</p>