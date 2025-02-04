<?php
/**
 * The Template for displaying the manufacturer for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/single-product/manufacturer.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 3.18.8
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $product;
?>

<?php if ( wc_gzd_get_product( $product )->get_manufacturer_html() ) : ?>
	<?php if ( isset( $print_title ) && $print_title ) : ?>
		<h3 class="wc-gzd-product-manufacturer-heading"><?php echo esc_html( apply_filters( 'woocommerce_gzd_product_manufacturer_heading', __( 'Manufacturer information', 'woocommerce-germanized' ) ) ); ?></h3>
	<?php endif; ?>

	<div class="manufacturer wc-gzd-additional-info">
		<?php echo wp_kses_post( wc_gzd_get_product( $product )->get_manufacturer_html() ); ?>
	</div>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
	<?php if ( isset( $print_title ) && $print_title ) : ?>
		<h3 class="wc-gzd-product-manufacturer-heading wc-gzd-additional-info-placeholder" aria-hidden="true"></h3>
	<?php endif; ?>

	<div class="wc-gzd-additional-info manufacturer wc-gzd-additional-info-placeholder" aria-hidden="true"></div>
<?php endif; ?>
