<?php
/**
 * Order shipments HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */

defined( 'ABSPATH' ) || exit;

$available_packaging    = $shipment->get_available_packaging();
$current_packaging_id   = $shipment->get_packaging_id();
$default_packaging_id   = \Vendidero\Germanized\Shipments\Package::get_setting( 'default_packaging' );
$default_packaging      = ! empty( $default_packaging_id ) ? wc_gzd_get_packaging( $default_packaging_id ) : false;
$default_exists_in_list = false;
?>
<select class="shipment-packaging-select" id="shipment-packaging-<?php echo esc_attr( $shipment->get_id() ); ?>" name="shipment_packaging_id[<?php echo esc_attr( $shipment->get_id() ); ?>]">
	<option value=""><?php _ex( 'None', 'shipments-packaging', 'woocommerce-germanized' ); ?></option>
	<?php foreach( $available_packaging as $packaging ) :
        if ( $packaging->get_id() == $default_packaging_id ) {
            $default_exists_in_list = true;
        }
        ?>
		<option
                data-width="<?php echo esc_attr( wc_format_localized_decimal( wc_get_dimension( $packaging->get_width(), $shipment->get_dimension_unit(), wc_gzd_get_packaging_dimension_unit() ) ) ); ?>"
                data-length="<?php echo esc_attr( wc_format_localized_decimal( wc_get_dimension( $packaging->get_length(), $shipment->get_dimension_unit(), wc_gzd_get_packaging_dimension_unit() ) ) ); ?>"
                data-height="<?php echo esc_attr( wc_format_localized_decimal( wc_get_dimension( $packaging->get_height(), $shipment->get_dimension_unit(), wc_gzd_get_packaging_dimension_unit() ) ) ); ?>"
                value="<?php echo esc_attr( $packaging->get_id() ); ?>" <?php selected( $packaging->get_id(), $shipment->get_packaging_id(), true ); ?>
        ><?php echo $packaging->get_title(); ?></option>
	<?php endforeach; ?>

    <?php if ( ! $default_exists_in_list && $default_packaging ) : ?>
        <option value="<?php echo esc_attr( $default_packaging->get_id() ); ?>" <?php selected( $default_packaging->get_id(), $shipment->get_packaging_id(), true ); ?>><?php echo $default_packaging->get_title(); ?> (<?php _ex( 'Does not fit', 'shipments', 'woocommerce-germanized' ); ?>)</option>
    <?php endif; ?>
</select>
