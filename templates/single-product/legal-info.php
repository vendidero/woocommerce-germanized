<?php
/**
 * The Template for displaying legal information notice (taxes, shipping costs) for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/single-product/legal-info.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 3.0.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $product;
?>
<div class="legal-price-info">
	<p class="wc-gzd-additional-info">
		<?php if ( wc_gzd_get_product( $product )->get_tax_info() && 'yes' === get_option( 'woocommerce_gzd_display_product_detail_tax_info' ) ) : ?>
			<span class="wc-gzd-additional-info tax-info"><?php echo wp_kses_post( wc_gzd_get_product( $product )->get_tax_info() ); ?></span>
		<?php elseif ( ( wc_gzd_is_small_business() && 'yes' === get_option( 'woocommerce_gzd_display_product_detail_tax_info' ) ) ) : ?>
			<span class="wc-gzd-additional-info small-business-info"><?php echo wp_kses_post( wc_gzd_get_small_business_product_notice() ); ?></span>
		<?php endif; ?>
		<?php if ( wc_gzd_get_product( $product )->get_shipping_costs_html() && 'yes' === get_option( 'woocommerce_gzd_display_product_detail_shipping_costs_info' ) ) : ?>
			<span class="wc-gzd-additional-info shipping-costs-info"><?php echo wp_kses_post( wc_gzd_get_product( $product )->get_shipping_costs_html() ); ?></span>
		<?php endif; ?>
	</p>
</div>
