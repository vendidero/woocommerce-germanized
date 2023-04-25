<?php
/**
 * The Template for displaying a notice to inform the customer of a possible VAT exemption for photovoltaic systems.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/checkout/photovoltaic-systems-notice.php.
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
<div class="wc-gzd-photovoltaic-systems-notice woocommerce-info">
	<h4><?php esc_html_e( 'Your shopping cart is eligible for VAT exemption', 'woocommerce-germanized' ); ?></h4>
	<p style="margin-bottom: 0"><?php echo wp_kses_post( apply_filters( 'woocommerce_gzd_photovoltaic_systems_vat_exemption_available_notice', sprintf( __( 'To benefit from the tax exemption, please confirm the VAT exemption according to <a href="%s" target="_blank">§12 paragraph 3 UStG</a> by activating the checkbox.', 'woocommerce-germanized' ), 'https://www.gesetze-im-internet.de/ustg_1980/__12.html' ) ) ); ?></p>
</div>
