<?php
/**
 * The Template for displaying the differential tax notice within the checkout.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/checkout/differential-taxation-notice.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 3.8.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<tr class="order-total order-total-differential-taxation-notice">
	<td colspan="2">
		<div class="wc-gzd-additional-wrapper">
			<p class="wc-gzd-additional-info wc-gzd-differential-taxation-notice-cart"><?php echo wp_kses_post( $notice ); ?></p>
		</div>
	</td>
</tr>
