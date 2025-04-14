<?php
/**
 * Shipment Tracking Details
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/shipment/shipment-details-tracking.php.
 *
 * HOWEVER, on occasion Shiptastic will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/Shiptastic/Templates
 * @version 4.3.0
 */
use Vendidero\Shiptastic\Shipment;

defined( 'ABSPATH' ) || exit;
?>
<section class="woocommerce-shipment-tracking-details">

	<h2 class="woocommerce-shipments-tracking__title"><?php echo esc_html_x( 'Tracking', 'shipments', 'woocommerce-germanized' ); ?></h2>

	<?php if ( $shipment->get_tracking_url() ) : ?>
		<p class="tracking-button-wrapper"><a class="woocommerce-button button btn<?php echo esc_attr( wc_stc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_stc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" target="_blank" href="<?php echo esc_url( $shipment->get_tracking_url() ); ?>"><?php echo esc_html_x( 'Track your shipment', 'shipments', 'woocommerce-germanized' ); ?></a></p>
	<?php endif; ?>

	<?php if ( $shipment->has_tracking_instruction() ) : ?>
		<p class="tracking-instruction"><?php echo wp_kses_post( $shipment->get_tracking_instruction() ); ?></p>
	<?php endif; ?>

</section>

<?php
/**
 * This action is executed after printing the shipment tracking details on the customer account page.
 *
 * @param Shipment $shipment The shipment instance.
 *
 * @package Vendidero/Shiptastic
 */
do_action( 'woocommerce_shiptastic_shipment_details_after_tracking_details', $shipment ); ?>
