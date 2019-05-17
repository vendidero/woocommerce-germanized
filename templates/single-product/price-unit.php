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

$_product = $product;

if ( isset( $gzd_product ) ) {
    $_product = $gzd_product;
}
?>

<?php if ( wc_gzd_get_gzd_product( $_product )->has_unit() ) : ?>
	<p class="price price-unit smaller wc-gzd-additional-info"><?php echo wc_gzd_get_gzd_product( $_product )->get_unit_html(); ?></p>
<?php endif; ?>