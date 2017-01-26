<?php
/**
 * Customer Trusted Shops Review Notification
 *
 * @author Vendidero
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$base 		= get_option( 'woocommerce_email_base_color' );
$base_text 	= wc_light_or_dark( $base, '#202020', '#ffffff' );
$text 		= get_option( 'woocommerce_email_text_color' );

?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php echo sprintf( _x( 'Dear %s %s,', 'trusted-shops', 'woocommerce-germanized' ), wc_gzd_get_crud_data( $order, 'billing_first_name' ), wc_gzd_get_crud_data( $order, 'billing_last_name' ) ); ?></p>
<p><?php echo sprintf( _x( 'You have recently shopped at %s. Thank you! We would be glad if you spent some time to write a review about your order. To do so please follow follow the link.', 'trusted-shops', 'woocommerce-germanized' ), get_bloginfo( 'name' ) ); ?></p>
<table cellspacing="0" cellpadding="0" style="width: 100%; border: none;" border="0">
	<tr align="center">
		<td align="center"><a class="email_btn" href="<?php echo esc_url( WC_Germanized()->trusted_shops->get_new_review_link( wc_gzd_get_crud_data( $order, 'billing_email' ), wc_gzd_get_crud_data( $order, 'id' ) ) ); ?>" target="_blank" style="text-decoration: none; background-color: <?php echo esc_attr( $base ); ?>; color: <?php echo $base_text;?>; border-radius: 3px !important; padding: font-family:Arial; font-weight:bold; line-height:100%; padding: 0.5rem;"><?php echo _x( 'Rate Order now', 'trusted-shops', 'woocommerce-germanized' );?></a></td>
	</tr>
</table>

<?php do_action( 'woocommerce_email_footer', $email ); ?>