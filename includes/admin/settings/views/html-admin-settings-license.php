<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$domain_to_register = \Vendidero\Germanized\PluginsHelper::get_current_domain( true );
?>
<?php if ( ! WC_germanized()->is_pro() ) : ?>
	<div class="gzd-pro-install-steps">
		<div class="gzd-pro-install-step">
			<div class="pro-step">1.</div>
			<div class="pro-step-desc">
				<h4><?php echo esc_html__( 'Purchase a license', 'woocommerce-germanized' ); ?></h4>
				<p><?php echo wp_kses_post( __( '<a href="https://vendidero.com/woocommerce-germanized#upgrade" target="_blank">Purchase your license</a> for Germanized Pro', 'woocommerce-germanized' ) ); ?></p>
			</div>
		</div>
		<div class="gzd-pro-install-step">
			<div class="pro-step">2.</div>
			<div class="pro-step-desc">
				<h4><?php echo esc_html__( 'Manage license', 'woocommerce-germanized' ); ?></h4>
				<p><?php echo wp_kses_post( sprintf( __( '<a href="https://vendidero.com/products/latest/%1$s" target="_blank">Register your domain</a> <code>%2$s</code> and retrieve your license key', 'woocommerce-germanized' ), esc_attr( \Vendidero\Germanized\PluginsHelper::get_pro_version_product_id() ), esc_html( $domain_to_register ) ) ); ?></p>
			</div>
		</div>
		<?php if ( current_user_can( 'install_plugins' ) ) : ?>
			<div class="gzd-pro-install-step">
				<div class="pro-step">3.</div>
				<div class="pro-step-desc">
					<h4><?php echo esc_html__( 'Install', 'woocommerce-germanized' ); ?></h4>

					<div class="license-form-wrapper">
						<div class="forminp submit">
							<input type="text" name="license_key" id="license_key" value="" placeholder="<?php echo esc_html__( 'Insert your license key', 'woocommerce-germanized' ); ?>" required />
							<button class="button button-primary woocommerce-save-button wc-gzd-install-extension-btn wc-gzd-ajax-loading-btn" data-extension="woocommerce-germanized-pro" href="#"><span class="btn-text"><?php esc_html_e( 'Download & install pro', 'woocommerce-germanized' ); ?></span></button>
						</div>
					</div>
				</div>
			</div>
		<?php else : ?>
			<div class="gzd-pro-install-step">
				<div class="pro-step">3.</div>
				<div class="pro-step-desc">
					<h4><?php echo esc_html__( 'Install', 'woocommerce-germanized' ); ?></h4>
					<p><?php echo wp_kses_post( sprintf( __( '<a href="https://vendidero.com/dashboard/downloads" target="_blank">Download Germanized Pro</a> and <a href="%s" target="_blank">install</a> the zip file as a plugin', 'woocommerce-germanized' ), esc_url( admin_url( 'plugin-install.php?tab=upload' ) ) ) ); ?></p>
				</div>
			</div>
		<?php endif; ?>
	</div>
<?php elseif ( class_exists( '\Vendidero\VendideroHelper\Admin' ) ) : ?>
	<?php \Vendidero\VendideroHelper\Admin::screen( \Vendidero\Germanized\PluginsHelper::get_pro_version_product_id() ); ?>
<?php endif; ?>
