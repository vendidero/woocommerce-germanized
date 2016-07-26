<?php
/**
 * Customer revocation confirmation
 *
 * @author Vendidero
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$fields = WC_GZD_Revocation::get_fields();

?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php echo _x( 'By sending you this email we confirm your Revocation. Please review your data.', 'revocation-form', 'woocommerce-germanized' );?></p>

<table cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top;" border="0">

	<?php if ( ! empty( $fields ) ) : ?>

		<?php foreach ( $fields as $name => $field ) : ?>

			<?php if ( isset( $user ) && is_array( $user ) && ! empty( $user[ $name ] ) ) : ?>

				<tr>

					<td valign="top" width="50%">

						<p><strong><?php echo $field[ 'label' ];?></strong></p>

					</td>

					<td valign="top" width="50%">

						<p><?php echo $user[ $name ];?></p>

					</td>

				</tr>

			<?php endif; ?>

		<?php endforeach;?>

	<?php endif;?>

</table>

<?php do_action( 'woocommerce_email_footer', $email ); ?>