<?php
/**
 * The Template for displaying delivery time notice for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/loop/defect-description.php.
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

$defect_description = wc_gzd_get_gzd_product( $product )->get_formatted_defect_description();
?>
<?php if ( $defect_description ) : ?>
	<div class="wc-gzd-additional-info defect-description wc-gzd-additional-info-loop"><?php echo wp_kses_post( $defect_description ); ?></div>
<?php endif; ?>
