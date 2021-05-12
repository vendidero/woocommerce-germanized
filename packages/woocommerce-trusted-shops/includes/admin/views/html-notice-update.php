<?php
/**
 * Admin View: Notice - Update
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Vendidero\TrustedShops\Package;

?>
<div id="message" class="updated woocommerce-message woocommerce-gzd-message wc-connect">
	<p><?php echo _x( '<strong>WooCommerce Trusted Shops Data Update Required</strong> &#8211; We just need to update your installation to the latest version', 'trusted-shops', 'woocommerce-germanized' ); ?></p>
	<p class="submit"><a href="<?php echo esc_url( add_query_arg( 'do_update_woocommerce_ts', 'true', ( Package::is_integration() ? admin_url( 'admin.php?page=wc-settings&tab=germanized-trusted_shops' ) : admin_url( 'admin.php?page=wc-settings&tab=trusted-shops' ) ) ) ); ?>" class="wc-gzd-update-now button-primary"><?php echo _x( 'Run the updater', 'trusted-shops', 'woocommerce-germanized' ); ?></a></p>
</div>
<script type="text/javascript">
	jQuery( '.wc-gzd-update-now' ).click( 'click', function() {
		return window.confirm( '<?php echo esc_js( _x( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'trusted-shops', 'woocommerce-germanized' ) ); ?>' );
	});
</script>
