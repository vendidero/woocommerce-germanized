<?php
/**
 * Order shipments HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */

defined( 'ABSPATH' ) || exit;

?>

<span class="item-count">
    <?php if ( ( $shippable_item_count = $shipment->get_shippable_item_count() ) > 0 ) :
        $item_count = $shipment->get_item_count();
        ?>
        <?php printf( _nx( '%d of %d item', '%d of %d items', $shippable_item_count, 'shipments', 'woocommerce-germanized' ), $item_count, $shippable_item_count ); ?>
    <?php endif; ?>
</span>
