<?php
/**
 * Order shipments HTML for meta box.
 *
 * @package WooCommerce_Shiptastic/Admin
 */

use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\Admin\Admin;

defined( 'ABSPATH' ) || exit;

?>

<div class="shipment-content">
	<div class="columns">
		<?php
		/**
		 * Action that fires before the first column of a Shipment's meta box is being outputted.
		 *
		 * @param Shipment $shipment The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		do_action( 'woocommerce_shiptastic_shipment_admin_before_columns', $shipment );
		?>

		<div class="column col-6">
			<div class="columns">
				<div class="column col-4">
					<div class="form-row">
						<label for="shipment-weight-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php printf( esc_html_x( 'Content (%s)', 'shipments', 'woocommerce-germanized' ), esc_html( $shipment->get_weight_unit() ) ); ?></label>
						<input type="text" class="wc_input_decimal wc-stc-shipment-weight" value="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_weight( 'edit' ) ) ); ?>" name="shipment_weight[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-weight-<?php echo esc_attr( $shipment->get_id() ); ?>" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_content_weight() ) ); ?>" />
					</div>
				</div>
				<div class="column col-8">
					<div class="form-row dimensions_field">
						<label for="shipment-length-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php printf( esc_html_x( 'Dimensions (%s)', 'shipments', 'woocommerce-germanized' ), esc_html( $shipment->get_dimension_unit() ) ); ?><?php echo wc_help_tip( _x( 'LxWxH in decimal form.', 'shipments', 'woocommerce-germanized' ) ); ?></label>

						<span class="input-inner-wrap">
							<input type="text" <?php echo ( $shipment->has_packaging() ? 'disabled="disabled"' : '' ); ?> size="6" class="wc_input_decimal wc-stc-shipment-dimension <?php echo ( $shipment->has_packaging() ? 'disabled' : '' ); ?>" value="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_length( 'edit' ) ) ); ?>" name="shipment_length[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-length-<?php echo esc_attr( $shipment->get_id() ); ?>" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_content_length() ) ); ?>" />
							<input type="text" <?php echo ( $shipment->has_packaging() ? 'disabled="disabled"' : '' ); ?> size="6" class="wc_input_decimal wc-stc-shipment-dimension <?php echo ( $shipment->has_packaging() ? 'disabled' : '' ); ?>" value="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_width( 'edit' ) ) ); ?>" name="shipment_width[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-width-<?php echo esc_attr( $shipment->get_id() ); ?>" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_content_width() ) ); ?>" />
							<input type="text" <?php echo ( $shipment->has_packaging() ? 'disabled="disabled"' : '' ); ?> size="6" class="wc_input_decimal wc-stc-shipment-dimension <?php echo ( $shipment->has_packaging() ? 'disabled' : '' ); ?>" value="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_height( 'edit' ) ) ); ?>" name="shipment_height[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-height-<?php echo esc_attr( $shipment->get_id() ); ?>" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_content_height() ) ); ?>" />
						</span>
					</div>
				</div>
			</div>

			<div class="form-row wc-stc-shipment-packaging-wrapper">
				<label for="shipment-packaging-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo esc_html_x( 'Packaging', 'shipments', 'woocommerce-germanized' ); ?></label>

				<?php require 'html-order-shipment-packaging-select.php'; ?>
			</div>
		</div>

		<div class="column col-6">
			<div class="form-row">
				<label for="shipment-status-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo esc_html_x( 'Status', 'shipments', 'woocommerce-germanized' ); ?></label>
				<select class="shipment-status-select" id="shipment-status-<?php echo esc_attr( $shipment->get_id() ); ?>" name="shipment_status[<?php echo esc_attr( $shipment->get_id() ); ?>]">
					<?php foreach ( wc_stc_get_shipment_selectable_statuses( $shipment ) as $status => $title ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
						<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status, $shipment->get_status(), true ); ?>><?php echo esc_html( $title ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php if ( count( $shipment->get_available_shipping_methods() ) > 1 ) : ?>
				<div class="form-row">
					<label for="shipment-shipping-method-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo esc_html_x( 'Shipping method', 'shipments', 'woocommerce-germanized' ); ?></label>
					<select class="shipment-shipping-method-select" id="shipment-shipping-method-<?php echo esc_attr( $shipment->get_id() ); ?>" name="shipment_shipping_method[<?php echo esc_attr( $shipment->get_id() ); ?>]">
						<?php foreach ( $shipment->get_available_shipping_methods() as $method => $title ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
							<option value="<?php echo esc_attr( $method ); ?>" <?php selected( $method, $shipment->get_shipping_method(), true ); ?>><?php echo esc_html( $title ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

			<div class="form-row">
				<label for="shipment-shipping-provider-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo esc_html_x( 'Shipping Service Provider', 'shipments', 'woocommerce-germanized' ); ?></label>
				<select class="shipment-shipping-provider-select" id="shipment-shipping-provider-<?php echo esc_attr( $shipment->get_id() ); ?>" name="shipment_shipping_provider[<?php echo esc_attr( $shipment->get_id() ); ?>]">
					<?php
					foreach ( wc_stc_get_shipping_provider_select() as $provider => $title ) :  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
						$provider_instance = wc_stc_get_shipping_provider( $provider );
						?>
						<option data-is-manual="<?php echo ( ( $provider_instance && $provider_instance->is_manual_integration() ) ? 'yes' : 'no' ); ?>" value="<?php echo esc_attr( $provider ); ?>" <?php selected( $provider, $shipment->get_shipping_provider(), true ); ?>><?php echo esc_html( $title ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="form-row show-if show-if-provider show-if-provider-is-manual">
				<label for="shipment-tracking-id-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo esc_html_x( 'Tracking Number', 'shipments', 'woocommerce-germanized' ); ?></label>
				<input type="text" value="<?php echo esc_attr( $shipment->get_tracking_id() ); ?>" name="shipment_tracking_id[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-tracking-id-<?php echo esc_attr( $shipment->get_id() ); ?>" />
			</div>

			<?php
			/**
			 * Action that fires after the left column of a Shipment's meta box admin view.
			 *
			 * @param Shipment $shipment The shipment object.
			 *
			 * @package Vendidero/Shiptastic
			 */
			do_action( 'woocommerce_shiptastic_meta_box_shipment_after_left_column', $shipment );
			?>
		</div>

		<div class="column col-12 column-shipment-documents">
			<div class="columns">
				<div class="column col-6">
					<div class="columns columns-auto">
						<?php
						/**
						 * Action that fires before outputting the label section of a Shipment's meta box admin view.
						 *
						 * @param Shipment $shipment The shipment object.
						 *
						 * @package Vendidero/Shiptastic
						 */
						do_action( 'woocommerce_shiptastic_meta_box_shipment_before_label', $shipment );

						if ( $shipment->supports_label() && ( ( $label = $shipment->get_label() ) || $shipment->needs_label() ) ) :
							include 'label/html-shipment-label.php';
						elseif ( $shipment->get_tracking_url() ) :
							?>
							<div class="wc-stc-shipment-tracking wc-stc-shipment-action-wrapper column col-auto column-spaced show-if show-if-provider show-if-provider-<?php echo esc_attr( $shipment->get_shipping_provider() ); ?>" data-shipment="<?php echo esc_attr( $shipment->get_id() ); ?>">
								<h4><?php echo esc_html_x( 'Track & Trace', 'shipments', 'woocommerce-germanized' ); ?></h4>
								<a class="button button-secondary" aria-label="<?php echo esc_attr( $shipment->get_tracking_id() ); ?>" href="<?php echo esc_url( $shipment->get_tracking_url() ); ?>" target="_blank"><?php echo esc_html( $shipment->get_tracking_id() ); ?></a>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<div class="column col-6">
					<div class="columns">
						<?php
						/**
						 * Action that fires after the right column of a Shipment's meta box admin view.
						 *
						 * @param Shipment $shipment The shipment object.
						 *
						 * @package Vendidero/Shiptastic
						 */
						do_action( 'woocommerce_shiptastic_meta_box_shipment_after_right_column', $shipment );
						?>
					</div>
				</div>
			</div>
		</div>

		<div class="column col-12">
			<div class="shipment-items" id="shipment-items-<?php echo esc_attr( $shipment->get_id() ); ?>">
				<div class="shipment-item-list-wrapper">
					<div class="shipment-item-heading">
						<div class="columns">
							<?php foreach ( Admin::get_admin_shipment_item_columns( $shipment ) as $column_name => $column ) : ?>
								<div class="column col-<?php echo esc_attr( $column['size'] ); ?> shipment-item-<?php echo esc_attr( $column_name ); ?>">
									<?php echo esc_html( $column['title'] ); ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="shipment-item-list">
						<?php foreach ( $shipment->get_items() as $item ) : ?>
							<?php include 'html-order-shipment-item.php'; ?>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="shipment-item-actions shipment-inner-actions">
					<?php
						$actions = array(
							'add_item'   => array(
								'action'            => 'add_item',
								'name'              => _x( 'Add item', 'shipments', 'woocommerce-germanized' ),
								'classes'           => 'add-shipment-item has-shipment-modal create',
								'custom_attributes' => array(
									'id'              => 'wc-stc-modal-add-shipment-item-' . $shipment->get_id(),
									'data-load-async' => true,
									'data-reference'  => $shipment->get_id(),
									'data-id'         => 'wc-stc-modal-add-shipment-item',
								),
							),
							'sync_items' => array(
								'action'  => 'sync_items',
								'name'    => _x( 'Sync items', 'shipments', 'woocommerce-germanized' ),
								'title'   => _x( 'Automatically adjust items and quantities based on order item data.', 'shipments', 'woocommerce-germanized' ),
								'classes' => 'sync-shipment-items refresh',
							),
						);
						?>
					<div class="shipment-inner-actions-wrapper">
						<?php echo wc_stc_render_shipment_action_buttons( $actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

						<script type="text/template" id="tmpl-wc-stc-modal-add-shipment-item-<?php echo esc_attr( $shipment->get_id() ); ?>">
							<div class="wc-backbone-modal wc-stc-admin-shipment-modal wc-stc-modal-add-shipment-item">
								<div class="wc-backbone-modal-content">
									<section class="wc-backbone-modal-main" role="main">
										<header class="wc-backbone-modal-header">
											<h1><?php echo esc_html_x( 'Add Item', 'shipments', 'woocommerce-germanized' ); ?></h1>
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
													<tbody class="wc-stc-shipment-add-items-table" data-row="">
													<tr>
														<td><select id="wc-stc-shipment-add-items-select" name="item_id"></select></td>
														<td><input id="wc-stc-shipment-add-items-quantity" type="number" step="1" min="0" max="9999" autocomplete="off" name="item_qty" placeholder="1" size="4" class="quantity" /></td>
													</tr>
													</tbody>
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
					</div>

					<?php
					/**
					 * Action that fires in the item action container of a Shipment's meta box admin view.
					 *
					 * @param Shipment $shipment The shipment object.
					 *
					 * @package Vendidero/Shiptastic
					 */
					do_action( 'woocommerce_shiptastic_meta_box_shipment_item_actions', $shipment );
					?>
				</div>
			</div>
		</div>

		<?php
		/**
		 * Action that fires after the fields of a Shipment's meta box admin view have been rendered.
		 *
		 * @param Shipment $shipment The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		do_action( 'woocommerce_shiptastic_meta_box_shipment_after_fields', $shipment );
		?>

		<div class="column col-12 shipment-footer" id="shipment-footer-<?php echo esc_attr( $shipment->get_id() ); ?>">
			<div class="shipment-footer-inner">
				<?php if ( 'return' === $shipment->get_type() && $shipment->has_status( 'processing' ) ) : ?>
					<a class="shipment-footer-action send-return-shipment-notification email" href="#" data-id="<?php echo esc_attr( $shipment->get_id() ); ?>">
						<?php echo wc_help_tip( _x( 'Send return instructions to your customer via email including return label as attachment (if available).', 'shipments', 'woocommerce-germanized' ) ); ?>
						<?php echo ( $shipment->is_customer_requested() ? esc_html_x( 'Resend notification', 'shipments', 'woocommerce-germanized' ) : esc_html_x( 'Notify customer', 'shipments', 'woocommerce-germanized' ) ); ?>
					</a>
				<?php elseif ( 'return' === $shipment->get_type() && $shipment->has_status( 'requested' ) ) : ?>
					<a class="shipment-footer-action confirm-return-shipment" href="#" data-id="<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo wc_help_tip( _x( 'Confirm the return request to the customer. The customer receives an email notification possibly containing return instructions.', 'shipments', 'woocommerce-germanized' ) ); ?><?php echo esc_html_x( 'Confirm return request', 'shipments', 'woocommerce-germanized' ); ?></a>
				<?php elseif ( 'return' === $shipment->get_type() && $shipment->has_status( 'delivered' ) && $shipment->needs_refund() ) : ?>
					<a class="shipment-footer-action create-return-shipment-refund has-shipment-modal" id="wc-stc-modal-create-return-shipment-refund-<?php echo esc_attr( $shipment->get_id() ); ?>" href="#" data-id="wc-stc-modal-create-return-shipment-refund" data-reference="<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo wc_help_tip( _x( 'Create a refund linked to this return. Return costs will be automatically deducted from refund total.', 'shipments', 'woocommerce-germanized' ) ); ?><?php echo esc_html_x( 'Create refund', 'shipments', 'woocommerce-germanized' ); ?></a>

					<script type="text/template" id="tmpl-wc-stc-modal-create-return-shipment-refund-<?php echo esc_attr( $shipment->get_id() ); ?>">
						<div class="wc-backbone-modal wc-stc-admin-shipment-modal wc-stc-modal-create-return-shipment-refund">
							<div class="wc-backbone-modal-content">
								<section class="wc-backbone-modal-main" role="main">
									<header class="wc-backbone-modal-header">
										<h1><?php echo esc_html_x( 'Create refund', 'shipments', 'woocommerce-germanized' ); ?></h1>
										<button class="modal-close modal-close-link dashicons dashicons-no-alt">
											<span class="screen-reader-text">Close modal panel</span>
										</button>
									</header>
									<article>
										<form action="" method="post">
											<p class="form-field">
												<label for="return-costs-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php printf( esc_html_x( 'Return costs (%s)', 'shipments', 'woocommerce-germanized' ), esc_html( get_woocommerce_currency_symbol() ) ); ?></label>
												<input type="text" class="wc_input_decimal wc-stc-shipment-return-costs" value="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_return_costs( 'edit' ) ) ); ?>" name="return_costs" id="return-costs-<?php echo esc_attr( $shipment->get_id() ); ?>" />
											</p>
											<p class="form-field form-field-checkbox">
												<label for="return-restock-refunded-items-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo esc_html_x( 'Restock refunded items?', 'shipments', 'woocommerce-germanized' ); ?></label>
												<input type="checkbox" name="return_restock_refunded_items" id="return-restock-refunded-items-<?php echo esc_attr( $shipment->get_id() ); ?>" value="yes" class="checkbox" />
											</p>
										</form>
									</article>
									<footer>
										<div class="inner">
											<button id="btn-ok" class="button button-primary button-large"><?php echo esc_html_x( 'Create refund', 'shipments', 'woocommerce-germanized' ); ?></button>
										</div>
									</footer>
								</section>
							</div>
						</div>
						<div class="wc-backbone-modal-backdrop modal-close"></div>
					</script>
				<?php endif; ?>

				<?php if ( $shipment->is_editable() ) : ?>
					<a class="shipment-footer-action remove-shipment delete" href="#" data-id="<?php echo esc_attr( $shipment->get_id() ); ?>"><?php printf( esc_html_x( 'Delete %s', 'shipments', 'woocommerce-germanized' ), esc_html( wc_stc_get_shipment_label_title( $shipment->get_type() ) ) ); ?></a>
				<?php endif; ?>

				<?php
				/**
				 * Action that fires in the shipment action container of a Shipment's meta box admin view.
				 *
				 * @param Shipment $shipment The shipment object.
				 *
				 * @package Vendidero/Shiptastic
				 */
				do_action( 'woocommerce_shiptastic_meta_box_shipment_actions', $shipment );
				?>
			</div>
		</div>
	</div>
</div>
