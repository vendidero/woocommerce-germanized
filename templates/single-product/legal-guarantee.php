<?php
/**
 * The Template for displaying legal guarantee label for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/single-product/legal-guarantee.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 4.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $product;

$variant = isset( $variant ) ? $variant : '';
?>
<?php if ( wc_gzd_get_product( $product )->needs_legal_guarantee() ) : ?>
	<div class="wc-gzd-legal-guarantee wc-gzd-additional-info"><?php echo wc_gzd_kses_post_svg( wc_gzd_get_product( $product )->get_legal_guarantee_html( $variant ) ); ?></div>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
	<div class="wc-gzd-legal-guarantee wc-gzd-additional-info wc-gzd-additional-info-placeholder" aria-hidden="true"></div>
<?php endif; ?>
