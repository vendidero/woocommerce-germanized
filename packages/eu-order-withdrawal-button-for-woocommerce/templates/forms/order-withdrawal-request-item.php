<?php
/**
 * Cancel order request item.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/forms/order-withdrawal-request-item.php.
 *
 * HOWEVER, on occasion EU OWB will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/OrderWithdrawalButton/Templates
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<tr class="<?php echo esc_attr( 'woocommerce-table__line-item order-withdrawal-request-item' ); ?>">
	<td class="woocommerce-table__product-select product-select">
		<input class="woocommerce-form__input woocommerce-form__input-checkbox order-withdrawal-request-item-checkbox" name="items[]" type="checkbox" id="item-<?php echo esc_attr( $item->get_id() ); ?>" value="<?php echo esc_attr( $item->get_id() ); ?>" />
	</td>

	<td class="woocommerce-table__product-name product-name">
		<?php
		$product    = $item->get_product();
		$is_visible = $product && $product->is_visible();
		$item_sku   = $product ? $product->get_sku() : '';

		$product_permalink = apply_filters( 'eu_owb_woocommerce_order_item_product_permalink', $is_visible ? $product->get_permalink() : '', $item, $order );

		echo wp_kses_post( apply_filters( 'eu_owb_woocommerce_order_item_name', ( $product_permalink ? sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $item->get_name() ) : $item->get_name() ) . ( ! empty( $item_sku ) ? ' <small>(' . esc_html( $item_sku ) . ')</small>' : '' ), $item, $is_visible ) );
		?>
	</td>

	<td class="woocommerce-table__product-quantity product-quantity">
		<?php
		if ( 1 === $quantity ) :
			?>
			1<?php endif; ?>

		<?php
		woocommerce_quantity_input(
			array(
				'input_name'  => 'item[' . esc_attr( $item->get_id() ) . '][quantity]',
				'input_value' => $quantity,
				'max_value'   => $quantity,
				'min_value'   => 1,
				'readonly'    => false,
			),
			$item->get_product()
		);
		?>
	</td>
</tr>
