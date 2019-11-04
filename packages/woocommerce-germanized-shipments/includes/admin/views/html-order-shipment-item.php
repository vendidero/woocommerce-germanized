<?php
/**
 * Order shipments HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="shipment-item" data-id="<?php echo esc_attr( $item->get_id() ); ?>">
    <div class="columns">
        <div class="column col-6 shipment-item-name">
            <?php echo wp_kses_post( $item->get_name() ); ?>
        </div>
        <div class="column col-3 shipment-item-quantity">
            <input type="number" size="6" step="1" name="shipment_item[<?php echo esc_attr( $shipment->get_id() ); ?>][quantity][<?php echo esc_attr( $item->get_id() ); ?>]" class="item-quantity" data-original-value="<?php echo esc_attr( $item->get_quantity() ); ?>" value="<?php echo esc_attr( $item->get_quantity() ); ?>" />
        </div>
        <div class="column col-3 shipment-item-action">
            <a class="remove-shipment-item delete" data-delete="<?php echo esc_attr( $item->get_id() ); ?>" href="#"><?php echo _x( 'Delete', 'shipments', 'woocommerce-germanized' ); ?></a>
        </div>
    </div>

    <input type="hidden" name="shipment_item[<?php echo esc_attr( $shipment->get_id() ); ?>][order_item_id][<?php echo esc_attr( $item->get_id() ); ?>]" value="<?php echo esc_attr( $item->get_order_item_id() ); ?>" />
</div>
