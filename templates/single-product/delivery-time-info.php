<?php
/**
 * Single Product Shipping Time Info
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $product;
?>

<?php if ( wc_gzd_get_gzd_product( $product )->get_delivery_time_html() ) : ?>
	<p class="wc-gzd-additional-info delivery-time-info"><?php echo wc_gzd_get_gzd_product( $product )->get_delivery_time_html();?></p>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
	<p class="wc-gzd-additional-info delivery-time-info"></p>
<?php endif; ?>