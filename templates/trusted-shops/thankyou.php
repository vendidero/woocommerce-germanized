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
	<span id="tsCheckoutOrderNr"><?php echo wc_gzd_get_crud_data( $order, 'id' );?></span> 
	<span id="tsCheckoutBuyerEmail"><?php echo wc_gzd_get_crud_data( $order, 'billing_email' ); ?></span>
	<span id="tsCheckoutBuyerId"><?php echo wc_gzd_get_crud_data( $order, 'user_id' ); ?></span>
	<span id="tsCheckoutOrderAmount"><?php echo $order->get_total(); ?></span>
	<span id="tsCheckoutOrderCurrency"><?php echo wc_gzd_get_order_currency( $order ); ?></span>
	<span id="tsCheckoutOrderPaymentType"><?php echo WC_germanized()->trusted_shops->get_payment_gateway( wc_gzd_get_crud_data( $order, 'payment_method' ) );?></span>
	<span id="tsCheckoutOrderEstDeliveryDate"></span>
	<?php if ( WC_germanized()->trusted_shops->is_product_reviews_enabled() ) : ?>
		<?php foreach( $order->get_items() as $item_id => $item ) : 
			
			$product = $order->get_product_from_item( $item );

	        if ( ! $product )
	            continue;
			
			// Currently not supporting reviews for variations	
			if ( $product->is_type( 'variation' ) )
				$product = wc_get_product( wc_gzd_get_crud_data( $product, 'parent' ) );

			$image = '';
			if ( has_post_thumbnail( wc_gzd_get_crud_data( $product, 'id' ) ) )
				$image = wp_get_attachment_image_src( get_post_thumbnail_id( wc_gzd_get_crud_data( $product, 'id' ) ), apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ) );
	
			?>
			<span class="tsCheckoutProductItem">
				<span class="tsCheckoutProductUrl"><?php echo get_permalink( wc_gzd_get_crud_data( $product, 'id' ) ); ?></span>
				<span class="tsCheckoutProductImageUrl"><?php echo ( ! empty( $image ) ? $image[0] : '' ); ?></span>
				<span class="tsCheckoutProductName"><?php echo get_the_title( wc_gzd_get_crud_data( $product, 'id' ) ); ?></span>
				<span class="tsCheckoutProductSKU"><?php echo ( $product->get_sku() ? $product->get_sku() : wc_gzd_get_crud_data( $product, 'id' ) ); ?></span>
				<span class="tsCheckoutProductGTIN"><?php echo apply_filters( 'woocommerce_gzd_trusted_shops_product_gtin', $product->get_attribute( $gtin_attribute ), $product ); ?></span>
				<span class="tsCheckoutProductBrand"><?php echo apply_filters( 'woocommerce_gzd_trusted_shops_product_brand', $product->get_attribute( $brand_attribute ), $product ); ?></span>
				<span class="tsCheckoutProductMPN"><?php echo apply_filters( 'woocommerce_gzd_trusted_shops_product_mpn', $product->get_attribute( $mpn_attribute ), $product ); ?></span>
 			</span>
		<?php endforeach; ?>
	<?php endif; ?>
</div>