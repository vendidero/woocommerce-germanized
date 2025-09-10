<?php
/**
 * Shipment return instructions
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/shipment/shipment-return-instructions.php.
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
use Vendidero\Shiptastic\ReturnShipment;

defined( 'ABSPATH' ) || exit;

$provider = $shipment->get_shipping_provider_instance();
?>

<?php if ( $provider && $provider->has_return_instructions() ) : ?>
	<div class="return-shipment-instructions"><?php echo wp_kses_post( wpautop( wptexturize( wp_kses_post( $provider->get_return_instructions() ) ) ) ); ?></div>
<?php endif; ?>

<?php if ( ! $shipment->has_status( 'delivered' ) && ( $label = $shipment->get_label() ) ) : ?>
	<p class="return-label-download-button-wrapper"><a class="woocommerce-button button btn<?php echo esc_attr( wc_stc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_stc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" target="_blank" target="_blank" href="<?php echo esc_url( $label->get_download_url() ); ?>"><?php echo esc_html_x( 'Download label', 'shipments', 'woocommerce-germanized' ); ?></a></p>
<?php endif; ?>

<?php
/**
 * This action is executed after printing the return shipment instructions.
 *
 * @param ReturnShipment $shipment The shipment instance.
 *
 * @package Vendidero/Shiptastic
 */
do_action( 'woocommerce_shiptastic_return_shipment_after_instructions', $shipment ); ?>
