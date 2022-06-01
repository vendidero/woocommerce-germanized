<?php
/**
 * The Template for displaying cart totals in the mini cart dropdown.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/cart/mini-cart-totals.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>

<?php if ( ! empty( $differential_taxation_info ) ) : ?>
	<p class="total differential-taxation-notice wc-gzd-total-mini-cart"><?php echo wp_kses_post( $differential_taxation_info ); ?></p>
<?php endif; ?>

<?php foreach ( $taxes as $tax_rate ) : ?>
	<p class="total total-cart-tax wc-gzd-total-mini-cart"><?php echo wp_kses_post( wc_gzd_get_tax_rate_label( $tax_rate['tax']->rate ) ); ?>: <?php echo wc_price( $tax_rate['amount'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
<?php endforeach; ?>

<?php if ( ! empty( $shipping_costs_info ) ) : ?>
	<p class="total shipping-costs-cart-info wc-gzd-total-mini-cart"><?php echo wp_kses_post( $shipping_costs_info ); ?></p>
<?php endif; ?>
