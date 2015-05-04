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

<?php if ( $product->gzd_product->get_delivery_time_term() ) : ?>
	<p class="wc-gzd-additional-info delivery-time-info"><?php echo $product->gzd_product->get_delivery_time_html();?></p>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
	<p class="wc-gzd-additional-info delivery-time-info"></p>
<?php endif; ?>