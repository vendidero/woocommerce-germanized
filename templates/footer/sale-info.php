<?php
/**
 * The Template for displaying a global sale info within footer.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/footer/sale-info.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<p class="footer-info sale-info"><?php esc_html_e( 'All striked out prices refer to prices used to be charged at this shop.', 'woocommerce-germanized' ); ?></p>
