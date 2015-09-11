<?php
/**
 * Admin View: Notice - Update
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated woocommerce-message woocommerce-gzd-message wc-connect">
	<p><?php _e( '<strong>WooCommerce Germanized Data Update Required</strong> &#8211; We just need to update your install to the latest version', 'woocommerce-germanized' ); ?></p>
	<p class="submit"><a href="<?php echo esc_url( add_query_arg( 'do_update_woocommerce_gzd', 'true', admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ) ); ?>" class="wc-gzd-update-now button-primary"><?php _e( 'Run the updater', 'woocommerce-germanized' ); ?></a></p>
</div>
<script type="text/javascript">
	jQuery( '.wc-gzd-update-now' ).click( 'click', function() {
		return window.confirm( '<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'woocommerce-germanized' ) ); ?>' );
	});
</script>