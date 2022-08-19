<?php
/**
 * Order shipments HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */

defined( 'ABSPATH' ) || exit;
?>

<?php
foreach ( $order_shipment->get_available_items_for_return() as $item_id => $item_data ) :
	?>
	<tr>
		<td><?php echo esc_attr( $item_data['name'] ); ?></td>
		<td><input class="wc-gzd-shipment-add-return-item-quantity" type="number" step="1" min="0" max="<?php echo esc_attr( $item_data['max_quantity'] ); ?>" value="<?php echo esc_attr( $item_data['max_quantity'] ); ?>" autocomplete="off" name="return_item[<?php echo esc_attr( $item_id ); ?>]" placeholder="1" size="4" class="quantity" /></td>
	</tr>
<?php endforeach; ?>
