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

<?php if ( wc_gzd_get_gzd_product( $product )->has_product_units() ) : ?>
	<span class="wc-gzd-additional-info product-units-wrapper product-units"><?php echo wc_gzd_get_gzd_product( $product )->get_product_units_html(); ?></span>
<?php endif; ?>