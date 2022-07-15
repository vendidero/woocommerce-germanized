<?php
/**
 * Admin View: Dep notice
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="error woocommerce-gzd-message wc-connect">
	<h3><?php esc_html_e( 'WooCommerce missing or outdated', 'woocommerce-germanized' ); ?></h3>
	<p>
		<?php
		if ( ! \Vendidero\Germanized\PluginsHelper::is_woocommerce_plugin_active() ) :
			$notice = \Vendidero\Germanized\PluginsHelper::get_plugin_manual_install_message( 'woocommerce' );

			// translators: 1$-2$: opening and closing <strong> tags, 3$-4$: link tags, takes to woocommerce plugin on wp.org.
			echo wp_kses_post( sprintf( __( '%1$sGermanized is inactive.%2$s The %3$sWooCommerce plugin%4$s must be active for Germanized to work. %5$s', 'woocommerce-germanized' ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', $notice ) );
		elseif ( WC_GZD_Dependencies::is_woocommerce_outdated() ) :
			// translators: 1$-2$: opening and closing <strong> tags, 3$: minimum supported WooCommerce version, 4$-5$: opening and closing link tags, leads to plugin admin
			echo wp_kses_post( sprintf( __( '%1$sGermanized is inactive.%2$s This version of Germanized requires WooCommerce %3$s or newer. Please %4$supdate WooCommerce to version %3$s or newer &raquo;%5$s', 'woocommerce-germanized' ), '<strong>', '</strong>', WC_GZD_Dependencies::get_woocommerce_min_version_required(), '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">', '</a>' ) );
		endif;
		?>
	</p>
</div>
