<?php
/**
 * Single Product Shipping Costs Info
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $product;
?>

<p class="wc-gzd-additional-info shipping-costs-info"><?php echo $product->get_shipping_costs_html();?></p>