<?php
/**
 * Shipment Tracking Details
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/shipment/shipment-details-tracking.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Vendidero/Germanized/Shipments/Templates
 * @version 3.1.0
 */
use Vendidero\Germanized\Shipments\Shipment;

defined( 'ABSPATH' ) || exit;
?>
<section class="woocommerce-shipment-tracking-details">

	<h2 class="woocommerce-shipments-tracking__title"><?php echo esc_html_x(  'Tracking', 'shipments', 'woocommerce-germanized' ); ?></h2>

	<?php if ( $shipment->get_tracking_url() ) : ?>
		<p class="tracking-button-wrapper"><a class="woocommerce-button button btn" target="_blank" href="<?php echo esc_url( $shipment->get_tracking_url() ); ?>"><?php _ex(  'Track your shipment', 'shipments', 'woocommerce-germanized' ); ?></a></p>
	<?php endif; ?>

	<?php if ( $shipment->has_tracking_instruction() ) : ?>
		<p class="tracking-instruction"><?php echo $shipment->get_tracking_instruction(); ?></p>
	<?php endif; ?>

</section>

<?php
/**
 * This action is executed after printing the shipment tracking details on the customer account page.
 *
 * @param Shipment $shipment The shipment instance.
 *
 * @since 3.0.0
 * @package Vendidero/Germanized/Shipments
 */
do_action( 'woocommerce_gzd_shipment_details_after_tracking_details', $shipment ); ?>
