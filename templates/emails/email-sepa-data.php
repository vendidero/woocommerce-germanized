<?php
/**
 * Email SEPA data
 *
 * @author 		vendidero
 * @package 	WooCommerceGermanized/Templates/Emails
 * @version     2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?><table id="sepa" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top;" border="0">

	<tr>

		<td class="td" style="text-align:left; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" valign="top" width="50%">

			<h2><?php _e( 'SEPA Data', 'woocommerce-germanized' ); ?></h2>

			<p class="text">
				<?php foreach ( $fields as $label => $data ) : ?>
					<span class="text-label"><?php echo wptexturize( $label ); ?>: </span>
					<span class="text-data"><?php echo wptexturize( $data ); ?></span><br/>
				<?php endforeach; ?>
			</p>

            <?php if ( $send_pre_notification ) : ?>

            <p class="pre-notification text">
                <?php echo $pre_notification_text; ?>
            </p>

            <?php endif; ?>

		</td>

	</tr>

</table>