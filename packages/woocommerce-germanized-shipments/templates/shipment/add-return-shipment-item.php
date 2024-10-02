<?php
/**
 * Shipment return item
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/shipment/shipment-return-item.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Vendidero/Germanized/Shipments/Templates
 * @version 3.0.2
 */
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ShipmentItem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<tr class="<?php echo esc_attr( 'woocommerce-table__line-item return_shipment_item' ); ?>">

	<td class="woocommerce-table__product-select product-select">
		<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="items[]" type="checkbox" id="item-<?php echo esc_attr( $order_item_id ); ?>-add-return" value="<?php echo esc_attr( $order_item_id ); ?>" />
	</td>

	<td class="woocommerce-table__product-name product-name">
		<?php
		$product    = $item->get_product();
		$is_visible = $product && $product->is_visible();
		$item_sku   = $item->get_sku();

		/** This filter is documented in templates/myaccount/shipment/shipment-details-item.php */
		$product_permalink = apply_filters( 'woocommerce_gzd_shipment_item_permalink', $is_visible ? $product->get_permalink() : '', $item, $order );

		/** This filter is documented in templates/emails/email-shipment-items.php */
		echo apply_filters( 'woocommerce_gzd_shipment_item_name', ( $product_permalink ? sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $item->get_name() ) : $item->get_name() ) . ( ! empty( $item_sku ) ? ' <small>(' . esc_html( $item_sku ) . ')</small>' : '' ), $item, $is_visible ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</td>

	<td class="woocommerce-table__product-return-reason product-return-reason">
		<select name="item[<?php echo esc_attr( $order_item_id ); ?>][reason]" id="item-<?php echo esc_attr( $order_item_id ); ?>-return_reason">
			<option value="">
				<?php
				/**
				 * This filter may be used to decice whether customers may skip
				 * choosing a return reason or not.
				 *
				 * @param boolean      $allow_empty Whether to allow empty return reasons or not..
				 * @param WC_Order     $order The order instance.
				 *
				 * @since 3.1.0
				 * @package Vendidero/Germanized/Shipments
				 */
				if ( wc_gzd_allow_customer_return_empty_return_reason( $order ) ) :
					?>
					<?php echo esc_html_x( 'None', 'shipments return reason', 'woocommerce-germanized' ); ?>
				<?php else : ?>
					<?php echo esc_html_x( 'Please choose', 'shipments return reason', 'woocommerce-germanized' ); ?>
				<?php endif; ?>
			</option>

			<?php foreach ( wc_gzd_get_return_shipment_reasons( $item->get_order_item() ) as $reason ) : ?>
				<option value="<?php echo esc_attr( $reason->get_code() ); ?>"><?php echo esc_html( $reason->get_reason() ); ?></option>
			<?php endforeach; ?>
		</select>
	</td>

	<td class="woocommerce-table__product-quantity product-quantity">
		<?php
		if ( 1 === $max_quantity ) :
			?>
			1<?php endif; ?>

		<?php
		woocommerce_quantity_input(
			array(
				'input_name'  => 'item[' . esc_attr( $order_item_id ) . '][quantity]',
				'input_value' => 1,
				'max_value'   => $max_quantity,
				'min_value'   => 1,
			),
			$item->get_product()
		);
		?>
	</td>
</tr>
