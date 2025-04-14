<?php
/**
 * The Template for adding return shipments by the customer.
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/myaccount/add-return-shipment.php.
 *
 * HOWEVER, on occasion Shiptastic will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/Shiptastic/Templates
 * @version 4.3.0
 */
defined( 'ABSPATH' ) || exit;
?>
<h2 class="woocommerce-add-return-shipment__title"><?php echo esc_html_x( 'New return request', 'shipments', 'woocommerce-germanized' ); ?></h2>

<p><?php echo esc_html_x( 'Please select one or more items to return.', 'shipments', 'woocommerce-germanized' ); ?></p>

<?php if ( wc_stc_customer_return_needs_manual_confirmation( $order ) ) : ?>
	<p><?php echo esc_html_x( 'After submitting your return request we will review it and notify you via email about the next steps.', 'shipments', 'woocommerce-germanized' ); ?></p>
<?php else : ?>
	<p><?php echo esc_html_x( 'After submitting your return request you\'ll receive an email with further information to the return process.', 'shipments', 'woocommerce-germanized' ); ?></p>
<?php endif; ?>

<form id="add_return_shipment" method="post">
	<table class="woocommerce-table woocommerce-table--shipment-details shop_table add_return_shipment_table">
		<thead>
		<tr>
			<th class="woocommerce-table__product-select product-select"></th>
			<th class="woocommerce-table__product-name product-name"><?php echo esc_html_x( 'Product', 'shipments', 'woocommerce-germanized' ); ?></th>
			<th class="woocommerce-table__product-table product-reason"><?php echo esc_html_x( 'Reason', 'shipments', 'woocommerce-germanized' ); ?></th>
			<th class="woocommerce-table__product-table product-quantity"><?php echo esc_html_x( 'Quantity', 'shipments', 'woocommerce-germanized' ); ?></th>
		</tr>
		</thead>

		<tbody>
		<?php
		/**
		 * This action is executed before printing the add return shipment table on the customer account page.
		 *
		 * @param WC_Order $order The order instance.
		 *
		 * @package Vendidero/Shiptastic
		 */
		do_action( 'woocommerce_shiptastic_add_return_shipment_details_before_shipment_table_items', $order );

		foreach ( $shipment_order->get_selectable_items_for_return() as $order_item_id => $item_data ) {
			wc_get_template(
				'shipment/add-return-shipment-item.php',
				array(
					'item'          => $shipment_order->get_simple_shipment_item( $order_item_id ),
					'order_item_id' => $order_item_id,
					'order'         => $order,
					'max_quantity'  => $item_data['max_quantity'],
				)
			);
		}

		/**
		 * This action is executed after printing the add return shipment table items on the customer account page.
		 *
		 * @param WC_Order $order The order instance.
		 *
		 * @package Vendidero/Shiptastic
		 */
		do_action( 'woocommerce_shiptastic_add_return_shipment_details_after_shipment_table_items', $order );
		?>
		</tbody>
	</table>

	<?php
	/**
	 * This action is executed after printing the return shipment table on the customer account page.
	 *
	 * @param WC_Order $order The order instance.
	 *
	 * @package Vendidero/Shiptastic
	 */
	do_action( 'woocommerce_shiptastic_add_return_shipment_details_after_shipment_table', $order );
	?>

	<p>
		<?php wp_nonce_field( 'add_return_shipment', 'add-return-shipment-nonce' ); ?>
		<button type="submit" class="woocommerce-Button button<?php echo esc_attr( wc_stc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_stc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="add_return_shipment" value="<?php echo esc_attr_x( 'Send request', 'shipments', 'woocommerce-germanized' ); ?>"><?php echo esc_attr_x( 'Send request', 'shipments', 'woocommerce-germanized' ); ?></button>

		<input type="hidden" name="action" value="shiptastic_add_return_shipment" />
		<input type="hidden" name="key" value="<?php echo esc_attr( wc_stc_get_customer_order_return_request_key() ); ?>" />
		<input type="hidden" name="order_id" value="<?php echo esc_attr( $order_id ); ?>" />
	</p>
</form>
<?php
/**
 * This action is executed after printing the add return shipment form
 * on the customer account page.
 *
 * @param int $order_id The order id.
 *
 * @package Vendidero/Shiptastic
 */
do_action( 'woocommerce_shiptastic_add_return_shipment', $order_id ); ?>
