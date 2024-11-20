<?php
/**
 * The Template for displaying product safety information for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/single-product/product-safety.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 3.18.5
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $product;
$heading = apply_filters( 'woocommerce_gzd_product_safety_heading', __( 'Product safety', 'woocommerce-germanized' ) );
?>

<?php if ( wc_gzd_get_product( $product )->has_product_safety_information() ) : ?>
	<?php if ( isset( $print_title ) && $print_title && $heading ) : ?>
		<h2 class="wc-gzd-product-safety-heading wc-tab"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<?php do_action( 'woocommerce_gzd_single_product_safety_information' ); ?>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
	<?php if ( isset( $print_title ) && $print_title && $heading ) : ?>
		<h2 class="wc-gzd-product-safety-heading wc-tab wc-gzd-additional-info-placeholder"></h2>
	<?php endif; ?>

	<?php do_action( 'woocommerce_gzd_single_product_safety_information' ); ?>
<?php endif; ?>
