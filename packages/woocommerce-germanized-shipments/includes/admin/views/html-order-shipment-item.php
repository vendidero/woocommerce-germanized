<?php
/**
 * Order shipments HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */

use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ShipmentItem;

defined( 'ABSPATH' ) || exit;

?>

<div class="shipment-item" data-id="<?php echo esc_attr( $item->get_id() ); ?>">
    <div class="columns">
        <div class="column col-7 shipment-item-name">
            <?php echo wp_kses_post( $item->get_name() ); ?>

            <?php
            /**
             * Action that fires after outputting the shipment item name in admin meta box.
             *
             * @param integer                                      $item_id The shipment item id.
             * @param ShipmentItem $shipment_item The shipment item instance.
             * @param Shipment     $shipment The shipment instance.
             *
             * @since 3.0.6
             * @package Vendidero/Germanized/Shipments
             */
            do_action( 'woocommerce_gzd_shipments_meta_box_shipment_item_after_name', $item->get_id(), $item, $shipment ); ?>
        </div>
        <div class="column col-2 shipment-item-quantity">
            <input type="number" size="6" step="1" name="shipment_item[<?php echo esc_attr( $shipment->get_id() ); ?>][quantity][<?php echo esc_attr( $item->get_id() ); ?>]" class="item-quantity" data-original-value="<?php echo esc_attr( $item->get_quantity() ); ?>" value="<?php echo esc_attr( $item->get_quantity() ); ?>" />
        </div>
        <div class="column col-3 shipment-item-action">
            <a class="remove-shipment-item delete" data-delete="<?php echo esc_attr( $item->get_id() ); ?>" href="#"><?php echo _x( 'Delete', 'shipments', 'woocommerce-germanized' ); ?></a>
        </div>
    </div>

    <input type="hidden" name="shipment_item[<?php echo esc_attr( $shipment->get_id() ); ?>][order_item_id][<?php echo esc_attr( $item->get_id() ); ?>]" value="<?php echo esc_attr( $item->get_order_item_id() ); ?>" />
</div>
