<?php
/**
 * Order shipments HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */

use Vendidero\Germanized\Shipments\Admin\Admin;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ShipmentItem;

defined( 'ABSPATH' ) || exit;
?>

<div class="shipment-item" data-id="<?php echo esc_attr( $item->get_id() ); ?>">
	<div class="columns">
		<?php foreach ( Admin::get_admin_shipment_item_columns( $shipment ) as $column_name => $column ) : ?>

			<div class="column col-<?php echo esc_attr( $column['size'] ); ?> shipment-item-<?php echo esc_attr( $column_name ); ?>">

				<?php if ( 'name' === $column_name ) : ?>

					<?php echo wp_kses_post( $item->get_name() ); ?> <?php echo ( $item->get_sku() ? '<small>(' . esc_html( $item->get_sku() ) . ')</small>' : '' ); ?>

				<?php elseif ( 'return_reason' === $column_name ) : ?>

					<select class="item-return-reason-code" id="shipment-item-return-reason-code-<?php echo esc_attr( $item->get_id() ); ?>" name="shipment_item[<?php echo esc_attr( $shipment->get_id() ); ?>][return_reason_code][<?php echo esc_attr( $item->get_id() ); ?>]">
						<option value=""><?php echo esc_html_x( 'None', 'shipments return reason', 'woocommerce-germanized' ); ?></option>

						<?php foreach ( wc_gzd_get_return_shipment_reasons( $item->get_order_item() ) as $reason ) : ?>
							<option value="<?php echo esc_attr( $reason->get_code() ); ?>" <?php selected( $reason->get_code(), $item->get_return_reason_code() ); ?>><?php echo esc_html( $reason->get_reason() ); ?></option>
						<?php endforeach; ?>
					</select>

				<?php elseif ( 'quantity' === $column_name ) : ?>

					<input type="number" size="6" step="1" name="shipment_item[<?php echo esc_attr( $shipment->get_id() ); ?>][quantity][<?php echo esc_attr( $item->get_id() ); ?>]" class="item-quantity" data-original-value="<?php echo esc_attr( $item->get_quantity() ); ?>" value="<?php echo esc_attr( $item->get_quantity() ); ?>" />

				<?php elseif ( 'action' === $column_name ) : ?>

					<a class="remove-shipment-item delete" data-delete="<?php echo esc_attr( $item->get_id() ); ?>" href="#"><?php echo esc_html_x( 'Delete', 'shipments', 'woocommerce-germanized' ); ?></a>

				<?php endif; ?>

				<?php
				/**
				 * Action that fires after outputting a shipment item column in admin meta box.
				 *
				 * The dynamic portion of this hook `$column_name` refers to the column name e.g. name or quantity.
				 *
				 * Example hook name: woocommerce_gzd_shipments_meta_box_shipment_item_after_name
				 *
				 * @param integer      $item_id The shipment item id.
				 * @param ShipmentItem $shipment_item The shipment item instance.
				 * @param Shipment     $shipment The shipment instance.
				 * @param string       $column_name The column name.
				 *
				 * @since 3.0.6
				 * @package Vendidero/Germanized/Shipments
				 */
				do_action( "woocommerce_gzd_shipments_meta_box_shipment_item_after_{$column_name}", $item->get_id(), $item, $shipment, $column_name );
				?>
			</div>

		<?php endforeach; ?>
	</div>

	<input type="hidden" name="shipment_item[<?php echo esc_attr( $shipment->get_id() ); ?>][order_item_id][<?php echo esc_attr( $item->get_id() ); ?>]" value="<?php echo esc_attr( $item->get_order_item_id() ); ?>" />
</div>
