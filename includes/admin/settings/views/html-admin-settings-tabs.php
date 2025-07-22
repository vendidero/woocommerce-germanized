<?php
/**
 * Admin View: Settings Tabs
 */
defined( 'ABSPATH' ) || exit;
?>

<h2 class="wc-gzd-setting-header">
	<?php esc_html_e( 'Germanized', 'woocommerce-germanized' ); ?>

	<?php if ( ! WC_germanized()->is_pro() ) : ?>
		<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-license' ) ); ?>"><?php printf( esc_html__( 'Upgrade to %s', 'woocommerce-germanized' ), '<span class="wc-gzd-pro">pro</span>' ); ?></a>
		<?php
	elseif ( function_exists( 'VD' ) ) :
		$license_status = 'not-registered invalid';
		$license_title  = __( 'Register your license', 'woocommerce-germanized' );
		$license_page   = admin_url( 'index.php?page=vendidero' );

		if ( function_exists( 'WC_Germanized_Pro' ) ) {
			$product = is_callable( array( WC_germanized_pro(), 'get_vd_product' ) ) ? WC_germanized_pro()->get_vd_product() : null;

			if ( $product ) {
				$license_has_expired  = $product->has_expired();
				$license_expires_soon = is_callable( array( $product, 'expires_soon' ) ) ? ( ! $license_has_expired && $product->expires_soon() ) : false;
				$license_page         = is_callable( array( $product, 'get_license_page' ) ) ? $product->get_license_page() : $license_page;

				if ( $product->is_registered() ) {
					$license_status = 'valid';
					$license_title  = __( 'Manage your license', 'woocommerce-germanized' );
				} elseif ( $license_has_expired ) {
					$license_title  = __( 'License expired', 'woocommerce-germanized' );
					$license_status = 'expired invalid';
				} elseif ( $license_expires_soon ) {
					$license_title  = __( 'License expires soon', 'woocommerce-germanized' );
					$license_status = 'expires-soon warning';
				}
			}
		}
		?>
		<a class="page-title-action <?php echo esc_attr( $license_status ); ?>" href="<?php echo esc_url( $license_page ); ?>"><?php echo esc_html( $license_title ); ?></a>
	<?php endif; ?>

	<a class="page-title-action" href="<?php echo esc_url( add_query_arg( array( 'tutorial' => 'yes' ) ) ); ?>"><?php esc_html_e( 'Start tutorial', 'woocommerce-germanized' ); ?></a>
</h2>

<p class="tab-description">
	<?php echo esc_html__( 'Adapt your WooCommerce installation to the german market with Germanized.', 'woocommerce-germanized' ); ?>
</p>

<table class="wc-gzd-setting-tabs widefat">
	<thead>
	<tr>
		<th class="wc-gzd-setting-tab-name"><?php esc_html_e( 'Name', 'woocommerce-germanized' ); ?></th>
		<th class="wc-gzd-setting-tab-enabled"><?php esc_html_e( 'Enabled', 'woocommerce-germanized' ); ?></th>
		<th class="wc-gzd-setting-tab-desc"><?php esc_html_e( 'Description', 'woocommerce-germanized' ); ?></th>
		<th class="wc-gzd-setting-tab-actions"></th>
	</tr>
	</thead>
	<tbody class="wc-gzd-setting-tab-rows">
	<?php
	foreach ( $tabs as $tab_id => $tab ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		if ( $tab->hide_from_main_panel() ) {
			continue;
		}
		?>
		<tr>
			<td class="wc-gzd-setting-tab-name <?php echo ( $tab->needs_install() ? 'tab-needs-install' : '' ); ?>" id="wc-gzd-setting-tab-name-<?php echo esc_attr( $tab->get_name() ); ?>">
				<?php if ( $tab->needs_install() ) : ?>
					<span class="wc-gzd-settings-tab-name-needs-install"><?php echo wp_kses_post( $tab->get_label() ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( $tab->get_link() ); ?>" class="wc-gzd-setting-tab-link"><?php echo wp_kses_post( $tab->get_label() ); ?></a>
				<?php endif; ?>
			</td>
			<td class="wc-gzd-setting-tab-enabled" id="wc-gzd-setting-tab-enabled-<?php echo esc_attr( $tab->get_name() ); ?>">
				<?php if ( $tab->needs_install() ) : ?>
					<?php if ( current_user_can( 'install_plugins' ) ) : ?>
						<a class="button button-secondary wc-gzd-install-extension-btn wc-gzd-ajax-loading-btn" data-extension="<?php echo esc_attr( $tab->get_extension_name() ); ?>" href="<?php echo esc_url( $tab->get_link() ); ?>"><span class="btn-text"><?php esc_html_e( 'Install', 'woocommerce-germanized' ); ?></span></a>
					<?php else : ?>
						<span class="<?php echo( $tab->is_enabled() ? 'status-enabled' : 'status-disabled' ); ?>"><?php echo( $tab->is_enabled() ? esc_attr__( 'Yes', 'woocommerce-germanized' ) : esc_attr__( 'No', 'woocommerce-germanized' ) ); ?></span>
					<?php endif; ?>
				<?php else : ?>
					<?php if ( $tab->supports_disabling() ) : ?>
						<fieldset>
							<?php
							WC_GZD_Admin::instance()->render_toggle(
								array(
									'id'                => "woocommerce_gzd_tab_status_{$tab->get_name()}",
									'value'             => $tab->is_enabled(),
									'custom_attributes' => array(
										'data-tab' => $tab->get_name(),
									),
									'class'             => 'woocommerce-gzd-tab-status-checkbox',
								)
							);
							?>
						</fieldset>
					<?php else : ?>
						<span class="<?php echo( $tab->is_enabled() ? 'status-enabled' : 'status-disabled' ); ?>"><?php echo( $tab->is_enabled() ? esc_attr__( 'Yes', 'woocommerce-germanized' ) : esc_attr__( 'No', 'woocommerce-germanized' ) ); ?></span>
					<?php endif; ?>
				<?php endif; ?>
			</td>
			<td class="wc-gzd-setting-tab-desc"><?php echo wp_kses_post( $tab->get_description() ); ?></td>
			<td class="wc-gzd-setting-tab-actions">
				<?php if ( $tab->has_help_link() ) : ?>
					<a
						class="button button-secondary wc-gzd-dash-button help-link"
						title="<?php esc_attr_e( 'Find out more', 'woocommerce-germanized' ); ?>"
						aria-label="<?php esc_attr_e( 'Find out more', 'woocommerce-germanized' ); ?>"
						href="<?php echo esc_url( $tab->get_help_link() ); ?>"
					><?php esc_html_e( 'How to', 'woocommerce-germanized' ); ?></a>
				<?php endif; ?>

				<?php if ( ! $tab->needs_install() ) : ?>
				<a
					class="button button-secondary wc-gzd-dash-button"
					aria-label="<?php esc_attr_e( 'Manage settings', 'woocommerce-germanized' ); ?>"
					title="<?php esc_attr_e( 'Manage settings', 'woocommerce-germanized' ); ?>"
					href="<?php echo esc_url( $tab->get_link() ); ?>"
				><?php esc_html_e( 'Manage', 'woocommerce-germanized' ); ?></a>
				<?php elseif ( current_user_can( 'install_plugins' ) ) : ?>
					<a
						class="button button-secondary wc-gzd-dash-button wc-gzd-install-extension-btn wc-gzd-ajax-loading-btn install"
						title="<?php esc_attr_e( 'Install', 'woocommerce-germanized' ); ?>"
						aria-label="<?php esc_attr_e( 'Install', 'woocommerce-germanized' ); ?>"
						data-extension="<?php echo esc_attr( $tab->get_extension_name() ); ?>"
						href="<?php echo esc_url( $tab->get_link() ); ?>"
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
