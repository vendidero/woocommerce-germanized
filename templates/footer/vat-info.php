<?php
/**
 * Footer global tax info
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<?php if ( get_option('woocommerce_gzd_small_enterprise') != 'yes' ) : ?> 
	<p class="footer-info vat-info"><?php echo ( get_option( 'woocommerce_tax_display_shop' ) == 'incl' ) ? __( 'All prices incl. VAT.', 'woocommerce-germanized' ) : __( 'All prices excl. VAT.', 'woocommerce-germanized' ) ?></p>
<?php else : ?>
	<p class="footer-info vat-info"><?php _e( 'Because of the small business owner state according to &#167;19 UStG the seller charge no sales tax, and therefore do not show it.', 'woocommerce-germanized' );?></p>
<?php endif; ?>