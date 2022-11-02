<?php
/**
 * Shipment details
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/shipment/shipment-details.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Vendidero/Germanized/Shipments/Templates
 * @version 3.1.1
 */
use Vendidero\Germanized\Shipments\Shipment;

defined( 'ABSPATH' ) || exit;

$shipment = wc_gzd_get_shipment( $shipment_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited

if ( ! $shipment ) {
	return;
}

$order                 = $shipment->get_order(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$show_receiver_details = is_user_logged_in() && $order && $order->get_user_id() === get_current_user_id();
$show_tracking         = $show_receiver_details && $shipment->has_tracking();
$shipment_items        = $shipment->get_items();

if ( 'return' === $shipment->get_type() ) {
	if ( $provider = $shipment->get_shipping_provider_instance() ) {
		if ( $provider->hide_return_address() ) {
			$show_receiver_details = false;
		}
	}
}
?>
<section class="woocommerce-shipment-details">
	<?php
	/**
	 * This action is executed before printing the shipment detail table on the customer account page.
	 *
	 * @param Shipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	do_action( 'woocommerce_gzd_shipment_details_before_shipment_table', $shipment );
	?>

	<h2 class="woocommerce-shipment-details__title"><?php echo esc_html_x( 'Shipment details', 'shipments', 'woocommerce-germanized' ); ?></h2>

	<table class="woocommerce-table woocommerce-table--shipment-details shop_table shipment_details">

		<thead>
		<tr>
			<th class="woocommerce-table__product-name product-name"><?php echo esc_html_x( 'Product', 'shipments', 'woocommerce-germanized' ); ?></th>
			<th class="woocommerce-table__product-table product-quantity"><?php echo esc_html_x( 'Quantity', 'shipments', 'woocommerce-germanized' ); ?></th>
		</tr>
		</thead>

		<tbody>
		<?php
		/**
		 * This action is executed before printing the shipment table items on the customer account page.
		 *
		 * @param Shipment $shipment The shipment instance.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_shipment_details_before_shipment_table_items', $shipment );

		foreach ( $shipment_items as $item_id => $item ) {
			$product = $item->get_product();

			wc_get_template(
				'shipment/shipment-details-item.php',
				array(
					'shipment' => $shipment,
					'item_id'  => $item_id,
					'item'     => $item,
					'product'  => $product,
				)
			);
		}

		/**
		 * This action is executed after printing the shipment table items on the customer account page.
		 *
		 * @param Shipment $shipment The shipment instance.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_shipment_details_after_shipment_table_items', $shipment );
		?>
		</tbody>
	</table>

	<?php
	/**
	 * This action is executed after printing the shipment detail table on the customer account page.
	 *
	 * @param Shipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	do_action( 'woocommerce_gzd_shipment_details_after_shipment_table', $shipment );
	?>
</section>

<?php
if ( $show_receiver_details ) {
	wc_get_template( 'shipment/shipment-details-address.php', array( 'shipment' => $shipment ) );
}

if ( $show_tracking ) {
	wc_get_template( 'shipment/shipment-details-tracking.php', array( 'shipment' => $shipment ) );
}
