<?php
/**
 * Thankyou page
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$order = get_order( $order_id );

?>
<h2><?php echo _x( 'Buyer Protection', 'trusted-shops', 'woocommerce-germanized' ); ?></h2>

<div class="trusted-shops-form" id="trusted-shops-buyer">
	
	<?php echo do_shortcode( '[trusted_shops_badge width=75]' ); ?>
	<p><?php echo _x( 'We offer you the free buyer protection of Trusted Shops as an additional service.', 'trusted-shops', 'woocommerce-germanized' ); ?></p>

	<form id="formTShops" name="formTShops" method="post" action="https://www.trustedshops.com/shop/protection.php" target="_blank">

		<input type="hidden" name="_charset_" />
		<input name="shop_id" type="hidden" value="<?php echo WC_germanized()->trusted_shops->id; ?>" />
		<input name="email" type="hidden" value="<?php echo ( $order->billing_email ? $order->billing_email : '' ); ?>" />
		<input name="amount" type="hidden" value="<?php echo $order->get_total(); ?>" />
		<input name="curr" type="hidden" value="<?php echo $order->get_order_currency(); ?>" />
		<input name="payment" type="hidden" value="<?php echo WC_germanized()->trusted_shops->get_payment_gateway( $order->payment_method );?>" />
		<input name="KDNR" type="hidden" value="<?php echo $order->user_id; ?>" />
		<input name="ORDERNR" type="hidden" value="<?php echo $order->id;?>" />
		<input type="submit" class="button" id="btnProtect" name="btnProtect" value="<?php echo _x( 'Register for Trusted Shops Buyer Protection', 'trusted-shops', 'woocommerce-germanized' );?>" />

	</form>

</div>

<?php if ( WC_germanized()->trusted_shops->is_rateable() ) : ?>

	<h2><?php echo _x( 'Rate our Shop', 'trusted-shops', 'woocommerce-germanized' ); ?></h2>

	<div class="trusted-shops-rate">
		
			<a href="https://www.trustedshops.com/buyerrating/rate_<?php echo WC_germanized()->trusted_shops->id; ?>.html&buyerEmail=<?php echo urlencode( base64_encode ( ( $order->billing_email ? $order->billing_email : '' ) ) ); ?>&shopOrderID=<?php echo urlencode( base64_encode ( $order->id ) ); ?>" target="_blank" title="" class="button trusted-shops-rate-button" id="trusted-shops-rate-button-now"><?php echo _x( 'Rate now', 'trusted-shops', 'woocommerce-germanized' );?></a>

			<a href="https://www.trustedshops.com/reviews/rateshoplater.php?shop_id=<?php echo WC_germanized()->trusted_shops->id; ?>&buyerEmail=<?php echo urlencode( base64_encode ( ( $order->billing_email ? $order->billing_email : '' ) ) ); ?>&shopOrderID=<?php echo urlencode( base64_encode ( $order->id ) ); ?>&days=<?php echo WC_germanized()->trusted_shops->review_reminder_days; ?>" target="_blank" title="" class="button trusted-shops-rate-button" id="trusted-shops-rate-button-later"><?php echo _x( 'Rate later', 'trusted-shops', 'woocommerce-germanized' );?></a>
			
			<p>
				<?php echo sprintf( _x( 'Yes, I would like to rate my purchase later and be remembered once per email by Trusted Shops after %d days.', 'trusted-shops', 'woocommerce-germanized' ), 7 ); ?>
				<?php echo ( wc_get_page_id( 'data_security' ) ? sprintf( _x( 'See <a href="%s" target="_blank">data security statement</a> for further information.', 'trusted-shops', 'woocommerce-germanized' ), esc_url( get_permalink( wc_get_page_id( 'data_security' ) ) ) ) : '' ) ;?>
			</p>

	</div>

<?php endif;?>