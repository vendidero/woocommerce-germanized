<?php
/**
 * Trusted Shops Product Widget
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
global $post;
$product = wc_get_product( $post->ID );
$plugin  = isset( $plugin ) ? $plugin : WC_trusted_shops()->trusted_shops;
$skus    = $plugin->get_product_skus( $post->ID );
?>
<!-- Module: WooCommerce Germanized -->
<div <?php echo $plugin->get_selector( 'product_widget' ); ?>></div>

<script type="text/javascript" src="//widgets.trustedshops.com/reviews/tsSticker/tsProductStickerSummary.js"></script>

<script type="text/javascript">
	<?php echo $plugin->get_product_widget_code( true, array( 'sku' => $skus ) ); ?>
</script>
