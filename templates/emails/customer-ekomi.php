<?php
/**
 * Customer new account email
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( _x( 'Dear %s %s,', 'ekomi', 'woocommerce-germanized' ), wc_gzd_get_crud_data( $order, 'billing_first_name' ), wc_gzd_get_crud_data( $order, 'billing_last_name' ) ); ?></p>

<p><?php printf( _x( 'You have recently shopped at %s. Thank you! We would be glad if you spent some time to write a review about your order. To do so please follow follow the link.', 'ekomi', 'woocommerce-germanized' ), get_bloginfo( 'name' ) ); ?></p>

<table cellspacing="0" cellpadding="0" style="width: 100%; border: none;" border="0">
	<tr align="center">
		<td align="center"><a class="wc-button button" href="<?php echo esc_url( wc_gzd_get_crud_data( $order, 'ekomi_review_link' ) ); ?>" target="_blank"><?php echo _x( 'Rate Order now', 'ekomi', 'woocommerce-germanized' );?></a></td>
	</tr>
</table>

<?php do_action( 'woocommerce_email_footer', $email ); ?>