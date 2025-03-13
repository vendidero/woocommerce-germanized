<?php
/**
 * Order shipments HTML for meta box.
 */
defined( 'ABSPATH' ) || exit;
?>
<tbody class="wc-stc-shipment-add-items-table" data-row="">
<tr>
	<td>
		<select id="wc-stc-shipment-add-items-select" name="item_id">
			<?php foreach ( $items as $item_id => $item_data ) : ?>
				<option data-max-quantity="<?php echo esc_attr( $item_data['max_quantity'] ); ?>" value="<?php echo esc_attr( $item_id ); ?>"><?php echo esc_html( $item_data['name'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</td>
	<td>
		<input id="wc-stc-shipment-add-items-quantity" type="number" step="1" min="0" max="9999" autocomplete="off" name="item_qty" placeholder="1" size="4" value="1" class="quantity" />
	</td>
</tr>
</tbody>
