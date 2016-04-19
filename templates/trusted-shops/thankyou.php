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
	<?php if ( WC_germanized()->trusted_shops->is_product_reviews_enabled() ) : ?>
		<?php foreach( $order->get_items() as $item_id => $item ) : 
			$product = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item ); ?>
			<span class="tsCheckoutProductItem">
				<span class="tsCheckoutProductUrl"><?php echo get_permalink( $product->id ); ?></span>
				<?php if ( has_post_thumbnail( $product->id ) ) : 
					$image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $product->id ), apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ) ); ?>
					<span class="tsCheckoutProductImageUrl"><?php echo $image_url[0]; ?></span>
				<?php endif; ?>
				<span class="tsCheckoutProductName"><?php echo get_the_title( $product->id ); ?></span>
				<span class="tsCheckoutProductSKU"><?php echo ( $product->get_sku() ? $product->get_sku() : $product->id ); ?></span>
				<span class="tsCheckoutProductGTIN"></span>
				<span class="tsCheckoutProductMPN"></span>
				<span class="tsCheckoutProductBrand"></span>
 			</span>
		<?php endforeach; ?>
	<?php endif; ?>
</div>