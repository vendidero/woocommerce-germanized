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

<?php if ( wc_gzd_get_gzd_product( $product )->get_tax_info() ) : ?>
	<p class="wc-gzd-additional-info tax-info"><?php echo wc_gzd_get_gzd_product( $product )->get_tax_info(); ?></p>
<?php elseif ( get_option( 'woocommerce_gzd_small_enterprise' ) == 'yes' ) : ?>
	<p class="wc-gzd-additional-info small-business-info"><?php echo wc_gzd_get_small_business_product_notice(); ?></p>
<?php endif; ?>