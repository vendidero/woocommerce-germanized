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

<?php if ( $product->gzd_product->get_tax_info() ) : ?>
	<p class="wc-gzd-additional-info tax-info"><?php echo $product->gzd_product->get_tax_info(); ?></p>
<?php elseif ( get_option( 'woocommerce_gzd_small_enterprise' ) == 'yes' ) : ?>
	<p class="wc-gzd-additional-info small-business-info"><?php _e( 'incl. VAT', 'woocommerce-germanized' );?></p>
<?php endif; ?>