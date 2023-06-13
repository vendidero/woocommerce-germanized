<?php
/**
 * Admin View: Shipping providers
 */
defined( 'ABSPATH' ) || exit;
?>

<table class="wc-gzd-shipping-providers widefat">
	<thead>
	<tr>
		<th class="sort"></th>
		<th class="wc-gzd-shipping-provider-title"><?php echo esc_html_x( 'Title', 'shipments', 'woocommerce-germanized' ); ?></th>
		<th class="wc-gzd-shipping-provider-desc"><?php echo esc_html_x( 'Description', 'shipments', 'woocommerce-germanized' ); ?></th>
		<th class="wc-gzd-shipping-provider-activated"><?php echo esc_html_x( 'Activated', 'shipments', 'woocommerce-germanized' ); ?></th>
		<th class="wc-gzd-shipping-provider-actions"></th>
	</tr>
	</thead>
	<tbody class="wc-gzd-setting-tab-rows">
	<?php foreach ( $providers as $provider_name => $provider ) : ?>
		<tr data-shipping-provider="<?php echo esc_attr( $provider->get_name() ); ?>">
			<td class="sort" id="wc-gzd-shipping-provider-sort-<?php echo esc_attr( $provider->get_name() ); ?>">
				<div class="wc-item-reorder-nav wc-gzd-shipping-provider-reorder-nav">
					<button type="button" class="wc-move-up" tabindex="0" aria-hidden="false" aria-label="<?php /* Translators: %s Payment gateway name. */ echo esc_attr( sprintf( _x( 'Move the "%s" provider up', 'shipments', 'woocommerce-germanized' ), esc_html( $provider->get_title() ) ) ); ?>"><?php echo esc_html_x( 'Move up', 'shipments', 'woocommerce-germanized' ); ?></button>
					<button type="button" class="wc-move-down" tabindex="0" aria-hidden="false" aria-label="<?php /* Translators: %s Payment gateway name. */ echo esc_attr( sprintf( _x( 'Move the "%s" provider down', 'shipments', 'woocommerce-germanized' ), esc_html( $provider->get_title() ) ) ); ?>"><?php echo esc_html_x( 'Move down', 'shipments', 'woocommerce-germanized' ); ?></button>
					<input type="hidden" name="provider_order[]" value="<?php echo esc_attr( $provider->get_name() ); ?>" />
				</div>
			</td>
			<td class="wc-gzd-shipping-provider-title" id="wc-gzd-shipping-provider-title-<?php echo esc_attr( $provider->get_name() ); ?>">
				<a href="<?php echo esc_url( $provider->get_edit_link() ); ?>" class="wc-gzd-shipping-provider-edit-link"><?php echo wp_kses_post( $provider->get_title() ); ?></a>
				<div class="row-actions">
					<a href="<?php echo esc_url( $provider->get_edit_link() ); ?>"><?php echo esc_html_x( 'Edit', 'shipments', 'woocommerce-germanized' ); ?></a>
					<?php if ( $provider->is_manual_integration() ) : ?>
						<span class="sep">|</span>
						<a class="wc-gzd-shipping-provider-delete" href="#"><?php echo esc_html_x( 'Delete', 'shipments', 'woocommerce-germanized' ); ?></a>
					<?php endif; ?>
				</div>
			</td>
			<td class="wc-gzd-shipping-provider-description" id="wc-gzd-shipping-provider-description-<?php echo esc_attr( $provider->get_name() ); ?>">
				<p><?php echo wp_kses_post( $provider->get_description() ); ?></p>
			</td>
			<td class="wc-gzd-shipping-provider-activated" id="wc-gzd-shipping-provider-activated-<?php echo esc_attr( $provider->get_name() ); ?>">
				<fieldset>
					<a class="woocommerce-gzd-input-toggle-trigger" href="#"><span class="woocommerce-gzd-input-toggle woocommerce-input-toggle woocommerce-input-toggle--<?php echo( $provider->is_activated() ? 'enabled' : 'disabled' ); ?>"><?php echo esc_attr_x( 'Yes', 'shipments', 'woocommerce-germanized' ); ?></span></a>
					<input
						name="shipping_provider_activated_<?php echo esc_attr( $provider->get_name() ); ?>"
						id="wc-gzd-shipping-provider-activated-<?php echo esc_attr( $provider->get_name() ); ?>"
						type="checkbox"
						data-shipping-provider="<?php echo esc_attr( $provider->get_name() ); ?>"
						style="display: none;"
						value="1"
						class="wc-gzd-shipping-provider-activated-checkbox"
						<?php checked( $provider->is_activated() ? 'yes' : 'no', 'yes' ); ?>
					/>
				</fieldset>
			</td>
			<td class="wc-gzd-shipping-provider-actions">
				<?php if ( '' !== $provider->get_help_link() ) : ?>
					<a
						class="button button-secondary wc-gzd-dash-button help-link"
						aria-label="<?php echo esc_attr_x( 'Help', 'shipments', 'woocommerce-germanized' ); ?>"
						title="<?php echo esc_attr_x( 'Help', 'shipments', 'woocommerce-germanized' ); ?>"
						href="<?php echo esc_url( $provider->get_help_link() ); ?>"
					><?php echo esc_html_x( 'Help', 'shipments', 'woocommerce-germanized' ); ?></a>
				<?php endif; ?>
				<a
					class="button button-secondary wc-gzd-dash-button"
					aria-label="<?php echo esc_attr_x( 'Manage shipping provider', 'shipments', 'woocommerce-germanized' ); ?>"
					title="<?php echo esc_attr_x( 'Manage shipping provider', 'shipments', 'woocommerce-germanized' ); ?>"
					href="<?php echo esc_url( $provider->get_edit_link() ); ?>"
				><?php echo esc_html_x( 'Manage', 'shipments', 'woocommerce-germanized' ); ?>
				</a>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
