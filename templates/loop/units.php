<?php
/**
 * The Template for displaying product units for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/loop/units.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 3.9.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $product;
?>

<?php if ( wc_gzd_get_product( $product )->has_unit_product() ) : ?>
	<p class="wc-gzd-additional-info wc-gzd-additional-info-loop product-units-wrapper product-units"><?php echo wp_kses_post( wc_gzd_get_product( $product )->get_unit_product_html() ); ?></p>
<?php endif; ?>
