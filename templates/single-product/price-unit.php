<?php
/**
 * Single Product Price per Unit
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $product;
?>

<?php if ( $product->gzd_product->has_unit() ) : ?>
	<p class="price price-unit smaller"><?php echo $product->gzd_product->get_unit_html(); ?></p>
<?php endif; ?>