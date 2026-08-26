<?php
/**
 * The Template for displaying legal guarantee label globally.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/global/legal-guarantee.php.
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

$variant  = isset( $variant ) ? $variant : '';
$location = isset( $location ) ? $location : '';
$lang     = isset( $lang ) ? $lang : '';
?>
<?php if ( wc_gzd_is_legal_guarantee_enabled() && apply_filters( 'woocommerce_gzd_show_global_legal_guarantee_notice', ( 'checkout' === $location ? wc_gzd_cart_needs_legal_guarantee() : true ), $location ) ) : ?>
	<div class="wc-gzd-legal-guarantee wc-gzd-legal-guarantee-global <?php echo ( ! empty( $location ) ? esc_attr( 'wc-gzd-legal-guarantee-' . $location ) : '' ); ?>"><?php echo wc_gzd_kses_post_svg( wc_gzd_get_legal_guarantee_html( $variant, $lang, $location ) ); ?></div>
<?php endif; ?>
