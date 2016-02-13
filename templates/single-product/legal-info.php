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
<div class="legal-price-info">
	<p class="wc-gzd-additional-info">
		<?php if ( wc_gzd_get_gzd_product( $product )->get_tax_info() && get_option( 'woocommerce_gzd_display_product_detail_tax_info' ) == 'yes' ) : ?>
			<span class="wc-gzd-additional-info tax-info"><?php echo wc_gzd_get_gzd_product( $product )->get_tax_info(); ?></span>
		<?php elseif ( ( get_option( 'woocommerce_gzd_small_enterprise' ) == 'yes' && get_option( 'woocommerce_gzd_display_product_detail_small_enterprise' ) == 'yes' ) ) : ?>
			<span class="wc-gzd-additional-info small-business-info"><?php echo wc_gzd_get_small_business_product_notice(); ?></span>		
		<?php endif; ?>
		<?php if ( wc_gzd_get_gzd_product( $product )->get_shipping_costs_html() && get_option( 'woocommerce_gzd_display_product_detail_shipping_costs' ) == 'yes' ) : ?>
			<span class="wc-gzd-additional-info shipping-costs-info"><?php echo wc_gzd_get_gzd_product( $product )->get_shipping_costs_html();?></span>
		<?php endif; ?>
	</p>
</div>