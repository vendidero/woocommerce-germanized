<?php
/**
 * Order shipments HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */

use Vendidero\Germanized\Shipments\Order;

defined( 'ABSPATH' ) || exit;

$active_shipment = isset( $active_shipment ) ? $active_shipment : false;
?>

<div id="order-shipments" class="germanized-shipments">
	<div id="panel-order-shipments" class="<?php echo ( $order_shipment->needs_shipping() ? 'needs-shipments' : '' ); ?> <?php echo ( $order_shipment->needs_return() ? 'needs-returns' : '' ); ?>">

		<div class="panel-title title-spread panel-inner">
			<h2 class="order-shipments-title"><?php echo esc_html_x( 'Shipments', 'shipments', 'woocommerce-germanized' ); ?></h2>
			<span class="order-shipping-status status-<?php echo esc_attr( $order_shipment->get_shipping_status() ); ?>"><?php echo esc_html( wc_gzd_get_shipment_order_shipping_status_name( $order_shipment->get_shipping_status() ) ); ?></span>
		</div>

		<div class="notice-wrapper panel-inner"></div>

		<?php require 'html-order-shipment-list.php'; ?>

		<div class="panel-footer panel-inner">
			<div class="order-shipments-actions">

				<script type="text/template" id="tmpl-wc-gzd-modal-add-shipment-return">
					<div class="wc-backbone-modal">
						<div class="wc-backbone-modal-content">
							<section class="wc-backbone-modal-main" role="main">
								<header class="wc-backbone-modal-header">
									<h1><?php echo esc_html_x( 'Add Return', 'shipments', 'woocommerce-germanized' ); ?></h1>
									<button class="modal-close modal-close-link dashicons dashicons-no-alt">
										<span class="screen-reader-text">Close modal panel</span>
									</button>
								</header>
								<article>
									<form action="" method="post">
										<table class="widefat">
											<thead>
											<tr>
												<th><?php echo esc_html_x( 'Item', 'shipments', 'woocommerce-germanized' ); ?></th>
												<th><?php echo esc_html_x( 'Quantity', 'shipments', 'woocommerce-germanized' ); ?></th>
											</tr>
											</thead>
											<tbody id="wc-gzd-return-shipment-items" data-row=""></tbody>
										</table>
									</form>
								</article>
								<footer>
									<div class="inner">
										<button id="btn-ok" class="button button-primary button-large"><?php echo esc_html_x( 'Add', 'shipments', 'woocommerce-germanized' ); ?></button>
									</div>
								</footer>
							</section>
						</div>
					</div>
					<div class="wc-backbone-modal-backdrop modal-close"></div>
				</script>

				<div class="shipment-actions-left">
					<div class="order-shipment-add">
						<a class="button button-secondary add-shipment" id="order-shipment-add" href="#"><?php echo esc_html_x( 'Add shipment', 'shipments', 'woocommerce-germanized' ); ?></a>
					</div>

					<div class="order-return-shipment-add">
						<a class="button button-secondary add-return-shipment" id="order-return-shipment-add" href="#"><?php echo esc_html_x( 'Add return', 'shipments', 'woocommerce-germanized' ); ?></a>
					</div>
				</div>

				<div class="shipment-actions-right">
					<div class="order-shipment-save">
						<button id="order-shipments-save" class="button button-primary" type="submit"><?php echo esc_html_x( 'Save', 'shipments', 'woocommerce-germanized' ); ?></button>
					</div>
				</div>

				<?php
				/**
				 * Action that fires in the action container for Shipments of a specific order.
				 *
				 * @param Order $order_shipment The shipment order object.
				 *
				 * @since 3.0.0
				 * @package Vendidero/Germanized/Shipments
				 */
				do_action( 'woocommerce_gzd_shipments_meta_box_actions', $order_shipment );
				?>
			</div>
		</div>
	</div>
</div>
