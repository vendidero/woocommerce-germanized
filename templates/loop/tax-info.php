<?php
/**
 * The Template for displaying tax notice for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/loop/tax-info.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 3.9.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $product;
?>

<?php if ( wc_gzd_get_product( $product )->get_tax_info() ) : ?>
	<p class="wc-gzd-additional-info wc-gzd-additional-info-loop tax-info"><?php echo wp_kses_post( wc_gzd_get_product( $product )->get_tax_info() ); ?></p>
<?php elseif ( wc_gzd_is_small_business() ) : ?>
	<p class="wc-gzd-additional-info wc-gzd-additional-info-loop small-business-info"><?php echo wp_kses_post( wc_gzd_get_small_business_product_notice() ); ?></p>
<?php endif; ?>
