<?php
/**
 * The Template for displaying a global VAT notice within footer.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/footer/vat-info.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.0.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<?php if ( ! wc_gzd_is_small_business() ) : ?>
	<p class="footer-info vat-info"><?php echo ( ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) ? esc_html__( 'All prices incl. VAT.', 'woocommerce-germanized' ) : esc_html__( 'All prices excl. VAT.', 'woocommerce-germanized' ) ); ?></p>
<?php else : ?>
	<p class="footer-info vat-info"><?php echo wp_kses_post( wc_gzd_get_small_business_notice() ); ?></p>
<?php endif; ?>
