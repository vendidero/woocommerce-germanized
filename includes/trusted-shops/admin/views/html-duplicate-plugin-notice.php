<?php
/**
 * Admin View: Duplicate plugin notice
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

$prefix = '';

if ( is_multisite() && is_network_admin() ) {
	$prefix = 'network/';
}

$deactivate_url = $prefix . 'plugins.php?action=deactivate&plugin=' . rawurlencode( 'woocommerce-trusted-shops/woocommerce-trusted-shops.php' ) . '&plugin_status=all&paged=1&s&_wpnonce=' . rawurlencode( wp_create_nonce( 'deactivate-plugin_woocommerce-trusted-shops/woocommerce-trusted-shops.php' ) );
?>

<div id="message" class="error woocommerce-gzd-message wc-connect">
	<h3><?php echo _x( 'Duplicate Plugin installation', 'trusted-shops', 'woocommerce-germanized' );?></h3>
	<p>
		<?php echo sprintf( _x( "It seems like you've installed WooCommerce Germanized and Trustbadge Reviews for WooCommerce. Please deactivate Trustbadge Reviews for WooCommerce as long as you are using WooCommerce Germanized. You can manage your Trusted Shops configuration within your %s.", 'trusted-shops', 'woocommerce-germanized' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=germanized&section=trusted_shops' ) . '">' . _x( 'Germanized settings', 'trusted-shops', 'woocommerce-germanized' ) . '</a>' ); ?>
	</p>
	<p><a class="button button-primary" href="<?php echo $deactivate_url; ?>"><?php echo _x( 'Deactivate standalone version', 'trusted-shops', 'woocommerce-germanized' ); ?></a></p>
</div>