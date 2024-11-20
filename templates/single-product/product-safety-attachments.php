<?php
/**
 * The Template for displaying the product safety attachments list for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/single-product/product-safety-attachments.php.
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
$heading = apply_filters( 'woocommerce_gzd_product_product_safety_attachments_heading', __( 'Product safety documents', 'woocommerce-germanized' ) );
?>

<?php if ( wc_gzd_get_product( $product )->get_product_safety_attachments_html() ) : ?>
	<?php if ( isset( $print_title ) && $print_title && $heading ) : ?>
		<h3 class="wc-gzd-product-safety-attachments-heading"><?php echo esc_html( $heading ); ?></h3>
	<?php endif; ?>

	<div class="product-safety-attachments wc-gzd-additional-info">
		<?php echo wp_kses_post( wc_gzd_get_product( $product )->get_product_safety_attachments_html() ); ?>
	</div>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
	<?php if ( isset( $print_title ) && $print_title && $heading ) : ?>
		<h3 class="wc-gzd-product-safety-attachments-heading wc-gzd-additional-info-placeholder"></h3>
	<?php endif; ?>

	<div class="wc-gzd-additional-info product-safety-attachments wc-gzd-additional-info-placeholder"></div>
<?php endif; ?>
