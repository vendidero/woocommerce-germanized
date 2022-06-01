<?php
/**
 * The Template for displaying shipping costs notice for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/single-product/shipping-costs-info.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 3.0.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $product;
?>

<?php if ( wc_gzd_get_product( $product )->get_shipping_costs_html() ) : ?>
	<p class="wc-gzd-additional-info shipping-costs-info"><?php echo wp_kses_post( wc_gzd_get_product( $product )->get_shipping_costs_html() ); ?></p>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
	<p class="wc-gzd-additional-info shipping-costs-info wc-gzd-additional-info-placeholder"></p>
<?php endif; ?>
