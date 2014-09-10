<?php
/**
 * Single Product Tax Info
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $product;
?>
<?php if ( $product->get_tax_info() ) : ?>
	<p class="wc-gzd-additional-info tax-info"><?php echo $product->get_tax_info(); ?></p>
<?php endif; ?>