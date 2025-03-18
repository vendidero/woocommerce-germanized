<?php
/**
 * Shipment Item Details
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/shipment/shipment-details-item.php.
 *
 * HOWEVER, on occasion Shiptastic will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/Shiptastic/Templates
 * @version 3.0.1
 */
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShipmentItem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This filter may adjust whether to show a certain shipment item within the shipment
 * detail table on customer account page or not.
 *
 * @param boolean      $show Whether to show or hide the item.
 * @param ShipmentItem $item The shipment item instance.
 *
 * @package Vendidero/Shiptastic
 */
if ( ! apply_filters( 'woocommerce_shiptastic_shipment_item_visible', true, $item ) ) {
	return;
}

/**
 * This filter may adjust the item class added to the shipment details table row
 * on the customer account page.
 *
 * @param ShipmentItem $item The shipment item instance.
 * @param Shipment     $shipment The shipment instance.
 *
 * @package Vendidero/Shiptastic
 */
$item_class = apply_filters( 'woocommerce_shiptastic_shipment_item_class', 'woocommerce-table__line-item shipment_item ' . ( $item->get_item_parent_id() > 0 ? 'shipment_item-is-child' : '' ) . ( $item->has_children() ? 'shipment_item-is-parent' : '' ), $item, $shipment );
?>
<tr class="<?php echo esc_attr( $item_class ); ?>">
	<td class="woocommerce-table__product-name product-name">
		<?php
		$is_visible = $product && $product->is_visible();
		$item_sku   = $item->get_sku();

		/**
		 * This filter may adjust the shipment item permalink on the customer account page.
		 *
		 * @param string                                       $permalink The permalink.
		 * @param ShipmentItem $item The shipment item instance.
		 * @param Shipment     $shipment The shipment instance.
		 *
		 * @package Vendidero/Shiptastic
		 */
		$product_permalink = apply_filters( 'woocommerce_shiptastic_shipment_item_permalink', $is_visible ? $product->get_permalink() : '', $item, $shipment );

		/** This filter is documented in templates/emails/email-shipment-items.php */
		echo apply_filters( 'woocommerce_shiptastic_shipment_item_name', ( $product_permalink ? sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $item->get_name() ) : $item->get_name() ) . ( ! empty( $item_sku ) ? ' <small>(' . esc_html( $item_sku ) . ')</small>' : '' ), $item, $is_visible ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</td>

	<td class="woocommerce-table__product-quantity product-quantity">
		<?php

		$qty         = $item->get_quantity();
		$qty_display = esc_html( $qty );

		/**
		 * This filter may adjust the shipment item quantity HTML on the customer account page.
		 *
		 * @param string                                       $html The HTML output.
		 * @param ShipmentItem $item The shipment item instance.
		 *
		 * @package Vendidero/Shiptastic
		 */
		echo wp_kses_post( apply_filters( 'woocommerce_shiptastic_shipment_item_quantity_html', ' <strong class="product-quantity">' . sprintf( '&times; %s', $qty_display ) . '</strong>', $item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</td>

</tr>
