<?php
/**
 * Order shipments HTML for meta box.
 */
defined( 'ABSPATH' ) || exit;
?>
<tbody id="wc-stc-return-shipment-items" data-row="">
<?php
foreach ( $order_shipment->get_selectable_items_for_return() as $item_id => $item_data ) :
	?>
	<tr>
		<td><?php echo esc_attr( $item_data['name'] ); ?></td>
		<td><input class="wc-stc-shipment-add-return-item-quantity quantity" type="number" step="1" min="0" max="<?php echo esc_attr( $item_data['max_quantity'] ); ?>" value="<?php echo esc_attr( $item_data['max_quantity'] ); ?>" autocomplete="off" id="return-item-<?php echo esc_attr( $item_id ); ?>" name="return_item[<?php echo esc_attr( $item_id ); ?>]" placeholder="1" size="4" /></td>
	</tr>
<?php endforeach; ?>
</tbody>
