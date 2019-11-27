<?php
/**
 * Order shipments HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */

use Vendidero\Germanized\Shipments\Order;

defined( 'ABSPATH' ) || exit;

$active_shipment = isset( $active_shipment ) ? $active_shipment : false;
?>

<div id="order-shipments" class="germanized-shipments">
    <div id="panel-order-shipments" class="<?php echo ( $order_shipment->needs_shipping() ? 'needs-shipments' : '' ); ?>">

        <div class="panel-title title-spread panel-inner">
            <h2 class="order-shipments-title"><?php echo _x( 'Shipments', 'shipments', 'woocommerce-germanized' ); ?></h2>
            <span class="order-shipping-status status-<?php echo esc_attr( $order_shipment->get_shipping_status() ); ?>"><?php echo wc_gzd_get_shipment_order_shipping_status_name( $order_shipment->get_shipping_status() ); ?></span>
        </div>

        <div class="notice-wrapper panel-inner"></div>

        <div id="order-shipments-list" class="panel-inner">
            <?php foreach( $order_shipment->get_simple_shipments() as $shipment ) :
                $is_active = ( $active_shipment && $shipment->get_id() === $active_shipment ) ? true : false;

                include 'html-order-shipment.php'; ?>
            <?php endforeach; ?>
        </div>

        <div class="panel-footer panel-inner">
            <div class="order-shipments-actions">
                <div class="order-shipment-add">
                    <a class="button button-secondary add-shipment" id="order-shipment-add" href="#"><?php echo _x( 'Add shipment', 'shipments', 'woocommerce-germanized' ); ?></a>
                </div>

                <div class="order-shipment-save">
                    <button id="order-shipments-save" class="button button-primary" type="submit"><?php echo _x( 'Save', 'shipments', 'woocommerce-germanized' ); ?></button>
                </div>

	            <?php
	            /**
	             * Action that fires in the action container for Shipments of a specific order.
	             *
	             * @param Order $order_shipment The shipment order object.
	             *
	             * @since 3.0.0
                 * @package Vendidero/Germanized/Shipments
	             */
                do_action( 'woocommerce_gzd_shipments_meta_box_actions', $order_shipment ); ?>
            </div>
        </div>
    </div>
</div>
