<?php
/**
 * The Template for displaying the deposit amount for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/single-product/deposit.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 3.8.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $product;
?>

<?php if ( $html = wc_gzd_get_gzd_product( $product )->get_deposit_amount_html() ) : ?>
	<p class="wc-gzd-additional-info deposit-amount deposit"><?php echo wp_kses_post( $html ); ?></p>
<?php elseif ( $product->is_type( 'variable' ) ) : ?>
	<p class="wc-gzd-additional-info deposit-amount deposit wc-gzd-additional-info-placeholder"></p>
<?php endif; ?>
