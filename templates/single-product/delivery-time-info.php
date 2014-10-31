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

<?php if ( $product->get_delivery_time_term() ) : ?>
	<p class="wc-gzd-additional-info delivery-time-info"><?php echo $product->get_delivery_time_html();?></p>
<?php endif; ?>