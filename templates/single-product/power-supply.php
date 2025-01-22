<?php
/**
 * The Template for displaying power supply information for a certain product.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/single-product/power-supply.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 3.18.5
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $product;
?>

<?php if ( wc_gzd_get_product( $product )->is_wireless_electronic_device() ) : ?>
	<div class="wc-gzd-power-supply wc-gzd-additional-info">
		<?php echo wc_gzd_kses_post_svg( wc_gzd_get_product( $product )->get_power_supply_html() ); ?>
	</div>
<?php endif; ?>
