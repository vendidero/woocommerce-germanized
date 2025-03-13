<?php
/**
 * Preview shipment HTML.
 */
defined( 'ABSPATH' ) || exit;

$order_shipment  = $shipment->get_order_shipment();
$total_shipments = $order_shipment ? $order_shipment->get_shipment_count( $shipment->get_type() ) : 1;
$position_number = $order_shipment ? $order_shipment->get_shipment_position_number( $shipment ) : 1;
?>
<div class="wc-stc-preview-shipment">
	<p class="shipment-summary">
		<?php printf( esc_html_x( '%1$s %2$d/%3$d to ', 'shipments', 'woocommerce-germanized' ), esc_html( wc_stc_get_shipment_label_title( $shipment->get_type() ) ), esc_html( $position_number ), esc_html( $total_shipments ) ); ?>
		<?php if ( ( $s_order = $shipment->get_order() ) && is_callable( array( $s_order, 'get_edit_order_url' ) ) ) : ?>
			<a href="<?php echo esc_url( $s_order->get_edit_order_url() ); ?>"><?php printf( esc_html_x( '#%1$s', 'shipments', 'woocommerce-germanized' ), esc_html( $s_order->get_order_number() ) ); ?></a>
		<?php else : ?>
			<?php printf( esc_html_x( '#%1$s', 'shipments', 'woocommerce-germanized' ), esc_html( $shipment->get_order_number() ) ); ?>
		<?php endif; ?>
	</p>

	<div class="wc-stc-preview-shipment-data columns">
		<div class="column col-6">
			<?php if ( 'return' === $shipment->get_type() ) : ?>
				<h2><?php echo esc_html_x( 'Return from', 'shipments', 'woocommerce-germanized' ); ?></h2>
				<address><?php echo wp_kses_post( $shipment->get_formatted_sender_address() ); ?></address>
			<?php else : ?>
				<h2><?php echo esc_html_x( 'Shipping to', 'shipments', 'woocommerce-germanized' ); ?></h2>
				<address><?php echo wp_kses_post( $shipment->get_formatted_address() ); ?></address>
			<?php endif; ?>
			<?php
			$provider    = $shipment->get_shipping_provider();
			$tracking_id = $shipment->get_tracking_id();

			if ( ! empty( $provider ) && ! empty( $tracking_id ) ) :
				?>
				<p>
					<span class="shipment-shipping-provider"><?php printf( esc_html_x( 'via %s', 'shipments', 'woocommerce-germanized' ), wp_kses_post( wc_stc_get_shipping_provider_title( $provider ) ) ); ?></span>

					<?php if ( $shipment->has_tracking() && ( $tracking_url = $shipment->get_tracking_url() ) ) : ?>
						<a class="shipment-tracking-id" target="_blank" href="<?php echo esc_url( $tracking_url ); ?>"><?php echo esc_html( $tracking_id ); ?></a>
					<?php else : ?>
						<span class="shipment-tracking-id"><?php echo esc_html( $tracking_id ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php do_action( 'woocommerce_shiptastic_preview_after_column_shipping_to', $shipment ); ?>
		</div>
		<div class="column col-6">
			<h2><?php echo esc_html_x( 'Package', 'shipments', 'woocommerce-germanized' ); ?></h2>
			<p>
				<?php if ( $packaging = $shipment->get_packaging() ) : ?>
					<?php echo wp_kses_post( $packaging->get_description() ); ?><br/>
				<?php endif; ?>

				<?php echo wp_kses_post( $shipment->get_formatted_dimensions() ); ?>, <?php echo wp_kses_post( wc_stc_format_shipment_weight( $shipment->get_total_weight(), $shipment->get_weight_unit() ) ); ?>
			</p>

			<?php do_action( 'woocommerce_shiptastic_preview_after_column_package', $shipment ); ?>
		</div>

		<?php do_action( 'woocommerce_shiptastic_preview_after_columns', $shipment ); ?>
	</div>

	<?php do_action( 'woocommerce_shiptastic_preview_before_item_list', $shipment ); ?>

	<div class="wc-stc-preview-shipment-item-list">
		<table class="wc-stc-preview-shipment-items">
			<?php
			$preview_table_columns = apply_filters(
				'woocommerce_shiptastic_preview_shipment_columns',
				array(
					'name'       => _x( 'Position', 'shipments', 'woocommerce-germanized' ),
					'quantity'   => _x( 'Quantity', 'shipments', 'woocommerce-germanized' ),
					'dimensions' => _x( 'Dimensions', 'shipments', 'woocommerce-germanized' ),
					'weight'     => _x( 'Weight', 'shipments', 'woocommerce-germanized' ),
				),
				$shipment->get_type()
			);
			?>
			<thead>
				<tr>
					<?php foreach ( $preview_table_columns as $column_name => $column_title ) : ?>
						<th class="wc-stc-preview-shipment-item-column wc-stc-preview-shipment-item-column-<?php echo esc_attr( $column_name ); ?>"><?php echo esc_html( $column_title ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $shipment->get_items() as $item ) : ?>
				<tr class="wc-stc-shipment-item-preview wc-stc-shipment-item-preview-<?php echo esc_attr( $item->get_id() ); ?> <?php echo esc_attr( $item->get_item_parent_id() > 0 ? 'shipment-item-is-child shipment-item-parent-' . $item->get_item_parent_id() : '' ); ?> <?php echo esc_attr( $item->has_children() ? 'shipment-item-is-parent' : '' ); ?>">
					<?php foreach ( $preview_table_columns as $column_name => $column_title ) : ?>
						<td class="wc-stc-preview-shipment-item-column wc-stc-preview-shipment-item-column-<?php echo esc_attr( $column_name ); ?>">
							<?php if ( 'name' === $column_name ) : ?>
								<?php if ( $product = $item->get_product() ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id() ) ); ?>"><?php echo wp_kses_post( $item->get_name() ); ?></a>
								<?php else : ?>
									<?php echo wp_kses_post( $item->get_name() ); ?>
								<?php endif; ?>

								<?php echo ( $item->get_sku() ? '<br/><small>' . esc_html_x( 'SKU:', 'shipments', 'woocommerce-germanized' ) . ' ' . esc_html( $item->get_sku() ) . '</small>' : '' ); ?>
							<?php elseif ( 'quantity' === $column_name ) : ?>
								<?php echo esc_html( $item->get_quantity() ); ?>x
							<?php elseif ( 'dimensions' === $column_name ) : ?>
								<?php echo wp_kses_post( wc_stc_format_shipment_dimensions( $item->get_dimensions(), $shipment->get_dimension_unit() ) ); ?>
							<?php elseif ( 'weight' === $column_name ) : ?>
								<?php echo wp_kses_post( wc_stc_format_shipment_weight( $item->get_weight(), $shipment->get_weight_unit() ) ); ?>
							<?php endif; ?>

							<?php do_action( "woocommerce_shiptastic_preview_shipment_column_{$column_name}", $item, $shipment ); ?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
