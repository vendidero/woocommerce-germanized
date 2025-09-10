<?php
/**
 * Email return shipment costs
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/emails/email-return-shipment-costs.php.
 *
 * HOWEVER, on occasion Shiptastic will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Shiptastic/Templates/Emails
 * @version 4.7.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php if ( $shipment->has_return_costs() ) : ?>
	<p><?php echo wp_kses_post( sprintf( _x( 'The return shipping costs are %s and will be automatically deducted from your refund amount.', 'shipments', 'woocommerce-germanized' ), wc_price( $shipment->get_return_costs() ) ) ); ?></p>
<?php endif; ?>
