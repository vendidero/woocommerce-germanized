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

<?php if ( wc_gzd_get_gzd_product( $product )->has_unit() ) : ?>
	<p class="price price-unit smaller"><?php echo wc_gzd_get_gzd_product( $product )->get_unit_html(); ?></p>
<?php endif; ?>