<?php
/**
 * Trusted Shops Cancel Review Reminder
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<!-- Module: WooCommerce Germanized -->
<div class="wc-ts-cancel-review-reminder">
	<p><?php echo sprintf( _x( 'If you do not want to receive the review reminder e-mail, please follow the %s link.', 'trusted-shops', 'woocommerce-germanized' ), '<a href="' . esc_url( $link ) . '" target="_blank">' . _x( 'cancel review reminder', 'trusted-shops', 'woocommerce-germanized' ) . '</a>' ); ?></p>
</div>
