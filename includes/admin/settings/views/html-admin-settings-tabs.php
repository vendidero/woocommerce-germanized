<?php
/**
 * Admin View: Settings Tabs
 */
defined( 'ABSPATH' ) || exit;
?>

<h2 class="wc-gzd-setting-header">
	<?php esc_html_e( 'Germanized', 'woocommerce-germanized' ); ?>

	<?php if ( ! WC_germanized()->is_pro() ) : ?>
		<a class="page-title-action" href="https://vendidero.de/woocommerce-germanized" target="_blank"><?php echo sprintf( esc_html__( 'Upgrade to %s', 'woocommerce-germanized' ), '<span class="wc-gzd-pro">pro</span>' ); ?></a>
	<?php elseif ( function_exists( 'VD' ) ) : ?>
		<a class="page-title-action" href="<?php echo esc_url( is_multisite() ? network_admin_url( 'index.php?page=vendidero' ) : admin_url( 'index.php?page=vendidero' ) ); ?>"><?php esc_html_e( 'Manage license', 'woocommerce-germanized' ); ?></a>
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
							<a class="woocommerce-gzd-input-toggle-trigger" href="#"><span class="woocommerce-gzd-input-toggle woocommerce-input-toggle woocommerce-input-toggle--<?php echo( $tab->is_enabled() ? 'enabled' : 'disabled' ); ?>"><?php echo esc_attr__( 'Yes', 'woocommerce-germanized' ); ?></span></a>
							<input
									name="woocommerce_gzd_tab_status_<?php echo esc_attr( $tab->get_name() ); ?>"
									id="woocommerce-gzd-tab-status-<?php echo esc_attr( $tab->get_name() ); ?>"
									type="checkbox"
									data-tab="<?php echo esc_attr( $tab->get_name() ); ?>"
									style="display: none;"
									value="1"
									class="woocommerce-gzd-tab-status-checkbox"
								<?php checked( $tab->is_enabled() ? 'yes' : 'no', 'yes' ); ?>
							/>
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
