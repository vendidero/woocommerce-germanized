<?php
/**
 * Template for inserting SEPA data within emails.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/emails/email-sepa-data.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 2.4.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<table id="sepa" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top;" border="0">
	<tr>
		<td class="td" style="text-align:left; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"
			valign="top" width="50%">

			<h2><?php esc_html_e( 'SEPA Data', 'woocommerce-germanized' ); ?></h2>

			<p class="text">
				<?php foreach ( $fields as $label => $data ) : ?>
					<span class="text-label"><?php echo esc_html( wptexturize( $label ) ); ?>: </span>
					<span class="text-data"><?php echo esc_html( wptexturize( $data ) ); ?></span><br/>
				<?php endforeach; ?>
			</p>

			<?php if ( $send_pre_notification ) : ?>
				<p class="pre-notification text">
					<?php echo wp_kses_post( $pre_notification_text ); ?>
				</p>
			<?php endif; ?>
		</td>
	</tr>
</table>
