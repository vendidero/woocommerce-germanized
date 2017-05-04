<?php
/**
 * Mini Cart Taxes
 *
 * @author   Vendidero
 * @package  WooCommerceGermanized/Templates
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<?php foreach( $taxes as $tax ) : ?>
	<p class="total total-cart-tax wc-gzd-total-mini-cart"><?php echo wc_gzd_get_tax_rate_label( $tax[ 'tax' ]->rate ); ?>: <?php echo wc_price( $tax[ 'amount' ] ); ?></p>
<?php endforeach; ?>

<?php if ( ! empty( $shipping_costs_info ) ) : ?>
    <p class="total shipping-costs-cart-info wc-gzd-total-mini-cart"><?php echo $shipping_costs_info; ?></p>
<?php endif; ?>
