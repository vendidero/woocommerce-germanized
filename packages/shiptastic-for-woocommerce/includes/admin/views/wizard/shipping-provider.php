<?php
defined( 'ABSPATH' ) || exit;

$features     = \Vendidero\Shiptastic\ShippingProvider\Helper::instance()->get_features();
$integrations = \Vendidero\Shiptastic\ShippingProvider\Helper::instance()->get_available_shipping_provider_integrations( true );
$new_provider = new \Vendidero\Shiptastic\ShippingProvider\Simple();
?>

<div class="wc-shiptastic-wizard-entry">
	<h1><?php echo esc_html_x( 'Setup Shipping Service Providers', 'shipments', 'woocommerce-germanized' ); ?></h1>
</div>

<div class="wc-shiptastic-wizard-inner-content inner-content-small">
	<p class="entry-desc"><?php echo esc_html_x( 'Shiptastic allows you to integrate with popular shipping service providers out of the box. In case there is no official integration available, you may manually add your shipping service provider later.', 'shipments', 'woocommerce-germanized' ); ?></p>

	<div class="wc-shiptastic-wizard-config wc-stc-shipping-providers">
		<div class="wc-shiptastic-error-wrapper"></div>

		<?php if ( ! empty( $integrations ) ) : ?>
			<fieldset>
				<?php foreach ( $integrations as $provider ) : ?>
					<div class="wc-shiptastic-wizard-list-item wc-shiptastic-wizard-provider <?php echo esc_attr( $provider->is_pro() ? 'is-pro' : '' ); ?>">
						<div class="list-item-content">
							<div class="list-item-left">
								<h3><?php echo esc_html( $provider->get_title() ); ?></h3>

								<ul class="wizard-features">
									<?php foreach ( $provider->get_supported_features() as $feature ) : ?>
										<li class="wizard-feature feature-<?php echo esc_attr( $feature ); ?>"><?php echo esc_html( isset( $features[ $feature ] ) ? $features[ $feature ] : $feature ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
							<?php if ( $provider_icon = $provider->get_logo_path() ) : ?>
								<div class="list-item-right">
									<span class="wc-stc-provider-icon"><?php include $provider_icon; ?></span>
								</div>
							<?php endif; ?>
						</div>
						<div class="list-item-footer">
							<?php if ( is_a( $provider, '\Vendidero\Shiptastic\ShippingProvider\Placeholder' ) ) : ?>
								<?php if ( ! $provider->is_pro() ) : ?>
									<a class="button button-secondary wc-shiptastic-ajax-action wc-shiptastic-ajax-loading-btn" data-nonce="<?php echo esc_attr( wp_create_nonce( 'shiptastic-install-extension' ) ); ?>" data-args="<?php echo esc_attr( "?redirect=no&provider_name={$provider->get_name()}&extension={$provider->get_extension_name()}" ); ?>" data-action="install_extension" href="<?php echo esc_url( $provider->get_help_link() ); ?>"><span class="btn-text"><?php echo esc_html_x( 'Install', 'shipments', 'woocommerce-germanized' ); ?></span></a>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</fieldset>

			<h4 class="wc-shiptastic-wizard-divider"><?php echo esc_html_x( 'Or', 'shipments', 'woocommerce-germanized' ); ?></h4>
		<?php endif; ?>

		<div class="wc-shiptastic-wizard-list-item wc-shiptastic-wizard-provider">
			<div class="list-item-content">
				<h3><?php echo esc_html_x( 'Create a new shipping service provider', 'shipments', 'woocommerce-germanized' ); ?></h3>
			</div>

			<table class="wc-shiptastic-wizard-settings form-table shipping-provider-settings">
				<tbody>
				<?php
				WC_Admin_Settings::output_fields(
					array(
						array(
							'title'       => _x( 'Title', 'shipments', 'woocommerce-germanized' ),
							'id'          => 'new_shipping_provider_title',
							'placeholder' => '',
							'value'       => '',
							'type'        => 'text',
						),
						array(
							'title'       => _x( 'Tracking URL', 'shipments', 'woocommerce-germanized' ),
							'desc'        => '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'Adjust the placeholder used to construct the tracking URL for this shipping provider. You may use on of the following placeholders to insert the tracking id or other dynamic data: %s', 'shipments', 'woocommerce-germanized' ), implode( ', ', array_slice( array_keys( $new_provider->get_tracking_placeholders() ), 0, 3 ) ) ) . '</div>',
							'id'          => 'new_shipping_provider_tracking_url_placeholder',
							'placeholder' => 'https://wwwapps.ups.com/tracking/tracking.cgi?tracknum={tracking_id}',
							'value'       => '',
							'type'        => 'text',
						),
					)
				);
				?>
				</tbody>
			</table>
		</div>
	</div>

	<div class="wc-shiptastic-wizard-links">
		<button class="button button-primary button-submit" type="submit"><?php echo esc_attr_x( 'Continue', 'shipments-wizard', 'woocommerce-germanized' ); ?></button>
	</div>
</div>

