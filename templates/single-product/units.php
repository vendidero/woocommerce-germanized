<?php
/**
 * Single Product Units
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $product;
?>

<?php if ( wc_gzd_get_product( $product )->has_unit_product() ) : ?>
	<p class="wc-gzd-additional-info product-units-wrapper product-units"><?php echo wc_gzd_get_product( $product )->get_unit_product_html(); ?></p>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
    <p class="wc-gzd-additional-info product-units-wrapper product-units"></p>
<?php endif; ?>