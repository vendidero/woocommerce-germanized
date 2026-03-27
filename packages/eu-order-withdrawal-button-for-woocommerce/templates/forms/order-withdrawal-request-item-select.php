<?php
/**
 * Cancel order request item select form.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/forms/order-withdrawal-request-item-select.php.
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
	exit; // Exit if accessed directly.
}

$cancelable_items      = eu_owb_get_withdrawable_order_items( $order );
$manually_select_items = apply_filters( 'eu_owb_woocommerce_manually_select_items_default', $manually_select_items );
?>
<p class="eu-owb-woocommerce-withdrawal-order-details">
<?php
echo wp_kses_post(
	apply_filters(
		'eu_owb_woocommerce_order_details_status',
		sprintf(
		/* translators: 1: order number 2: order date */
			esc_html_x( 'Order #%1$s was placed on %2$s.', 'owb', 'woocommerce-germanized' ),
			'<mark class="order-number">' . $order->get_order_number() . '</mark>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'<mark class="order-date">' . wc_format_datetime( $order->get_date_created() ) . '</mark>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		),
		$order
	)
);
?>
</p>
<?php if ( eu_owb_order_supports_partial_withdrawal( $order ) ) : ?>
	<div class="form-row form-row-full">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="manually-select-items">
			<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="manually_select_items" <?php checked( $manually_select_items, true ); // WPCS: input var ok, csrf ok. ?> id="manually-select-items" />
			<span class="eu-owb-woocommerce-select-certain-items-text"><?php echo esc_html_x( 'I want to select the items to cancel manually.', 'owb', 'woocommerce-germanized' ); ?></span>
		</label>
	</div>

	<table class="woocommerce-table woocommerce-table--order-withdrawal-request-items shop_table order-withdrawal-request-items-table <?php echo esc_attr( ! $manually_select_items ? 'hidden' : '' ); ?>">
		<thead>
		<tr>
			<th class="woocommerce-table__product-select product-select">
				<input class="woocommerce-form__input woocommerce-form__input-checkbox order-withdrawal-request-item-checkbox-select-all" id="select-all-items" type="checkbox" />
				<label for="select-all-items">
					<span class="screen-reader-text"><?php echo esc_html_x( 'Select all', 'owb', 'woocommerce-germanized' ); ?></span>
				</label>
			</th>
			<th class="woocommerce-table__product-name product-name"><?php echo esc_html_x( 'Product', 'owb', 'woocommerce-germanized' ); ?></th>
			<th class="woocommerce-table__product-table product-quantity"><?php echo esc_html_x( 'Quantity', 'owb', 'woocommerce-germanized' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $cancelable_items as $order_item_id => $item_data ) {
			wc_get_template(
				'forms/order-withdrawal-request-item.php',
				array(
					'item'     => $item_data['item'],
					'quantity' => $item_data['quantity'],
					'order'    => $order,
				)
			);
		}
		?>
		</tbody>
	</table>
<?php endif; ?>