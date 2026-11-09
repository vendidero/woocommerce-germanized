<?php
/**
 * Setup wizard: shipping provider integrations.
 *
 * Mirrors the box-based layout of the Shiptastic shipping provider wizard step.
 *
 * @var WC_GZD_Admin_Setup_Wizard $wizard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$features     = \Vendidero\Shiptastic\ShippingProvider\Helper::instance()->get_features();
$integrations = $wizard->get_available_shipping_provider_integrations();
?>
<div class="wc-gzd-wizard-entry">
	<h1><?php esc_html_e( 'Choose integrations', 'woocommerce-germanized' ); ?></h1>
</div>

<div class="wc-gzd-wizard-inner-content inner-content-small">
	<p class="entry-desc"><?php esc_html_e( 'Germanized & Shiptastic offer seamless integration with your favourite shipping provider.', 'woocommerce-germanized' ); ?></p>

	<div class="wc-gzd-wizard-config">
		<?php $wizard->output_errors(); ?>

		<?php // Do not use a wrapping fieldset here - the toggle script looks up its checkbox via the closest fieldset ancestors. ?>
		<div class="wc-gzd-wizard-list">
			<?php foreach ( $integrations as $provider ) : ?>
				<?php
				$title_clean    = wp_strip_all_tags( preg_replace( '/>.*?</s', '><', $provider->get_title() ) );
				$needs_upgrade  = $provider->is_pro() && ! WC_germanized()->is_pro();
				$is_installed   = is_callable( array( $provider, 'is_installed' ) ) ? $provider->is_installed() : false;
				$is_active      = $provider->is_activated();
				$provider_icon  = $provider->get_icon();
				$provider_feats = $provider->get_supported_features();

				if ( 'deutsche_post' === $provider->get_name() && ! $provider->get_id() ) {
					$is_active = false;
				}
				?>
				<div class="wc-gzd-wizard-list-item wc-gzd-wizard-provider <?php echo esc_attr( $provider->is_pro() ? 'is-pro' : '' ); ?>">
					<div class="list-item-content">
						<div class="list-item-left">
							<h3><?php echo wp_kses_post( $provider->get_title() ); ?></h3>

							<?php if ( ! empty( $provider_feats ) ) : ?>
								<ul class="wc-gzd-wizard-provider-features">
									<?php foreach ( $provider_feats as $feature ) : ?>
										<li class="wizard-feature feature-<?php echo esc_attr( $feature ); ?>"><?php echo esc_html( isset( $features[ $feature ] ) ? $features[ $feature ] : $feature ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
						<?php if ( $provider_icon ) : ?>
							<div class="list-item-right">
								<span class="wc-gzd-wizard-provider-icon"><img alt="<?php echo esc_attr( $title_clean ); ?>" src="<?php echo esc_url( $provider_icon ); ?>" /></span>
							</div>
						<?php endif; ?>
					</div>
					<div class="list-item-footer">
						<?php if ( $needs_upgrade ) : ?>
							<p class="wc-gzd-wizard-provider-upgrade">
								<?php
								/* translators: 1: pro badge link 2: provider title */
								echo wp_kses_post( sprintf( __( 'Upgrade to %1$s to activate %2$s integration.', 'woocommerce-germanized' ), '<a href="https://vendidero.de/woocommerce-germanized#upgrade" class="wc-gzd-pro wc-gzd-pro-outlined" target="_blank" rel="noreferrer">pro</a>', $title_clean ) );
								?>
							</p>
						<?php else : ?>
							<fieldset class="wc-gzd-wizard-provider-toggle">
								<?php
								WC_GZD_Admin::instance()->render_toggle(
									array(
										'type'  => 'gzd_toggle',
										'id'    => 'woocommerce_gzd_' . $provider->get_name() . '_activate',
										'value' => wc_bool_to_string( $is_active ),
										/* translators: %s: provider title */
										'desc'  => ! $is_installed ? sprintf( __( 'Install %s integration plugin', 'woocommerce-germanized' ), $title_clean ) : sprintf( __( 'Enable %s integration', 'woocommerce-germanized' ), $title_clean ),
									)
								);
								?>
							</fieldset>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<?php $wizard->output_links(); ?>
</div>
