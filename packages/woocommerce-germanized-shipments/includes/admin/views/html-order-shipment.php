<?php
/**
 * Order shipments HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */

defined( 'ABSPATH' ) || exit;

$is_active = ( isset( $is_active ) ) ? $is_active : false;
?>

<div id="shipment-<?php echo esc_attr( $shipment->get_id() ); ?>" class="order-shipment shipment-<?php echo esc_attr( $shipment->get_type() ); ?> <?php echo ( $is_active ? 'active' : '' ); ?> <?php echo ( $shipment->is_editable() ? 'is-editable' : '' ); ?> <?php echo ( $shipment->needs_items() ? 'needs-items' : '' ); ?>" data-shipment="<?php echo esc_attr( $shipment->get_id() ); ?>">
	<div class="shipment-header title-spread">
		<div class="left">
			<h3><?php printf( esc_html_x( '%1$s #%2$s', 'shipment admin title', 'woocommerce-germanized' ), esc_html( wc_gzd_get_shipment_label_title( $shipment->get_type() ) ), esc_html( $shipment->get_shipment_number() ) ); ?></h3>
			<span class="shipment-status shipment-type-<?php echo esc_attr( $shipment->get_type() ); ?>-status status-<?php echo esc_attr( $shipment->get_status() ); ?>"><?php echo esc_html( wc_gzd_get_shipment_status_name( $shipment->get_status() ) ); ?></span>
		</div>

		<div class="right">
			<?php require 'html-order-shipment-item-count.php'; ?>
			<button type="button" class="handlediv">
				<span class="toggle-indicator" aria-hidden="true"></span>
			</button>
		</div>
	</div>

	<div class="shipment-content-wrapper">
		<?php require 'html-order-shipment-content.php'; ?>
	</div>
</div>
