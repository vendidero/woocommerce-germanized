<?php
/**
 * The Template for displaying small business info text.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/global/small-business-info.php.
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

<div class="wc-gzd-additional-wrapper">
	<p class="wc-gzd-additional-info small-business-info">
		<?php echo wp_kses_post( wc_gzd_get_small_business_notice() ); ?>
	</p>
</div>
