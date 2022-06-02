<?php
/**
 * Customer revocation email.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/customer-revocation.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.0.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$fields = WC_GZD_Revocation::get_fields();
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php echo esc_html_x( 'By sending you this email we confirm receiving your withdrawal. Please review your data.', 'revocation-form', 'woocommerce-germanized' ); ?></p>

<table cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top;" border="0">
	<?php if ( ! empty( $fields ) ) : ?>
		<?php foreach ( $fields as $name => $field ) : ?>
			<?php if ( isset( $user ) && is_array( $user ) && ! empty( $user[ $name ] ) ) : ?>
				<tr>
					<td valign="top" width="50%">
						<p><strong><?php echo esc_html( $field['label'] ); ?></strong></p>
					</td>

					<td valign="top" width="50%">
						<p><?php echo esc_html( $user[ $name ] ); ?></p>
					</td>
				</tr>
			<?php endif; ?>
		<?php endforeach; ?>
	<?php endif; ?>
</table>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
