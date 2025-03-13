<?php
/**
 * Order shipments HTML for meta box.
 */
use Vendidero\Shiptastic\Admin\Admin;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShipmentItem;

defined( 'ABSPATH' ) || exit;
?>

<div class="shipment-item <?php echo esc_attr( $item->get_item_parent_id() > 0 ? 'shipment-item-is-child shipment-item-parent-' . $item->get_item_parent_id() : '' ); ?> <?php echo esc_attr( $item->has_children() ? 'shipment-item-is-parent' : '' ); ?>" data-id="<?php echo esc_attr( $item->get_id() ); ?>" id="shipment-item-<?php echo esc_attr( $item->get_id() ); ?>">
	<div class="columns">
		<?php foreach ( Admin::get_admin_shipment_item_columns( $shipment ) as $column_name => $column ) : ?>
			<div class="column col-<?php echo esc_attr( $column['size'] ); ?> shipment-item-<?php echo esc_attr( $column_name ); ?>">
				<?php if ( 'name' === $column_name ) : ?>
					<?php echo wp_kses_post( $item->get_name() ); ?> <?php echo ( $item->get_sku() ? '<small>(' . esc_html( $item->get_sku() ) . ')</small>' : '' ); ?>
				<?php elseif ( 'return_reason' === $column_name ) : ?>
					<select class="item-return-reason-code <?php echo ( $item->is_readonly() ? 'disabled' : '' ); ?>" id="shipment-item-return-reason-code-<?php echo esc_attr( $item->get_id() ); ?>" name="shipment_item[<?php echo esc_attr( $shipment->get_id() ); ?>][return_reason_code][<?php echo esc_attr( $item->get_id() ); ?>]" <?php echo ( $item->is_readonly() ? 'disabled' : '' ); ?>>
						<option value=""><?php echo esc_html_x( 'None', 'shipments return reason', 'woocommerce-germanized' ); ?></option>
						<?php foreach ( wc_stc_get_return_shipment_reasons( $item->get_order_item() ) as $reason ) : ?>
							<option value="<?php echo esc_attr( $reason->get_code() ); ?>" <?php selected( $reason->get_code(), $item->get_return_reason_code() ); ?>><?php echo esc_html( $reason->get_reason() ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php elseif ( 'quantity' === $column_name ) : ?>
					<input type="number" size="6" step="1" name="shipment_item[<?php echo esc_attr( $shipment->get_id() ); ?>][quantity][<?php echo esc_attr( $item->get_id() ); ?>]" class="item-quantity" <?php echo ( $item->is_readonly() ? 'readonly' : '' ); ?> data-original-value="<?php echo esc_attr( $item->get_quantity() ); ?>" value="<?php echo esc_attr( $item->get_quantity() ); ?>" />
				<?php elseif ( 'action' === $column_name ) : ?>
					<?php if ( ! $item->is_readonly() ) : ?>
						<?php
						echo wc_stc_render_shipment_action_buttons( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							array(
								'delete' => array(
									'classes'           => 'remove-shipment-item',
									'action'            => 'delete',
									'name'              => _x( 'Delete item', 'shipments', 'woocommerce-germanized' ),
									'custom_attributes' => array(
										'data-delete' => $item->get_id(),
									),
								),
							)
						);
						?>
					<?php endif; ?>
				<?php endif; ?>

				<?php
				/**
				 * Action that fires after outputting a shipment item column in admin meta box.
				 *
				 * The dynamic portion of this hook `$column_name` refers to the column name e.g. name or quantity.
				 *
				 * Example hook name: woocommerce_shiptastic_meta_box_shipment_item_after_name
				 *
				 * @param integer      $item_id The shipment item id.
				 * @param ShipmentItem $shipment_item The shipment item instance.
				 * @param Shipment     $shipment The shipment instance.
				 * @param string       $column_name The column name.
				 *
				 * @package Vendidero/Shiptastic
				 */
				do_action( "woocommerce_shiptastic_meta_box_shipment_item_after_{$column_name}", $item->get_id(), $item, $shipment, $column_name );
				?>
			</div>
		<?php endforeach; ?>
	</div>
	<input type="hidden" name="shipment_item[<?php echo esc_attr( $shipment->get_id() ); ?>][order_item_id][<?php echo esc_attr( $item->get_id() ); ?>]" value="<?php echo esc_attr( $item->get_order_item_id() ); ?>" />
</div>
