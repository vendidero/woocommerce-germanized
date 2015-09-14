<?php
/**
 * Single Product Units
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $product;
?>

<?php if ( $product->gzd_product->has_product_units() ) : ?>
	<span class="product-units-wrapper product-units"><?php echo $product->gzd_product->get_product_units_html(); ?></span>
<?php endif; ?>