<?php
/**
 * Order shipments HTML for meta box.
 */
defined( 'ABSPATH' ) || exit;

$active_shipment = isset( $active_shipment ) ? $active_shipment : false;
$returns         = $order_shipment->get_return_shipments();
?>

<div id="order-shipments-list" class="panel-inner">
	<?php
	foreach ( $order_shipment->get_simple_shipments() as $shipment ) :
		$is_active = ( $active_shipment && $shipment->get_id() === $active_shipment ) ? true : false;

		include 'html-order-shipment.php';
		?>
	<?php endforeach; ?>

	<div class="panel-title panel-title-inner title-spread panel-inner panel-order-return-title <?php echo ( empty( $returns ) ? 'hide-default' : '' ); ?>">
		<h2 class="order-returns-title"><?php echo esc_html_x( 'Returns', 'shipments', 'woocommerce-germanized' ); ?></h2>
		<mark class="order-return-status status-<?php echo esc_attr( $order_shipment->get_return_status() ); ?>"><span><?php echo esc_html( wc_stc_get_shipment_order_return_status_name( $order_shipment->get_return_status() ) ); ?></span></mark>
	</div>

	<?php if ( ! empty( $returns ) ) : ?>
		<?php
		foreach ( $returns as $shipment ) :
			$is_active = ( $active_shipment && $shipment->get_id() === $active_shipment ) ? true : false;
			include 'html-order-shipment.php';
			?>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
